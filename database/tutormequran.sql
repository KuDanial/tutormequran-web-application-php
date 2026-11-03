-- TutorMeQuran Phase 3A: approved 23-table foundation only.
-- Target: XAMPP MariaDB 10.4.32. Fresh installation; do NOT use --force.
-- CREATE DATABASE intentionally fails if tutormequran already exists.
-- Do not run subsequent statements after an error. No DROP/TRUNCATE or fake data.
-- All timestamps use UTC; application input/display uses Asia/Kuala_Lumpur.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET SESSION time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
CREATE DATABASE `tutormequran` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tutormequran`;

CREATE TABLE `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `username` VARCHAR(80) NOT NULL,
  `email` VARCHAR(254) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `ic_number` VARCHAR(20) NULL DEFAULT NULL,
  `phone` VARCHAR(32) NULL DEFAULT NULL,
  `gender` VARCHAR(24) COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `date_of_birth` DATE NULL DEFAULT NULL,
  `personal_state` VARCHAR(40) NULL DEFAULT NULL,
  `address` TEXT NULL DEFAULT NULL,
  `profile_picture_path` VARCHAR(255) NULL DEFAULT NULL,
  `account_status` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL DEFAULT 'active',
  `eligibility_confirmed_at` DATETIME NULL DEFAULT NULL,
  `password_changed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_users_1` (`username`),
  UNIQUE KEY `uq_users_2` (`email`),
  KEY `ix_users_1` (`role`, `account_status`),
  KEY `ix_users_2` (`created_at`),
  CONSTRAINT `ck_users_role` CHECK (`role` IN ('student', 'tutor', 'admin')),
  CONSTRAINT `ck_users_account_status` CHECK (`account_status` IN ('active', 'inactive')),
  CONSTRAINT `ck_users_gender` CHECK (`gender` IN ('male', 'female', 'prefer_not_to_say'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `students` (
  `student_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `uq_students_1` (`user_id`),
  CONSTRAINT `fk_students_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `tutors` (
  `tutor_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `application_submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `display_name` VARCHAR(150) NULL DEFAULT NULL,
  `displayed_certificate_id` INT UNSIGNED NULL DEFAULT NULL,
  `teaching_state` VARCHAR(40) NULL DEFAULT NULL,
  `teaching_area` VARCHAR(255) NULL DEFAULT NULL,
  `teaching_mode` VARCHAR(16) COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `hourly_rate` DECIMAL(10,2) NULL DEFAULT NULL,
  `bio` TEXT NULL DEFAULT NULL,
  `profile_visibility` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL DEFAULT 'draft',
  `facebook_url` VARCHAR(2048) NULL DEFAULT NULL,
  `instagram_url` VARCHAR(2048) NULL DEFAULT NULL,
  `linkedin_url` VARCHAR(2048) NULL DEFAULT NULL,
  `twitter_url` VARCHAR(2048) NULL DEFAULT NULL,
  `tiktok_url` VARCHAR(2048) NULL DEFAULT NULL,
  `duitnow_qr_path` VARCHAR(255) NULL DEFAULT NULL,
  `duitnow_qr_updated_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tutor_id`),
  UNIQUE KEY `uq_tutors_1` (`user_id`),
  KEY `ix_tutors_1` (`application_submitted_at`),
  KEY `ix_tutors_2` (`profile_visibility`, `teaching_state`, `hourly_rate`),
  KEY `ix_tutors_3` (`teaching_state`),
  KEY `ix_tutors_4` (`hourly_rate`),
  CONSTRAINT `fk_tutors_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_tutors_fk2` (`displayed_certificate_id`, `tutor_id`),
  CONSTRAINT `ck_tutors_teaching_mode` CHECK (`teaching_mode` IN ('online', 'physical', 'both')),
  CONSTRAINT `ck_tutors_profile_visibility` CHECK (`profile_visibility` IN ('draft', 'private', 'public')),
  CONSTRAINT `ck_tutors_rate` CHECK (`hourly_rate` IS NULL OR `hourly_rate` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `admins` (
  `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uq_admins_1` (`user_id`),
  CONSTRAINT `fk_admins_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `certificates` (
  `certificate_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tutor_id` INT UNSIGNED NOT NULL,
  `replaces_certificate_id` INT UNSIGNED NULL DEFAULT NULL,
  `submission_purpose` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL,
  `certificate_name` VARCHAR(255) NOT NULL,
  `certificate_type` VARCHAR(150) NULL DEFAULT NULL,
  `qualification_level` VARCHAR(32) COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `certificate_year` SMALLINT UNSIGNED NOT NULL,
  `origin` VARCHAR(80) NULL DEFAULT NULL,
  `issuing_authority` VARCHAR(150) NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size_bytes` INT UNSIGNED NOT NULL,
  `status` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `admin_remark` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`certificate_id`),
  UNIQUE KEY `uq_certificates_1` (`certificate_id`, `tutor_id`),
  UNIQUE KEY `uq_certificates_2` (`replaces_certificate_id`),
  KEY `ix_certificates_1` (`status`, `submitted_at`),
  KEY `ix_certificates_2` (`tutor_id`, `submitted_at`),
  CONSTRAINT `fk_certificates_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_certificates_2` FOREIGN KEY (`replaces_certificate_id`) REFERENCES `certificates` (`certificate_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_certificates_fk3` (`reviewed_by_admin_id`),
  CONSTRAINT `fk_certificates_3` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_certificates_status` CHECK (`status` IN ('pending', 'approved', 'rejected', 'superseded')),
  CONSTRAINT `ck_certificates_submission_purpose` CHECK (`submission_purpose` IN ('initial', 'application_correction', 'higher_qualification')),
  CONSTRAINT `ck_certificates_qualification_level` CHECK (`qualification_level` IN ('ijazah', 'advanced_ijazah', 'diploma', 'bachelor', 'master', 'doctorate')),
  CONSTRAINT `ck_certificates_file_size` CHECK (`file_size_bytes` BETWEEN 1 AND 5242880),
  CONSTRAINT `ck_certificates_mime` CHECK (`mime_type` IN ('application/pdf', 'image/jpeg', 'image/png')),
  CONSTRAINT `ck_certificates_rejection_reason` CHECK (`status` <> 'rejected' OR (`admin_remark` IS NOT NULL AND CHAR_LENGTH(TRIM(`admin_remark`)) > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Resolve tutor/certificate cycle after both tables exist.
ALTER TABLE `tutors` ADD CONSTRAINT `fk_tutors_2` FOREIGN KEY (`displayed_certificate_id`, `tutor_id`) REFERENCES `certificates` (`certificate_id`, `tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

CREATE TABLE `languages` (
  `language_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  PRIMARY KEY (`language_id`),
  UNIQUE KEY `uq_languages_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `tutor_languages` (
  `tutor_id` INT UNSIGNED NOT NULL,
  `language_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`tutor_id`, `language_id`),
  KEY `ix_tutor_languages_1` (`language_id`, `tutor_id`),
  CONSTRAINT `fk_tutor_languages_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tutor_languages_2` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `lesson_focuses` (
  `focus_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`focus_id`),
  UNIQUE KEY `uq_lesson_focuses_1` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `tutor_lesson_focuses` (
  `tutor_id` INT UNSIGNED NOT NULL,
  `focus_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`tutor_id`, `focus_id`),
  KEY `ix_tutor_lesson_focuses_1` (`focus_id`, `tutor_id`),
  CONSTRAINT `fk_tutor_lesson_focuses_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tutor_lesson_focuses_2` FOREIGN KEY (`focus_id`) REFERENCES `lesson_focuses` (`focus_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `availability_slots` (
  `slot_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tutor_id` INT UNSIGNED NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `mode` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `availability_status` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL DEFAULT 'available',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`slot_id`),
  UNIQUE KEY `uq_availability_slots_1` (`tutor_id`, `starts_at`),
  UNIQUE KEY `uq_availability_slots_2` (`slot_id`, `tutor_id`),
  KEY `ix_availability_slots_1` (`tutor_id`, `availability_status`, `starts_at`),
  CONSTRAINT `fk_availability_slots_1` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_availability_slots_mode` CHECK (`mode` IN ('online', 'physical', 'both')),
  CONSTRAINT `ck_availability_slots_availability_status` CHECK (`availability_status` IN ('available', 'unavailable')),
  CONSTRAINT `ck_availability_slots_interval` CHECK (`ends_at` > `starts_at` AND TIMESTAMPDIFF(SECOND, `starts_at`, `ends_at`) BETWEEN 3600 AND 86400 AND MOD(TIMESTAMPDIFF(SECOND, `starts_at`, `ends_at`), 3600) = 0),
  CONSTRAINT `ck_availability_slots_hour_boundary` CHECK (MINUTE(`starts_at`) = 0 AND SECOND(`starts_at`) = 0 AND MINUTE(`ends_at`) = 0 AND SECOND(`ends_at`) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `bookings` (
  `booking_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `tutor_id` INT UNSIGNED NOT NULL,
  `slot_id` INT UNSIGNED NOT NULL,
  `starts_at` DATETIME NOT NULL,
  `ends_at` DATETIME NOT NULL,
  `class_mode` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `lesson_focus_summary` VARCHAR(255) NULL DEFAULT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `hourly_rate_snapshot` DECIMAL(10,2) NOT NULL,
  `expected_amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'requested',
  `occupied_slot_id` INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `status` IN ('requested', 'accepted', 'completed', 'closed') THEN `slot_id` ELSE NULL END) PERSISTENT,
  `decision_at` DATETIME NULL DEFAULT NULL,
  `rejection_reason` TEXT NULL DEFAULT NULL,
  `venue_address` VARCHAR(500) NULL DEFAULT NULL,
  `meeting_platform` VARCHAR(80) NULL DEFAULT NULL,
  `meeting_url` VARCHAR(2048) NULL DEFAULT NULL,
  `student_arranged_at` DATETIME NULL DEFAULT NULL,
  `tutor_arranged_at` DATETIME NULL DEFAULT NULL,
  `student_completed_at` DATETIME NULL DEFAULT NULL,
  `tutor_completed_at` DATETIME NULL DEFAULT NULL,
  `cancelled_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `cancelled_at` DATETIME NULL DEFAULT NULL,
  `cancellation_reason` TEXT NULL DEFAULT NULL,
  `cancellation_timing` VARCHAR(24) COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `closed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `agreement_version` INT UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  UNIQUE KEY `uq_bookings_1` (`occupied_slot_id`),
  KEY `ix_bookings_1` (`student_id`, `status`, `starts_at`),
  KEY `ix_bookings_2` (`tutor_id`, `status`, `starts_at`),
  KEY `ix_bookings_3` (`created_at`),
  CONSTRAINT `fk_bookings_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_bookings_2` FOREIGN KEY (`tutor_id`) REFERENCES `tutors` (`tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_bookings_fk3` (`slot_id`, `tutor_id`),
  CONSTRAINT `fk_bookings_3` FOREIGN KEY (`slot_id`, `tutor_id`) REFERENCES `availability_slots` (`slot_id`, `tutor_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_bookings_fk4` (`cancelled_by_user_id`),
  CONSTRAINT `fk_bookings_4` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_bookings_class_mode` CHECK (`class_mode` IN ('online', 'physical')),
  CONSTRAINT `ck_bookings_status` CHECK (`status` IN ('requested', 'accepted', 'rejected', 'completed', 'cancelled', 'closed')),
  CONSTRAINT `ck_bookings_cancellation_timing` CHECK (`cancellation_timing` IN ('early', 'same_day', 'after_lesson_date')),
  CONSTRAINT `ck_bookings_rate` CHECK (`hourly_rate_snapshot` > 0),
  CONSTRAINT `ck_bookings_amount` CHECK (`expected_amount` > 0),
  CONSTRAINT `ck_bookings_agreed_price` CHECK (`expected_amount` = `hourly_rate_snapshot` * TIMESTAMPDIFF(HOUR, `starts_at`, `ends_at`)),
  CONSTRAINT `ck_bookings_version` CHECK (`agreement_version` >= 1),
  CONSTRAINT `ck_bookings_completion` CHECK (`status` NOT IN ('completed', 'closed') OR (`student_completed_at` IS NOT NULL AND `tutor_completed_at` IS NOT NULL)),
  CONSTRAINT `ck_bookings_rejection_reason` CHECK (`status` <> 'rejected' OR (`rejection_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`rejection_reason`)) > 0)),
  CONSTRAINT `ck_bookings_interval` CHECK (`ends_at` > `starts_at` AND TIMESTAMPDIFF(SECOND, `starts_at`, `ends_at`) BETWEEN 3600 AND 86400 AND MOD(TIMESTAMPDIFF(SECOND, `starts_at`, `ends_at`), 3600) = 0),
  CONSTRAINT `ck_bookings_hour_boundary` CHECK (MINUTE(`starts_at`) = 0 AND SECOND(`starts_at`) = 0 AND MINUTE(`ends_at`) = 0 AND SECOND(`ends_at`) = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `booking_change_requests` (
  `change_request_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `requested_by_user_id` INT UNSIGNED NOT NULL,
  `is_admin_correction` TINYINT(1) NOT NULL DEFAULT 0,
  `base_agreement_version` INT UNSIGNED NOT NULL,
  `original_slot_id` INT UNSIGNED NOT NULL,
  `requested_slot_id` INT UNSIGNED NOT NULL,
  `original_mode` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `requested_mode` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `original_venue_address` VARCHAR(500) NULL DEFAULT NULL,
  `requested_venue_address` VARCHAR(500) NULL DEFAULT NULL,
  `original_meeting_platform` VARCHAR(80) NULL DEFAULT NULL,
  `requested_meeting_platform` VARCHAR(80) NULL DEFAULT NULL,
  `original_meeting_url` VARCHAR(2048) NULL DEFAULT NULL,
  `requested_meeting_url` VARCHAR(2048) NULL DEFAULT NULL,
  `original_expected_amount` DECIMAL(10,2) NOT NULL,
  `proposed_expected_amount` DECIMAL(10,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `pending_booking_id` INT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `status` = 'pending' THEN `booking_id` ELSE NULL END) PERSISTENT,
  `responded_by_user_id` INT UNSIGNED NULL DEFAULT NULL,
  `response_note` TEXT NULL DEFAULT NULL,
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`change_request_id`),
  UNIQUE KEY `uq_booking_change_requests_1` (`pending_booking_id`),
  KEY `ix_booking_change_requests_1` (`booking_id`, `requested_at`),
  CONSTRAINT `fk_booking_change_requests_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_booking_change_requests_fk2` (`requested_by_user_id`),
  CONSTRAINT `fk_booking_change_requests_2` FOREIGN KEY (`requested_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_booking_change_requests_fk3` (`responded_by_user_id`),
  CONSTRAINT `fk_booking_change_requests_3` FOREIGN KEY (`responded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_booking_change_requests_fk4` (`original_slot_id`),
  CONSTRAINT `fk_booking_change_requests_4` FOREIGN KEY (`original_slot_id`) REFERENCES `availability_slots` (`slot_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_booking_change_requests_fk5` (`requested_slot_id`),
  CONSTRAINT `fk_booking_change_requests_5` FOREIGN KEY (`requested_slot_id`) REFERENCES `availability_slots` (`slot_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_booking_change_requests_original_mode` CHECK (`original_mode` IN ('online', 'physical')),
  CONSTRAINT `ck_booking_change_requests_requested_mode` CHECK (`requested_mode` IN ('online', 'physical')),
  CONSTRAINT `ck_booking_change_requests_status` CHECK (`status` IN ('pending', 'accepted', 'rejected', 'withdrawn')),
  CONSTRAINT `ck_booking_change_requests_admin_flag` CHECK (`is_admin_correction` IN (0, 1)),
  CONSTRAINT `ck_booking_change_requests_version` CHECK (`base_agreement_version` >= 1),
  CONSTRAINT `ck_booking_change_requests_amounts` CHECK (`original_expected_amount` > 0 AND `proposed_expected_amount` > 0),
  CONSTRAINT `ck_booking_change_requests_reason` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `payments` (
  `payment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(32) COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `evidence_revision` INT UNSIGNED NOT NULL DEFAULT 1,
  `recipient_name_snapshot` VARCHAR(200) NOT NULL,
  `qr_path_snapshot` VARCHAR(255) NULL DEFAULT NULL,
  `admin_remark` TEXT NULL DEFAULT NULL,
  `reviewed_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `reviewed_at` DATETIME NULL DEFAULT NULL,
  `verified_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `verified_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  UNIQUE KEY `uq_payments_1` (`booking_id`),
  KEY `ix_payments_1` (`status`, `created_at`),
  KEY `ix_payments_2` (`verified_at`),
  CONSTRAINT `fk_payments_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_payments_fk2` (`verified_by_admin_id`),
  CONSTRAINT `fk_payments_2` FOREIGN KEY (`verified_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_payments_fk3` (`reviewed_by_admin_id`),
  CONSTRAINT `fk_payments_3` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_payments_status` CHECK (`status` IN ('pending', 'underpaid', 'rejected', 'reupload_required', 'verified')),
  CONSTRAINT `ck_payments_revision` CHECK (`evidence_revision` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `payment_transfers` (
  `transfer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT UNSIGNED NOT NULL,
  `bank_reference` VARCHAR(150) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transfer_id`),
  KEY `ix_payment_transfers_1` (`payment_id`, `created_at`),
  KEY `ix_payment_transfers_2` (`bank_reference`),
  CONSTRAINT `fk_payment_transfers_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `payment_submissions` (
  `submission_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id` INT UNSIGNED NOT NULL,
  `attempt_number` SMALLINT UNSIGNED NOT NULL,
  `uploaded_by_user_id` INT UNSIGNED NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `transfer_date` DATE NULL DEFAULT NULL,
  `payment_note` TEXT NULL DEFAULT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size_bytes` INT UNSIGNED NOT NULL,
  `file_sha256` CHAR(64) NOT NULL,
  `tutor_status` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL DEFAULT 'awaiting',
  `tutor_issue_type` VARCHAR(40) COLLATE utf8mb4_bin NULL DEFAULT NULL,
  `tutor_remark` TEXT NULL DEFAULT NULL,
  `tutor_reviewed_at` DATETIME NULL DEFAULT NULL,
  `admin_status` VARCHAR(24) COLLATE utf8mb4_bin NOT NULL DEFAULT 'pending',
  `reviewed_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `admin_reviewed_at` DATETIME NULL DEFAULT NULL,
  `admin_remark` TEXT NULL DEFAULT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`submission_id`),
  UNIQUE KEY `uq_payment_submissions_1` (`transfer_id`, `attempt_number`),
  KEY `ix_payment_submissions_1` (`uploaded_at`),
  KEY `ix_payment_submissions_2` (`file_sha256`),
  CONSTRAINT `fk_payment_submissions_1` FOREIGN KEY (`transfer_id`) REFERENCES `payment_transfers` (`transfer_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_payment_submissions_fk2` (`uploaded_by_user_id`),
  CONSTRAINT `fk_payment_submissions_2` FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_payment_submissions_fk3` (`reviewed_by_admin_id`),
  CONSTRAINT `fk_payment_submissions_3` FOREIGN KEY (`reviewed_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_payment_submissions_tutor_status` CHECK (`tutor_status` IN ('awaiting', 'received', 'issue')),
  CONSTRAINT `ck_payment_submissions_tutor_issue_type` CHECK (`tutor_issue_type` IN ('not_received', 'amount_mismatch', 'reference_mismatch')),
  CONSTRAINT `ck_payment_submissions_admin_status` CHECK (`admin_status` IN ('pending', 'valid', 'rejected', 'reupload_required')),
  CONSTRAINT `ck_payment_submissions_amount` CHECK (`amount_paid` >= 0),
  CONSTRAINT `ck_payment_submissions_attempt` CHECK (`attempt_number` >= 1),
  CONSTRAINT `ck_payment_submissions_file_size` CHECK (`file_size_bytes` BETWEEN 1 AND 5242880),
  CONSTRAINT `ck_payment_submissions_mime` CHECK (`mime_type` IN ('application/pdf', 'image/jpeg', 'image/png'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `messages` (
  `message_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `sender_user_id` INT UNSIGNED NOT NULL,
  `message_type` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `body` TEXT NULL DEFAULT NULL,
  `location_label` VARCHAR(500) NULL DEFAULT NULL,
  `latitude` DECIMAL(9,6) NULL DEFAULT NULL,
  `longitude` DECIMAL(10,6) NULL DEFAULT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `ix_messages_1` (`booking_id`, `message_id`),
  CONSTRAINT `fk_messages_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_messages_fk2` (`sender_user_id`),
  CONSTRAINT `fk_messages_2` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_messages_message_type` CHECK (`message_type` IN ('text', 'attachment', 'location')),
  CONSTRAINT `ck_messages_body_length` CHECK (`body` IS NULL OR CHAR_LENGTH(`body`) <= 2000),
  CONSTRAINT `ck_messages_coordinates` CHECK ((`latitude` IS NULL AND `longitude` IS NULL) OR (`latitude` IS NOT NULL AND `longitude` IS NOT NULL AND `latitude` BETWEEN -90 AND 90 AND `longitude` BETWEEN -180 AND 180))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `message_attachments` (
  `attachment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `file_size_bytes` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `ix_message_attachments_fk1` (`message_id`),
  CONSTRAINT `fk_message_attachments_1` FOREIGN KEY (`message_id`) REFERENCES `messages` (`message_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_message_attachments_file_size` CHECK (`file_size_bytes` BETWEEN 1 AND 10485760)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `feedback` (
  `feedback_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`feedback_id`),
  UNIQUE KEY `uq_feedback_1` (`booking_id`),
  KEY `ix_feedback_1` (`created_at`),
  CONSTRAINT `fk_feedback_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_feedback_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `notifications` (
  `notification_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `booking_id` INT UNSIGNED NULL DEFAULT NULL,
  `certificate_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`notification_id`),
  KEY `ix_notifications_1` (`recipient_user_id`, `read_at`, `created_at`),
  CONSTRAINT `fk_notifications_1` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_notifications_fk2` (`booking_id`),
  CONSTRAINT `fk_notifications_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_notifications_fk3` (`certificate_id`),
  CONSTRAINT `fk_notifications_3` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`certificate_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_notifications_one_related_record` CHECK (`booking_id` IS NULL OR `certificate_id` IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `faqs` (
  `faq_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(500) NOT NULL,
  `answer` TEXT NOT NULL,
  `status` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL DEFAULT 'active',
  `created_by_admin_id` INT UNSIGNED NOT NULL,
  `updated_by_admin_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`faq_id`),
  KEY `ix_faqs_1` (`status`),
  KEY `ix_faqs_fk1` (`created_by_admin_id`),
  CONSTRAINT `fk_faqs_1` FOREIGN KEY (`created_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_faqs_fk2` (`updated_by_admin_id`),
  CONSTRAINT `fk_faqs_2` FOREIGN KEY (`updated_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_faqs_status` CHECK (`status` IN ('active', 'inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `faq_keywords` (
  `faq_id` INT UNSIGNED NOT NULL,
  `keyword` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`faq_id`, `keyword`),
  KEY `ix_faq_keywords_1` (`keyword`, `faq_id`),
  CONSTRAINT `fk_faq_keywords_1` FOREIGN KEY (`faq_id`) REFERENCES `faqs` (`faq_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `profile_field_settings` (
  `role` VARCHAR(16) COLLATE utf8mb4_bin NOT NULL,
  `field_key` VARCHAR(40) COLLATE utf8mb4_bin NOT NULL,
  `is_editable` TINYINT(1) NOT NULL DEFAULT 1,
  `updated_by_admin_id` INT UNSIGNED NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role`, `field_key`),
  KEY `ix_profile_field_settings_fk1` (`updated_by_admin_id`),
  CONSTRAINT `fk_profile_field_settings_1` FOREIGN KEY (`updated_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_profile_field_settings_role` CHECK (`role` IN ('student', 'tutor')),
  CONSTRAINT `ck_profile_field_settings_editable` CHECK (`is_editable` IN (0, 1)),
  CONSTRAINT `ck_profile_field_settings_field_allowlist` CHECK ((`role` = 'student' AND `field_key` IN ('profile_picture', 'full_name', 'phone', 'address', 'gender', 'date_of_birth', 'password')) OR (`role` = 'tutor' AND `field_key` IN ('profile_picture', 'full_name', 'phone', 'gender', 'date_of_birth', 'address', 'teaching_mode', 'teaching_state', 'teaching_location', 'languages', 'lesson_focus', 'hourly_rate', 'bio', 'social_networks', 'password')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `support_requests` (
  `request_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requester_user_id` INT UNSIGNED NOT NULL,
  `category` VARCHAR(24) COLLATE utf8mb4_bin NOT NULL,
  `booking_id` INT UNSIGNED NULL DEFAULT NULL,
  `description` TEXT NOT NULL,
  `photo_path` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(24) COLLATE utf8mb4_bin NOT NULL DEFAULT 'open',
  `handled_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
  `resolution_note` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `ix_support_requests_1` (`status`, `created_at`),
  KEY `ix_support_requests_2` (`requester_user_id`, `created_at`),
  CONSTRAINT `fk_support_requests_1` FOREIGN KEY (`requester_user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_support_requests_fk2` (`booking_id`),
  CONSTRAINT `fk_support_requests_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  KEY `ix_support_requests_fk3` (`handled_by_admin_id`),
  CONSTRAINT `fk_support_requests_3` FOREIGN KEY (`handled_by_admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `ck_support_requests_category` CHECK (`category` IN ('billing', 'attendance', 'other')),
  CONSTRAINT `ck_support_requests_status` CHECK (`status` IN ('open', 'resolved'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Canonical reference data only; no user, booking or transaction fixtures.
INSERT INTO `languages` (`name`) VALUES ('Bahasa Melayu'), ('English'), ('Arabic');
INSERT INTO `lesson_focuses` (`name`) VALUES ('Tajweed'), ('Quran Reading'), ('Iqra'), ('Hafazan'), ('Talaqqi');

-- profile_field_settings stays empty until a real admin is manually provisioned.
-- Then run seed-profile-field-settings.sql with @profile_settings_admin_id set.
-- Role/ownership, overlap, history and state transitions require PHP transactions.
-- See docs/database-implementation-notes.md; this schema does not implement them.
