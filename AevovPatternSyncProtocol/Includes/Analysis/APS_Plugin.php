<?php
/**
 * Plugin Name: Aevov Pattern Sync-protocol
 * Description: Advanced pattern comparison and synchronization system for BLOOM Pattern Recognition
 * Version: 1.0.0
 * Text Domain: aps
 * Domain Path: /languages
 */

namespace APS\Analysis;

if (!defined('ABSPATH')) exit;

final class APS_Plugin {
    private static $instance = null;
    public $admin;
    public $loader;
    private $initialized = false;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct($loader = null, $admin = null) {
        $this->loader = $loader;
        $this->admin = $admin;
        $this->define_constants();
        if (function_exists('add_action')) {
            add_action('plugins_loaded', [$this, 'initialize'], 0);
        }
    }

    private function define_constants() {
        if (!defined('APS_VERSION')) {
            define('APS_VERSION', '1.0.0');
        }
        if (!defined('APS_FILE')) {
            define('APS_FILE', __FILE__);
        }
        if (!defined('APS_PATH')) {
            // APS_Plugin.php is in Includes/Analysis/, so we need to go up 2 levels to get to plugin root
            $plugin_root = function_exists('plugin_dir_path') ?
                plugin_dir_path(dirname(dirname(__FILE__))) :
                dirname(dirname(dirname(__FILE__))) . '/';
            define('APS_PATH', $plugin_root);
        }
        if (!defined('APS_URL')) {
            define('APS_URL', function_exists('plugin_dir_url') ? plugin_dir_url(__FILE__) : '');
        }
        if (!defined('APS_ASSETS')) {
            define('APS_ASSETS', APS_URL . 'assets/');
        }
    }

