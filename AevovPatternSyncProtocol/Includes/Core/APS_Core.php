<?php

namespace APS\Core;

/**
 * Core plugin functionality and bootstrapping
 */
class APS_Core {
    private static $instance = null;
    private $initialized = false;
    private $bloom_instance = null;

    // Component instances
    private $loader;
    private $i18n;
    private $admin;
    private $public;
    private $comparator;
    private $integration;
    private $system_monitor;
    private $alert_manager;
    private $network_monitor;
    private $queue_manager;

    /**
     * Get singleton instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize only after plugins are loaded
        if (function_exists('add_action')) {
            add_action('plugins_loaded', [$this, 'maybe_initialize'], 1);
        }
    }

    /**
     * Maybe initialize the core if dependencies are met
     */
    public function maybe_initialize() {
        if (!$this->initialized && $this->check_dependencies()) {
            $this->initialized = true;
            $this->init_core();
        }
    }

    /**
     * Initialize core functionality
     */
    private function init_core() {
        try {
            $this->load_dependencies();
            $this->init_components();
            $this->setup_hooks();
            
            if (function_exists('do_action')) {
                do_action('aps_core_initialized');
            }
        } catch (\Exception $e) {
            $this->handle_error($e);
        }
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Admin
        if (file_exists(APS_PATH . 'includes/admin/class-aps-admin.php')) {
            require_once APS_PATH . 'includes/admin/class-aps-admin.php';
        }

        // Frontend
        if (file_exists(APS_PATH . 'includes/frontend/class-aps-public.php')) {
            require_once APS_PATH . 'includes/frontend/class-aps-public.php';
        }

        // Integration
        if (file_exists(APS_PATH . 'includes/integration/class-bloom-integration.php')) {
            require_once APS_PATH . 'includes/integration/class-bloom-integration.php';
        }

        // Monitoring
        if (file_exists(APS_PATH . 'includes/monitoring/class-system-monitor.php')) {
            require_once APS_PATH . 'includes/monitoring/class-system-monitor.php';
        }

        if (file_exists(APS_PATH . 'includes/monitoring/class-alert-manager.php')) {
            require_once APS_PATH . 'includes/monitoring/class-alert-manager.php';
        }

        // Queue
        if (file_exists(APS_PATH . 'includes/queue/class-queue-manager.php')) {
            require_once APS_PATH . 'includes/queue/class-queue-manager.php';
        }
    }

    /**
     * Initialize core components
     */
    private function init_components() {
        $this->loader = new Loader();
        $this->i18n = new i18n();

        if (class_exists('\APS\Admin\Admin')) {
            $this->admin = new \APS\Admin\Admin();
        }

        if (class_exists('\APS\Frontend\PublicFrontend')) {
            $this->public = new \APS\Frontend\PublicFrontend();
        }

        if (class_exists('\APS\Comparison\Comparator')) {
            $this->comparator = new \APS\Comparison\Comparator();
        }

        if (class_exists('\APS\Integration\BloomIntegration')) {
            $this->integration = new \APS\Integration\BloomIntegration($this->bloom_instance);
        }

        if (class_exists('\APS\Monitoring\SystemMonitor')) {
            $this->system_monitor = new \APS\Monitoring\SystemMonitor();
        }

        if (class_exists('\APS\Monitoring\AlertManager')) {
            $this->alert_manager = new \APS\Monitoring\AlertManager();
        }

        if (class_exists('\APS\Queue\QueueManager')) {
            $this->queue_manager = new \APS\Queue\QueueManager();
        }
    }

    /**
     * Set up WordPress hooks
     */
    private function setup_hooks() {
        // Core hooks
        $this->loader->add_action('init', $this->i18n, 'load_plugin_textdomain');

        // Admin hooks
        if ($this->admin && function_exists('is_admin') && is_admin()) {
            // Admin menu integration is now handled by APS Tools - removed duplicate registration
            // $this->loader->add_action('admin_menu', $this->admin, 'add_menu_pages');
            $this->loader->add_action('admin_init', $this->admin, 'init_settings');
            $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_assets');
        }

        // BLOOM integration hooks
        if ($this->integration && $this->bloom_instance) {
            $this->loader->add_action('bloom_pattern_processed', $this->integration, 'handle_pattern');
            $this->loader->add_filter('bloom_pre_pattern_process', $this->integration, 'prepare_pattern_data');
        }

        // Monitoring hooks
        if ($this->system_monitor) {
            $this->loader->add_action('init', $this->system_monitor, 'schedule_health_checks');
        }

        if ($this->queue_manager) {
            $this->loader->add_action('init', $this->queue_manager, 'schedule_processor');
        }

        // Run the loader
        $this->loader->run();
    }

    /**
     * Check if all dependencies are available
     */
    private function check_dependencies() {
        $bloom_exists = class_exists('BLOOM_Pattern_System') || 
                       function_exists('BLOOM') ||
                       defined('BLOOM_VERSION');

        if (!$bloom_exists) {
            if (function_exists('add_action')) {
                add_action('admin_notices', function() {
                    $message = function_exists('__') ? __('APS Core requires the BLOOM Pattern Recognition System plugin to be installed and activated.', 'aps') : 'APS Core requires the BLOOM Pattern Recognition System plugin to be installed and activated.';
                    $escaped_message = function_exists('esc_html') ? esc_html($message) : htmlspecialchars($message);
                    echo '<div class="notice notice-error"><p>' . $escaped_message . '</p></div>';
                });
            }
            return false;
        }

        // Store BLOOM instance if available
        if (function_exists('BLOOM')) {
            $this->bloom_instance = BLOOM();
        }

        return true;
    }

    /**
     * Handle initialization errors
     */
    private function handle_error(\Exception $e) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('APS Core Error: ' . $e->getMessage());
        }

        if (function_exists('add_action')) {
            add_action('admin_notices', function() use ($e) {
                $message = function_exists('__') ? sprintf(__('APS Core Error: %s', 'aps'), $e->getMessage()) : 'APS Core Error: ' . $e->getMessage();
                $escaped_message = function_exists('esc_html') ? esc_html($message) : htmlspecialchars($message);
                echo '<div class="notice notice-error"><p>' . $escaped_message . '</p></div>';
            });
        }
    }

    public function get_bloom_instance() {
        return $this->bloom_instance;
    }

    public function is_bloom_active() {
        return !is_null($this->bloom_instance);
    }

    /**
     * Get component instances
     */
    public function get_loader() {
        return $this->loader;
    }

    public function get_admin() {
        return $this->admin;
    }

    public function get_public() {
        return $this->public;
    }

    public function get_integration() {
        return $this->integration;
    }

    public function get_system_monitor() {
        return $this->system_monitor;
    }

    public function get_queue_manager() {
        return $this->queue_manager;
    }

    private function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}