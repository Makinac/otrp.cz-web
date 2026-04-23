<?php

declare(strict_types=1);

namespace App\Models;

class CkVoteModel extends BaseModel
{
    /**
     * Get all votes, newest first, with vote counts.
     *
     * @param string $status 'open'|'closed'|'all'
     * @return array<int,array<string,mixed>>
     */
    public function getAll(string $status = 'all'): array
    {
        $params = [];
        $where = '';
        if ($status === 'open' || $status === 'closed') {
            $where = 'WHERE v.status = ?';
            $params[] = $status;
        }

        $sql = "SELECT v.*,
                       u.username AS creator_username,
                       (SELECT COUNT(*) FROM ck_vote_entries e WHERE e.vote_id = v.id) AS total_votes,
                       (SELECT COUNT(*) FROM ck_vote_entries e WHERE e.vote_id = v.id AND e.decision = 'approve') AS approve_count,
                       (SELECT COUNT(*) FROM ck_vote_entries e WHERE e.vote_id = v.id AND e.decision = 'reject') AS reject_count,
                       (SELECT COUNT(*) FROM ck_vote_entries e WHERE e.vote_id = v.id AND e.decision = 'abstain') AS abstain_count
                FROM ck_votes v
                JOIN users u ON u.id = v.created_by
                {$where}
                ORDER BY v.created_at DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Find a single vote by ID, including creator info.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT v.*,
                       u.username AS creator_username,
                       u.avatar AS creator_avatar,
                       u.discord_id AS creator_discord_id
                FROM ck_votes v
                JOIN users u ON u.id = v.created_by
                WHERE v.id = ?";
        $row = $this->db->query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Create a new CK vote.
     */
    public function create(string $applicant, string $victim, string $description, ?string $contextUrls, int $createdBy): int
    {
        $sql = "INSERT INTO ck_votes (applicant, victim, description, context_urls, created_by)
                VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [$applicant, $victim, $description, $contextUrls, $createdBy]);
        return (int)$this->db->getConnection()->lastInsertId();
    }

    /**
     * Cast or update a vote entry.
     */
    public function castVote(int $voteId, int $userId, string $decision, string $reason): void
    {
        $sql = "INSERT INTO ck_vote_entries (vote_id, user_id, decision, reason)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE decision = VALUES(decision), reason = VALUES(reason), created_at = NOW()";
        $this->db->query($sql, [$voteId, $userId, $decision, $reason]);
    }

    /**
     * Get all vote entries for a vote.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getEntries(int $voteId): array
    {
        $sql = "SELECT e.*, u.username, u.avatar, u.discord_id
                FROM ck_vote_entries e
                JOIN users u ON u.id = e.user_id
                WHERE e.vote_id = ?
                ORDER BY e.created_at ASC";
        return $this->db->query($sql, [$voteId])->fetchAll();
    }

    /**
     * Check if user already voted on a specific vote.
     */
    public function getUserVote(int $voteId, int $userId): ?array
    {
        $sql = "SELECT * FROM ck_vote_entries WHERE vote_id = ? AND user_id = ?";
        $row = $this->db->query($sql, [$voteId, $userId])->fetch();
        return $row ?: null;
    }

    /**
     * Close a vote and compute the result.
     */
    public function close(int $voteId, int $closedBy): string
    {
        $entries = $this->getEntries($voteId);
        $approve = 0;
        $reject = 0;
        foreach ($entries as $e) {
            if ($e['decision'] === 'approve') $approve++;
            if ($e['decision'] === 'reject') $reject++;
        }

        if ($approve > $reject) {
            $result = 'approved';
        } elseif ($reject > $approve) {
            $result = 'rejected';
        } else {
            $result = 'tie';
        }

        $sql = "UPDATE ck_votes SET status = 'closed', result = ?, closed_by = ?, closed_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$result, $closedBy, $voteId]);

        return $result;
    }

    /**
     * Delete a vote entry by ID.
     */
    public function deleteEntry(int $entryId): void
    {
        $sql = "DELETE FROM ck_vote_entries WHERE id = ?";
        $this->db->query($sql, [$entryId]);
    }

    /**
     * Get comments for a vote.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getComments(int $voteId): array
    {
        $sql = "SELECT c.*, u.username, u.avatar, u.discord_id
                FROM ck_vote_comments c
                JOIN users u ON u.id = c.user_id
                WHERE c.vote_id = ?
                ORDER BY c.created_at ASC";
        return $this->db->query($sql, [$voteId])->fetchAll();
    }

    /**
     * Add a comment to a vote.
     */
    public function addComment(int $voteId, int $userId, string $body): void
    {
        $sql = "INSERT INTO ck_vote_comments (vote_id, user_id, body) VALUES (?, ?, ?)";
        $this->db->query($sql, [$voteId, $userId, $body]);
    }

    /**
     * Count open votes (for badge).
     */
    public function countOpen(): int
    {
        $sql = "SELECT COUNT(*) FROM ck_votes WHERE status = 'open'";
        return (int)$this->db->query($sql)->fetch(\PDO::FETCH_COLUMN);
    }
}
