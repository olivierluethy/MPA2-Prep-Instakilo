-- Instakilo schema.
--
-- Auto-loaded by the MySQL container on first start (the database itself is
-- created by the container from MYSQL_DATABASE, so this file only defines
-- tables + seed data). Columns use clean English snake_case names.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ---------------------------------------------------------------------------
-- users
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(255) NOT NULL UNIQUE,
    email       VARCHAR(255) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- password_hash() output
    description VARCHAR(500) DEFAULT NULL,
    avatar_type VARCHAR(100) DEFAULT NULL,
    avatar_data LONGBLOB     DEFAULT NULL,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- posts  (a post owns one or more images)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS posts (
    id          INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         DEFAULT NULL,        -- sanitized rich-text HTML
    location    VARCHAR(255) DEFAULT NULL,
    taken_on    DATE         DEFAULT NULL,
    is_public   TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_posts_user (user_id),
    INDEX idx_posts_public (is_public)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- post_images  (image blobs, ordered within a post)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS post_images (
    id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id    INT          NOT NULL,
    image_type VARCHAR(100) NOT NULL,
    image_data LONGBLOB     NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    CONSTRAINT fk_post_images_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    INDEX idx_post_images_post (post_id, sort_order)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- followers  (user_id is followed BY follower_id)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS followers (
    id          INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     INT      NOT NULL,
    follower_id INT      NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_follow (user_id, follower_id),
    CONSTRAINT fk_follow_user     FOREIGN KEY (user_id)     REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_follow_follower FOREIGN KEY (follower_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- likes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS likes (
    id         INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id    INT      NOT NULL,
    user_id    INT      NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_like (post_id, user_id),
    CONSTRAINT fk_like_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT fk_like_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- Seed users (demo). Password for both accounts: "kauz.git"
-- ---------------------------------------------------------------------------
INSERT INTO users (username, email, password, description) VALUES
    ('LE FOU',     'olivier@kauz.ch', '$2y$10$LbYMnWuawyliVSj64qarwudXRWDLy1HvjN4udgbHBszHZXcmetU5m', 'Demo-Konto'),
    ('TestFaktor', 'test@test.ch',    '$2y$10$LbYMnWuawyliVSj64qarwudXRWDLy1HvjN4udgbHBszHZXcmetU5m', NULL)
ON DUPLICATE KEY UPDATE username = username;
