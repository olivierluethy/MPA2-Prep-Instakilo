<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\NotificationType;

/**
 * Notifications: a generic, type-driven activity feed (see the `notifications`
 * table and {@see NotificationType}).
 *
 * Retrieval collapses repeat events about the same subject into one line for
 * "grouping" types (like/comment → "A, B and N others …") while leaving others
 * (follow) one-per-event. All reads are scoped to the recipient, and the whole
 * listing is built from a small, fixed number of bounded queries (no N+1).
 */
final class Notification extends Model
{
    public const PER_PAGE = 20;

    /**
     * Record an event. No-ops for self-actions and unknown types. The UNIQUE
     * key dedupes repeats; a repeat bumps the row back to the top and unread.
     */
    public function create(int $recipientId, int $actorId, string $type, int $referenceId, ?int $secondaryId = null): void
    {
        if ($recipientId === $actorId || !NotificationType::isKnown($type)) {
            return;
        }
        $this->run(
            'INSERT INTO notifications (recipient_user_id, actor_user_id, type, reference_id, secondary_id)
             VALUES (:r, :a, :t, :ref, :sec)
             ON DUPLICATE KEY UPDATE created_at = NOW(), is_read = 0, secondary_id = VALUES(secondary_id)',
            ['r' => $recipientId, 'a' => $actorId, 't' => $type, 'ref' => $referenceId, 'sec' => $secondaryId]
        );
    }

    /**
     * Unread count as the user perceives it: one per unread *item*, so a grouped
     * like/comment line counts once no matter how many actors are behind it
     * (keeps the nav badge consistent with the rendered list).
     */
    public function unreadCount(int $userId): int
    {
        $grouping = NotificationType::groupingTypes();
        if ($grouping === []) {
            $row = $this->fetchOne(
                'SELECT COUNT(*) AS c FROM notifications WHERE recipient_user_id = :me AND is_read = 0',
                ['me' => $userId]
            );
            return (int) ($row['c'] ?? 0);
        }
        $in = $this->quotedList($grouping);
        $row = $this->fetchOne(
            "SELECT
                (SELECT COUNT(*) FROM (
                    SELECT 1 FROM notifications
                    WHERE recipient_user_id = :me_g AND is_read = 0 AND type IN ({$in})
                    GROUP BY type, reference_id
                ) g)
              + (SELECT COUNT(*) FROM notifications
                    WHERE recipient_user_id = :me_s AND is_read = 0 AND type NOT IN ({$in})) AS c",
            ['me_g' => $userId, 'me_s' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Cheap change-detector for polling: a string that differs whenever the
     * feed's content or read-state changes (new event, re-bumped event, read).
     */
    public function signature(int $userId): string
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(is_read = 0), 0) AS unread,
                    COALESCE(MAX(id), 0) AS latest,
                    COALESCE(MAX(UNIX_TIMESTAMP(created_at)), 0) AS ts
             FROM notifications WHERE recipient_user_id = :me',
            ['me' => $userId]
        ) ?? [];
        return ($row['total'] ?? 0) . ':' . ($row['unread'] ?? 0) . ':' . ($row['latest'] ?? 0) . ':' . ($row['ts'] ?? 0);
    }

