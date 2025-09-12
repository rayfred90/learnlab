-- Add delete/reset script columns to lab_templates table
ALTER TABLE {prefix}sixlab_lab_templates 
ADD COLUMN delete_reset_script LONGTEXT NULL AFTER verification_rules,
ADD COLUMN guided_delete_reset_script LONGTEXT NULL AFTER delete_reset_script;

-- Add index for script queries if needed
CREATE INDEX idx_lab_scripts ON {prefix}sixlab_lab_templates(template_type);