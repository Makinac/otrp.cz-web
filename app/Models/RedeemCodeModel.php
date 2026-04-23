<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages redeemable codes for QP or character-slot bonuses.
 */
class RedeemCodeModel extends BaseModel
{
    protected string $table = 'redeem_codes';

    /**
     * Generate a unique redemption code and insert it into the DB.
     *
     * Code format: XXXX-XXXX-XXXX (uppercase alphanumeric, unambiguous chars).
     *
     * @param string      $type       'qp' or 'chars'
     * @param int         $amount
     * @param int         $maxUses
     * @param string|null $expiresAt  MySQL DATETIME or null
     * @param string      $note
     * @param int|null    $createdBy  Internal user ID
     * @return string  The generated code string.
     */
    public function generate(
        string $type,
        int $amount,
        int $maxUses,
        ?string $expiresAt,
        string $note,
        ?int $createdBy
    ): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous characters (0/O, 1/I)
        $len   = strlen($chars);

        do {
            $parts = [];
            for ($i = 0; $i < 3; $i++) {
                $part = '';
                for ($j = 0; $j < 4; $j++) {
                    $part .= $chars[random_int(0, $len - 1)];
                }
                $parts[] = $part;
            }
            $code = implode('-', $parts);
        } while ($this->findByCode($code) !== null);

        $this->db->query(
            "INSERT INTO `redeem_codes`
                (`code`, `type`, `amount`, `max_uses`, `expires_at`, `note`, `created_by`)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$code, $type, $amount, $maxUses, $expiresAt, $note, $createdBy]
        );

        return $code;
    }

    /**
     * All codes ordered newest-first, with creator username joined.
     *
     * @return list<array<string,mixed>>
     */
    public function getAll(): array
    {
        return $this->db->query(
            "SELECT rc.*, u.username AS created_by_name
             FROM `redeem_codes` rc
             LEFT JOIN `users` u ON u.id = rc.created_by
             ORDER BY rc.created_at DESC"
        )->fetchAll();
    }

    /**
     * Find a code row by its code string.
     *
     * @return array<string,mixed>|null
     */
    public function findByCode(string $code): ?array
    {
        $row = $this->db->query(
            "SELECT * FROM `redeem_codes` WHERE `code` = ?",
            [$code]
        )->fetch();
        return $row ?: null;
    }

    /**
     * Record a redemption: increments used_count and inserts a redeem_log row.
     */
    public function redeem(int $codeId, int $userId): void
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            // Lock the row to prevent race conditions
            $row = $pdo->prepare("SELECT `used_count`, `max_uses` FROM `redeem_codes` WHERE `id` = ? FOR UPDATE");
            $row->execute([$codeId]);
            $code = $row->fetch();

            if (!$code || (int)$code['used_count'] >= (int)$code['max_uses']) {
                $pdo->rollBack();
                throw new \RuntimeException('Kód byl již plně využit.');
            }

            // Check if user already redeemed within the transaction
            $check = $pdo->prepare("SELECT COUNT(*) FROM `redeem_log` WHERE `code_id` = ? AND `user_id` = ?");
            $check->execute([$codeId, $userId]);
            if ((int)$check->fetchColumn() > 0) {
                $pdo->rollBack();
                throw new \RuntimeException('Tento kód jsi již uplatnil/a.');
            }

            $this->db->query(
                "UPDATE `redeem_codes` SET `used_count` = `used_count` + 1 WHERE `id` = ?",
                [$codeId]
            );
            $this->db->query(
                "INSERT INTO `redeem_log` (`code_id`, `user_id`) VALUES (?, ?)",
                [$codeId, $userId]
            );
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Check whether a user has already redeemed a specific code.
     */
    public function hasUserRedeemed(int $codeId, int $userId): bool
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM `redeem_log` WHERE `code_id` = ? AND `user_id` = ?",
            [$codeId, $userId]
        )->fetchColumn() > 0;
    }

    /**
     * Redemption history for a specific user: code details + whether their bonus is still active.
     *
     * is_active = 1 when the corresponding qp_bonus / char_bonus for this user still exists
     * and has not expired (expires_at IS NULL OR expires_at > NOW()).
     *
     * @return list<array<string,mixed>>
     */
    public function getHistoryByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT
                rc.code,
                rc.type,
                rc.amount,
                rc.note,
                rc.expires_at,
                rc.max_uses,
                rc.used_count,
                rl.redeemed_at,
                CASE
                    WHEN rc.type = 'qp' THEN
                        IF(qb.id IS NOT NULL AND (qb.expires_at IS NULL OR qb.expires_at > NOW()), 1, 0)
                    WHEN rc.type = 'chars' THEN
                        IF(cb.id IS NOT NULL AND (cb.expires_at IS NULL OR cb.expires_at > NOW()), 1, 0)
                    WHEN rc.type = 'ped' THEN
                        IF(pb.id IS NOT NULL AND (pb.expires_at IS NULL OR pb.expires_at > NOW()), 1, 0)
                    ELSE 0
                END AS is_active
             FROM `redeem_log` rl
             JOIN `redeem_codes` rc ON rc.id = rl.code_id
             LEFT JOIN `qp_bonuses`   qb ON qb.user_id = rl.user_id
                                        AND qb.reason  = CONCAT('Kód: ', rc.code)
                                        AND rc.type    = 'qp'
             LEFT JOIN `char_bonuses` cb ON cb.user_id = rl.user_id
                                        AND cb.reason  = CONCAT('Kód: ', rc.code)
                                        AND rc.type    = 'chars'
             LEFT JOIN `ped_bonuses`  pb ON pb.user_id = rl.user_id
                                        AND pb.reason  = CONCAT('Kód: ', rc.code)
                                        AND rc.type    = 'ped'
             WHERE rl.user_id = ?
             ORDER BY rl.redeemed_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Delete a code (and cascade its redeem_log rows).
     */
    public function delete(int $id): bool
    {
        return $this->db->query(
            "DELETE FROM `redeem_codes` WHERE `id` = ?",
            [$id]
        )->rowCount() > 0;
    }
}
