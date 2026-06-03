<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Uploaded DM attachments (image/gif/video/file). A message can have several;
 * sort_order preserves the order they were added.
 */
final class MessageMedia extends Model
{
    public function add(int $messageId, string $kind, string $mime, string $fileName, int $size, string $data, int $sortOrder = 0): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO message_media (message_id, kind, mime, file_name, file_size, sort_order, data)
             VALUES (:msg, :kind, :mime, :name, :size, :sort, :data)'
        );
        $stmt->bindValue(':msg', $messageId, PDO::PARAM_INT);
        $stmt->bindValue(':kind', $kind);
        $stmt->bindValue(':mime', $mime);
        $stmt->bindValue(':name', $fileName);
        $stmt->bindValue(':size', $size, PDO::PARAM_INT);
        $stmt->bindValue(':sort', $sortOrder, PDO::PARAM_INT);
        $stmt->bindValue(':data', $data, PDO::PARAM_LOB);
        $stmt->execute();
    }

    /**
     * Attachments for a set of messages, grouped by message_id (ordered).
     *
     * @param array<int,int> $messageIds
     * @return array<int, array<int, array{id:int, kind:string, mime:string, name:string, size:int}>>
     */
    public function forMessages(array $messageIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $messageIds)));
        if ($ids === []) {
            return [];
        }
        $in = implode(',', $ids);
        $rows = $this->fetchAll(
            "SELECT id, message_id, kind, mime, file_name, file_size
             FROM message_media WHERE message_id IN ({$in})
             ORDER BY message_id, sort_order, id"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['message_id']][] = [
                'id'   => (int) $r['id'],
                'kind' => $r['kind'],
                'mime' => $r['mime'],
                'name' => $r['file_name'],
                'size' => (int) $r['file_size'],
            ];
        }
        return $out;
    }

    /**
     * Fetch a media blob only if $userId is a participant of its message.
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
