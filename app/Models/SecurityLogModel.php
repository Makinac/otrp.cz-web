<?php

declare(strict_types=1);

namespace App\Models;

class SecurityLogModel extends BaseModel
{
    /**
     * Create a new security log entry.
     *
     * @param int         $userId
     * @param string      $eventType    'new_identifier', 'identifier_conflict', 'multi_account'
     * @param string      $severity     'info', 'warning', 'critical'
     * @param string      $description  Human-readable description
     * @param array|null  $details      Extra JSON details
     */
    public function create(int $userId, string $eventType, string $severity, string $description, ?array $details = null): void
    {
        $this->db->query(
            'INSERT INTO `security_logs` (`user_id`, `event_type`, `severity`, `description`, `details_json`)
             VALUES (?, ?, ?, ?, ?)',
            [$userId, $eventType, $severity, $description, $details ? json_encode($details) : null]
        );
    }

    /**
     * Get all security logs, optionally filtered.
     *
     * @param string $filter  'all', 'critical', 'warning', 'info'
     * @param int    $limit
     * @return array<int, array<string, mixed>>
     */
    public function getAll(string $filter = 'all', int $limit = 200): array
    {
        $where = '';
        $params = [];

        switch ($filter) {
            case 'critical':
                $where = 'WHERE sl.severity = ?';
                $params[] = 'critical';
                break;
            case 'warning':
                $where = 'WHERE sl.severity = ?';
                $params[] = 'warning';
                break;
            case 'info':
                $where = 'WHERE sl.severity = ?';
                $params[] = 'info';
                break;
            case 'all':
            default:
                break;
        }

        $params[] = $limit;

        return $this->db->query(
            "SELECT sl.*, u.username, u.discord_id, u.avatar,
                    r.username AS resolver_name
             FROM `security_logs` sl
             JOIN `users` u ON u.id = sl.user_id
             LEFT JOIN `users` r ON r.id = sl.resolved_by
             {$where}
             ORDER BY sl.created_at DESC
             LIMIT ?",
            $params
        )->fetchAll();
    }

    /**
     * Get security logs for a specific user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getByUserId(int $userId): array
    {
        return $this->db->query(
            "SELECT sl.*, r.username AS resolver_name
             FROM `security_logs` sl
             LEFT JOIN `users` r ON r.id = sl.resolved_by
             WHERE sl.user_id = ?
             ORDER BY sl.created_at DESC",
            [$userId]
        )->fetchAll();
    }

    /**
     * Count events by severity.
     *
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        $rows = $this->db->query(
            "SELECT severity, COUNT(*) AS cnt
             FROM `security_logs`
             GROUP BY severity"
        )->fetchAll();

        $counts = ['info' => 0, 'warning' => 0, 'critical' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $counts[$row['severity']] = (int)$row['cnt'];
            $counts['total'] += (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * Mark a security log as resolved.
     */
    public function resolve(int $logId, int $resolvedBy): void
    {
        $this->db->query(
            'UPDATE `security_logs` SET `resolved` = 1, `resolved_by` = ?, `resolved_at` = NOW() WHERE `id` = ?',
            [$resolvedBy, $logId]
        );
    }

    /**
     * Find a log entry by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `security_logs` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }
}
