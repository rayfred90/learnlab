-- Migration: 006_create_analytics_table.sql
-- Create the analytics table for tracking user behavior and system events

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_analytics` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `event_type` varchar(100) NOT NULL,
  `event_category` varchar(50) NOT NULL,
  `event_data` longtext,
  `user_agent` text,
  `ip_address` varchar(45),
  `referrer` text,
  `page_url` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `event_category` (`event_category`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_analytics_session_id` FOREIGN KEY (`session_id`) REFERENCES `{prefix}sixlab_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_analytics_user_id` FOREIGN KEY (`user_id`) REFERENCES `{wp_prefix}users` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
