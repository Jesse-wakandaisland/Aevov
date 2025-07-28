<?php
/**
 * Handles plugin settings page and options management
 */
class BLOOM_Settings_Page {
    private $option_group = 'bloom_settings';
    private $option_name = 'bloom_options';
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            [$this, 'validate_settings']
        );

        $this->add_settings_sections();
    }

    private function add_settings_sections() {
        // Network Settings
        add_settings_section(
            'network_settings',
            __('Network Settings', 'bloom-pattern-system'),
            [$this, 'render_network_section'],
            $this->option_group
        );

        // Processing Settings
        add_settings_section(
            'processing_settings',
            __('Processing Settings', 'bloom-pattern-system'),
            [$this, 'render_processing_section'],
            $this->option_group
        );

        // Integration Settings
        add_settings_section(
            'integration_settings',
            __('Integration Settings', 'bloom-pattern-system'),
            [$this, 'render_integration_section'],
            $this->option_group
        );

        $this->add_settings_fields();
    }

    private function add_settings_fields() {
        // Network Fields
        add_settings_field(
            'chunk_size',
            __('Chunk Size (MB)', 'bloom-pattern-system'),
            [$this, 'render_number_field'],
            $this->option_group,
            'network_settings',
            [
                'name' => 'chunk_size',
                'min' => 1,
                'max' => 50,
                'default' => 7
            ]
        );

        add_settings_field(
            'sites_per_chunk',
            __('Sites Per Chunk', 'bloom-pattern-system'),
            [$this, 'render_number_field'],
            $this->option_group,
            'network_settings',
            [
                'name' => 'sites_per_chunk',
                'min' => 2,
                'max' => 10,
                'default' => 3
            ]
        );

        // Add more fields as needed
    }

    public function render_number_field($args) {
        $options = get_option($this->option_name);
        $value = $options[$args['name']] ?? $args['default'];
        
        printf(
            '<input type="number" id="%1$s" name="%2$s[%1$s]" value="%3$s" min="%4$s" max="%5$s" class="regular-text">',
            esc_attr($args['name']),
            esc_attr($this->option_name),
            esc_attr($value),
            esc_attr($args['min']),
            esc_attr($args['max'])
        );
    }

    public function validate_settings($input) {
        $validated = [];
        $defaults = $this->get_default_settings();

        foreach ($defaults as $key => $default) {
            if (isset($input[$key])) {
                $validated[$key] = $this->validate_setting($input[$key], $key);
            } else {
                $validated[$key] = $default;
            }
        }

        return $validated;
    }

    private function get_default_settings() {
        return [
            'chunk_size' => 7,
            'sites_per_chunk' => 3,
            'processing_batch_size' => 100,
            'sync_interval' => 300,
            'api_enabled' => true
        ];
    }
}