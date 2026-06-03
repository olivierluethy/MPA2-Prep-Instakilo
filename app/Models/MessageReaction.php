<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Emoji reactions on messages. UNIQUE(message_id, user_id, emoji) — a user can
 * add several distinct emojis but each only once; toggling removes it.
 */
final class MessageReaction extends Model
{
    /** Add or remove the reaction; returns true if it is now active. */
    public function toggle(int $messageId, int $userId, string $emoji): bool
    {
        $exists = $this->fetchOne(
            'SELECT id FROM message_reactions WHERE message_id = :m AND user_id = :u AND emoji = :e',
            ['m' => $messageId, 'u' => $userId, 'e' => $emoji]
        );
        if ($exists) {
            $this->run('DELETE FROM message_reactions WHERE id = :id', ['id' => $exists['id']]);
            return false;
        }
        $this->run(
            'INSERT INTO message_reactions (message_id, user_id, emoji) VALUES (:m, :u, :e)',
            ['m' => $messageId, 'u' => $userId, 'e' => $emoji]
        );
        return true;
    }

    /**
     * Aggregated reactions for every message in a conversation.
     *
     * @return array<int, array<int, array{emoji:string, count:int, mine:bool}>>
     *         keyed by message_id
     */
    public function forConversation(int $userId, int $otherId): array
    {
        $rows = $this->fetchAll(
            'SELECT r.message_id, r.emoji, COUNT(*) AS cnt,
                    MAX(CASE WHEN r.user_id = :me THEN 1 ELSE 0 END) AS mine
             FROM message_reactions r
             JOIN messages m ON m.id = r.message_id
             WHERE (m.sender_id = :a AND m.recipient_id = :b)
                OR (m.sender_id = :b2 AND m.recipient_id = :a2)
             GROUP BY r.message_id, r.emoji
             ORDER BY cnt DESC, r.emoji',
            ['me' => $userId, 'a' => $userId, 'b' => $otherId, 'a2' => $userId, 'b2' => $otherId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['message_id']][] = [
                'emoji' => $r['emoji'],
                'count' => (int) $r['cnt'],
                'mine'  => (bool) $r['mine'],
            ];
        }
        return $out;
    }
}
