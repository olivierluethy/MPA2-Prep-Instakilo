<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Direct messages. A message has a kind (text|post|link|media and the URL-only
 * image|gif|video), may reference a shared post, may reply to another message,
 * may carry one or more uploaded attachments (message_media), and supports edit
 * (edited_at) and soft delete (deleted_at).
 */
final class Message extends Model
{
    private const SELECT =
        'm.id, m.sender_id, m.recipient_id, m.kind, m.body, m.shared_post_id, m.reply_to_id,
         m.created_at, m.edited_at, m.deleted_at, su.username AS sender_username';

    private const SECONDS_TYPING = 6;

    public function send(int $senderId, int $recipientId, string $kind, ?string $body, ?int $sharedPostId = null, ?int $replyToId = null): int
    {
        $this->run(
            'INSERT INTO messages (sender_id, recipient_id, kind, body, shared_post_id, reply_to_id)
             VALUES (:sender, :recipient, :kind, :body, :post, :reply)',
            [
                'sender'    => $senderId,
                'recipient' => $recipientId,
                'kind'      => $kind,
                'body'      => ($body === null || $body === '') ? null : $body,
                'post'      => $sharedPostId,
                'reply'     => $replyToId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, sender_id, recipient_id, kind, body, deleted_at FROM messages WHERE id = :id',
            ['id' => $id]
        );
    }

    public function isOwnedBy(int $id, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM messages WHERE id = :id AND sender_id = :user AND deleted_at IS NULL',
            ['id' => $id, 'user' => $userId]
        );
    }

