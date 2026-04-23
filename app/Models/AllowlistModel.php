<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Model for the `allowlist_applications` table.
 */
class AllowlistModel extends BaseModel
{
    /**
     * Find the latest application for a given user.
     *
     * @param int $userId Internal user DB id.
     * @return array<string,mixed>|null
     */
    public function findLatestByUserId(int $userId): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `allowlist_applications`
             WHERE `user_id` = ?
             ORDER BY `submitted_at` DESC
             LIMIT 1',
            [$userId]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Find all applications for a given user, newest first.
     *
     * @param int $userId Internal user DB id.
     * @return array<int, array<string,mixed>>
     */
    public function findAllByUserId(int $userId): array
    {
        return $this->db->query(
            'SELECT * FROM `allowlist_applications`
             WHERE `user_id` = ?
             ORDER BY `submitted_at` DESC',
            [$userId]
        )->fetchAll();
    }

    /**
     * Count how many applications a user has submitted.
     *
     * @param int $userId Internal user DB id.
     * @return int
     */
    public function countByUserId(int $userId): int
    {
        return (int)$this->db->query(
            'SELECT COUNT(*) FROM `allowlist_applications` WHERE `user_id` = ?',
            [$userId]
        )->fetchColumn();
    }

    /**
     * Count how many applications were rejected for a user.
     *
     * @param int $userId Internal user DB id.
     * @return int
     */
    public function countRejectedByUserId(int $userId): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM `allowlist_applications`
             WHERE `user_id` = ? AND `status` = 'rejected'",
            [$userId]
        )->fetchColumn();
    }

    /**
     * Count failed attempts (rejected + blocked with failed interview) for a user.
     *
     * @param int $userId Internal user DB id.
     * @return int
     */
    public function countFailedAttemptsByUserId(int $userId): int
    {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM `allowlist_applications`
             WHERE `user_id` = ? AND (
                 `status` = 'rejected'
                 OR (`status` = 'blocked' AND `interview_status` = 'failed')
             )",
            [$userId]
        )->fetchColumn();
    }

    /**
     * Create a new allowlist application.
     *
     * @param int                  $userId        Internal user DB id.
     * @param array<string,mixed>  $formData      Submitted form values.
     * @param int                  $attemptNumber Attempt number (previous + 1).
     * @return int New application ID.
     */
    public function create(int $userId, array $formData, int $attemptNumber): int
    {
        $this->db->query(
            "INSERT INTO `allowlist_applications`
             (`user_id`, `form_data_json`, `status`, `attempt_number`)
             VALUES (?, ?, 'pending', ?)",
            [$userId, json_encode($formData), $attemptNumber]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Fetch all pending applications (for tester panel), newest first.
     *
     * @return array<int, array<string,mixed>>
     */
    public function getPendingApplications(): array
    {
        return $this->db->query(
            "SELECT a.*, u.username, u.discord_id, u.avatar
             FROM `allowlist_applications` a
             JOIN `users` u ON u.id = a.user_id
             WHERE a.status = 'pending'
             ORDER BY a.submitted_at ASC"
        )->fetchAll();
    }

    /**
     * Fetch all approved-but-not-interviewed applications (Phase 2 queue).
     *
     * @return array<int, array<string,mixed>>
     */
    public function getApprovedPendingInterview(): array
    {
        return $this->db->query(
            "SELECT a.*, u.username, u.discord_id, u.avatar
             FROM `allowlist_applications` a
             JOIN `users` u ON u.id = a.user_id
             WHERE a.status = 'approved' AND (a.interview_status = 'pending' OR a.interview_status IS NULL)
             ORDER BY a.reviewed_at ASC"
        )->fetchAll();
    }

    /**
     * Fetch applications filtered by status tab and optional search, newest first.
     *
     * @param string $filter  'all'|'pending'|'interview'|'active'|'rejected'
     * @param string $search  Optional username / Discord ID substring search.
     * @return array<int, array<string,mixed>>
     */
    public function getAllApplications(string $filter = 'all', string $search = ''): array
    {
        $statusWheres = [
            'pending'   => "a.status = 'pending'",
            'interview' => "a.status = 'approved' AND (a.interview_status IS NULL OR a.interview_status = 'pending')",
            'active'    => "a.status = 'approved' AND a.interview_status = 'passed'",
            'rejected'  => "a.status IN ('rejected','blocked')",
        ];

        $conditions = [];
        $params     = [];

        if (isset($statusWheres[$filter])) {
            $conditions[] = $statusWheres[$filter];
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = '(u.username LIKE ? OR u.discord_id LIKE ?)';
            $params[]     = $like;
            $params[]     = $like;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return $this->db->query(
            "SELECT a.*, u.username, u.discord_id, u.avatar
             FROM `allowlist_applications` a
             JOIN `users` u ON u.id = a.user_id
             $where
             ORDER BY a.submitted_at DESC",
            $params
        )->fetchAll();
    }

    /**
     * Count applications grouped by logical status tabs.
     *
     * @return array<string,int>
     */
    public function getStatusCounts(): array
    {
        $rows = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'pending') AS pending,
                SUM(status = 'approved' AND (interview_status IS NULL OR interview_status = 'pending')) AS interview,
                SUM(status = 'approved' AND interview_status = 'passed') AS active,
                SUM(status IN ('rejected','blocked')) AS rejected
             FROM `allowlist_applications`"
        )->fetch();

        return [
            'all'       => (int)($rows['total']     ?? 0),
            'pending'   => (int)($rows['pending']   ?? 0),
            'interview' => (int)($rows['interview'] ?? 0),
            'active'    => (int)($rows['active']    ?? 0),
            'rejected'  => (int)($rows['rejected']  ?? 0),
        ];
    }

    /**
     * Find application by ID including joined user fields.
     *
     * @param int $id Application DB id.
     * @return array<string,mixed>|null
     */
    public function findByIdWithUser(int $id): ?array
    {
        $row = $this->db->query(
            "SELECT a.*, u.username, u.discord_id, u.avatar,
                    r.username  AS reviewer_name,
                    ir.username AS interview_reviewer_name
             FROM `allowlist_applications` a
             JOIN `users` u  ON u.id  = a.user_id
             LEFT JOIN `users` r  ON r.id  = a.reviewer_id
             LEFT JOIN `users` ir ON ir.id = a.interview_reviewer_id
             WHERE a.id = ?
             LIMIT 1",
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Approve an application.
     *
     * @param int $applicationId Application DB id.
     * @param int $reviewerId    Reviewer's internal user id.
     */
    public function approve(int $applicationId, int $reviewerId): void
    {
        $this->db->query(
            "UPDATE `allowlist_applications`
             SET `status` = 'approved', `reviewed_at` = NOW(), `reviewer_id` = ?,
                 `interview_status` = 'pending'
             WHERE `id` = ?",
            [$reviewerId, $applicationId]
        );
    }

    /**
     * Reject an application.
     *
     * @param int $applicationId Application DB id.
     * @param int $reviewerId    Reviewer's internal user id.
     * @param int $errorCount    Number of errors found.
     */
    public function reject(int $applicationId, int $reviewerId, int $errorCount): void
    {
        $this->db->query(
            "UPDATE `allowlist_applications`
             SET `status` = 'rejected', `reviewed_at` = NOW(), `reviewer_id` = ?,
                 `error_count` = ?
             WHERE `id` = ?",
            [$reviewerId, $errorCount, $applicationId]
        );
    }

    /**
     * Mark interview as passed.
     *
     * @param int $applicationId Application DB id.
     */
    public function passInterview(int $applicationId, int $reviewerId): void
    {
        $this->db->query(
            "UPDATE `allowlist_applications`
             SET `interview_status` = 'passed',
                 `interview_reviewer_id` = ?,
                 `interview_reviewed_at` = NOW()
             WHERE `id` = ?",
            [$reviewerId, $applicationId]
        );

        $this->logInterviewAttempt($applicationId, $reviewerId, 'passed', null);
    }

    /**
     * Record a failed interview attempt.
     * If this is the 3rd attempt, block the application.
     * Otherwise, keep it in approved/pending state for another try.
     *
     * @param int $applicationId Application DB id.
     * @param int $reviewerId    Reviewer's user id.
     * @param int $errorCount    Number of errors in this attempt.
     * @return int New total attempts count.
     */
    public function failInterview(int $applicationId, int $reviewerId, int $errorCount): int
    {
        // Log this attempt
        $this->logInterviewAttempt($applicationId, $reviewerId, 'failed', $errorCount);

        // Increment attempts
        $this->db->query(
            "UPDATE `allowlist_applications`
             SET `interview_attempts` = `interview_attempts` + 1,
                 `interview_reviewer_id` = ?,
                 `interview_reviewed_at` = NOW(),
                 `interview_error_count` = ?
             WHERE `id` = ?",
            [$reviewerId, $errorCount, $applicationId]
        );

        // Check how many attempts now
        $row = $this->db->query(
            "SELECT `interview_attempts` FROM `allowlist_applications` WHERE `id` = ?",
            [$applicationId]
        )->fetch();

        $attempts = (int)($row['interview_attempts'] ?? 0);

        if ($attempts >= 3) {
            // Block the application — max attempts reached
            $this->db->query(
                "UPDATE `allowlist_applications`
                 SET `interview_status` = 'failed', `status` = 'blocked'
                 WHERE `id` = ?",
                [$applicationId]
            );
        }

        return $attempts;
    }

    /**
     * Reset interview attempts — allows player to retry interviews.
     *
     * @param int $applicationId Application DB id.
     */
    public function resetInterviewAttempts(int $applicationId): void
    {
        $this->db->query(
            "UPDATE `allowlist_applications`
             SET `interview_attempts` = 0,
                 `interview_status` = 'pending',
                 `interview_error_count` = NULL,
                 `interview_reviewer_id` = NULL,
                 `interview_reviewed_at` = NULL,
                 `status` = 'approved'
             WHERE `id` = ?",
            [$applicationId]
        );
    }

    /**
     * Create a manually granted allowlist (auto-approved + auto-passed interview).
     *
     * @param int $userId    Target user DB id.
     * @param int $grantedBy Admin user who granted it.
     * @return int New application ID.
     */
    public function grantManual(int $userId, int $grantedBy): int
    {
        $attemptNumber = $this->countByUserId($userId) + 1;

        $this->db->query(
            "INSERT INTO `allowlist_applications`
             (`user_id`, `form_data_json`, `status`, `attempt_number`,
              `reviewed_at`, `reviewer_id`, `interview_status`,
              `interview_reviewer_id`, `interview_reviewed_at`)
             VALUES (?, '{}', 'approved', ?, NOW(), ?, 'passed', ?, NOW())",
            [$userId, $attemptNumber, $grantedBy, $grantedBy]
        );

        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Permanently delete an application so the player can reapply.
     * Only safe to call for non-active applications (not approved+passed).
     *
     * @param int $applicationId Application DB id.
     */
    public function deleteApplication(int $applicationId): void
    {
        $this->db->query(
            "DELETE FROM `allowlist_applications` WHERE `id` = ?",
            [$applicationId]
        );
    }

    /**
     * Find an application by its ID.
     *
     * @param int $id Application DB id.
     * @return array<string,mixed>|null
     */
    public function findById(int $id): ?array
    {
        $row = $this->db->query(
            'SELECT * FROM `allowlist_applications` WHERE `id` = ? LIMIT 1',
            [$id]
        )->fetch();

        return $row ?: null;
    }

    /**
     * Per-tester statistics for the allowlist stats page.
     *
     * @param int $days  0 = all time, otherwise last N days.
     * @return array<int, array<string,mixed>>
     */
    public function getTesterStats(int $days = 0): array
    {
        $formWhere      = $days > 0 ? 'AND a.reviewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)' : '';
        $interviewWhere = $days > 0 ? 'AND ih.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)' : '';
        $params         = $days > 0 ? [$days] : [];

        $formRows = $this->db->query(
            "SELECT
                u.id,
                u.username,
                COUNT(*)                                                        AS total_reviewed,
                SUM(a.status != 'rejected')                                     AS forms_approved,
                SUM(a.status = 'rejected')                                      AS forms_rejected,
                MIN(a.reviewed_at)                                              AS first_form,
                MAX(a.reviewed_at)                                              AS last_form,
                ROUND(AVG(TIMESTAMPDIFF(MINUTE, a.submitted_at, a.reviewed_at)), 0) AS avg_review_minutes
             FROM `allowlist_applications` a
             JOIN `users` u ON u.id = a.reviewer_id
             WHERE a.reviewer_id IS NOT NULL
               AND a.reviewed_at IS NOT NULL
               {$formWhere}
             GROUP BY u.id, u.username",
            $params
        )->fetchAll();

        $interviewRows = $this->db->query(
            "SELECT
                u.id,
                u.username,
                COUNT(*)                      AS total_interviews,
                SUM(ih.result = 'passed')     AS interviews_passed,
                SUM(ih.result = 'failed')     AS interviews_failed,
                MIN(ih.created_at)            AS first_interview,
                MAX(ih.created_at)            AS last_interview
             FROM `interview_history` ih
             JOIN `users` u ON u.id = ih.reviewer_id
             WHERE ih.reviewer_id IS NOT NULL
               {$interviewWhere}
             GROUP BY u.id, u.username",
            $params
        )->fetchAll();

        // Merge by user id
        $stats = [];
        foreach ($formRows as $row) {
            $id          = (int)$row['id'];
            $stats[$id]  = [
                'id'                 => $id,
                'username'           => $row['username'],
                'forms_approved'     => (int)($row['forms_approved']  ?? 0),
                'forms_rejected'     => (int)($row['forms_rejected']  ?? 0),
                'total_forms'        => (int)($row['total_reviewed']  ?? 0),
                'avg_review_minutes' => $row['avg_review_minutes'] !== null ? (float)$row['avg_review_minutes'] : null,
                'first_form'         => $row['first_form'],
                'last_form'          => $row['last_form'],
                'interviews_passed'  => 0,
                'interviews_failed'  => 0,
                'total_interviews'   => 0,
                'first_interview'    => null,
                'last_interview'     => null,
            ];
        }

        foreach ($interviewRows as $row) {
            $id = (int)$row['id'];
            if (!isset($stats[$id])) {
                $stats[$id] = [
                    'id'                 => $id,
                    'username'           => $row['username'],
                    'forms_approved'     => 0,
                    'forms_rejected'     => 0,
                    'total_forms'        => 0,
                    'avg_review_minutes' => null,
                    'first_form'         => null,
                    'last_form'          => null,
                ];
            }
            $stats[$id]['interviews_passed'] = (int)($row['interviews_passed'] ?? 0);
            $stats[$id]['interviews_failed'] = (int)($row['interviews_failed'] ?? 0);
            $stats[$id]['total_interviews']  = (int)($row['total_interviews']  ?? 0);
            $stats[$id]['first_interview']   = $row['first_interview'];
            $stats[$id]['last_interview']    = $row['last_interview'];
        }

        // Sort by total actions descending
        usort($stats, fn($a, $b) =>
            ($b['total_forms'] + $b['total_interviews']) <=> ($a['total_forms'] + $a['total_interviews'])
        );

        return array_values($stats);
    }

    /**
     * Log an interview attempt to the history table.
     */
    private function logInterviewAttempt(int $applicationId, int $reviewerId, string $result, ?int $errorCount): void
    {
        $this->db->query(
            "INSERT INTO `interview_history` (`application_id`, `reviewer_id`, `result`, `error_count`)
             VALUES (?, ?, ?, ?)",
            [$applicationId, $reviewerId, $result, $errorCount]
        );
    }

    /**
     * Get all interview attempts for an application, oldest first.
     *
     * @param int $applicationId Application DB id.
     * @return array<int, array<string,mixed>>
     */
    public function getInterviewHistory(int $applicationId): array
    {
        return $this->db->query(
            "SELECT ih.*, u.username AS reviewer_name
             FROM `interview_history` ih
             LEFT JOIN `users` u ON u.id = ih.reviewer_id
             WHERE ih.application_id = ?
             ORDER BY ih.created_at ASC",
            [$applicationId]
        )->fetchAll();
    }
}
