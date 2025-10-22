-- REST API Plugin Database Tables

-- API Keys Table
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `assignment_type` enum('internal','external') COLLATE utf8_unicode_ci DEFAULT 'internal',
  `status` enum('active','inactive','revoked') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'active',
  `rate_limit_per_minute` int(11) NOT NULL DEFAULT 60,
  `rate_limit_per_hour` int(11) NOT NULL DEFAULT 1000,
  `rate_limit_per_day` int(11) NOT NULL DEFAULT 10000,
  `ip_whitelist` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `total_calls` bigint(20) NOT NULL DEFAULT 0,
  `require_https` tinyint(1) DEFAULT NULL,
  `cors_enabled` tinyint(1) DEFAULT NULL,
  `cors_allowed_origins` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `enabled_endpoints` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `permission_group_id` int(11) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_unique` (`key`),
  KEY `status` (`status`),
  KEY `total_calls` (`total_calls`),
  KEY `permission_group_id` (`permission_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

CREATE TABLE IF NOT EXISTS `api_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) DEFAULT NULL,
  `method` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `endpoint` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
  `request_body` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `response_code` int(11) NOT NULL,
  `response_body` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `response_time` decimal(10,4) NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `api_key_id` (`api_key_id`),
  KEY `method` (`method`),
  KEY `response_code` (`response_code`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

CREATE TABLE IF NOT EXISTS `api_rate_limits` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `api_key_id` int(11) NOT NULL,
  `minute_window` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `hour_window` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `day_window` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `minute_count` int(11) NOT NULL DEFAULT 0,
  `hour_count` int(11) NOT NULL DEFAULT 0,
  `day_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key_minute` (`api_key_id`,`minute_window`),
  KEY `api_key_hour` (`api_key_id`,`hour_window`),
  KEY `api_key_day` (`api_key_id`,`day_window`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

CREATE TABLE IF NOT EXISTS `api_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'app',
  `created_at` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

INSERT IGNORE INTO `api_settings` (`setting_name`, `setting_value`, `type`, `deleted`) VALUES
('api_enabled', '1', 'app', 0),
('default_rate_limit_per_minute', '60', 'app', 0),
('default_rate_limit_per_hour', '1000', 'app', 0),
('default_rate_limit_per_day', '10000', 'app', 0),
('log_retention_days', '90', 'app', 0),
('require_https', '0', 'app', 0),
('cors_enabled', '0', 'app', 0),
('cors_allowed_origins', '', 'app', 0),
('default_ip_whitelist', '', 'app', 0);

--#

-- Persistent API Statistics Table
CREATE TABLE IF NOT EXISTS `api_statistics` (
  `id` int(11) NOT NULL DEFAULT 1,
  `total_calls` bigint(20) NOT NULL DEFAULT 0,
  `successful_calls` bigint(20) NOT NULL DEFAULT 0,
  `failed_calls` bigint(20) NOT NULL DEFAULT 0,
  `total_response_time` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `first_call_date` datetime DEFAULT NULL,
  `last_updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

-- Initialize api_statistics with single row
INSERT IGNORE INTO `api_statistics` (`id`, `total_calls`, `successful_calls`, `failed_calls`, `total_response_time`, `first_call_date`, `last_updated_at`) 
VALUES (1, 0, 0, 0, 0.0000, NULL, NULL);

--#

-- Permission Groups Table
CREATE TABLE IF NOT EXISTS `api_permission_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `permissions` longtext COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`deleted`),
  KEY `is_system` (`is_system`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--#

-- Insert default "Default All" permission group
INSERT IGNORE INTO `api_permission_groups` (`name`, `description`, `is_system`, `permissions`, `created_at`, `deleted`) VALUES
('Default All', 'System default group with full access to all endpoints and operations', 1, '{"users":{"create":true,"read":true,"update":true,"delete":true},"projects":{"create":true,"read":true,"update":true,"delete":true},"tasks":{"create":true,"read":true,"update":true,"delete":true},"clients":{"create":true,"read":true,"update":true,"delete":true},"invoices":{"create":true,"read":true,"update":true,"delete":true},"estimates":{"create":true,"read":true,"update":true,"delete":true},"proposals":{"create":true,"read":true,"update":true,"delete":true},"contracts":{"create":true,"read":true,"update":true,"delete":true},"expenses":{"create":true,"read":true,"update":true,"delete":true},"tickets":{"create":true,"read":true,"update":true,"delete":true},"timesheets":{"create":true,"read":true,"update":true,"delete":true},"events":{"create":true,"read":true,"update":true,"delete":true},"notes":{"create":true,"read":true,"update":true,"delete":true},"messages":{"create":true,"read":true,"update":true,"delete":true},"notifications":{"create":true,"read":true,"update":true,"delete":true},"announcements":{"create":true,"read":true,"update":true,"delete":true}}', NOW(), 0);