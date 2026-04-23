<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages which Discord roles grant ped menu access.
 */
class PedRoleConfigModel extends BaseModel
{
    protected string $table = 'ped_role_config';

    /**
     * Returns all role IDs that grant ped access.
     *
     * @return list<string>
     */
    public function getRoleIds(): array
    {
        $rows = $this->db->query("SELECT `role_id` FROM `ped_role_config`")->fetchAll();
        return array_map(static fn(array $r): string => (string)$r['role_id'], $rows);
    }

    /**
     * Returns role IDs as a set for quick lookup: [role_id => true].
     *
     * @return array<string,bool>
     */
    public function getRoleMap(): array
    {
        $ids = $this->getRoleIds();
        $map = [];
        foreach ($ids as $id) {
            $map[$id] = true;
        }
        return $map;
    }

    /**
     * Bulk-replace the entire set of ped-granting roles.
     *
     * @param list<string> $roleIds
     */
    public function save(array $roleIds): void
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->db->query("DELETE FROM `ped_role_config`");
            foreach ($roleIds as $roleId) {
                $roleId = substr((string)$roleId, 0, 30);
                if ($roleId !== '') {
                    $this->db->query(
                        "INSERT INTO `ped_role_config` (`role_id`) VALUES (?)",
                        [$roleId]
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
