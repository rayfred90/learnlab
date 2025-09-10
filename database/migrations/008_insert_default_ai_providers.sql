-- Migration: 008_insert_default_ai_providers.sql
-- Insert default AI provider configurations

-- Check if the ai_providers table exists (it should be created elsewhere)
-- INSERT IGNORE INTO `{prefix}sixlab_ai_providers` 
-- (`name`, `type`, `display_name`, `config`, `is_active`, `is_default`) 
-- VALUES 

-- For now, we'll use WordPress options to store AI provider defaults
-- These will be handled by the plugin's AI factory system

-- Default AI provider configurations will be stored as WordPress options:
-- 'sixlab_ai_providers' - array of available AI providers
-- 'sixlab_default_ai_provider' - default AI provider name

-- OpenAI Provider
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_openai_config', 
'{"api_key":"","model":"gpt-4o-mini","temperature":0.7,"max_tokens":2000,"timeout":30,"rate_limit_per_minute":60,"input_token_rate":0.00015,"output_token_rate":0.0006}', 
'no') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- Anthropic Provider  
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_anthropic_config', 
'{"api_key":"","model":"claude-3-haiku-20240307","temperature":0.7,"max_tokens":2000,"timeout":30,"rate_limit_per_minute":60,"input_token_rate":0.00025,"output_token_rate":0.00125}', 
'no') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- Gemini Provider
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_gemini_config', 
'{"api_key":"","model":"gemini-1.5-flash","temperature":0.7,"max_tokens":2000,"timeout":30,"rate_limit_per_minute":60,"input_token_rate":0.000075,"output_token_rate":0.0003}', 
'no') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- OpenRouter Provider (NEW)
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_openrouter_config', 
'{"api_key":"","app_name":"SixLab Tool","model":"openai/gpt-4o-mini","temperature":0.7,"max_tokens":2000,"timeout":30,"rate_limit_per_minute":60,"input_token_rate":0.00015,"output_token_rate":0.0006}', 
'no') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- Available AI providers list
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_available_ai_providers', 
'["openai","anthropic","gemini","openrouter"]', 
'yes') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- Default AI provider (set to OpenRouter as it offers multiple models)
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_default_ai_provider', 'openrouter', 'yes') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- AI provider display names
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_names', 
'{"openai":"OpenAI GPT","anthropic":"Anthropic Claude","gemini":"Google Gemini","openrouter":"OpenRouter (Multi-Model)"}', 
'yes') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);

-- AI provider capabilities
INSERT INTO `{prefix}options` (`option_name`, `option_value`, `autoload`) 
VALUES ('sixlab_ai_provider_capabilities', 
'{"openai":["contextual_help","configuration_analysis","chat","error_explanation","hint_request"],"anthropic":["contextual_help","configuration_analysis","chat","error_explanation","hint_request","code_analysis"],"gemini":["contextual_help","configuration_analysis","chat","error_explanation","hint_request","multimodal"],"openrouter":["contextual_help","configuration_analysis","chat","error_explanation","hint_request","multi_model_access"]}', 
'yes') 
ON DUPLICATE KEY UPDATE `option_value` = VALUES(`option_value`);
