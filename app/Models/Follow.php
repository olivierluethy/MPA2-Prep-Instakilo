<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Follow relationships.
 *   user_id     = the account being followed
 *   follower_id = the account doing the following
 * UNIQUE(user_id, follower_id) keeps follows idempotent.
 */
final class Follow extends Model
{
    public function follow(int $userId, int $followerId): void
    {
        if ($userId === $followerId) {
            return; // can't follow yourself
        }
        $this->run(
            'INSERT IGNORE INTO followers (user_id, follower_id) VALUES (:user, :follower)',
            ['user' => $userId, 'follower' => $followerId]
        );
    }

    public function unfollow(int $userId, int $followerId): void
    {
        $this->run(
            'DELETE FROM followers WHERE user_id = :user AND follower_id = :follower',
            ['user' => $userId, 'follower' => $followerId]
        );
    }

    public function isFollowing(int $userId, int $followerId): bool
    {
        return (bool) $this->fetchOne(
            'SELECT 1 FROM followers WHERE user_id = :user AND follower_id = :follower',
            ['user' => $userId, 'follower' => $followerId]
        );
    }

    /** How many accounts follow $userId. */
    public function followerCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM followers WHERE user_id = :id',
            ['id' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }

    /** How many accounts $userId follows. */
    public function followingCount(int $userId): int
    {
        $row = $this->fetchOne(
            'SELECT COUNT(*) AS c FROM followers WHERE follower_id = :id',
            ['id' => $userId]
        );
        return (int) ($row['c'] ?? 0);
    }
}
