<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Post likes. A UNIQUE(post_id, user_id) constraint makes liking idempotent,
 * so duplicate likes are impossible regardless of double-clicks.
 */
final class Like extends Model
{
    public function like(int $postId, int $userId): void
    {
        $this->run(
            'INSERT IGNORE INTO likes (post_id, user_id) VALUES (:post, :user)',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function unlike(int $postId, int $userId): void
    {
        $this->run(
            'DELETE FROM likes WHERE post_id = :post AND user_id = :user',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function countForPost(int $postId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM likes WHERE post_id = :post',
            ['post' => $postId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
