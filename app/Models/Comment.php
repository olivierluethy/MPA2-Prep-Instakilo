<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Comments on posts. Each comment belongs to one post and one user.
 * Bodies are stored as plain text and always escaped on output.
 */
final class Comment extends Model
{
    /** Default page size for the paginated comment list. */
    public const PER_PAGE = 10;

    /**
     * Add a comment and return the created row (joined with the author name).
     */
    public function create(int $postId, int $userId, string $body): array
    {
        $this->run(
            'INSERT INTO comments (post_id, user_id, body) VALUES (:post, :user, :body)',
            ['post' => $postId, 'user' => $userId, 'body' => $body]
        );
        $id = (int) $this->db->lastInsertId();

        return $this->fetchOne(
            'SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, u.username
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.id = :id',
            ['id' => $id]
        ) ?? [];
    }

    /**
     * A page of comments for a post, oldest first, with the author username.
     */
    public function forPost(int $postId, int $page = 1): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * self::PER_PAGE;

        // LIMIT/OFFSET are cast to int and inlined safely (PDO can't bind them
        // as parameters with native prepares); $postId stays a bound param.
        $limit = self::PER_PAGE;
        return $this->fetchAll(
            "SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, u.username
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :post
             ORDER BY c.created_at ASC, c.id ASC
             LIMIT {$limit} OFFSET {$offset}",
            ['post' => $postId]
        );
    }

    public function countForPost(int $postId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM comments WHERE post_id = :post',
            ['post' => $postId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public function postExists(int $postId): bool
    {
        return (bool) $this->fetchOne('SELECT 1 FROM posts WHERE id = :id', ['id' => $postId]);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, u.username
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.id = :id',
            ['id' => $id]
        );
    }

    /** Only the comment's author may edit/delete it. */
    public function isOwnedBy(int $id, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM comments WHERE id = :id AND user_id = :user',
            ['id' => $id, 'user' => $userId]
        );
    }

    public function update(int $id, string $body): void
    {
        $this->run('UPDATE comments SET body = :body WHERE id = :id', ['body' => $body, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->run('DELETE FROM comments WHERE id = :id', ['id' => $id]);
    }
}