    /**
     * One page of the feed as normalized, view-ready items (newest first).
     *
     * @return array<int, array{
     *     id:int, type:string, group:bool, key:int, last_at:string, unread:bool,
     *     total:int, actors:array<int, array{id:int, username:string}>,
     *     actor_id:int, secondary:?int, i_follow:?bool
     * }>
     */
    public function forUser(int $userId, int $limit = self::PER_PAGE, int $offset = 0): array
    {
        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $grouping = NotificationType::groupingTypes();
        $rows = $this->pageRows($userId, $grouping, $limit, $offset);
        if ($rows === []) {
            return [];
        }

        // --- gather what the page needs, then hydrate in bounded batches ---
        $groupRefs = [];      // reference_ids of grouping rows (for actor + target lookups)
        $postRefs  = [];      // like/comment references (must point to a live post)
        $actorIds  = [];      // single actors of non-grouping rows (follow)
        foreach ($rows as $r) {
            if (NotificationType::isGrouping($r['n_type'])) {
                $groupRefs[(int) $r['n_key']] = true;
                if ($r['n_type'] === 'like' || $r['n_type'] === 'comment') {
                    $postRefs[(int) $r['n_key']] = true;
                }
            } else {
                $actorIds[(int) $r['actor_id']] = true;
            }
        }

        $groupActors = $this->groupActors($userId, $grouping, array_keys($groupRefs));
        $livePosts   = $this->existingPostIds(array_keys($postRefs));
        [$actorNames, $iFollow] = $this->actorMeta($userId, array_keys($actorIds));

        // --- assemble in page order ---
        $items = [];
        foreach ($rows as $r) {
            $type  = (string) $r['n_type'];
            $key   = (int) $r['n_key'];
            $group = NotificationType::isGrouping($type);

            if ($group) {
                // Drop groups whose post target no longer exists (clicking would 404).
                if (($type === 'like' || $type === 'comment') && !isset($livePosts[$key])) {
                    continue;
                }
                $info   = $groupActors[$type . ':' . $key] ?? ['actors' => [], 'secondary' => null];
                $actors = $info['actors'];
                if ($actors === []) {
                    continue; // group emptied out underneath us
                }
                $items[] = [
                    'id'        => (int) $r['rep_id'],
                    'type'      => $type,
                    'group'     => true,
                    'key'       => $key,
                    'last_at'   => (string) $r['last_at'],
                    'unread'    => (bool) $r['has_unread'],
                    'total'     => (int) $r['total'],
                    'actors'    => $actors,
                    'actor_id'  => (int) $actors[0]['id'],
                    'secondary' => $info['secondary'] !== null ? (int) $info['secondary'] : null,
                    'i_follow'  => null,
                ];
            } else {
                $aid = (int) $r['actor_id'];
                if (!isset($actorNames[$aid])) {
                    continue;
                }
                $items[] = [
                    'id'        => (int) $r['rep_id'],
                    'type'      => $type,
                    'group'     => false,
                    'key'       => $key,
                    'last_at'   => (string) $r['last_at'],
                    'unread'    => (bool) $r['has_unread'],
                    'total'     => 1,
                    'actors'    => [['id' => $aid, 'username' => $actorNames[$aid]]],
                    'actor_id'  => $aid,
                    'secondary' => $r['secondary'] !== null ? (int) $r['secondary'] : null,
                    'i_follow'  => NotificationType::action($type) === 'follow' ? isset($iFollow[$aid]) : null,
                ];
            }
        }
        return $items;
    }

    /** Mark read: the whole group containing $id (grouping type) or just $id. */
    public function markRead(int $userId, int $id): int
    {
        $row = $this->fetchOne(
            'SELECT type, reference_id FROM notifications WHERE id = :id AND recipient_user_id = :me',
            ['id' => $id, 'me' => $userId]
        );
        if ($row !== null && NotificationType::isGrouping((string) $row['type'])) {
            $this->run(
                'UPDATE notifications SET is_read = 1
                 WHERE recipient_user_id = :me AND type = :t AND reference_id = :ref AND is_read = 0',
                ['me' => $userId, 't' => $row['type'], 'ref' => $row['reference_id']]
            );
        } elseif ($row !== null) {
            $this->run(
                'UPDATE notifications SET is_read = 1 WHERE id = :id AND recipient_user_id = :me',
                ['id' => $id, 'me' => $userId]
            );
        }
        return $this->unreadCount($userId);
    }

    public function markAllRead(int $userId): void
    {
        $this->run(
            'UPDATE notifications SET is_read = 1 WHERE recipient_user_id = :me AND is_read = 0',
            ['me' => $userId]
        );
    }

    /* ---------- internal helpers ---------- */

