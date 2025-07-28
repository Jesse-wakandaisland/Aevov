<?php
/**
 * Plugin Name: BLOOM Pattern Recognition System
 * Description: Distributed pattern recognition system for BLOOM tensor chunks using WordPress Multisite
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: bloom-pattern-system
 * Network: true
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define minimum PHP version
define('BLOOM_MIN_PHP_VERSION', '7.4');

// Define constants first
if (!defined('BLOOM_VERSION')) {
    define('BLOOM_VERSION', '1.0.0');
}
if (!defined('BLOOM_FILE')) {
    define('BLOOM_FILE', __FILE__);
}
if (!defined('BLOOM_PATH')) {
    define('BLOOM_PATH', plugin_dir_path(__FILE__));
}
if (!defined('BLOOM_URL')) {
    define('BLOOM_URL', plugin_dir_url(__FILE__));
}
if (!defined('BLOOM_CHUNK_SIZE')) {
    define('BLOOM_CHUNK_SIZE', 7 * 1024 * 1024);
}

// Include debug logging
require_once BLOOM_PATH . 'debug-log.php';

// Require core plugin files in proper dependency order
require_once BLOOM_PATH . 'includes/core/class-plugin-activator.php';

// Load utility classes first
require_once BLOOM_PATH . 'includes/utilities/class-data-validator.php';
require_once BLOOM_PATH . 'includes/utilities/class-error-handler.php';

// Load model classes
require_once BLOOM_PATH . 'includes/models/class-pattern-model.php';
require_once BLOOM_PATH . 'includes/models/class-chunk-model.php';
require_once BLOOM_PATH . 'includes/models/class-tensor-model.php';

// Load processing classes
require_once BLOOM_PATH . 'includes/processing/class-tensor-processor.php';

// Load monitoring classes
require_once BLOOM_PATH . 'includes/monitoring/class-metrics-collector.php';
require_once BLOOM_PATH . 'includes/monitoring/class-system-monitor.php';

// Load network classes
require_once BLOOM_PATH . 'includes/network/class-network-manager.php';
require_once BLOOM_PATH . 'includes/network/class-message-queue.php';

// Load API classes
if (file_exists(BLOOM_PATH . 'includes/api/class-api-controller.php')) {
    require_once BLOOM_PATH . 'includes/api/class-api-controller.php';
}

// Load core class
require_once BLOOM_PATH . 'includes/core/class-bloom.php';

// Load integration classes last (they depend on everything above)
require_once BLOOM_PATH . 'includes/integration/class-aps-integration.php';

// Core plugin class
final class BLOOM_Pattern_System {
    private static $instance = null;
    private $plugin_slug = 'bloom-pattern-system';
    private $core;
    private $aps_integration;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
        $this->load_dependencies();
    }

    private function define_constants() {
        // Constants already defined above to avoid issues with require_once
        // This method kept for compatibility
    }

    private function init_hooks() {
        register_activation_hook(BLOOM_FILE, function() {
            $activator = new \BLOOM\Core\PluginActivator();
            $activator->activate();
        });
        
        register_deactivation_hook(BLOOM_FILE, function() {
            $activator = new \BLOOM\Core\PluginActivator();
            $activator->deactivate();
        });

        add_action('network_admin_menu', [$this, 'add_network_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'load_textdomain']); // Add this line
    }

    public function load_textdomain() {
        load_plugin_textdomain('bloom-pattern-system', false, BLOOM_PATH . 'languages');
    }

    private function load_dependencies() {
        // Add diagnostic logging to validate class loading
        $required_classes = [
            'BLOOM\Models\PatternModel',
            'BLOOM\Models\ChunkModel',
            'BLOOM\Models\TensorModel',
            'BLOOM\Processing\TensorProcessor',
            'BLOOM\Monitoring\MetricsCollector',
            'BLOOM\Monitoring\SystemMonitor',
            'BLOOM\Utilities\DataValidator',
            'BLOOM\Utilities\ErrorHandler'
        ];
        
        $missing_classes = [];
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                $missing_classes[] = $class;
            }
        }
        
        if (!empty($missing_classes)) {
            error_log('BLOOM: Missing required classes: ' . implode(', ', $missing_classes));
            wp_die('BLOOM Plugin Error: Required classes not found. Please check plugin installation.');
        }
        
        error_log('BLOOM: All required classes loaded successfully');
        
        $this->core = \BLOOM\Core::get_instance();
        $this->aps_integration = new \BLOOM\Integration\APSIntegration();
        
        error_log('BLOOM: Plugin dependencies loaded successfully');
    }

    public function add_network_menu() {
        // Add main menu
        add_menu_page(
            'BLOOM Pattern System',
            'BLOOM Patterns',
            'manage_network_options',
            $this->plugin_slug,
            [$this, 'render_main_page'],
            'dashicons-visibility',
            30
        );

        // Add submenus
        add_submenu_page(
            $this->plugin_slug,
            'Dashboard',
            'Dashboard',
            'manage_network_options',
            $this->plugin_slug,
            [$this, 'render_main_page']
        );

        add_submenu_page(
            $this->plugin_slug,
            'Pattern Management',
            'Patterns',
            'manage_network_options',
            $this->plugin_slug . '-patterns',
            [$this, 'render_patterns_page']
        );

        add_submenu_page(
            $this->plugin_slug,
            'Tensor Upload',
            'Upload Tensors',
            'manage_network_options',
            $this->plugin_slug . '-upload',
            [$this, 'render_upload_page']
        );

        add_submenu_page(
            $this->plugin_slug,
            'Settings',
            'Settings',
            'manage_network_options',
            $this->plugin_slug . '-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, $this->plugin_slug) === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'bloom-admin',
            BLOOM_URL . 'assets/css/admin.css',
            [],
            BLOOM_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'bloom-admin',
            BLOOM_URL . 'assets/js/admin.js',
            ['jquery'],
            BLOOM_VERSION,
            true
        );

        wp_localize_script('bloom-admin', 'bloomAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bloom-admin'),
            'i18n' => [
                'confirm' => __('Are you sure?', 'bloom-pattern-system'),
                'success' => __('Operation successful', 'bloom-pattern-system'),
                'error' => __('An error occurred', 'bloom-pattern-system')
            ]
        ]);
    }

    public function render_main_page() {
        include BLOOM_PATH . 'admin/views/dashboard.php';
    }

    public function render_patterns_page() {
        include BLOOM_PATH . 'admin/views/patterns.php';
    }

    public function render_upload_page() {
        include BLOOM_PATH . 'admin/views/upload.php';
    }

    public function render_settings_page() {
        include BLOOM_PATH . 'admin/views/settings.php';
    }

}

// Initialize plugin
function BLOOM() {
    return BLOOM_Pattern_System::instance();
}

// Start the plugin
add_action('plugins_loaded', 'BLOOM');