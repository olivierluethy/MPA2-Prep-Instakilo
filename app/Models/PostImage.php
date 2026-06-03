<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Image blobs belonging to a post. Stored in MySQL (LONGBLOB) and streamed to
 * the browser through the /posts/image endpoint — never base64-inlined.
 */
final class PostImage extends Model
{
    /**
     * Persist one image for a post at the given display order.
     */
    public function add(int $postId, string $mime, string $data, int $sortOrder): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_images (post_id, image_type, image_data, sort_order)
             VALUES (:post_id, :type, :data, :sort_order)'
        );
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':type', $mime);
        $stmt->bindValue(':data', $data, PDO::PARAM_LOB);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Raw bytes + mime for one image, used by the streaming endpoint.
     *
     * @return array{image_type:string, image_data:string}|null
     */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT image_type, image_data FROM post_images WHERE id = :id',
            ['id' => $id]
        );
    }

    /** Ordered image ids for a post. @return array<int, int> */
    public function idsFor(int $postId): array
    {
        $rows = $this->fetchAll(
            'SELECT id FROM post_images WHERE post_id = :post ORDER BY sort_order, id',
            ['post' => $postId]
        );
        return array_map(static fn (array $r): int => (int) $r['id'], $rows);
    }

    public function countFor(int $postId): int
    {
        $row = $this->fetchOne('SELECT COUNT(*) AS c FROM post_images WHERE post_id = :post', ['post' => $postId]);
        return (int) ($row['c'] ?? 0);
    }

    public function maxSortOrder(int $postId): int
    {
        $row = $this->fetchOne('SELECT COALESCE(MAX(sort_order), -1) AS m FROM post_images WHERE post_id = :post', ['post' => $postId]);
        return (int) ($row['m'] ?? -1);
    }

    public function delete(int $id, int $postId): void
    {
        // Scoped by post_id so a user can only remove images from their own post.
        $this->run('DELETE FROM post_images WHERE id = :id AND post_id = :post', ['id' => $id, 'post' => $postId]);
    }

    /** Persist a new display order for a post's images. @param array<int,int> $orderedIds */
    public function reorder(int $postId, array $orderedIds): void
    {
        $sort = 0;
        foreach ($orderedIds as $imageId) {
            $this->run(
                'UPDATE post_images SET sort_order = :sort WHERE id = :id AND post_id = :post',
                ['sort' => $sort++, 'id' => (int) $imageId, 'post' => $postId]
            );
        }
    }
}
