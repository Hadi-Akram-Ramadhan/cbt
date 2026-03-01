-- ============================================
-- CBT (Computer Based Test) Database Schema
-- ============================================
-- Created: 2026-03-01

CREATE DATABASE IF NOT EXISTS `cbt_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cbt_db`;

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(150) NOT NULL,
    `username`   VARCHAR(100) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
    `nis`        VARCHAR(30) NULL COMMENT 'Nomor Induk Siswa',
    `kelas`      VARCHAR(50) NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: exams
-- ============================================
CREATE TABLE `exams` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `title`            VARCHAR(200) NOT NULL,
    `description`      TEXT NULL,
    `duration_minutes` INT NOT NULL DEFAULT 60,
    `start_time`       DATETIME NULL,
    `end_time`         DATETIME NULL,
    `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`       INT NOT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: exam_participants
-- Assigns which students can take which exams
-- ============================================
CREATE TABLE `exam_participants` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id`    INT NOT NULL,
    `user_id`    INT NOT NULL,
    `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_exam_user` (`exam_id`, `user_id`),
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: questions
-- ============================================
CREATE TABLE `questions` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id`       INT NOT NULL,
    `question_text` TEXT NOT NULL,
    `question_type` ENUM('pg','multiple_choice','essay') NOT NULL DEFAULT 'pg',
    `order_num`     INT NOT NULL DEFAULT 1,
    `points`        DECIMAL(8,2) NOT NULL DEFAULT 1.00,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: options (answer choices for pg & multiple_choice)
-- ============================================
CREATE TABLE `options` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `question_id` INT NOT NULL,
    `option_label` CHAR(1) NOT NULL COMMENT 'A, B, C, D, E',
    `option_text`  TEXT NOT NULL,
    `is_correct`   TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: exam_sessions
-- One row per student per attempt
-- ============================================
CREATE TABLE `exam_sessions` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id`          INT NOT NULL,
    `user_id`          INT NOT NULL,
    `started_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `submitted_at`     DATETIME NULL,
    `tab_switch_count` INT NOT NULL DEFAULT 0,
    `status`           ENUM('ongoing','submitted','timeout') NOT NULL DEFAULT 'ongoing',
    UNIQUE KEY `uq_session` (`exam_id`, `user_id`),
    FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: answers
-- Student answers per question per session
-- ============================================
CREATE TABLE `answers` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `session_id`       INT NOT NULL,
    `question_id`      INT NOT NULL,
    `answer_text`      TEXT NULL COMMENT 'For essay answers',
    `selected_options` VARCHAR(50) NULL COMMENT 'Comma-separated option labels e.g. A or A,C',
    `points_earned`    DECIMAL(8,2) NULL DEFAULT 0.00,
    `is_graded`        TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (`session_id`)  REFERENCES `exam_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Table: results
-- Final score summary per session
-- ============================================
CREATE TABLE `results` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `session_id`   INT NOT NULL UNIQUE,
    `exam_id`      INT NOT NULL,
    `user_id`      INT NOT NULL,
    `total_score`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `max_score`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `percentage`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `graded_at`    DATETIME NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_id`) REFERENCES `exam_sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exam_id`)    REFERENCES `exams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SEED DATA
-- ============================================

-- Default admin (password: admin123)
INSERT INTO `users` (`name`, `username`, `password`, `role`) VALUES
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Default students (password: siswa123)
INSERT INTO `users` (`name`, `username`, `password`, `role`, `nis`, `kelas`) VALUES
('Budi Santoso',  'budi',  '$2y$10$eImiTXuWVxfM37uY4JANjQ==',  'siswa', '2024001', 'XII-IPA-1'),
('Siti Rahayu',   'siti',  '$2y$10$eImiTXuWVxfM37uY4JANjQ==',  'siswa', '2024002', 'XII-IPA-1');

-- Note: The above student passwords use a placeholder hash. Run the seeder PHP script
-- (database/seed_users.php) to generate proper bcrypt hashes for 'siswa123'.
