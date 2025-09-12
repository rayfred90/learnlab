-- Migration: 011_add_guided_lab_session_tracking.sql
-- Add fields to track guided lab session progress

-- Add guided lab progress tracking fields to sessions table
ALTER TABLE `{prefix}sixlab_sessions` 
ADD COLUMN `completed_steps` longtext NULL AFTER `current_step`,
ADD COLUMN `step_progress` longtext NULL AFTER `completed_steps`,
ADD COLUMN `commands_history` longtext NULL AFTER `step_progress`,
ADD COLUMN `step_start_time` datetime NULL AFTER `commands_history`,
ADD COLUMN `step_end_time` datetime NULL AFTER `step_start_time`,
ADD COLUMN `time_spent_minutes` int(11) DEFAULT 0 AFTER `step_end_time`,
ADD COLUMN `device_configs` longtext NULL AFTER `time_spent_minutes`;

-- Add index for performance
ALTER TABLE `{prefix}sixlab_sessions` 
ADD KEY `current_step` (`current_step`),
ADD KEY `time_spent_minutes` (`time_spent_minutes`);