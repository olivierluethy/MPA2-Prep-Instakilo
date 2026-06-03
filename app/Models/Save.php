<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * A user's saved-post collection (bookmarks). UNIQUE(user_id, post_id) makes
 * saving idempotent.
 */
final class Save extends Model
{
    public function save(int $postId, int $userId): void
    {
        $this->run(
            'INSERT IGNORE INTO saved_posts (post_id, user_id) VALUES (:post, :user)',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function unsave(int $postId, int $userId): void
    {
        $this->run(
            'DELETE FROM saved_posts WHERE post_id = :post AND user_id = :user',
            ['post' => $postId, 'user' => $userId]
        );
    }

    public function isSaved(int $postId, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM saved_posts WHERE post_id = :post AND user_id = :user',
            ['post' => $postId, 'user' => $userId]
        );
    }

    /**
     * Post ids the user saved, most recently saved first.
     *
     * @return array<int, int>
     */
    public function postIdsFor(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT post_id FROM saved_posts WHERE user_id = :user ORDER BY created_at DESC, id DESC',
            ['user' => $userId]
        );
        return array_map(static fn (array $r): int => (int) $r['post_id'], $rows);
    }
}
