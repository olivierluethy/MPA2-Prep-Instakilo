<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Posts (the "image" entity of the old app, now a post that can hold several
 * images). Handles feed building, creation and per-user listings.
 *
 * Feed rules (preserved from the original product spec, but corrected):
 *   - Logged out: only public posts.
 *   - Logged in:  public posts + posts from people you follow + your own,
 *                 each annotated with whether the current user liked it.
 *   - Ordered by like count (desc), newest first as a tie-breaker.
 */
final class Post extends Model
{
    private const SELECT_FIELDS =
        'p.id, p.title, p.description, p.location, p.taken_on, p.created_at,
         p.is_public, p.user_id, u.username';

    /**
     * Public feed for anonymous visitors.
     */
    public function publicFeed(): array
    {
        $rows = $this->fetchAll(
            'SELECT ' . self::SELECT_FIELDS . ',
                    COUNT(l.id) AS like_count,
                    0 AS liked_by_me
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN likes l ON l.post_id = p.id
             WHERE p.is_public = 1
             GROUP BY p.id, u.username
             ORDER BY like_count DESC, p.created_at DESC'
        );
        return $this->hydrate($rows);
    }

    /**
     * Personalized feed for a logged-in user.
     */
    public function feedFor(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT ' . self::SELECT_FIELDS . ',
                    COUNT(DISTINCT l.id) AS like_count,
                    MAX(CASE WHEN lm.user_id IS NOT NULL THEN 1 ELSE 0 END) AS liked_by_me
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN likes l  ON l.post_id = p.id
             LEFT JOIN likes lm ON lm.post_id = p.id AND lm.user_id = :viewer
             WHERE p.is_public = 1
                OR p.user_id = :owner
                OR p.user_id IN (SELECT f.user_id FROM followers f WHERE f.follower_id = :follower)
             GROUP BY p.id, u.username
             ORDER BY like_count DESC, p.created_at DESC',
            ['viewer' => $userId, 'owner' => $userId, 'follower' => $userId]
        );
        return $this->hydrate($rows);
    }

    /**
     * Posts authored by a user, respecting visibility for the viewer.
     */
    public function byUser(int $authorId, ?int $viewerId, bool $canSeePrivate): array
    {
        $params = ['author' => $authorId];
        $visibility = 'p.is_public = 1';
        if ($canSeePrivate) {
            $visibility = '1 = 1'; // owner or follower sees everything
        }

        $likedSelect = '0 AS liked_by_me';
        $likedJoin = '';
        if ($viewerId !== null) {
            $likedSelect = 'MAX(CASE WHEN lm.user_id IS NOT NULL THEN 1 ELSE 0 END) AS liked_by_me';
            $likedJoin = 'LEFT JOIN likes lm ON lm.post_id = p.id AND lm.user_id = :viewer';
            $params['viewer'] = $viewerId;
        }

        $rows = $this->fetchAll(
            'SELECT ' . self::SELECT_FIELDS . ",
                    COUNT(DISTINCT l.id) AS like_count,
                    {$likedSelect}
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN likes l ON l.post_id = p.id
             {$likedJoin}
             WHERE p.user_id = :author AND ({$visibility})
             GROUP BY p.id, u.username
             ORDER BY p.created_at DESC",
            $params
        );
        return $this->hydrate($rows);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Create a post and return its new id.
     */
    public function create(int $userId, string $title, string $description, string $location, ?string $takenOn, bool $isPublic): int
    {
        $this->run(
            'INSERT INTO posts (user_id, title, description, location, taken_on, is_public)
             VALUES (:user_id, :title, :description, :location, :taken_on, :is_public)',
            [
                'user_id'     => $userId,
                'title'       => $title,
                'description' => $description,
                'location'    => $location,
                'taken_on'    => $takenOn ?: null,
                'is_public'   => $isPublic ? 1 : 0,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Hydrate post rows with their images and a comment preview, each in a
     * single batched query (avoids the N+1 problem and base64-inlining).
     */
    private function hydrate(array $posts): array
    {
        if ($posts === []) {
            return [];
        }

        $ids = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        // --- images (ordered) ---
        $imgStmt = $this->db->prepare(
            "SELECT post_id, id FROM post_images
             WHERE post_id IN ({$placeholders})
             ORDER BY post_id, sort_order, id"
        );
        $imgStmt->execute($ids);
        $imagesByPost = [];
        foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $imagesByPost[(int) $row['post_id']][] = (int) $row['id'];
        }

        // --- comment preview: the 2 most recent comments + total per post, in
        //     one query using window functions (efficient, no N+1) ---
        $commentStmt = $this->db->prepare(
            "SELECT x.id, x.post_id, x.user_id, x.body, x.created_at, x.username, x.total
             FROM (
                SELECT c.id, c.post_id, c.user_id, c.body, c.created_at, u.username,
                       ROW_NUMBER() OVER (PARTITION BY c.post_id ORDER BY c.created_at DESC, c.id DESC) AS rn,
                       COUNT(*)     OVER (PARTITION BY c.post_id) AS total
                FROM comments c JOIN users u ON u.id = c.user_id
                WHERE c.post_id IN ({$placeholders})
             ) x
             WHERE x.rn <= 2
             ORDER BY x.post_id, x.created_at ASC, x.id ASC"
        );
        $commentStmt->execute($ids);
        $commentsByPost = [];
        $commentCount = [];
        foreach ($commentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $pid = (int) $row['post_id'];
            $commentCount[$pid] = (int) $row['total'];
            $commentsByPost[$pid][] = [
                'id'         => (int) $row['id'],
                'user_id'    => (int) $row['user_id'],
                'username'   => $row['username'],
                'body'       => $row['body'],
                'created_at' => $row['created_at'],
            ];
        }

        foreach ($posts as &$post) {
            $id = (int) $post['id'];
            $post['images']        = $imagesByPost[$id] ?? [];
            $post['like_count']    = (int) $post['like_count'];
            $post['liked_by_me']   = (bool) $post['liked_by_me'];
            $post['comments']      = $commentsByPost[$id] ?? [];
            $post['comment_count'] = $commentCount[$id] ?? 0;
        }
        unset($post);

        return $posts;
    }
}
