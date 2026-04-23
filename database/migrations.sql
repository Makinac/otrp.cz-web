-- Old Times RP — Database Migrations
-- MySQL 8+
-- Run once to initialize the schema.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `discord_id`       VARCHAR(30)     NOT NULL,
    `username`         VARCHAR(100)    NOT NULL,
    `avatar`           VARCHAR(100)        NULL DEFAULT NULL,
    `roles_json`       JSON                NULL DEFAULT NULL,
    `role_ids_json`    JSON                NULL DEFAULT NULL,
    `roles_cached_at`  DATETIME            NULL DEFAULT NULL,
    `access_dev`       TINYINT(1)      NOT NULL DEFAULT 0,
    `access_maps`      TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_discord_id` (`discord_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: allowlist_applications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `allowlist_applications` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          BIGINT UNSIGNED NOT NULL,
    `form_data_json`   JSON                NULL DEFAULT NULL,
    `status`           ENUM('pending','approved','rejected','blocked') NOT NULL DEFAULT 'pending',
    `attempt_number`   TINYINT UNSIGNED    NOT NULL DEFAULT 1,
    `submitted_at`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`      DATETIME            NULL DEFAULT NULL,
    `interview_reviewed_at` DATETIME       NULL DEFAULT NULL,
    `reviewer_id`      BIGINT UNSIGNED     NULL DEFAULT NULL,
    `interview_reviewer_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `error_count`      INT UNSIGNED        NULL DEFAULT NULL,
    `interview_error_count` INT UNSIGNED   NULL DEFAULT NULL,
    `interview_status` ENUM('pending','passed','failed') NULL DEFAULT NULL,
    `interview_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`  (`user_id`),
    KEY `idx_status`   (`status`),
    CONSTRAINT `fk_app_user`     FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_app_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: interview_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `interview_history` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `application_id` BIGINT UNSIGNED NOT NULL,
    `reviewer_id`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `result`         ENUM('passed','failed') NOT NULL,
    `error_count`    INT UNSIGNED    NULL DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_app_id`  (`application_id`),
    CONSTRAINT `fk_ih_app`      FOREIGN KEY (`application_id`) REFERENCES `allowlist_applications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ih_reviewer` FOREIGN KEY (`reviewer_id`)    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: blacklist
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blacklist` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `discord_id` VARCHAR(20)     NOT NULL,
    `name`       VARCHAR(100)    NULL DEFAULT NULL,
    `reason`     VARCHAR(500)    NULL DEFAULT NULL,
    `added_by`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `added_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_discord_id` (`discord_id`),
    KEY `idx_added_by` (`added_by`),
    CONSTRAINT `fk_bl_added_by` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: appeals
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `appeals` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `reason`      TEXT            NOT NULL,
    `staff_present` TEXT          NULL DEFAULT NULL,
    `status`      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `type`        ENUM('ban','warn','blacklist','allowlist') NOT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`    (`user_id`),
    KEY `idx_status`     (`status`),
    CONSTRAINT `fk_appeal_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_appeal_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: form_schema
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_schema` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100)    NOT NULL,
    `fields_json` JSON           NOT NULL,
    `active`     TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: content_pages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `content_pages` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(50)     NOT NULL,
    `title`      VARCHAR(255)    NOT NULL,
    `body_html`  LONGTEXT        NOT NULL,
    `updated_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`),
    CONSTRAINT `fk_cp_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default rules page
INSERT IGNORE INTO `content_pages` (`slug`, `title`, `body_html`) VALUES
('rules', 'Pravidla serveru', '<p>Pravidla budou brzy doplněna.</p>');

