<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Direct messages between two users. A message may optionally reference a shared
 * post (shared_post_id).
 */
final class Message extends Model
{
    public function send(int $senderId, int $recipientId, ?string $body, ?int $sharedPostId = null): int
    {
        $this->run(
            'INSERT INTO messages (sender_id, recipient_id, body, shared_post_id)
             VALUES (:sender, :recipient, :body, :post)',
            [
                'sender'    => $senderId,
                'recipient' => $recipientId,
                'body'      => ($body === null || $body === '') ? null : $body,
                'post'      => $sharedPostId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Conversation list for a user: the latest message per other participant,
     * with that user's name and an unread count. Newest conversation first.
     */
    public function conversations(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT m.id, m.sender_id, m.recipient_id, m.body, m.shared_post_id, m.is_read, m.created_at,
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
                    'user_id'      => $otherId,
                    'username'     => $otherName,
                    'last_body'    => $row['body'] ?? ($row['shared_post_id'] ? '📷 Beitrag geteilt' : ''),
                    'last_at'      => $row['created_at'],
                    'unread'       => 0,
                ];
            }
            if ($isIncoming && (int) $row['is_read'] === 0) {
                $conversations[$otherId]['unread']++;
            }
        }

        return array_values($conversations);
    }

    /**
     * Full thread between two users, oldest first.
     */
    public function thread(int $userId, int $otherId): array
    {
        return $this->fetchAll(
            'SELECT m.id, m.sender_id, m.recipient_id, m.body, m.shared_post_id, m.created_at,
                    su.username AS sender_username
             FROM messages m JOIN users su ON su.id = m.sender_id
             WHERE (m.sender_id = :me AND m.recipient_id = :other)
                OR (m.sender_id = :other2 AND m.recipient_id = :me2)
             ORDER BY m.created_at ASC, m.id ASC',
            ['me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId]
        );
    }

    /** Mark messages from $otherId to $userId as read. */
    public function markRead(int $userId, int $otherId): void
    {
        $this->run(
            'UPDATE messages SET is_read = 1 WHERE recipient_id = :me AND sender_id = :other AND is_read = 0',
            ['me' => $userId, 'other' => $otherId]
        );
    }

    /** Total unread messages for the nav badge. */
    public function unreadCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM messages WHERE recipient_id = :me AND is_read = 0',
            ['me' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
