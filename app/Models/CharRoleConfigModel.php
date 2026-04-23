<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Manages the character-slot value assigned to each Discord role.
 */
class CharRoleConfigModel extends BaseModel
{
    protected string $table = 'char_role_config';

    /**
     * Returns all configured roles as [role_id => char_value].
     *
     * @return array<string,int>
     */
    public function getRoleMap(): array
    {
        $rows = $this->db->query("SELECT `role_id`, `char_value` FROM `char_role_config`")->fetchAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['role_id']] = (int)$row['char_value'];
        }
        return $map;
    }

    /**
     * Bulk-replace the entire role→char-slot mapping.
     *
     * @param array<string,int> $config  role_id => char_value
     */
    public function save(array $config): void
    {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $this->db->query("DELETE FROM `char_role_config`");
            foreach ($config as $roleId => $charValue) {
                $roleId    = substr((string)$roleId, 0, 30);
                $charValue = min(15, max(0, (int)$charValue));
                if ($charValue > 0) {
                    $this->db->query(
                        "INSERT INTO `char_role_config` (`role_id`, `char_value`) VALUES (?, ?)",
                        [$roleId, $charValue]
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