    /** Is $userId a participant of the conversation containing message $id? */
    public function participates(int $id, int $userId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM messages WHERE id = :id AND (sender_id = :u OR recipient_id = :u2)',
            ['id' => $id, 'u' => $userId, 'u2' => $userId]
        );
    }

    public function edit(int $id, string $body): void
    {
        $this->run('UPDATE messages SET body = :b, edited_at = NOW() WHERE id = :id', ['b' => $body, 'id' => $id]);
    }

    public function softDelete(int $id): void
    {
        // Keep the row (and ordering) but blank the content.
        $this->run('UPDATE messages SET deleted_at = NOW(), body = NULL WHERE id = :id', ['id' => $id]);
        $this->run('DELETE FROM message_media WHERE message_id = :id', ['id' => $id]);
    }

    /** Conversation list: latest message per other participant + unread count. */
    public function conversations(int $userId): array
    {
        $rows = $this->fetchAll(
            'SELECT m.id, m.sender_id, m.recipient_id, m.kind, m.body, m.shared_post_id, m.is_read, m.deleted_at, m.created_at,
                    su.username AS sender_username, ru.username AS recipient_username,
                    (SELECT COUNT(*) FROM message_media mm WHERE mm.message_id = m.id) AS media_count
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
            if (!isset($conversations[$otherId])) {
                $conversations[$otherId] = [
                    'user_id'   => $otherId,
                    'username'  => $isIncoming ? $row['sender_username'] : $row['recipient_username'],
                    'last_body' => $this->preview($row),
                    'last_at'   => $row['created_at'],
                    'unread'    => 0,
                ];
            }
            if ($isIncoming && (int) $row['is_read'] === 0 && $row['deleted_at'] === null) {
                $conversations[$otherId]['unread']++;
            }
        }
        return array_values($conversations);
    }

    private function preview(array $row): string
    {
        if ($row['deleted_at'] !== null) {
            return 'Nachricht gelöscht';
        }
        if (!empty($row['body'])) {
            return $row['body'];
        }
        if ((int) ($row['media_count'] ?? 0) > 0) {
            return '📎 Anhang';
        }
        return match ($row['kind']) {
            'post'  => '📷 Beitrag geteilt',
            'image' => '🖼️ Bild',
            'gif'   => '🖼️ GIF',
            'video' => '🎬 Video',
            'link'  => '🔗 Link',
            default => '',
        };
    }

    /** Full thread between two users, oldest first (hydrated). */
    public function thread(int $userId, int $otherId): array
    {
        return $this->hydrate($this->fetchAll(
            'SELECT ' . self::SELECT . '
             FROM messages m JOIN users su ON su.id = m.sender_id
             WHERE (m.sender_id = :me AND m.recipient_id = :other)
                OR (m.sender_id = :other2 AND m.recipient_id = :me2)
             ORDER BY m.created_at ASC, m.id ASC',
            ['me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId]
        ));
    }

    /** New messages after $afterId (real-time). */
    public function since(int $userId, int $otherId, int $afterId): array
    {
        return $this->hydrate($this->fetchAll(
            'SELECT ' . self::SELECT . '
             FROM messages m JOIN users su ON su.id = m.sender_id
             WHERE m.id > :after
               AND ((m.sender_id = :me AND m.recipient_id = :other)
                 OR (m.sender_id = :other2 AND m.recipient_id = :me2))
             ORDER BY m.id ASC',
            ['after' => $afterId, 'me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId]
        ));
    }

    /** Already-delivered messages (id <= $maxId) edited/deleted after $sinceTs. */
    public function revisionsSince(int $userId, int $otherId, int $sinceTs, int $maxId): array
    {
        return $this->hydrate($this->fetchAll(
            'SELECT ' . self::SELECT . '
             FROM messages m JOIN users su ON su.id = m.sender_id
             WHERE m.id <= :max
               AND ((m.sender_id = :me AND m.recipient_id = :other)
                 OR (m.sender_id = :other2 AND m.recipient_id = :me2))
               AND (COALESCE(UNIX_TIMESTAMP(m.edited_at), 0) > :s1
                 OR COALESCE(UNIX_TIMESTAMP(m.deleted_at), 0) > :s2)
             ORDER BY m.id ASC',
            ['max' => $maxId, 'me' => $userId, 'other' => $otherId, 'other2' => $otherId, 'me2' => $userId, 's1' => $sinceTs, 's2' => $sinceTs]
        ));
    }

    /**
     * Attach media[] and reply preview to message rows.
     */
    private function hydrate(array $messages): array
    {
        if ($messages === []) {
            return [];
        }
        $ids = array_map(static fn ($m) => (int) $m['id'], $messages);
        $media = (new MessageMedia())->forMessages($ids);

        // Reply previews.
        $replyIds = array_values(array_filter(array_map(
            static fn ($m) => $m['reply_to_id'] !== null ? (int) $m['reply_to_id'] : null,
            $messages
        )));
        $replies = $this->replyPreviews($replyIds);

        foreach ($messages as &$m) {
            $id = (int) $m['id'];
            $m['deleted'] = $m['deleted_at'] !== null;
            $m['edited'] = $m['edited_at'] !== null;
            $m['media'] = $m['deleted'] ? [] : ($media[$id] ?? []);
            $m['reply'] = ($m['reply_to_id'] !== null && isset($replies[(int) $m['reply_to_id']]))
                ? $replies[(int) $m['reply_to_id']]
                : null;
        }
        unset($m);
        return $messages;
    }

    /** @param array<int,int> $ids @return array<int, array{id:int, username:string, snippet:string}> */
    private function replyPreviews(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_map('intval', array_unique($ids)));
        $rows = $this->fetchAll(
            "SELECT m.id, m.kind, m.body, m.deleted_at, su.username,
                    (SELECT COUNT(*) FROM message_media mm WHERE mm.message_id = m.id) AS media_count
             FROM messages m JOIN users su ON su.id = m.sender_id
             WHERE m.id IN ({$in})"
        );
        $out = [];
        foreach ($rows as $r) {
            $snippet = $r['deleted_at'] !== null
                ? 'Nachricht gelöscht'
                : ($r['body'] ?? $this->preview($r));
            $out[(int) $r['id']] = [
                'id'       => (int) $r['id'],
                'username' => $r['username'],
                'snippet'  => mb_substr((string) $snippet, 0, 120),
            ];
        }
        return $out;
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
            'SELECT COUNT(*) AS c FROM messages WHERE recipient_id = :me AND is_read = 0 AND deleted_at IS NULL',
            ['me' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }

    public function setTyping(int $userId, int $peerId): void
    {
        $this->run(
            'INSERT INTO dm_typing (user_id, peer_id, updated_at) VALUES (:u, :p, NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()',
            ['u' => $userId, 'p' => $peerId]
        );
    }

    public function peerTyping(int $userId, int $otherId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM dm_typing
             WHERE user_id = :other AND peer_id = :me
               AND updated_at > (NOW() - INTERVAL ' . self::SECONDS_TYPING . ' SECOND)',
            ['other' => $otherId, 'me' => $userId]
        );
    }
}
