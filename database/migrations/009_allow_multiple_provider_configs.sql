-- Migration: 009_allow_multiple_provider_configs.sql
-- Allow multiple configurations per provider type

-- Remove unique constraint on type to allow multiple configs per provider type
ALTER TABLE `{prefix}sixlab_providers` DROP INDEX `type`;

-- Add index for better performance on type queries
ALTER TABLE `{prefix}sixlab_providers` ADD INDEX `type_active` (`type`, `is_active`);

-- Add a unique constraint on name to ensure unique provider names
ALTER TABLE `{prefix}sixlab_providers` ADD UNIQUE KEY `unique_name` (`name`);
