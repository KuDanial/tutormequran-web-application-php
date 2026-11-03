-- Deferred canonical defaults: run ONCE after manually provisioning a REAL admin.
-- In the SAME database session: select tutormequran, then set
-- @profile_settings_admin_id to that existing admins.admin_id before this file.
-- No admin/account is created here. Missing/invalid ID fails NOT NULL/FK checks.
-- One atomic INSERT: duplicate keys fail rather than overwriting saved settings.
SET SESSION time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
INSERT INTO `profile_field_settings` (`role`, `field_key`, `is_editable`, `updated_by_admin_id`, `updated_at`) VALUES
('student', 'profile_picture', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'full_name', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'phone', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'address', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'gender', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'date_of_birth', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('student', 'password', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'profile_picture', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'full_name', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'phone', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'gender', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'date_of_birth', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'address', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'teaching_mode', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'teaching_state', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'teaching_location', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'languages', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'lesson_focus', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'hourly_rate', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'bio', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'social_networks', 1, @profile_settings_admin_id, UTC_TIMESTAMP()),
('tutor', 'password', 1, @profile_settings_admin_id, UTC_TIMESTAMP());
