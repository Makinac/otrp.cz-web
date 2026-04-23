<?php

declare(strict_types=1);

namespace App\Models;

/**
 * PlayerActionModel — CRUD for player_bans and player_warns tables.
 */
class PlayerActionModel extends BaseModel
{
    // ── Bans ──────────────────────────────────────────────────────────────

    /**
     * Get the active (non-revoked, non-expired) ban for a user, if any.
     *
     * @return array<string,mixed>|null
     */
    public function getActiveBan(int $userId): ?array
    {
        $row = $this->db->query(
            "SELECT `id`, `reason`, `expires_at`, `issued_at`
             FROM `player_bans`
             WHERE `user_id` = ?
               AND `revoked` = 0
               AND (`expires_at` IS NULL OR `expires_at` > NOW())
             ORDER BY `issued_at` DESC
             LIMIT 1",
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Get all bans for a given user, newest first.
     *
     * @param int $userId Internal DB user id.
     * @return array<int, array<string,mixed>>
     */
    public function getBansByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT pb.*, u.username AS issuer_name, r.username AS revoker_name
             FROM player_bans pb
             LEFT JOIN users u ON u.id = pb.issued_by
             LEFT JOIN users r ON r.id = pb.revoked_by
             WHERE pb.user_id = ?
             ORDER BY pb.issued_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Add a ban for a user.
     *
     * @param int    $userId   Target user DB id.
     * @param string $reason   Reason text.
     * @param int    $issuedBy Reviewer DB id.
     */
    public function addBan(int $userId, string $reason, ?string $expiresAt, array $witnesses, int $issuedBy): void
    {
        $this->db->query(
            "INSERT INTO player_bans (user_id, reason, expires_at, witnesses_json, issued_by) VALUES (?, ?, ?, ?, ?)",
            [$userId, $reason, $expiresAt, $witnesses ? json_encode($witnesses) : null, $issuedBy]
        );
    }

    /**
     * Delete a ban record.
     *
     * @param int $banId Ban DB id.
     */
    public function revokeBan(int $banId, int $revokedBy, string $reason): void
    {
        $this->db->query(
            "UPDATE player_bans SET revoked = 1, revoked_reason = ?, revoked_by = ?, revoked_at = NOW() WHERE id = ?",
            [$reason, $revokedBy, $banId]
        );
    }

    // ── Warns ─────────────────────────────────────────────────────────────

    /**
     * Get all warns for a given user, newest first.
     *
     * @param int $userId Internal DB user id.
     * @return array<int, array<string,mixed>>
     */
    public function getWarnsByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT pw.*, u.username AS issuer_name, r.username AS revoker_name
             FROM player_warns pw
             LEFT JOIN users u ON u.id = pw.issued_by
             LEFT JOIN users r ON r.id = pw.revoked_by
             WHERE pw.user_id = ?
             ORDER BY pw.issued_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Add a warn for a user.
     *
     * @param int    $userId   Target user DB id.
     * @param string $reason   Reason text.
     * @param int    $issuedBy Reviewer DB id.
     */
    public function addWarn(int $userId, string $reason, ?string $expiresAt, array $witnesses, int $issuedBy): void
    {
        $this->db->query(
            "INSERT INTO player_warns (user_id, reason, expires_at, witnesses_json, issued_by) VALUES (?, ?, ?, ?, ?)",
            [$userId, $reason, $expiresAt, $witnesses ? json_encode($witnesses) : null, $issuedBy]
        );
    }

    /**
     * Delete a warn record.
     *
     * @param int $warnId Warn DB id.
     */
    public function revokeWarn(int $warnId, int $revokedBy, string $reason): void
    {
        $this->db->query(
            "UPDATE player_warns SET revoked = 1, revoked_reason = ?, revoked_by = ?, revoked_at = NOW() WHERE id = ?",
            [$reason, $revokedBy, $warnId]
        );
    }

    // ── Mutes ─────────────────────────────────────────────────────────────

    /**
     * Get all mutes for a given user, newest first.
     *
     * @param int $userId Internal DB user id.
     * @return array<int, array<string,mixed>>
     */
    public function getMutesByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT pm.*, u.username AS issuer_name, r.username AS revoker_name
             FROM player_mutes pm
             LEFT JOIN users u ON u.id = pm.issued_by
             LEFT JOIN users r ON r.id = pm.revoked_by
             WHERE pm.user_id = ?
             ORDER BY pm.issued_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Get the active (non-revoked, non-expired) mute for a user, if any.
     *
     * @return array<string,mixed>|null
     */
    public function getActiveMute(int $userId): ?array
    {
        $row = $this->db->query(
            "SELECT `id`, `reason`, `expires_at`, `issued_at`
             FROM `player_mutes`
             WHERE `user_id` = ?
               AND `revoked` = 0
               AND (`expires_at` IS NULL OR `expires_at` > NOW())
             ORDER BY `issued_at` DESC
             LIMIT 1",
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Add a mute for a user.
     *
     * @param int         $userId    Target user DB id.
     * @param string      $reason    Reason text.
     * @param string|null $expiresAt ISO datetime or null for permanent.
     * @param int|null    $issuedBy  Reviewer DB id (null if issued via Discord).
     * @param string      $issuedVia 'web' or 'discord'.
     */
    public function addMute(int $userId, string $reason, ?string $expiresAt, ?int $issuedBy, string $issuedVia = 'web'): void
    {
        $this->db->query(
            "INSERT INTO player_mutes (user_id, reason, expires_at, issued_by, issued_via) VALUES (?, ?, ?, ?, ?)",
            [$userId, $reason, $expiresAt, $issuedBy, $issuedVia]
        );
    }

    /**
     * Revoke a mute.
     *
     * @param int    $muteId    Mute DB id.
     * @param int    $revokedBy Reviewer DB id.
     * @param string $reason    Reason for revoke.
     */
    public function revokeMute(int $muteId, int $revokedBy, string $reason): void
    {
        $this->db->query(
            "UPDATE player_mutes SET revoked = 1, revoked_reason = ?, revoked_by = ?, revoked_at = NOW() WHERE id = ?",
            [$reason, $revokedBy, $muteId]
        );
    }
}
