-- Migration: 002_create_providers_table.sql
-- Create the providers table for lab provider configuration

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_providers` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `config` longtext NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_default` tinyint(1) DEFAULT 0,
  `supported_features` longtext,
  `last_health_check` datetime DEFAULT NULL,
  `health_status` enum('healthy','warning','error','unknown') DEFAULT 'unknown',
  `health_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`),
  KEY `is_active` (`is_active`),
  KEY `is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
