-- Migration: 010_add_lab_template_types.sql
-- Add support for guided and non-guided lab template types

-- Add lab template type column
ALTER TABLE `{prefix}sixlab_lab_templates` 
ADD COLUMN `template_type` enum('guided','non_guided') DEFAULT 'guided' AFTER `provider_type`;

-- Add guided lab specific fields
ALTER TABLE `{prefix}sixlab_lab_templates` 
ADD COLUMN `guided_steps` longtext NULL AFTER `template_data`,
ADD COLUMN `step_validation_rules` longtext NULL AFTER `guided_steps`,
ADD COLUMN `terminal_commands` longtext NULL AFTER `step_validation_rules`;

-- Add non-guided lab specific fields
ALTER TABLE `{prefix}sixlab_lab_templates` 
ADD COLUMN `startup_script` longtext NULL AFTER `terminal_commands`,
ADD COLUMN `startup_script_filename` varchar(255) NULL AFTER `startup_script`,
ADD COLUMN `verification_script` longtext NULL AFTER `startup_script_filename`,
ADD COLUMN `verification_script_filename` varchar(255) NULL AFTER `verification_script`,
ADD COLUMN `instructions_content` longtext NULL AFTER `verification_script_filename`,
ADD COLUMN `instructions_images` longtext NULL AFTER `instructions_content`;

-- Update existing templates to be guided type by default
UPDATE `{prefix}sixlab_lab_templates` SET `template_type` = 'guided' WHERE `template_type` IS NULL;

-- Add index for template type
ALTER TABLE `{prefix}sixlab_lab_templates` 
ADD KEY `template_type` (`template_type`);