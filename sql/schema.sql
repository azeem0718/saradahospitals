-- Sarada Nursing Home — database schema
-- MySQL 5.7+ / MariaDB 10.3+
-- Import via hPanel > Databases > phpMyAdmin > Import

SET NAMES utf8mb4;
SET time_zone = '+05:30';

-- ---------------------------------------------------------------
-- Staff accounts for the reception / admin panel
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`       VARCHAR(50)  NOT NULL,
  `password_hash`  VARCHAR(255) NOT NULL,
  `full_name`      VARCHAR(100) NOT NULL,
  `role`           ENUM('admin','reception') NOT NULL DEFAULT 'reception',
  `must_change_pw` TINYINT(1)   NOT NULL DEFAULT 1,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login_at`  DATETIME     DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Doctors
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctors` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`          VARCHAR(60)  NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  `qualifications` VARCHAR(200) NOT NULL,
  `speciality`    VARCHAR(120) NOT NULL,
  `bio`           TEXT,
  `photo`         VARCHAR(160) DEFAULT NULL,
  `sort_order`    SMALLINT     NOT NULL DEFAULT 0,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctors_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Per-doctor session schedule.
-- weekday: 0 = Sunday ... 6 = Saturday  (matches PHP date('w'))
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `doctor_sessions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id`  INT UNSIGNED NOT NULL,
  `weekday`    TINYINT UNSIGNED NOT NULL,
  `session`    ENUM('morning','evening') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time`   TIME NOT NULL,
  `token_cap`  SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doctor_weekday_session` (`doctor_id`,`weekday`,`session`),
  CONSTRAINT `fk_ds_doctor` FOREIGN KEY (`doctor_id`)
    REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Leave / blocked days. doctor_id NULL = whole hospital closed.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blocked_days` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doctor_id`  INT UNSIGNED DEFAULT NULL,
  `block_date` DATE NOT NULL,
  `session`    ENUM('morning','evening','both') NOT NULL DEFAULT 'both',
  `reason`     VARCHAR(160) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blocked_date` (`block_date`),
  CONSTRAINT `fk_bd_doctor` FOREIGN KEY (`doctor_id`)
    REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Bookings. One token number per (doctor, date, session).
-- The UNIQUE key is what makes concurrent booking safe.
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference`      CHAR(10)     NOT NULL,
  `doctor_id`      INT UNSIGNED NOT NULL,
  `booking_date`   DATE         NOT NULL,
  `session`        ENUM('morning','evening') NOT NULL,
  `token_no`       SMALLINT UNSIGNED NOT NULL,
  `patient_name`   VARCHAR(120) NOT NULL,
  `patient_age`    TINYINT UNSIGNED NOT NULL,
  `patient_sex`    ENUM('male','female','other') NOT NULL,
  `phone`          VARCHAR(15)  NOT NULL,
  `town`           VARCHAR(100) DEFAULT NULL,
  `reason`         VARCHAR(500) DEFAULT NULL,
  `status`         ENUM('booked','arrived','completed','no_show','cancelled')
                   NOT NULL DEFAULT 'booked',
  `booked_via`     ENUM('online','reception') NOT NULL DEFAULT 'online',
  `notes`          VARCHAR(500) DEFAULT NULL,
  `ip_address`     VARBINARY(16) DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bookings_reference` (`reference`),
  UNIQUE KEY `uq_token_per_session` (`doctor_id`,`booking_date`,`session`,`token_no`),
  KEY `idx_date_session` (`booking_date`,`session`),
  KEY `idx_phone` (`phone`),
  CONSTRAINT `fk_bk_doctor` FOREIGN KEY (`doctor_id`)
    REFERENCES `doctors`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Rate limiting for the public booking form
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `booking_attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip_address` VARBINARY(16) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_time` (`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------
-- Editable site settings
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key`   VARCHAR(60)  NOT NULL,
  `setting_value` TEXT         NOT NULL,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
