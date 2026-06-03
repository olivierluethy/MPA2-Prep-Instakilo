<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Uploaded DM attachments (image/gif/video/file) stored as blobs and streamed
 * through the /messages/media endpoint.
 */
final class MessageMedia extends Model
{
    public function add(int $messageId, string $kind, string $mime, string $fileName, int $size, string $data): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO message_media (message_id, kind, mime, file_name, file_size, data)
             VALUES (:msg, :kind, :mime, :name, :size, :data)'
        );
        $stmt->bindValue(':msg', $messageId, PDO::PARAM_INT);
        $stmt->bindValue(':kind', $kind);
        $stmt->bindValue(':mime', $mime);
        $stmt->bindValue(':name', $fileName);
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':data', $data, PDO::PARAM_LOB);
        $stmt->execute();
    }

    /**
     * Fetch a media blob only if $userId is a participant of its message
     * (authorization enforced in the query).
     *
     * @return array{mime:string, file_name:string, data:string, kind:string}|null
     */
    public function findForParticipant(int $id, int $userId): ?array
    {
        return $this->fetchOne(
            'SELECT mm.mime, mm.file_name, mm.data, mm.kind
             FROM message_media mm
             JOIN messages m ON m.id = mm.message_id
             WHERE mm.id = :id AND (m.sender_id = :u OR m.recipient_id = :u2)',
            ['id' => $id, 'u' => $userId, 'u2' => $userId]
        );
    }
}
