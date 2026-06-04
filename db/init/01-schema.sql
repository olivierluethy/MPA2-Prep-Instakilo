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
-- comments
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id         INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id    INT      NOT NULL,
    user_id    INT      NOT NULL,
    body       VARCHAR(1000) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_comments_post (post_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- saved_posts  (a user's personal bookmark collection)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS saved_posts (
    id         INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    post_id    INT      NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_save (user_id, post_id),
    CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_saved_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    INDEX idx_saved_user (user_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- reposts  (a user re-sharing a public post to their own feed)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reposts (
    id         INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id    INT      NOT NULL,
    post_id    INT      NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_repost (user_id, post_id),
    CONSTRAINT fk_repost_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_repost_post FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
    INDEX idx_repost_user (user_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- messages  (direct messages; may carry a shared post reference)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id             INT      NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sender_id      INT      NOT NULL,
    recipient_id   INT      NOT NULL,
    -- text | post | link | media (media = one or more uploaded attachments)
    -- (image | gif | video are still used for URL-only media in body)
    kind           VARCHAR(20) NOT NULL DEFAULT 'text',
    body           VARCHAR(2000) DEFAULT NULL,
    shared_post_id INT      DEFAULT NULL,
    reply_to_id    INT      DEFAULT NULL,   -- referenced message (reply)
    is_read        TINYINT(1) NOT NULL DEFAULT 0,
    edited_at      DATETIME DEFAULT NULL,   -- set when the body is edited
    deleted_at     DATETIME DEFAULT NULL,   -- soft delete (record kept)
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_sender    FOREIGN KEY (sender_id)      REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_recipient FOREIGN KEY (recipient_id)   REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_post      FOREIGN KEY (shared_post_id) REFERENCES posts (id) ON DELETE SET NULL,
    CONSTRAINT fk_msg_reply     FOREIGN KEY (reply_to_id)    REFERENCES messages (id) ON DELETE SET NULL,
    INDEX idx_msg_pair (sender_id, recipient_id, created_at),
    INDEX idx_msg_inbox (recipient_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- message_media  (uploaded DM attachments: image/gif/video/file blobs)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS message_media (
    id         INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    message_id INT          NOT NULL,
    kind       VARCHAR(20)  NOT NULL,            -- image | gif | video | file
    mime       VARCHAR(150) NOT NULL,
    file_name  VARCHAR(255) NOT NULL,
    file_size  INT          NOT NULL DEFAULT 0,
    sort_order INT          NOT NULL DEFAULT 0,  -- order within the message
    data       LONGBLOB     NOT NULL,
    CONSTRAINT fk_media_message FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE,
    INDEX idx_media_message (message_id, sort_order)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- message_reactions  (emoji reactions; one per user per emoji per message)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS message_reactions (
    id         INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    message_id INT         NOT NULL,
    user_id    INT         NOT NULL,
    emoji      VARCHAR(16) NOT NULL,
    created_at DATETIME    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_reaction (message_id, user_id, emoji),
    CONSTRAINT fk_react_message FOREIGN KEY (message_id) REFERENCES messages (id) ON DELETE CASCADE,
    CONSTRAINT fk_react_user    FOREIGN KEY (user_id)    REFERENCES users (id)    ON DELETE CASCADE,
    INDEX idx_react_message (message_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- dm_typing  (short-lived "user is typing" signal for real-time polling)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS dm_typing (
    user_id    INT      NOT NULL,   -- who is typing
    peer_id    INT      NOT NULL,   -- to whom
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, peer_id),
    CONSTRAINT fk_typing_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_typing_peer FOREIGN KEY (peer_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- notifications  (generic, type-driven activity feed)
--
-- One row per (recipient, actor, type, reference) event. Designed to support
-- future types (repost, save, mention, message, share) WITHOUT schema changes:
--   type         = event kind ('like' | 'comment' | 'follow' | …)
--   reference_id = the subject the event is about (post id for like/comment;
--                  the actor's id for follow). Polymorphic, so no FK on it —
--                  references are validated in PHP at create/render time.
--   secondary_id = optional extra target (e.g. the comment id, for deep-linking)
-- The UNIQUE key dedupes repeat events (re-like, re-comment, re-follow) so a
-- single actor never spams the recipient; ON DUPLICATE bumps it back to unread.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id                INT         NOT NULL AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT         NOT NULL,
    actor_user_id     INT         NOT NULL,
    type              VARCHAR(40) NOT NULL,
    reference_id      INT         DEFAULT NULL,
    secondary_id      INT         DEFAULT NULL,
    is_read           TINYINT(1)  NOT NULL DEFAULT 0,
    created_at        DATETIME    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_event (recipient_user_id, actor_user_id, type, reference_id),
    CONSTRAINT fk_notif_recipient FOREIGN KEY (recipient_user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_actor     FOREIGN KEY (actor_user_id)     REFERENCES users (id) ON DELETE CASCADE,
    -- Inbox listing (newest first) and unread-count lookups are both covered:
    INDEX idx_notif_inbox (recipient_user_id, created_at),
    INDEX idx_notif_unread (recipient_user_id, is_read),
    -- Grouping like/comment events by their subject:
    INDEX idx_notif_group (recipient_user_id, type, reference_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- ---------------------------------------------------------------------------
-- Seed users (demo). Password for both accounts: "kauz.git"
-- ---------------------------------------------------------------------------
INSERT INTO users (username, email, password, description) VALUES
    ('LE FOU',     'olivier@kauz.ch', '$2y$10$LbYMnWuawyliVSj64qarwudXRWDLy1HvjN4udgbHBszHZXcmetU5m', 'Demo-Konto'),
    ('TestFaktor', 'test@test.ch',    '$2y$10$LbYMnWuawyliVSj64qarwudXRWDLy1HvjN4udgbHBszHZXcmetU5m', NULL)
ON DUPLICATE KEY UPDATE username = username;
