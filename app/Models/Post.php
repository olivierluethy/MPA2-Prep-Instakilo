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
             ORDER BY p.created_at DESC'
        );
        return $this->hydrate($rows, null);
    }

    /**
     * Personalized feed for a logged-in user.
     *
     * @param string $scope 'all' (public + own + followed) or
     *                       'following' (own + followed only)
     */
    public function feedFor(int $userId, string $scope = 'all'): array
    {
        // In the "all" scope, public posts from anyone are included as well.
        $publicClause = $scope === 'following' ? '' : 'p.is_public = 1 OR ';

        $rows = $this->fetchAll(
            'SELECT ' . self::SELECT_FIELDS . ',
                    COUNT(DISTINCT l.id) AS like_count,
                    MAX(CASE WHEN lm.user_id IS NOT NULL THEN 1 ELSE 0 END) AS liked_by_me
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN likes l  ON l.post_id = p.id
             LEFT JOIN likes lm ON lm.post_id = p.id AND lm.user_id = :viewer
             WHERE ' . $publicClause . 'p.user_id = :owner
                OR p.user_id IN (SELECT f.user_id FROM followers f WHERE f.follower_id = :follower)
             GROUP BY p.id, u.username
             ORDER BY p.created_at DESC',
            ['viewer' => $userId, 'owner' => $userId, 'follower' => $userId]
        );
        return $this->hydrate($rows, $userId);
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
        return $this->hydrate($rows, $viewerId);
    }

    /**
     * Hydrated posts for a set of ids, respecting visibility, preserving the
     * order of $ids. Used by the saved-posts page, reposts and single-post view.
     *
     * @param array<int, int> $ids
     */
    public function byIds(array $ids, ?int $viewerId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids); // pure ints — safe to inline

        $params = [];
        $likedSelect = '0 AS liked_by_me';
        $likedJoin = '';
        $visibility = 'p.is_public = 1';
        if ($viewerId !== null) {
            $likedSelect = 'MAX(CASE WHEN lm.user_id IS NOT NULL THEN 1 ELSE 0 END) AS liked_by_me';
            $likedJoin = 'LEFT JOIN likes lm ON lm.post_id = p.id AND lm.user_id = :viewer';
            $visibility = '(p.is_public = 1 OR p.user_id = :owner
                            OR p.user_id IN (SELECT f.user_id FROM followers f WHERE f.follower_id = :follower))';
            $params = ['viewer' => $viewerId, 'owner' => $viewerId, 'follower' => $viewerId];
        }

        $rows = $this->fetchAll(
            'SELECT ' . self::SELECT_FIELDS . ",
                    COUNT(DISTINCT l.id) AS like_count,
                    {$likedSelect}
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN likes l ON l.post_id = p.id
             {$likedJoin}
             WHERE p.id IN ({$in}) AND {$visibility}
             GROUP BY p.id, u.username
             ORDER BY FIELD(p.id, {$in})",
            $params
        );
        return $this->hydrate($rows, $viewerId);
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Live public counters (likes/comments/reposts) for a set of posts, used to
     * reconcile feed cards across sessions without a page reload.
     *
     * @param array<int, int> $ids
     * @return array<int, array{id:int, likes:int, comments:int, reposts:int}>
     */
    public function stats(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids); // pure ints — safe to inline
        $rows = $this->fetchAll(
            "SELECT p.id,
                    (SELECT COUNT(*) FROM likes l    WHERE l.post_id = p.id) AS likes,
                    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments,
                    (SELECT COUNT(*) FROM reposts r  WHERE r.post_id = p.id) AS reposts
             FROM posts p WHERE p.id IN ({$in})"
        );
        return array_map(
            static fn (array $r): array => [
                'id'       => (int) $r['id'],
                'likes'    => (int) $r['likes'],
                'comments' => (int) $r['comments'],
                'reposts'  => (int) $r['reposts'],
            ],
            $rows
        );
    }

    /** Lightweight preview (title, author, cover image) for DM shares. */
    public function preview(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT p.id, p.title, p.is_public, p.user_id, u.username,
                    (SELECT pi.id FROM post_images pi WHERE pi.post_id = p.id
                     ORDER BY pi.sort_order, pi.id LIMIT 1) AS cover_image_id
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    /** The author of a post, or null if it does not exist. */
    public function ownerId(int $id): ?int
    {
        $row = $this->fetchOne('SELECT user_id FROM posts WHERE id = :id', ['id' => $id]);
        return $row === null ? null : (int) $row['user_id'];
    }

    public function isOwnedBy(int $id, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM posts WHERE id = :id AND user_id = :user',
            ['id' => $id, 'user' => $userId]
        );
    }

    public function update(int $id, string $title, string $description, string $location, ?string $takenOn, bool $isPublic): void
    {
        $this->run(
            'UPDATE posts SET title = :title, description = :description, location = :location,
                              taken_on = :taken_on, is_public = :is_public
             WHERE id = :id',
            [
                'title'       => $title,
                'description' => $description,
                'location'    => $location,
                'taken_on'    => $takenOn ?: null,
                'is_public'   => $isPublic ? 1 : 0,
                'id'          => $id,
            ]
        );
    }

    /** Deletes the post; images, likes, comments, saves & reposts cascade. */
    public function delete(int $id): void
    {
        $this->run('DELETE FROM posts WHERE id = :id', ['id' => $id]);
    }

    /**
     * Search posts by title (visible: public, or the viewer's own).
     * Injection-safe: bound LIKE with escaped metacharacters.
     *
     * @return array<int, array{id:int, title:string, username:string, cover_image_id:?int}>
     */
    public function search(string $term, ?int $viewerId, int $limit = 6): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $like = '%' . addcslashes($term, '%_\\') . '%';
        $limit = max(1, min(20, $limit));

        $where = 'p.is_public = 1';
        $params = ['q' => $like];
        if ($viewerId !== null) {
            $where = '(p.is_public = 1 OR p.user_id = :viewer)';
            $params['viewer'] = $viewerId;
        }

        $rows = $this->fetchAll(
            "SELECT p.id, p.title, u.username,
                    (SELECT pi.id FROM post_images pi WHERE pi.post_id = p.id
                     ORDER BY pi.sort_order, pi.id LIMIT 1) AS cover_image_id
             FROM posts p JOIN users u ON u.id = p.user_id
             WHERE p.title LIKE :q AND {$where}
             ORDER BY p.created_at DESC
             LIMIT {$limit}",
            $params
        );
        return array_map(
            static fn (array $r): array => [
                'id'             => (int) $r['id'],
                'title'          => $r['title'],
                'username'       => $r['username'],
                'cover_image_id' => $r['cover_image_id'] !== null ? (int) $r['cover_image_id'] : null,
            ],
            $rows
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
    private function hydrate(array $posts, ?int $viewerId): array
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

        // --- viewer-specific flags: saved / reposted by the current user ---
        $savedIds = [];
        $repostedIds = [];
        if ($viewerId !== null) {
            $savedStmt = $this->db->prepare(
                "SELECT post_id FROM saved_posts WHERE user_id = ? AND post_id IN ({$placeholders})"
            );
            $savedStmt->execute(array_merge([$viewerId], $ids));
            foreach ($savedStmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $savedIds[(int) $pid] = true;
            }
            $repostStmt = $this->db->prepare(
                "SELECT post_id FROM reposts WHERE user_id = ? AND post_id IN ({$placeholders})"
            );
            $repostStmt->execute(array_merge([$viewerId], $ids));
            foreach ($repostStmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
                $repostedIds[(int) $pid] = true;
            }
        }

        foreach ($posts as &$post) {
            $id = (int) $post['id'];
            $post['images']        = $imagesByPost[$id] ?? [];
            $post['like_count']    = (int) $post['like_count'];
            $post['liked_by_me']   = (bool) $post['liked_by_me'];
            $post['comments']      = $commentsByPost[$id] ?? [];
            $post['comment_count'] = $commentCount[$id] ?? 0;
            $post['is_saved']      = isset($savedIds[$id]);
            $post['is_reposted']   = isset($repostedIds[$id]);
            // Feed metadata (overridden for repost entries in the controller).
            $post['event_time']    = $post['created_at'] ?? null;
            $post['reposted_by']   = null;
        }
        unset($post);

        return $posts;
    }
}
