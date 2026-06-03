<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Direct messages between two users. A message is one of several kinds
 * (text | post | image | gif | video | file | link) and may carry a shared post
 * (shared_post_id) and/or an uploaded attachment (message_media row).
 */
final class Message extends Model
{
    /** Columns selected for thread/poll, including the (optional) attachment. */
    private const SELECT =
        'm.id, m.sender_id, m.recipient_id, m.kind, m.body, m.shared_post_id, m.created_at,
         su.username AS sender_username,
         mm.id AS media_id, mm.kind AS media_kind, mm.mime AS media_mime,
         mm.file_name AS media_name, mm.file_size AS media_size';

    private const SECONDS_TYPING = 6;

    public function send(int $senderId, int $recipientId, string $kind, ?string $body, ?int $sharedPostId = null): int
    {
        $this->run(
            'INSERT INTO messages (sender_id, recipient_id, kind, body, shared_post_id)
             VALUES (:sender, :recipient, :kind, :body, :post)',
            [
                'sender'    => $senderId,
                'recipient' => $recipientId,
                'kind'      => $kind,
                'body'      => ($body === null || $body === '') ? null : $body,
                'post'      => $sharedPostId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Conversation list: latest message per other participant + unread count.
     */
    public function conversations(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT m.id, m.sender_id, m.recipient_id, m.kind, m.body, m.shared_post_id, m.is_read, m.created_at,
                    su.username AS sender_username, ru.username AS recipient_username
             FROM messages m
             JOIN users su ON su.id = m.sender_id
             JOIN users ru ON ru.id = m.recipient_id
             WHERE m.sender_id = :me OR m.recipient_id = :me2
             ORDER BY m.created_at DESC, m.id DESC',
            ['me' => $userId, 'me2' => $userId]
        );

        $conversations = [];
        foreach ($rows as $row) {
            $isIncoming = (int) $row['recipient_id'] === $userId;
            $otherId = $isIncoming ? (int) $row['sender_id'] : (int) $row['recipient_id'];
            $otherName = $isIncoming ? $row['sender_username'] : $row['recipient_username'];

            if (!isset($conversations[$otherId])) {
                $conversations[$otherId] = [
                    'user_id'  => $otherId,
                    'username' => $otherName,
                    'last_body' => $this->preview($row),
                    'last_at'  => $row['created_at'],
                    'unread'   => 0,
                ];
            }
            if ($isIncoming && (int) $row['is_read'] === 0) {
                $conversations[$otherId]['unread']++;
            }
        }

        return array_values($conversations);
    }

    private function preview(array $row): string
    {
        if (!empty($row['body'])) {
            return $row['body'];
        }
        return match ($row['kind']) {
            'post'  => '📷 Beitrag geteilt',
            'image' => '🖼️ Bild',
            'gif'   => '🖼️ GIF',
            'video' => '🎬 Video',
            'file'  => '📎 Datei',
            'link'  => '🔗 Link',
            default => '',
        };
    }

    /** Full thread between two users, oldest first. */
    public function thread(int $userId, int $otherId): array
    {
        return $this->fetchAll(
            'SELECT ' . self::SELECT . '
             FROM messages m
             JOIN users su ON su.id = m.sender_id
             LEFT JOIN message_media mm ON mm.message_id = m.id
             WHERE (m.sender_id = :me AND m.recipient_id = :other)
                OR (m.sender_id = :other2 AND m.recipient_id = :me2)
             ORDER BY m.created_at ASC, m.id ASC',
            ['me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId]
        );
    }

    /** Messages in the thread newer than $afterId (for real-time polling). */
    public function since(int $userId, int $otherId, int $afterId): array
    {
        return $this->fetchAll(
            'SELECT ' . self::SELECT . '
             FROM messages m
             JOIN users su ON su.id = m.sender_id
             LEFT JOIN message_media mm ON mm.message_id = m.id
             WHERE m.id > :after
               AND ((m.sender_id = :me AND m.recipient_id = :other)
                 OR (m.sender_id = :other2 AND m.recipient_id = :me2))
             ORDER BY m.id ASC',
            ['after' => $afterId, 'me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId]
        );
    }

    public function markRead(int $userId, int $otherId): void
    {
        $this->run(
            'UPDATE messages SET is_read = 1 WHERE recipient_id = :me AND sender_id = :other AND is_read = 0',
            ['me' => $userId, 'other' => $otherId]
        );
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM messages WHERE recipient_id = :me AND is_read = 0',
            ['me' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }

    /** Record that $userId is typing to $peerId (refreshed on each keystroke). */
    public function setTyping(int $userId, int $peerId): void
    {
        $this->run(
            'INSERT INTO dm_typing (user_id, peer_id, updated_at) VALUES (:u, :p, NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()',
            ['u' => $userId, 'p' => $peerId]
        );
    }

    /** Is $otherId currently typing to $userId? */
    public function peerTyping(int $userId, int $otherId): bool
    {
        $row = $this->fetchOne(
            'SELECT 1 FROM dm_typing
             WHERE user_id = :other AND peer_id = :me
               AND updated_at > (NOW() - INTERVAL ' . self::SECONDS_TYPING . ' SECOND)',
            ['other' => $otherId, 'me' => $userId]
        );
        return (bool) $row;
    }
}
