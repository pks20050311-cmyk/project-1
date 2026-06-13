-- ============================================================
--  Tasko — Full SQL Dump
--  Import this file in InfinityFree phpMyAdmin
--
--  IMPORTANT: Before importing, make sure you have already
--  created a database in the InfinityFree Control Panel and
--  that you are INSIDE that database in phpMyAdmin
--  (click the database name in the left sidebar first).
--
--  This script does NOT create the database itself because
--  InfinityFree does not allow CREATE DATABASE from phpMyAdmin.
--  You must create it through the Control Panel.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ------------------------------------------------------------
--  TABLE: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)   NOT NULL,
    `email`      VARCHAR(150)  NOT NULL,
    `password`   VARCHAR(255)  NOT NULL,           -- bcrypt hash, never plaintext
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_username` (`username`),
    UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  TABLE: tasks
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `tasks`;

CREATE TABLE `tasks` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED  NOT NULL,
    `title`       VARCHAR(150)  NOT NULL,
    `description` TEXT,
    `due_date`    DATE,
    `status`      ENUM('In Progress','Done','Cancelled')
                  NOT NULL DEFAULT 'In Progress',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_tasks_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    INDEX `idx_user_due`  (`user_id`, `due_date`),
    INDEX `idx_status`    (`status`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  End of dump — import complete.
-- ============================================================
