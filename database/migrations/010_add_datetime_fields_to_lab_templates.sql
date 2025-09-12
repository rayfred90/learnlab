-- Add date and time fields to lab_templates table for scheduled labs
ALTER TABLE {prefix}sixlab_lab_templates 
ADD COLUMN lab_start_date DATE NULL AFTER estimated_duration,
ADD COLUMN lab_start_time TIME NULL AFTER lab_start_date,
ADD COLUMN lab_end_date DATE NULL AFTER lab_start_time,
ADD COLUMN lab_end_time TIME NULL AFTER lab_end_date;

-- Add index for date-based queries
CREATE INDEX idx_lab_schedule ON {prefix}sixlab_lab_templates(lab_start_date, lab_start_time);