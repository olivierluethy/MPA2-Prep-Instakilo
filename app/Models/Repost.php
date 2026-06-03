<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Reposts: a user re-sharing a public post to their own feed.
 * UNIQUE(user_id, post_id) makes reposting idempotent.
 */
final class Repost extends Model
{
    public function repost(int $postId, int $userId): void
    {
        $this->run(
            'INSERT IGNORE INTO reposts (post_id, user_id) VALUES (:post, :user)',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function unrepost(int $postId, int $userId): void
    {
        $this->run(
            'DELETE FROM reposts WHERE post_id = :post AND user_id = :user',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function isReposted(int $postId, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM reposts WHERE post_id = :post AND user_id = :user',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function countForPost(int $postId): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS c FROM reposts WHERE post_id = :post', ['post' => $postId]);
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Repost events authored by any of $reposterIds, newest first. Each row is a
     * feed event referencing the original post.
     *
     * @param array<int, int> $reposterIds
     * @return array<int, array{post_id:int, reposter_id:int, reposter_username:string, created_at:string}>
     */
    public function eventsBy(array $reposterIds): array
    {
        if ($reposterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($reposterIds), '?'));
        $rows = $this->fetchAll(
            "SELECT r.post_id, r.user_id AS reposter_id, u.username AS reposter_username, r.created_at
             FROM reposts r JOIN users u ON u.id = r.user_id
             WHERE r.user_id IN ({$placeholders})
             ORDER BY r.created_at DESC, r.id DESC",
            array_values($reposterIds)
        );
        return array_map(
            static fn (array $r): array => [
                'post_id'           => (int) $r['post_id'],
                'reposter_id'       => (int) $r['reposter_id'],
                'reposter_username' => $r['reposter_username'],
                'created_at'        => $r['created_at'],
            ],
            $rows
        );
    }
}
