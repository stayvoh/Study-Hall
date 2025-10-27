-- =====================================================
-- DATABASE INIT
-- =====================================================
CREATE DATABASE IF NOT EXISTS studyhall
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE studyhall;

-- =====================================================
-- USERS & PROFILES
-- =====================================================
CREATE TABLE IF NOT EXISTS user_account (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email          VARCHAR(255) NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_email (email)
) ENGINE=InnoDB;

ALTER TABLE user_account
  ADD remember_token  VARCHAR(64) NULL,
  ADD remember_expiry DATETIME NULL,
  ADD INDEX idx_user_email (email);

CREATE TABLE IF NOT EXISTS user_profile (
  user_id   INT UNSIGNED NOT NULL PRIMARY KEY,
  profile_picture LONGBLOB NULL,
  mime_type VARCHAR(100) NULL,
  username  VARCHAR(50) NOT NULL,
  bio       VARCHAR(200) NULL,
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES user_account(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_profile_username (username)
) ENGINE=InnoDB;

-- =====================================================
-- BOARDS & POSTS
-- =====================================================
CREATE TABLE IF NOT EXISTS board (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id   INT UNSIGNED NULL,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_board_course_name (course_id, name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS post (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_id    INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(120) NOT NULL,
  body        TEXT NOT NULL,
  is_question TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX ix_board_created (board_id, created_at),
  FULLTEXT KEY ft_post_title_body (title, body),
  CONSTRAINT fk_post_board FOREIGN KEY (board_id) REFERENCES board(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_post_user  FOREIGN KEY (user_id)  REFERENCES user_account(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS comment (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  post_id     INT UNSIGNED NOT NULL,
  user_id     INT UNSIGNED NOT NULL,
  body        VARCHAR(2000) NOT NULL,
  is_answer   TINYINT(1) NOT NULL DEFAULT 0,
  is_accepted TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_post_created (post_id, created_at),
  CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES post(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES user_account(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- TAGS
-- =====================================================
CREATE TABLE IF NOT EXISTS tag (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80) NOT NULL,
  slug        VARCHAR(100) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tag_name (name),
  UNIQUE KEY uq_tag_slug (slug),
  FULLTEXT KEY ft_tag_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS post_tag (
  post_id INT UNSIGNED NOT NULL,
  tag_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  CONSTRAINT fk_pt_post FOREIGN KEY (post_id) REFERENCES post(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_pt_tag  FOREIGN KEY (tag_id)  REFERENCES tag(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- USER ↔ BOARD RELATIONSHIPS
-- =====================================================
-- Tracks which boards a user follows
CREATE TABLE IF NOT EXISTS user_follow_board (
  user_id  INT UNSIGNED NOT NULL,
  board_id INT UNSIGNED NOT NULL,
  followed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, board_id),
  CONSTRAINT fk_ufb_user  FOREIGN KEY (user_id)  REFERENCES user_account(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ufb_board FOREIGN KEY (board_id) REFERENCES board(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- PASSWORD RESET TOKENS
-- =====================================================
CREATE TABLE IF NOT EXISTS password_reset (
  user_id    INT UNSIGNED NOT NULL PRIMARY KEY,
  token      VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES user_account(id)
    ON DELETE CASCADE
);