    public function initialize() {
        if ($this->initialized) return;

        if (!$this->check_bloom_dependencies()) {
            if (function_exists('add_action')) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>APS requires BLOOM Pattern Recognition System.</p></div>';
                });
            }
            return;
        }

        $this->initialized = true;
        $this->load_dependencies();
        $this->init_components();
        $this->init_hooks();

        if (function_exists('do_action')) {
            do_action('aps_initialized');
        }
    }

    private function check_bloom_dependencies() {
        // Multiple methods to check if BLOOM Pattern Recognition is available
        
        // Method 1: Check for integration class
        if (class_exists('BLOOM_APS_Integration')) {
            return true;
        }
        
        // Method 2: Check for core BLOOM classes
        if (class_exists('BLOOM\\Core\\BloomPatternSystem') ||
            class_exists('BLOOM\\Integration\\APSIntegration')) {
            return true;
        }
        
        // Method 3: Check if BLOOM functions exist
        if (function_exists('bloom_get_pattern_data') ||
            function_exists('bloom_register_pattern')) {
            return true;
        }
        
        // Method 4: Check if plugin is active
        if (function_exists('is_plugin_active')) {
            $possible_paths = [
                'bloom-pattern-recognition/bloom-pattern-system.php',
                'bloom-pattern-recognition/bloom-pattern-recognition.php',
                'bloom-pattern-recognition/index.php'
            ];
            
            foreach ($possible_paths as $path) {
                if (is_plugin_active($path)) {
                    return true;
                }
            }
        }
        
        // Method 5: Check if plugin files exist and are loaded (only if WP_PLUGIN_DIR is defined)
        if (defined('WP_PLUGIN_DIR')) {
            $possible_files = [
                WP_PLUGIN_DIR . '/bloom-pattern-recognition/bloom-pattern-system.php',
                WP_PLUGIN_DIR . '/bloom-pattern-recognition/bloom-pattern-recognition.php',
                WP_PLUGIN_DIR . '/bloom-pattern-recognition/index.php'
            ];
            
            $included_files = get_included_files();
            foreach ($possible_files as $file) {
                if (file_exists($file) && in_array($file, $included_files)) {
                    return true;
                }
            }
        }
        
        // Method 6: Check for post types that BLOOM should register (only if function exists)
        if (function_exists('post_type_exists') &&
            (post_type_exists('bloom_model') || post_type_exists('bloom_pattern'))) {
            return true;
        }
        
        return false;
    }

    private function load_dependencies() {
        // Load core dependencies first
        require_once APS_PATH . 'Includes/Core/Loader.php';
        require_once APS_PATH . 'Includes/Core/APS_i18n.php';
        require_once APS_PATH . 'Includes/Core/Logger.php';
        
        // Load DB and Monitoring dependencies (required by Integration)
        require_once APS_PATH . 'Includes/DB/MetricsDB.php';
        require_once APS_PATH . 'Includes/Monitoring/AlertManager.php';
        
        // Load Admin dependencies
        require_once APS_PATH . 'Includes/Admin/APS_Settings.php';
        require_once APS_PATH . 'Includes/Admin/APS_Metaboxes.php';
        require_once APS_PATH . 'Includes/Admin/APS_BLOOM_Integration.php';
        require_once APS_PATH . 'Includes/Admin/APS_Admin.php';
        
        // Load other components
        require_once APS_PATH . 'Includes/Frontend/ReactComponents.php';
        require_once APS_PATH . 'Includes/Pattern/PatternGenerator.php';
        require_once APS_PATH . 'Includes/Pattern/ChunkProcessor.php';
        require_once APS_PATH . 'Includes/Pattern/PatternStorage.php';
        require_once APS_PATH . 'Includes/Pattern/PatternValidator.php';
        require_once APS_PATH . 'Includes/Comparison/APS_Comparator.php';
        require_once APS_PATH . 'Includes/Integration/BloomIntegration.php';
        require_once APS_PATH . 'Includes/Features/PatternOfTheDay.php';
        require_once APS_PATH . 'Includes/Features/FeaturedPattern.php';
        require_once APS_PATH . 'Includes/Features/PatternSpotlight.php';
    }

    private $poc;
    private $pattern_of_the_day;
    private $featured_pattern;
    private $pattern_spotlight;

    private function init_components() {
        if ($this->loader === null) {
            $this->loader = new \APS\Core\Loader();
        }
        
        if ($this->admin === null) {
            $this->admin = new \APS\Admin\APS_Admin();
        }

        $this->poc = new \Aevov\Decentralized\ProofOfContribution();
        $this->pattern_of_the_day = new \Aevov\Features\PatternOfTheDay(new \APS\DB\APS_Pattern_DB());
        $this->featured_pattern = new \Aevov\Features\FeaturedPattern();
        $this->pattern_spotlight = new \Aevov\Features\PatternSpotlight();
    }

    public function submit_contribution($data)
    {
        $contributor = new \Aevov\Decentralized\Contributor('test');
        $contribution = new \Aevov\Decentralized\Contribution($contributor, $data);
        return $this->poc->submitContribution($contribution);
    }

    private function init_hooks() {
        if (function_exists('register_activation_hook')) {
            register_activation_hook(APS_FILE, [$this, 'activate']);
        }
        if (function_exists('register_deactivation_hook')) {
            register_deactivation_hook(APS_FILE, [$this, 'deactivate']);
        }

        $this->loader->add_action('init', $this, 'load_text_domain');
        
        if (function_exists('is_admin') && is_admin()) {
            // Admin menu integration is now handled by APS Tools - removed duplicate registration
            // $this->loader->add_action('admin_menu', $this->admin, 'add_menu_pages');
            $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_assets');
        }

        $this->loader->run();
    }
    
    

    public function load_text_domain() {
        if (function_exists('load_plugin_textdomain') && function_exists('plugin_basename')) {
            load_plugin_textdomain('aps', false, dirname(plugin_basename(APS_FILE)) . '/languages');
        }
    }

    public function activate() {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) return;
        require_once APS_PATH . 'Includes/Core/APS_Activator.php';
        \APS\Core\APS_Activator::activate();
    }

    public function deactivate() {
        if (function_exists('current_user_can') && !current_user_can('activate_plugins')) return;
        require_once APS_PATH . 'Includes/Core/Deactivator.php';
        \APS\Core\Deactivator::deactivate();
    }

    public function __clone() {}

    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}




function APS() {
    return APS_Plugin::instance();
}

// Only instantiate when WordPress is loaded to avoid circular dependencies
if (defined('ABSPATH')) {
    $GLOBALS['aps'] = APS();
}
