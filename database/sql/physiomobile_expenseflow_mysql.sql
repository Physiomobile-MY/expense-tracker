CREATE DATABASE IF NOT EXISTS `physiomobile_expenseflow`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `physiomobile_expenseflow`;

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `expense_notifications`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `expense_comments`;
DROP TABLE IF EXISTS `expense_approvals`;
DROP TABLE IF EXISTS `ai_extraction_logs`;
DROP TABLE IF EXISTS `expense_receipt_items`;
DROP TABLE IF EXISTS `expense_receipts`;
DROP TABLE IF EXISTS `expense_records`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `expense_categories`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `password_reset_tokens`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `departments_code_unique` (`code`),
    KEY `departments_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL,
    `phone` VARCHAR(255) NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `manager_id` BIGINT UNSIGNED NULL,
    `role` VARCHAR(255) NOT NULL DEFAULT 'staff',
    `status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `password` VARCHAR(255) NOT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_department_id_index` (`department_id`),
    KEY `users_manager_id_index` (`manager_id`),
    KEY `users_role_index` (`role`),
    KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
    `id` VARCHAR(255) NOT NULL,
    `user_id` BIGINT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
    `key` VARCHAR(255) NOT NULL,
    `value` MEDIUMTEXT NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` VARCHAR(255) NOT NULL,
    `owner` VARCHAR(255) NOT NULL,
    `expiration` INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue` VARCHAR(255) NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL,
    `reserved_at` INT UNSIGNED NULL,
    `available_at` INT UNSIGNED NOT NULL,
    `created_at` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `total_jobs` INT NOT NULL,
    `pending_jobs` INT NOT NULL,
    `failed_jobs` INT NOT NULL,
    `failed_job_ids` LONGTEXT NOT NULL,
    `options` MEDIUMTEXT NULL,
    `cancelled_at` INT NULL,
    `created_at` INT NOT NULL,
    `finished_at` INT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid` VARCHAR(255) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue` TEXT NOT NULL,
    `payload` LONGTEXT NOT NULL,
    `exception` LONGTEXT NOT NULL,
    `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `guard_name` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
    KEY `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_roles` (
    `role_id` BIGINT UNSIGNED NOT NULL,
    `model_type` VARCHAR(255) NOT NULL,
    `model_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `model_id`, `model_type`),
    KEY `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`permission_id`, `role_id`),
    KEY `role_has_permissions_role_id_foreign` (`role_id`),
    CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_categories` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `code` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `keywords` JSON NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `expense_categories_code_unique` (`code`),
    KEY `expense_categories_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_records` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `department_id` BIGINT UNSIGNED NULL,
    `expense_category_id` BIGINT UNSIGNED NULL,
    `claim_reference_no` VARCHAR(255) NULL,
    `record_type` VARCHAR(255) NULL,
    `claim_expense_type` VARCHAR(255) NULL,
    `merchant_name` VARCHAR(255) NULL,
    `merchant_address` TEXT NULL,
    `receipt_date` DATE NULL,
    `receipt_time` TIME NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'MYR',
    `subtotal` DECIMAL(12,2) NULL,
    `tax_amount` DECIMAL(12,2) NULL,
    `service_charge` DECIMAL(12,2) NULL,
    `discount` DECIMAL(12,2) NULL,
    `total_amount` DECIMAL(12,2) NULL,
    `payment_method` VARCHAR(255) NULL,
    `receipt_number` VARCHAR(255) NULL,
    `project_cost_center` VARCHAR(255) NULL,
    `route_origin` VARCHAR(255) NULL,
    `route_destination` VARCHAR(255) NULL,
    `route_summary` VARCHAR(255) NULL,
    `route_distance_km` DECIMAL(8,2) NULL,
    `route_duration_minutes` INT UNSIGNED NULL,
    `route_arrival_time` VARCHAR(255) NULL,
    `mileage_rate` DECIMAL(8,2) NULL,
    `mileage_amount` DECIMAL(12,2) NULL,
    `toll_amount` DECIMAL(12,2) NULL,
    `toll_entries` JSON NULL,
    `parking_amount` DECIMAL(12,2) NULL,
    `description` TEXT NULL,
    `remarks` TEXT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'draft',
    `duplicate_warning` TINYINT(1) NOT NULL DEFAULT 0,
    `ai_confidence_score` DECIMAL(5,4) NULL,
    `submitted_at` TIMESTAMP NULL,
    `approved_at` TIMESTAMP NULL,
    `rejected_at` TIMESTAMP NULL,
    `paid_at` TIMESTAMP NULL,
    `recorded_at` TIMESTAMP NULL,
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `expense_records_claim_reference_no_unique` (`claim_reference_no`),
    KEY `expense_records_user_id_record_type_receipt_date_index` (`user_id`, `record_type`, `receipt_date`),
    KEY `expense_records_department_id_foreign` (`department_id`),
    KEY `expense_records_expense_category_id_foreign` (`expense_category_id`),
    KEY `expense_records_record_type_status_index` (`record_type`, `status`),
    KEY `expense_records_claim_expense_type_index` (`claim_expense_type`),
    KEY `expense_records_merchant_name_index` (`merchant_name`),
    KEY `expense_records_receipt_date_index` (`receipt_date`),
    KEY `expense_records_total_amount_index` (`total_amount`),
    KEY `expense_records_receipt_number_index` (`receipt_number`),
    KEY `expense_records_status_index` (`status`),
    CONSTRAINT `expense_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expense_records_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `expense_records_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_receipts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_record_id` BIGINT UNSIGNED NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_type` VARCHAR(255) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `uploaded_by` BIGINT UNSIGNED NOT NULL,
    `document_type` VARCHAR(255) NOT NULL DEFAULT 'receipt',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_receipts_expense_record_id_foreign` (`expense_record_id`),
    KEY `expense_receipts_uploaded_by_foreign` (`uploaded_by`),
    CONSTRAINT `expense_receipts_expense_record_id_foreign` FOREIGN KEY (`expense_record_id`) REFERENCES `expense_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expense_receipts_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_receipt_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_record_id` BIGINT UNSIGNED NOT NULL,
    `description` VARCHAR(255) NULL,
    `quantity` DECIMAL(10,2) NULL,
    `unit_price` DECIMAL(12,2) NULL,
    `amount` DECIMAL(12,2) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_receipt_items_expense_record_id_foreign` (`expense_record_id`),
    CONSTRAINT `expense_receipt_items_expense_record_id_foreign` FOREIGN KEY (`expense_record_id`) REFERENCES `expense_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_extraction_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_record_id` BIGINT UNSIGNED NULL,
    `provider` VARCHAR(255) NOT NULL DEFAULT 'openai',
    `model` VARCHAR(255) NULL,
    `prompt` LONGTEXT NULL,
    `raw_response` LONGTEXT NULL,
    `extracted_json` JSON NULL,
    `confidence_score` DECIMAL(5,4) NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `token_usage_input` INT UNSIGNED NULL,
    `token_usage_output` INT UNSIGNED NULL,
    `total_cost_estimate` DECIMAL(12,6) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `ai_extraction_logs_expense_record_id_foreign` (`expense_record_id`),
    KEY `ai_extraction_logs_status_index` (`status`),
    CONSTRAINT `ai_extraction_logs_expense_record_id_foreign` FOREIGN KEY (`expense_record_id`) REFERENCES `expense_records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_record_id` BIGINT UNSIGNED NOT NULL,
    `approver_id` BIGINT UNSIGNED NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `previous_status` VARCHAR(255) NULL,
    `new_status` VARCHAR(255) NULL,
    `remarks` TEXT NULL,
    `acted_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_approvals_expense_record_id_foreign` (`expense_record_id`),
    KEY `expense_approvals_approver_id_foreign` (`approver_id`),
    KEY `expense_approvals_action_index` (`action`),
    CONSTRAINT `expense_approvals_expense_record_id_foreign` FOREIGN KEY (`expense_record_id`) REFERENCES `expense_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expense_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_comments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `expense_record_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_comments_expense_record_id_foreign` (`expense_record_id`),
    KEY `expense_comments_user_id_foreign` (`user_id`),
    CONSTRAINT `expense_comments_expense_record_id_foreign` FOREIGN KEY (`expense_record_id`) REFERENCES `expense_records` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expense_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `action` VARCHAR(255) NOT NULL,
    `module` VARCHAR(255) NOT NULL,
    `record_id` BIGINT UNSIGNED NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `audit_logs_user_id_foreign` (`user_id`),
    KEY `audit_logs_action_index` (`action`),
    KEY `audit_logs_module_index` (`module`),
    KEY `audit_logs_record_id_index` (`record_id`),
    CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expense_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `expense_notifications_user_id_foreign` (`user_id`),
    KEY `expense_notifications_type_index` (`type`),
    CONSTRAINT `expense_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL,
    `value` JSON NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2026_05_10_031205_create_permission_tables', 1),
('2026_05_10_031300_create_expenseflow_tables', 1),
('2026_05_10_060000_add_must_change_password_to_users_table', 1),
('2026_05_21_000000_add_travel_claim_fields_to_expense_records', 1),
('2026_05_21_010000_add_toll_entries_to_expense_records', 1);

INSERT INTO `departments` (`id`, `name`, `code`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Management', 'MGT', 'active', NOW(), NOW()),
(2, 'Finance', 'FIN', 'active', NOW(), NOW()),
(3, 'HR & Admin', 'HRA', 'active', NOW(), NOW()),
(4, 'Operations', 'OPS', 'active', NOW(), NOW()),
(5, 'Customer Support', 'CS', 'active', NOW(), NOW()),
(6, 'Marketing', 'MKT', 'active', NOW(), NOW()),
(7, 'Sales', 'SAL', 'active', NOW(), NOW()),
(8, 'Clinical', 'CLI', 'active', NOW(), NOW()),
(9, 'Technology', 'TEC', 'active', NOW(), NOW()),
(10, 'Corporate Wellness', 'CW', 'active', NOW(), NOW());

INSERT INTO `expense_categories` (`id`, `name`, `code`, `description`, `keywords`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Travel', 'TRAVEL', NULL, JSON_ARRAY('grab', 'airasia', 'flight', 'taxi', 'train', 'bus', 'transport', 'travel'), 'active', NOW(), NOW()),
(2, 'Petrol', 'PETROL', NULL, JSON_ARRAY('petronas', 'shell', 'caltex', 'bhp', 'petrol', 'fuel', 'ron95', 'ron97'), 'active', NOW(), NOW()),
(3, 'Mileage', 'MILEAGE', NULL, JSON_ARRAY('mileage', 'waze', 'distance', 'km'), 'active', NOW(), NOW()),
(4, 'Parking', 'PARKING', NULL, JSON_ARRAY('parking', 'parkir'), 'active', NOW(), NOW()),
(5, 'Toll', 'TOLL', NULL, JSON_ARRAY('toll', 'tol', 'touch n go', 'smart tag', 'rfid'), 'active', NOW(), NOW()),
(6, 'Meal', 'MEAL', NULL, JSON_ARRAY('restaurant', 'cafe', 'kopitiam', 'nasi', 'kopi', 'meal', 'food', 'dine', 'ayam', 'makan'), 'active', NOW(), NOW()),
(7, 'Accommodation', 'ACCOMMODATION', NULL, JSON_ARRAY('hotel', 'inn', 'homestay', 'accommodation', 'booking'), 'active', NOW(), NOW()),
(8, 'Office Supplies', 'OFFICE_SUPPLIES', NULL, JSON_ARRAY('stationery', 'paper', 'printer', 'office supplies'), 'active', NOW(), NOW()),
(9, 'Clinic Supplies', 'CLINIC_SUPPLIES', NULL, JSON_ARRAY('clinic supplies', 'clinic'), 'active', NOW(), NOW()),
(10, 'Medical Supplies', 'MEDICAL_SUPPLIES', NULL, JSON_ARRAY('medical', 'pharmacy', 'guardian', 'watsons', 'medicine'), 'active', NOW(), NOW()),
(11, 'Equipment', 'EQUIPMENT', NULL, JSON_ARRAY('equipment', 'device', 'hardware'), 'active', NOW(), NOW()),
(12, 'Training', 'TRAINING', NULL, JSON_ARRAY('training', 'course', 'workshop', 'seminar'), 'active', NOW(), NOW()),
(13, 'Software Subscription', 'SOFTWARE_SUBSCRIPTION', NULL, JSON_ARRAY('software', 'subscription', 'saas', 'google workspace', 'microsoft', 'openai'), 'active', NOW(), NOW()),
(14, 'Marketing', 'MARKETING', NULL, JSON_ARRAY('marketing', 'ads', 'advertising', 'printing', 'banner'), 'active', NOW(), NOW()),
(15, 'Corporate Event', 'CORPORATE_EVENT', NULL, JSON_ARRAY('event', 'corporate event'), 'active', NOW(), NOW()),
(16, 'Client Entertainment', 'CLIENT_ENTERTAINMENT', NULL, JSON_ARRAY('client entertainment', 'entertainment'), 'active', NOW(), NOW()),
(17, 'Maintenance', 'MAINTENANCE', NULL, JSON_ARRAY('maintenance', 'repair', 'service'), 'active', NOW(), NOW()),
(18, 'Utilities', 'UTILITIES', NULL, JSON_ARRAY('utility', 'utilities', 'electric', 'water', 'tnb', 'syabas'), 'active', NOW(), NOW()),
(19, 'Internet / Telco', 'INTERNET_TELCO', NULL, JSON_ARRAY('internet', 'telco', 'maxis', 'celcom', 'digi', 'umobile', 'unifi'), 'active', NOW(), NOW()),
(20, 'Courier / Delivery', 'COURIER_DELIVERY', NULL, JSON_ARRAY('courier', 'delivery', 'poslaju', 'j&t', 'dhl', 'grab express', 'lalamove'), 'active', NOW(), NOW()),
(21, 'Others', 'OTHERS', NULL, JSON_ARRAY(), 'active', NOW(), NOW());

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'expense.view_all', 'web', NOW(), NOW()),
(2, 'expense.view_own', 'web', NOW(), NOW()),
(3, 'expense.create', 'web', NOW(), NOW()),
(4, 'expense.review', 'web', NOW(), NOW()),
(5, 'expense.approve', 'web', NOW(), NOW()),
(6, 'expense.reject', 'web', NOW(), NOW()),
(7, 'expense.mark_paid', 'web', NOW(), NOW()),
(8, 'expense.export', 'web', NOW(), NOW()),
(9, 'settings.manage', 'web', NOW(), NOW()),
(10, 'users.manage', 'web', NOW(), NOW()),
(11, 'audit.view', 'web', NOW(), NOW()),
(12, 'ai_logs.view', 'web', NOW(), NOW());

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'director_super_admin', 'web', NOW(), NOW()),
(2, 'admin_finance', 'web', NOW(), NOW()),
(3, 'staff', 'web', NOW(), NOW()),
(4, 'executive', 'web', NOW(), NOW());

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1), (2, 1), (3, 1), (4, 1), (5, 1), (6, 1), (7, 1), (8, 1), (9, 1), (10, 1), (11, 1), (12, 1),
(1, 2), (4, 2), (5, 2), (6, 2), (7, 2), (8, 2), (12, 2),
(2, 3), (3, 3),
(2, 4), (3, 4);

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `phone`, `department_id`, `manager_id`, `role`, `status`, `must_change_password`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Nidzam Yatimi', 'nidzamyatimi@physiomobile.com', NOW(), NULL, 1, NULL, 'director_super_admin', 'active', 1, '$2y$12$4yL7euiRUTR4FUZXA3p8seAdoi5J7HY1qTpCTrjf85I.XVM1/v9l2', NULL, NOW(), NOW()),
(2, 'Saiful', 'saiful@physiomobile.com', NOW(), NULL, 1, NULL, 'director_super_admin', 'active', 1, '$2y$12$4yL7euiRUTR4FUZXA3p8seAdoi5J7HY1qTpCTrjf85I.XVM1/v9l2', NULL, NOW(), NOW()),
(3, 'Executive Staff 1', 'executive1@physiomobile.com', NOW(), NULL, 4, NULL, 'executive', 'active', 1, '$2y$12$4yL7euiRUTR4FUZXA3p8seAdoi5J7HY1qTpCTrjf85I.XVM1/v9l2', NULL, NOW(), NOW()),
(4, 'Executive Staff 2', 'executive2@physiomobile.com', NOW(), NULL, 8, NULL, 'executive', 'active', 1, '$2y$12$4yL7euiRUTR4FUZXA3p8seAdoi5J7HY1qTpCTrjf85I.XVM1/v9l2', NULL, NOW(), NOW());

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(1, 'App\\Models\\User', 2),
(4, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4);

INSERT INTO `system_settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'openai', JSON_OBJECT('enabled', true, 'model', 'gpt-4.1-mini', 'daily_scan_limit', 50), NOW(), NOW()),
(2, 'claims', JSON_OBJECT('mileage_rate', 0.50), NOW(), NOW());
