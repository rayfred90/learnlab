-- Migration: 001_create_sessions_table.sql
-- Create the main sessions table for lab session management

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `lab_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(50) NOT NULL,
  `provider_session_id` varchar(255) NOT NULL,
  `session_data` longtext,
  `ai_context` longtext,
  `current_step` int(11) DEFAULT 1,
  `total_steps` int(11) DEFAULT 1,
  `status` enum('active','paused','completed','expired','error') DEFAULT 'active',
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `lab_id` (`lab_id`),
  KEY `provider` (`provider`),
  KEY `status` (`status`),
  KEY `expires_at` (`expires_at`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_sessions_user_id` FOREIGN KEY (`user_id`) REFERENCES `{wp_prefix}users` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
