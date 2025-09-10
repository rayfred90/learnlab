-- Migration: 004_create_validations_table.sql
-- Create the validations table for tracking step validation results

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_validations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `step` int(11) NOT NULL,
  `validation_type` varchar(50) NOT NULL,
  `validation_data` longtext,
  `expected_result` longtext,
  `actual_result` longtext,
  `score` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_score` decimal(5,2) NOT NULL DEFAULT 100.00,
  `passed` tinyint(1) DEFAULT 0,
  `feedback` longtext,
  `ai_analysis` longtext,
  `validation_time_ms` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `step` (`step`),
  KEY `passed` (`passed`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_validations_session_id` FOREIGN KEY (`session_id`) REFERENCES `{prefix}sixlab_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
