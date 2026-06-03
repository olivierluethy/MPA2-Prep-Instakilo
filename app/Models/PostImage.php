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
}
