<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages the QP (QuePoints) value assigned to each Discord role.
 */
class QpRoleConfigModel extends BaseModel
{
    protected string $table = 'qp_role_config';

    /**
     * Returns all configured roles as [role_id => qp_value].
     *
     * @return array<string,int>
     */
    public function getRoleMap(): array
    {
        $rows = $this->db->query("SELECT `role_id`, `qp_value` FROM `qp_role_config`")->fetchAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['role_id']] = (int)$row['qp_value'];
        }
        return $map;
    }

    /**
     * Bulk-replace the entire role→QP mapping.
     * Roles not in $config will be deleted; existing ones upserted.
     *
     * @param array<string,int> $config  role_id => qp_value
     */
    public function save(array $config): void
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->db->query("DELETE FROM `qp_role_config`");
            foreach ($config as $roleId => $qpValue) {
                $roleId  = substr((string)$roleId, 0, 30);
                $qpValue = min(100_000, (int)$qpValue);
                if ($qpValue !== 0) {
                    $this->db->query(
                        "INSERT INTO `qp_role_config` (`role_id`, `qp_value`) VALUES (?, ?)",
                        [$roleId, $qpValue]
                    );
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
