-- Migration: 003_create_ai_interactions_table.sql
-- Create the AI interactions table for tracking AI assistant usage

CREATE TABLE IF NOT EXISTS `{prefix}sixlab_ai_interactions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `ai_provider` varchar(50) NOT NULL,
  `interaction_type` enum('contextual_help','configuration_analysis','chat','error_explanation','hint_request') NOT NULL,
  `request_data` longtext,
  `response_data` longtext,
  `tokens_used` int(11) DEFAULT 0,
  `response_time_ms` int(11) DEFAULT NULL,
  `user_rating` tinyint(1) DEFAULT NULL,
  `cost_usd` decimal(10,6) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `ai_provider` (`ai_provider`),
  KEY `interaction_type` (`interaction_type`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_ai_interactions_session_id` FOREIGN KEY (`session_id`) REFERENCES `{prefix}sixlab_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ai_interactions_user_id` FOREIGN KEY (`user_id`) REFERENCES `{wp_prefix}users` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