    /**
     * The page of grouped/single rows. Grouping types collapse by reference_id;
     * the rest are one row each. Registry-driven, so new types slot in by their
     * `group` flag without touching this SQL.
     */
    private function pageRows(int $userId, array $grouping, int $limit, int $offset): array
    {
        $branches = [];
        $params = [];

        if ($grouping !== []) {
            $in = $this->quotedList($grouping);
            $branches[] =
                "SELECT type AS n_type, reference_id AS n_key, MAX(created_at) AS last_at,
                        COUNT(*) AS total, MAX(is_read = 0) AS has_unread,
                        MAX(id) AS rep_id, NULL AS actor_id, NULL AS secondary
                 FROM notifications
                 WHERE recipient_user_id = :me_g AND type IN ({$in})
                 GROUP BY type, reference_id";
            $params['me_g'] = $userId;

            $branches[] =
                "SELECT type AS n_type, id AS n_key, created_at AS last_at,
                        1 AS total, (is_read = 0) AS has_unread,
                        id AS rep_id, actor_user_id AS actor_id, secondary_id AS secondary
                 FROM notifications
                 WHERE recipient_user_id = :me_s AND type NOT IN ({$in})";
            $params['me_s'] = $userId;
        } else {
            $branches[] =
                "SELECT type AS n_type, id AS n_key, created_at AS last_at,
                        1 AS total, (is_read = 0) AS has_unread,
                        id AS rep_id, actor_user_id AS actor_id, secondary_id AS secondary
                 FROM notifications WHERE recipient_user_id = :me_s";
            $params['me_s'] = $userId;
        }

        $sql = '(' . implode(') UNION ALL (', $branches) . ')'
             . " ORDER BY last_at DESC, rep_id DESC LIMIT {$limit} OFFSET {$offset}";

        return $this->fetchAll($sql, $params);
    }

    /**
     * Up to 3 most-recent distinct actors per grouping (type, reference_id),
     * plus the latest secondary_id (e.g. comment id) for deep-linking.
     *
     * @return array<string, array{actors:array<int, array{id:int, username:string}>, secondary:?int}>
     */
    private function groupActors(int $userId, array $grouping, array $refs): array
    {
        if ($grouping === [] || $refs === []) {
            return [];
        }
        $in     = $this->quotedList($grouping);
        $refList = implode(',', array_map('intval', $refs));

        $rows = $this->fetchAll(
            "SELECT t.type, t.reference_id, t.actor_user_id, t.secondary_id, u.username, t.rn
             FROM (
                SELECT type, reference_id, actor_user_id, secondary_id,
                       ROW_NUMBER() OVER (PARTITION BY type, reference_id
                                          ORDER BY created_at DESC, id DESC) AS rn
                FROM notifications
                WHERE recipient_user_id = :me AND type IN ({$in}) AND reference_id IN ({$refList})
             ) t
             JOIN users u ON u.id = t.actor_user_id
             WHERE t.rn <= 3
             ORDER BY t.type, t.reference_id, t.rn",
            ['me' => $userId]
        );

        $out = [];
        foreach ($rows as $r) {
            $k = $r['type'] . ':' . (int) $r['reference_id'];
            if (!isset($out[$k])) {
                $out[$k] = ['actors' => [], 'secondary' => null];
            }
            $out[$k]['actors'][] = ['id' => (int) $r['actor_user_id'], 'username' => (string) $r['username']];
            if ((int) $r['rn'] === 1) {
                $out[$k]['secondary'] = $r['secondary_id'] !== null ? (int) $r['secondary_id'] : null;
            }
        }
        return $out;
    }

    /** @return array<int, true> ids of posts that still exist */
    private function existingPostIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $in = implode(',', array_map('intval', $ids));
        $rows = $this->fetchAll("SELECT id FROM posts WHERE id IN ({$in})");
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['id']] = true;
        }
        return $out;
    }

    /**
     * Usernames for a set of actors + which of them the viewer already follows.
     *
     * @return array{0: array<int, string>, 1: array<int, true>}
     */
    private function actorMeta(int $userId, array $actorIds): array
    {
        if ($actorIds === []) {
            return [[], []];
        }
        $in = implode(',', array_map('intval', $actorIds));

        $names = [];
        foreach ($this->fetchAll("SELECT id, username FROM users WHERE id IN ({$in})") as $r) {
            $names[(int) $r['id']] = (string) $r['username'];
        }

        $follow = [];
        $rows = $this->fetchAll(
            "SELECT user_id FROM followers WHERE follower_id = :me AND user_id IN ({$in})",
            ['me' => $userId]
        );
        foreach ($rows as $r) {
            $follow[(int) $r['user_id']] = true;
        }
        return [$names, $follow];
    }

    /** Quote a list of internal type strings for safe inlining in an IN(). */
    private function quotedList(array $types): string
    {
        return implode(',', array_map(fn (string $t): string => $this->db->quote($t), $types));
    }
}
