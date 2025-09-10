# 6Lab Tool WordPress Plugin

A comprehensive WordPress plugin designed to provide interactive network engineering and system administration laboratory experiences within LearnDash courses.

## Features

- **Multi-Provider Lab System**: Support for GNS3, Apache Guacamole, EVE-NG, and custom providers
- **AI Learning Assistant**: Integration with OpenAI GPT-4, Anthropic Claude, and Google Gemini
- **Advanced Assessment System**: Automated grading with AI-enhanced feedback
- **Enhanced Student Experience**: Responsive interface with real-time collaboration
- **Comprehensive Admin Tools**: Visual lab builder and analytics dashboard

## Requirements

- WordPress 6.0 or higher
- PHP 8.0 or higher
- LearnDash LMS plugin
- MySQL 5.7 or higher

## Installation

1. Upload the plugin files to the `/wp-content/plugins/6lab-tool/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure providers and AI settings in the admin panel
4. Create lab templates and assign them to LearnDash lessons

## Project Structure

```
6lab-tool/
├── 6lab-tool.php                    # Main plugin file
├── uninstall.php                    # Uninstall cleanup
├── includes/                        # Core plugin classes
│   ├── class-sixlab-core.php        # Main plugin class
│   ├── class-provider-factory.php   # Provider management
│   ├── class-ai-factory.php         # AI integration
│   ├── class-session-manager.php    # Session lifecycle
│   ├── providers/                   # Lab provider classes
│   │   ├── abstract-lab-provider.php
│   │   ├── class-gns3-provider.php
│   │   ├── class-guacamole-provider.php
│   │   └── class-eveng-provider.php
│   └── ai/                          # AI provider classes
│       ├── abstract-ai-provider.php
│       ├── class-openai-provider.php
│       ├── class-anthropic-provider.php
│       └── class-gemini-provider.php
├── admin/                           # Admin interface
│   ├── class-sixlab-admin.php
│   ├── views/                       # Admin templates
│   └── assets/                      # Admin CSS/JS
├── public/                          # Frontend files
│   ├── class-sixlab-public.php
│   ├── js/                          # Frontend JavaScript
│   ├── css/                         # Frontend CSS
│   └── templates/                   # Frontend templates
├── database/                        # Database management
│   ├── class-sixlab-database.php
│   └── migrations/                  # Database migrations
├── assets/                          # Plugin assets
│   └── images/                      # Images and icons
└── languages/                       # Translation files
```

## Database Schema

The plugin creates the following custom tables:

- `sixlab_sessions` - Active lab sessions
- `sixlab_providers` - Provider configurations
- `sixlab_ai_interactions` - AI conversation history
- `sixlab_validations` - Assessment results
- `sixlab_lab_templates` - Lab templates
- `sixlab_analytics` - Usage analytics

## Configuration

### Lab Providers

Configure lab providers in the admin panel:

1. Navigate to **6Lab Tool > Providers**
2. Add provider configurations for GNS3, Guacamole, or EVE-NG
3. Test connections and set default provider

### AI Integration

Configure AI providers:

1. Navigate to **6Lab Tool > AI Settings**
2. Add API keys for OpenAI, Anthropic, or Google Gemini
3. Configure prompt templates and behavior settings

### Lab Templates

Create lab templates:

1. Navigate to **6Lab Tool > Lab Templates**
2. Create new template with step-by-step instructions
3. Configure validation rules and scoring
4. Assign to LearnDash lessons

## Shortcodes

- `[sixlab_interface]` - Display lab interface
- `[sixlab_progress]` - Show progress tracker

## Hooks and Filters

### Actions

- `sixlab_session_created` - Fired when session is created
- `sixlab_session_completed` - Fired when session is completed
- `sixlab_step_validated` - Fired when step is validated

### Filters

- `sixlab_provider_config` - Filter provider configuration
- `sixlab_ai_prompt` - Filter AI prompt templates
- `sixlab_validation_rules` - Filter validation rules

## Development

### Local Development Setup

1. Clone the repository
2. Set up WordPress development environment
3. Install LearnDash LMS
4. Configure lab providers (GNS3, etc.)
5. Add AI provider API keys

### Creating Custom Providers

Extend the `SixLab_Lab_Provider_Abstract` class:

```php
class Custom_Provider extends SixLab_Lab_Provider_Abstract {
    public function create_session($user_id, $template_data, $options = array()) {
        // Implementation
    }
    
    public function validate_step($session_id, $step_config, $validation_data) {
        // Implementation
    }
    
    public function destroy_session($session_id) {
        // Implementation
    }
}
```

### Creating Custom AI Providers

Extend the `SixLab_AI_Provider_Abstract` class:

```php
class Custom_AI_Provider extends SixLab_AI_Provider_Abstract {
    public function get_contextual_help($context) {
        // Implementation
    }
    
    public function analyze_configuration($context) {
        // Implementation
    }
    
    public function chat_response($context) {
        // Implementation
    }
}
```

## API Endpoints

### REST API

- `GET /wp-json/sixlab/v1/sessions/{id}` - Get session details
- `POST /wp-json/sixlab/v1/sessions` - Create new session
- `POST /wp-json/sixlab/v1/sessions/{id}/validate` - Validate step
- `POST /wp-json/sixlab/v1/ai/chat` - AI chat interaction

### AJAX Endpoints

- `sixlab_start_session` - Start lab session
- `sixlab_validate_step` - Validate step
- `sixlab_ai_chat` - AI assistance
- `sixlab_end_session` - End session

## License

GPL v2 or later

## Support

For support and documentation, visit [plugin documentation](https://example.com/docs).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Changelog

### 1.0.0
- Initial release
- Multi-provider lab system
- AI integration
- LearnDash integration
- Assessment system
# learnlab
