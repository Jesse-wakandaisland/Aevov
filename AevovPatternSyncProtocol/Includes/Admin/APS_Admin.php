<?php
/**
 * includes/admin/class-aps-admin.php
 */

namespace APS\Admin;

class APS_Admin {
    private $settings;
    private $metaboxes;
    private $bloom_integration;
    
    public function __construct() {
        $this->settings = new APS_Settings();
        $this->metaboxes = new APS_Metaboxes();
        $this->bloom_integration = new APS_BLOOM_Integration();
        
        $this->init_hooks();
    }

    private function init_hooks() {
        if (function_exists('add_action')) {
            // Admin menu and pages
            add_action('admin_menu', [$this, 'add_menu_pages']);
            add_action('admin_init', [$this, 'init_settings']);
            
            // Admin scripts and styles
            add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
            
            // AJAX handlers
            add_action('wp_ajax_aps_run_comparison', [$this, 'handle_comparison_ajax']);
            add_action('wp_ajax_aps_get_pattern_details', [$this, 'handle_pattern_details_ajax']);
            
            // Integration hooks
            add_action('bloom_admin_pattern_updated', [$this, 'sync_bloom_pattern']);
        }
    }

    public function add_menu_pages() {
        // Only add submenu pages to integrate with APS Tools main menu
        if (function_exists('add_submenu_page') && function_exists('__')) {
            // Check if APS Tools main menu exists, if not, don't add our pages
            if (!$this->aps_tools_menu_exists()) {
                return;
            }

            // Add Pattern Comparisons submenu to APS Tools
            add_submenu_page(
                'aps-dashboard', // Parent slug from APS Tools
                __('Pattern Comparisons', 'aps'),
                __('Pattern Comparisons', 'aps'),
                'manage_options',
                'aps-pattern-comparisons',
                [$this, 'render_comparisons']
            );

            // Add Pattern Sync Tools submenu to APS Tools
            add_submenu_page(
                'aps-dashboard', // Parent slug from APS Tools
                __('Pattern Sync Tools', 'aps'),
                __('Pattern Sync Tools', 'aps'),
                'manage_options',
                'aps-pattern-sync-tools',
                [$this, 'render_tools']
            );
        }
    }

