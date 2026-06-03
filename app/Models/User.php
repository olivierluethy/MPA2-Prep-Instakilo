<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Users: registration, lookup, profile updates and avatar storage.
 */
final class User extends Model
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT id, username, email, description, created_at,
                    (avatar_data IS NOT NULL) AS has_avatar
             FROM users WHERE id = :id',
            ['id' => $id]
        );
    }

    /**
     * Login lookup — accepts either an email address or a username.
     * Returns the password hash so the controller can verify it.
     */
    public function findByLogin(string $login): ?array
    {
        return $this->fetchOne(
            'SELECT id, username, email, password
             FROM users
             WHERE email = :login OR username = :login2
             LIMIT 1',
            ['login' => $login, 'login2' => $login]
        );
    }

    public function emailExists(string $email): bool
    {
        return (bool) $this->fetchOne('SELECT 1 FROM users WHERE email = :email', ['email' => $email]);
    }

    public function usernameExists(string $username): bool
    {
        return (bool) $this->fetchOne('SELECT 1 FROM users WHERE username = :u', ['u' => $username]);
    }

    /**
     * Create a user with an already-hashed password. Returns the new id.
     */
    public function create(string $username, string $email, string $passwordHash): int
    {
        $this->run(
            'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)',
            ['username' => $username, 'email' => $email, 'password' => $passwordHash]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateDescription(int $id, string $description): void
    {
        $this->run(
            'UPDATE users SET description = :d WHERE id = :id',
            ['d' => $description, 'id' => $id]
        );
    }

    public function updateUsername(int $id, string $username): void
    {
        $this->run(
            'UPDATE users SET username = :u WHERE id = :id',
            ['u' => $username, 'id' => $id]
        );
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->run(
            'UPDATE users SET password = :p WHERE id = :id',
            ['p' => $passwordHash, 'id' => $id]
        );
    }

    public function updateAvatar(int $id, string $mime, string $data): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET avatar_type = :t, avatar_data = :d WHERE id = :id'
        );
        $stmt->bindValue(':t', $mime);
        $stmt->bindValue(':d', $data, \PDO::PARAM_LOB);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Raw avatar bytes for the /avatar endpoint, or null if none set.
     *
     * @return array{avatar_type:string, avatar_data:string}|null
     */
    public function avatar(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT avatar_type, avatar_data FROM users
             WHERE id = :id AND avatar_data IS NOT NULL',
            ['id' => $id]
        );
    }

    /**
     * Search users by username (case-insensitive substring match).
     *
     * The term is bound as a parameter (injection-safe) and LIKE metacharacters
     * (% _ \) are escaped so they are matched literally. The username column is
     * indexed (UNIQUE), so the lookup is efficient.
     *
     * @return array<int, array{id:int, username:string}>
     */
    public function search(string $term, int $limit = 8): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }
        $like = '%' . addcslashes($term, '%_\\') . '%';
        $limit = max(1, min(20, $limit)); // clamp, then inline (LIMIT can't bind)

        return $this->fetchAll(
            "SELECT id, username FROM users
             WHERE username LIKE :q
             ORDER BY username ASC
             LIMIT {$limit}",
            ['q' => $like]
        );
    }
}
