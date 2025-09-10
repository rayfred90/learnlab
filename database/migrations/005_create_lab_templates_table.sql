-- Migration: 005_create_lab_templates_table.sql
-- Create the lab templates table for storing lab configurations

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_lab_templates` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text,
  `provider_type` varchar(50) NOT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `estimated_duration` int(11) DEFAULT NULL,
  `template_data` longtext NOT NULL,
  `validation_rules` longtext,
  `instructions` longtext,
  `prerequisites` longtext,
  `learning_objectives` longtext,
  `tags` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `author_id` bigint(20) UNSIGNED NOT NULL,
  `usage_count` int(11) DEFAULT 0,
  `average_score` decimal(4,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `provider_type` (`provider_type`),
  KEY `difficulty_level` (`difficulty_level`),
  KEY `is_active` (`is_active`),
  KEY `is_featured` (`is_featured`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `fk_lab_templates_author_id` FOREIGN KEY (`author_id`) REFERENCES `{wp_prefix}users` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