-- --------------------------------------------------------
-- Table: news
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`          VARCHAR(255)    NOT NULL,
    `slug`           VARCHAR(255)    NOT NULL,
    `category`       VARCHAR(50)     NOT NULL DEFAULT 'Novinka',
    `category_color` VARCHAR(7)      NOT NULL DEFAULT '#cc0000',
    `body_html`      LONGTEXT        NOT NULL,
    `author_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `published_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`),
    KEY `idx_category`    (`category`),
    KEY `idx_published_at`(`published_at`),
    CONSTRAINT `fk_news_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: team_cache
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_cache` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED        NULL DEFAULT NULL,
    `discord_id`  VARCHAR(30)     NOT NULL,
    `username`    VARCHAR(100)    NOT NULL,
    `avatar_url`  VARCHAR(255)    NULL DEFAULT NULL,
    `roles_json`  JSON            NOT NULL,
    `cached_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: rules_sections
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rules_sections` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(255)  NOT NULL,
    `body_html`   MEDIUMTEXT    NOT NULL DEFAULT '',
    `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
    `updated_by`  INT UNSIGNED  DEFAULT NULL,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: player_bans
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `player_bans` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `reason`         TEXT            NOT NULL,
    `expires_at`     DATETIME        NULL DEFAULT NULL,
    `witnesses_json` JSON            NULL DEFAULT NULL,
    `issued_by`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `issued_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked`        TINYINT(1)      NOT NULL DEFAULT 0,
    `revoked_reason` TEXT            NULL DEFAULT NULL,
    `revoked_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `revoked_at`     DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ban_user` (`user_id`),
    CONSTRAINT `fk_ban_user`    FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ban_issuer`  FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ban_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: player_warns
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `player_warns` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `reason`         TEXT            NOT NULL,
    `expires_at`     DATETIME        NULL DEFAULT NULL,
    `witnesses_json` JSON            NULL DEFAULT NULL,
    `issued_by`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `issued_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked`        TINYINT(1)      NOT NULL DEFAULT 0,
    `revoked_reason` TEXT            NULL DEFAULT NULL,
    `revoked_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `revoked_at`     DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_warn_user` (`user_id`),
    CONSTRAINT `fk_warn_user`    FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_warn_issuer`  FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_warn_revoker` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: management_permissions
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `management_permissions` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_key` VARCHAR(100)    NOT NULL,
    `subject_type`   ENUM('role','user') NOT NULL,
    `subject_value`  VARCHAR(255)    NOT NULL,
    `created_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ux_perm_subject` (`permission_key`, `subject_type`, `subject_value`),
    KEY `idx_perm_key` (`permission_key`),
    CONSTRAINT `fk_management_perm_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: team_categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_categories` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)    NOT NULL,
    `color`         VARCHAR(7)      NULL DEFAULT NULL,
    `role_ids_json` JSON            NOT NULL DEFAULT '[]',
    `sort_order`    SMALLINT        NOT NULL DEFAULT 0,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill migration for existing installations.
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `role_ids_json` JSON NULL DEFAULT NULL AFTER `roles_json`;

-- Add category_id to team_cache
ALTER TABLE `team_cache`
    ADD COLUMN IF NOT EXISTS `category_id` INT UNSIGNED NULL DEFAULT NULL AFTER `id`;

-- Add color to team_categories
ALTER TABLE `team_categories`
    ADD COLUMN IF NOT EXISTS `color` VARCHAR(7) NULL DEFAULT NULL AFTER `name`;

-- --------------------------------------------------------
-- Table: cheatsheet_sections
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `cheatsheet_sections` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`        VARCHAR(255) NOT NULL,
    `body_html`    LONGTEXT NOT NULL DEFAULT '',
    `sort_order`   INT UNSIGNED NOT NULL DEFAULT 0,
    `updated_by`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sort` (`sort_order`),
    CONSTRAINT `fk_cs_updater` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: site_settings
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL DEFAULT NULL,
    `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ck_votes
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ck_votes` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `applicant`    VARCHAR(255) NOT NULL COMMENT 'Žadatel (jméno postavy)',
    `victim`       VARCHAR(255) NOT NULL COMMENT 'Oběť (jméno postavy)',
    `description`  TEXT NOT NULL,
    `context_urls` TEXT NULL DEFAULT NULL COMMENT 'JSON array of Discord room URLs',
    `status`       ENUM('open','closed') NOT NULL DEFAULT 'open',
    `result`       ENUM('approved','rejected','tie') NULL DEFAULT NULL,
    `created_by`   BIGINT UNSIGNED NOT NULL,
    `closed_by`    BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `closed_at`    DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_ck_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ck_closer`  FOREIGN KEY (`closed_by`)  REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ck_vote_entries
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ck_vote_entries` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `vote_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `decision`   ENUM('approve','reject','abstain') NOT NULL,
    `reason`     TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vote_user` (`vote_id`, `user_id`),
    CONSTRAINT `fk_cke_vote` FOREIGN KEY (`vote_id`) REFERENCES `ck_votes`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cke_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admin_vacations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_vacations` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `date_from`   DATE            NOT NULL,
    `date_to`     DATE            NOT NULL,
    `note`        VARCHAR(255)    NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_dates` (`user_id`, `date_from`, `date_to`),
    CONSTRAINT `fk_vacation_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: player_identifiers
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `player_identifiers` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `identifier_type` VARCHAR(20)     NOT NULL COMMENT 'license, steam, ip, xbl, live, fivem, discord',
    `identifier_value` VARCHAR(255)   NOT NULL,
    `first_seen_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_type_value` (`user_id`, `identifier_type`, `identifier_value`),
    KEY `idx_type_value` (`identifier_type`, `identifier_value`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_pi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: security_logs
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `security_logs` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `event_type`      VARCHAR(50)     NOT NULL COMMENT 'new_identifier, identifier_conflict, multi_account',
    `severity`        ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    `description`     TEXT            NOT NULL,
    `details_json`    JSON            NULL DEFAULT NULL,
    `resolved`        TINYINT(1)      NOT NULL DEFAULT 0,
    `resolved_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `resolved_at`     DATETIME        NULL DEFAULT NULL,
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id`    (`user_id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_severity`   (`severity`),
    KEY `idx_resolved`   (`resolved`),
    CONSTRAINT `fk_sl_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_sl_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: partners
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partners` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)    NOT NULL,
    `logo_url`    VARCHAR(500)        NULL DEFAULT NULL,
    `description` TEXT                NULL DEFAULT NULL,
    `url`         VARCHAR(500)        NULL DEFAULT NULL,
    `sort_order`  SMALLINT        NOT NULL DEFAULT 0,
    `active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: player_notes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `player_notes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL COMMENT 'Hráč ke kterému patří poznámka',
    `author_id`  BIGINT UNSIGNED NULL DEFAULT NULL COMMENT 'Admin který napsal poznámku',
    `note`       TEXT            NOT NULL,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pn_user`   (`user_id`),
    KEY `idx_pn_author` (`author_id`),
    CONSTRAINT `fk_pn_user`   FOREIGN KEY (`user_id`)   REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pn_author` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- --------------------------------------------------------
-- Table: qp_role_config
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qp_role_config` (
    `role_id`   VARCHAR(30)  NOT NULL,
    `qp_value`  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: qp_bonuses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `qp_bonuses` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `amount`      INT             NOT NULL,
    `reason`      VARCHAR(255)    NOT NULL DEFAULT '',
    `expires_at`  DATETIME        NULL DEFAULT NULL,
    `created_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_qpb_user` (`user_id`),
    CONSTRAINT `fk_qpb_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_qpb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: char_role_config
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `char_role_config` (
    `role_id`    VARCHAR(30) NOT NULL,
    `char_value` INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: char_bonuses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `char_bonuses` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `amount`      INT             NOT NULL,
    `reason`      VARCHAR(255)    NOT NULL DEFAULT '',
    `expires_at`  DATETIME        NULL DEFAULT NULL,
    `created_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_chb_user` (`user_id`),
    CONSTRAINT `fk_chb_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_chb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: ped_bonuses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ped_bonuses` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `reason`      VARCHAR(255)    NOT NULL DEFAULT '',
    `expires_at`  DATETIME        NULL DEFAULT NULL,
    `created_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pedb_user` (`user_id`),
    CONSTRAINT `fk_pedb_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pedb_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: redeem_codes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `redeem_codes` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`        VARCHAR(64)     NOT NULL,
    `type`        ENUM('qp','chars','ped') NOT NULL,
    `amount`      INT             NOT NULL,
    `max_uses`    INT             NOT NULL DEFAULT 1,
    `used_count`  INT             NOT NULL DEFAULT 0,
    `expires_at`  DATETIME        NULL DEFAULT NULL,
    `note`        VARCHAR(255)    NOT NULL DEFAULT '',
    `created_by`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_code` (`code`),
    CONSTRAINT `fk_rc_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: redeem_log
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `redeem_log` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code_id`     BIGINT UNSIGNED NOT NULL,
    `user_id`     BIGINT UNSIGNED NOT NULL,
    `redeemed_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_code` (`code_id`, `user_id`),
    CONSTRAINT `fk_rl_code` FOREIGN KEY (`code_id`) REFERENCES `redeem_codes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: api_keys
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label`          VARCHAR(100)    NOT NULL,
    `api_key`        VARCHAR(64)     NOT NULL COMMENT 'SHA-256 hash of the actual key',
    `api_key_prefix` VARCHAR(8)          NULL DEFAULT NULL COMMENT 'First 8 chars of the key for identification',
    `allowed_ips`    TEXT                NULL DEFAULT NULL COMMENT 'Comma-separated IPs, NULL = any',
    `is_active`      TINYINT(1)      NOT NULL DEFAULT 1,
    `last_used_at`   DATETIME            NULL DEFAULT NULL,
    `created_by`     BIGINT UNSIGNED     NULL DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_api_key` (`api_key`),
    CONSTRAINT `fk_ak_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admin_settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_settings` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`               BIGINT UNSIGNED NOT NULL,
    `admin_prefix_chat`     TINYINT(1)      NOT NULL DEFAULT 1,
    `report_notifications`  TINYINT(1)      NOT NULL DEFAULT 1,
    `updated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_id` (`user_id`),
    CONSTRAINT `fk_as_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: player_mutes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `player_mutes` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        BIGINT UNSIGNED NOT NULL,
    `reason`         TEXT            NOT NULL,
    `expires_at`     DATETIME        NULL DEFAULT NULL,
    `issued_by`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `issued_via`     VARCHAR(20)     NOT NULL DEFAULT 'web' COMMENT 'web or discord',
    `issued_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked`        TINYINT(1)      NOT NULL DEFAULT 0,
    `revoked_reason` TEXT            NULL DEFAULT NULL,
    `revoked_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `revoked_at`     DATETIME        NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_revoked` (`revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