    /**
     * Check if APS Tools main menu exists
     */
    private function aps_tools_menu_exists() {
        global $menu;
        
        if (!is_array($menu)) {
            return false;
        }
        
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'aps-dashboard') {
                return true;
            }
        }
        
        return false;
    }

    public function enqueue_assets($hook) {
        // Only load on APS admin pages
        if (strpos($hook, 'aps-') === false) {
            return;
        }

        if (function_exists('wp_enqueue_style') && function_exists('wp_enqueue_script') && function_exists('wp_localize_script')) {
            wp_enqueue_style(
                'aps-admin',
                APS_URL . 'assets/css/aps-admin.css',
                [],
                APS_VERSION
            );

            wp_enqueue_script(
                'aps-admin',
                APS_URL . 'assets/js/aps-admin.js',
                ['jquery', 'wp-api'],
                APS_VERSION,
                true
            );

            wp_localize_script('aps-admin', 'apsAdmin', [
                'ajaxUrl' => function_exists('admin_url') ? admin_url('admin-ajax.php') : '',
                'restUrl' => function_exists('rest_url') ? rest_url('aps/v1') : '',
                'nonce' => function_exists('wp_create_nonce') ? wp_create_nonce('aps-admin') : '',
                'i18n' => [
                    'comparing' => function_exists('__') ? __('Running comparison...', 'aps') : 'Running comparison...',
                    'success' => function_exists('__') ? __('Comparison complete', 'aps') : 'Comparison complete',
                    'error' => function_exists('__') ? __('Error during comparison', 'aps') : 'Error during comparison',
                    'confirm' => function_exists('__') ? __('Are you sure?', 'aps') : 'Are you sure?'
                ]
            ]);
        }
    }

    public function render_dashboard() {
        // Get overview metrics
        $system_monitor = new \APS\Monitoring\SystemMonitor();
        $metrics = $system_monitor->collect_and_store_metrics();

        include APS_PATH . 'includes/admin/views/dashboard.php';
    }

    public function render_status() {
        $system_monitor = new \APS\Monitoring\SystemMonitor();
        $status = $system_monitor->get_system_status();

        include APS_PATH . 'includes/admin/views/system-status.php';
    }

    public function render_comparisons() {
        // Handle comparison actions
        $action = $_GET['action'] ?? '';
        $comparison_id = $_GET['id'] ?? 0;

        switch ($action) {
            case 'view':
                $this->render_comparison_details($comparison_id);
                break;
            case 'new':
                $this->render_comparison_form();
                break;
            default:
                $this->render_comparisons_list();
                break;
        }
    }

    private function render_comparison_details($comparison_id) {
        try {
            $comparison = $this->get_comparison_data($comparison_id);
            include APS_PATH . 'includes/admin/views/comparison-details.php';
        } catch (Exception $e) {
            $this->show_error_notice($e->getMessage());
            $this->render_comparisons_list();
        }
    }

    private function render_comparison_form() {
        $available_patterns = $this->get_available_patterns();
        include APS_PATH . 'includes/admin/views/comparison-form.php';
    }

    private function render_comparisons_list() {
        // Create comparisons list table
        require_once APS_PATH . 'includes/admin/class-aps-comparisons-list-table.php';
        $list_table = new APS_Comparisons_List_Table();
        $list_table->prepare_items();

        include APS_PATH . 'includes/admin/views/comparisons-list.php';
    }

    public function handle_comparison_ajax() {
        if (function_exists('check_ajax_referer')) {
            check_ajax_referer('aps-admin', 'nonce');
        }

        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
            }
            return;
        }

        $items = $_POST['items'] ?? [];
        $options = $_POST['options'] ?? [];

        try {
            $comparison = $this->run_comparison($items, $options);
            if (function_exists('wp_send_json_success')) {
                wp_send_json_success($comparison);
            }
        } catch (Exception $e) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }
    }

    private function run_comparison($items, $options) {
        $comparator = new APS_Comparator();
        return $comparator->compare_patterns($items, $options);
    }

    private function get_comparison_data($comparison_id) {
        global $wpdb;

        $comparison = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aps_comparisons 
             WHERE id = %d",
            $comparison_id
        ));

        if (!$comparison) {
            throw new Exception(function_exists('__') ? __('Comparison not found', 'aps') : 'Comparison not found');
        }

        $comparison->results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aps_results 
             WHERE comparison_id = %d 
             ORDER BY match_score DESC",
            $comparison_id
        ));

        return $comparison;
    }

    private function get_available_patterns() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT DISTINCT pattern_hash, pattern_data 
             FROM {$wpdb->prefix}aps_patterns_cache 
             WHERE cache_expires > NOW()"
        );
    }

    private function show_error_notice($message) {
        if (function_exists('add_settings_error')) {
            add_settings_error(
                'aps_admin_notices',
                'aps_error',
                $message,
                'error'
            );
        }
    }

    /**
     * Initialize admin settings
     */
    public function init_settings() {
        if ($this->settings && method_exists($this->settings, 'init')) {
            $this->settings->init();
        }
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        if ($this->settings && method_exists($this->settings, 'render_page')) {
            $this->settings->render_page();
        } else {
            // Fallback settings page
            echo '<div class="wrap">';
            echo '<h1>' . (function_exists('__') ? __('APS Settings', 'aps') : 'APS Settings') . '</h1>';
            echo '<p>' . (function_exists('__') ? __('Settings configuration will be available soon.', 'aps') : 'Settings configuration will be available soon.') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Render tools page
     */
    public function render_tools() {
        echo '<div class="wrap">';
        echo '<h1>' . (function_exists('__') ? __('APS Tools', 'aps') : 'APS Tools') . '</h1>';
        echo '<div class="aps-tools-grid">';
        
        // System diagnostics tool
        echo '<div class="aps-tool-card">';
        echo '<h3>' . (function_exists('__') ? __('System Diagnostics', 'aps') : 'System Diagnostics') . '</h3>';
        echo '<p>' . (function_exists('__') ? __('Run comprehensive system checks and diagnostics.', 'aps') : 'Run comprehensive system checks and diagnostics.') . '</p>';
        echo '<button class="button button-primary" onclick="apsRunDiagnostics()">' . (function_exists('__') ? __('Run Diagnostics', 'aps') : 'Run Diagnostics') . '</button>';
        echo '</div>';
        
        // Pattern sync tool
        echo '<div class="aps-tool-card">';
        echo '<h3>' . (function_exists('__') ? __('Pattern Synchronization', 'aps') : 'Pattern Synchronization') . '</h3>';
        echo '<p>' . (function_exists('__') ? __('Synchronize patterns with BLOOM integration.', 'aps') : 'Synchronize patterns with BLOOM integration.') . '</p>';
        echo '<button class="button button-primary" onclick="apsRunPatternSync()">' . (function_exists('__') ? __('Sync Patterns', 'aps') : 'Sync Patterns') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Handle pattern details AJAX request
     */
    public function handle_pattern_details_ajax() {
        if (function_exists('check_ajax_referer')) {
            check_ajax_referer('aps-admin', 'nonce');
        }

        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
            }
            return;
        }

        $pattern_id = $_POST['pattern_id'] ?? 0;
        
        try {
            $pattern_details = $this->get_pattern_details($pattern_id);
            if (function_exists('wp_send_json_success')) {
                wp_send_json_success($pattern_details);
            }
        } catch (Exception $e) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }
    }

    /**
     * Sync pattern with BLOOM
     */
    public function sync_bloom_pattern($pattern_data) {
        try {
            if ($this->bloom_integration && method_exists($this->bloom_integration, 'sync_pattern')) {
                $result = $this->bloom_integration->sync_pattern($pattern_data);
                
                // Log the sync result
                if (class_exists('\APS\Utilities\Logger')) {
                    $logger = new \APS\Utilities\Logger();
                    $logger->info('BLOOM pattern sync completed', [
                        'pattern_id' => $pattern_data['id'] ?? 'unknown',
                        'success' => $result['success'] ?? false
                    ]);
                }
                
                return $result;
            }
        } catch (Exception $e) {
            if (class_exists('\APS\Utilities\Logger')) {
                $logger = new \APS\Utilities\Logger();
                $logger->error('BLOOM pattern sync failed: ' . $e->getMessage());
            }
            
            return ['success' => false, 'error' => $e->getMessage()];
        }
        
        return ['success' => false, 'error' => 'BLOOM integration not available'];
    }

    /**
     * Get pattern details by ID
     */
    private function get_pattern_details($pattern_id) {
        global $wpdb;

        $pattern = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}aps_patterns
             WHERE id = %d",
            $pattern_id
        ));

        if (!$pattern) {
            throw new Exception(function_exists('__') ? __('Pattern not found', 'aps') : 'Pattern not found');
        }

        // Get additional pattern metadata
        $metadata = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->prefix}aps_pattern_meta
             WHERE pattern_id = %d",
            $pattern_id
        ));

        $pattern->metadata = [];
        foreach ($metadata as $meta) {
            $pattern->metadata[$meta->meta_key] = $meta->meta_value;
        }

        return $pattern;
    }
}