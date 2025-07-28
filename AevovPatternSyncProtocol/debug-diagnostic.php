<?php
/**
 * Debug version of AevovPatternSyncProtocol Diagnostic Check
 * Tests classes one by one to identify hanging issues
 */

// Determine the base path dynamically
$base_path = __DIR__ . '/';

echo "=== AevovPatternSyncProtocol DEBUG Diagnostic Check ===\n\n";

// Define autoloader for APS classes
spl_autoload_register(function ($class) use ($base_path) {
    if (strpos($class, 'APS\\') === 0) {
        $class_path = str_replace('APS\\', '', $class);
        $class_path = str_replace('\\', '/', $class_path);
        
        // Map class paths based on namespace structure
        $class_mappings = [
            'Analysis/APS_Plugin' => 'Includes/Analysis/APS_Plugin.php',
            'Core/APS_Core' => 'Includes/Core/APS_Core.php',
            'Core/Logger' => 'Includes/Core/Logger.php',
            'Integration/BloomIntegration' => 'Includes/Integration/BloomIntegration.php',
            'DB/MetricsDB' => 'Includes/DB/MetricsDB.php',
            'Monitoring/AlertManager' => 'Includes/Monitoring/AlertManager.php',
            'Queue/ProcessQueue' => 'Includes/Queue/ProcessQueue.php',
            'Queue/QueueManager' => 'Includes/Queue/QueueManager.php',
            'Network/SyncManager' => 'Includes/Network/SyncManager.php',
            'Pattern/PatternGenerator' => 'Includes/Pattern/PatternGenerator.php',
            'Pattern/PatternStorage' => 'Includes/Pattern/PatternStorage.php',
            'Pattern/ChunkProcessor' => 'Includes/Pattern/ChunkProcessor.php',
            'Pattern/PatternValidator' => 'Includes/Pattern/PatternValidator.php',
            'Comparison/APS_Comparator' => 'Includes/Comparison/APS_Comparator.php',
            'DB/NetworkCache' => 'Includes/DB/NetworkCache.php',
            'API/API' => 'Includes/API/API.php',
            'Admin/APS_Admin' => 'Includes/Admin/APS_Admin.php',
            'Admin/APS_Settings' => 'Includes/Admin/APS_Settings.php',
            'Admin/APS_Metaboxes' => 'Includes/Admin/APS_Metaboxes.php',
            'Admin/APS_BLOOM_Integration' => 'Includes/Admin/APS_BLOOM_Integration.php',
            'Frontend/PublicFrontend' => 'Includes/Frontend/PublicFrontend.php'
        ];
        
        if (isset($class_mappings[$class_path])) {
            $file_path = $base_path . $class_mappings[$class_path];
            if (file_exists($file_path)) {
                echo "   DEBUG: Loading file {$file_path}\n";
                require_once $file_path;
                echo "   DEBUG: File loaded successfully\n";
            }
        }
    }
});

// Mock WordPress functions and globals for CLI testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) { return true; }
    function add_filter($hook, $callback, $priority = 10, $args = 1) { return true; }
    function do_action($hook, ...$args) { return true; }
    function apply_filters($hook, $value, ...$args) { return $value; }
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) { return true; }
    function wp_next_scheduled($hook, $args = []) { return false; }
    function wp_clear_scheduled_hook($hook, $args = []) { return true; }
    function get_option($option, $default = false) { return $default; }
    function update_option($option, $value) { return true; }
    function delete_option($option) { return true; }
    function current_time($type, $gmt = 0) { return date('Y-m-d H:i:s'); }
    function plugin_dir_path($file) { return dirname($file) . '/'; }
    function plugin_dir_url($file) { return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/'; }
    function is_admin() { return false; }
    function __($text, $domain = 'default') { return $text; }
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function esc_url($url) { return $url; }
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
    function wp_die($message) { die($message); }
    function wp_generate_uuid4() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }
    function get_current_blog_id() { return 1; }
    function get_sites() { return []; }
    function switch_to_blog($blog_id) { return true; }
    function restore_current_blog() { return true; }
    function is_multisite() { return false; }
    function current_user_can($capability) { return true; }
    function register_rest_route($namespace, $route, $args) { return true; }
    function register_rest_field($object_type, $attribute, $args) { return true; }
    function rest_url($path = '') { return 'http://localhost/wp-json/' . $path; }
    function admin_url($path = '') { return 'http://localhost/wp-admin/' . $path; }
    function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') { return true; }
    function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) { return true; }
    function wp_localize_script($handle, $object_name, $l10n) { return true; }
    function wp_create_nonce($action) { return 'test_nonce'; }
    function check_ajax_referer($action, $query_arg = false, $die = true) { return true; }
    function wp_send_json_success($data = null) { echo json_encode(['success' => true, 'data' => $data]); exit; }
    function wp_send_json_error($data = null) { echo json_encode(['success' => false, 'data' => $data]); exit; }
    function add_settings_error($setting, $code, $message, $type = 'error') { return true; }
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null) { return true; }
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '') { return true; }
    function get_bloginfo($show = '') { return 'Test Site'; }
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []) { return true; }
    function wp_remote_post($url, $args = []) { return ['response' => ['code' => 200], 'body' => '']; }
    function get_current_user_id() { return 1; }
    function shortcode_atts($pairs, $atts, $shortcode = '') { return array_merge($pairs, (array) $atts); }
    function add_shortcode($tag, $callback) { return true; }
    function wp_mkdir_p($target) { return wp_mkdir_p_fallback($target); }
}

// Fallback function for wp_mkdir_p
if (!function_exists('wp_mkdir_p_fallback')) {
    function wp_mkdir_p_fallback($target) {
        $target = str_replace('//', '/', $target);
        if (file_exists($target)) {
            return @is_dir($target);
        }
        if (wp_mkdir_p_fallback(dirname($target))) {
            if ($dir_perms = @fileperms(dirname($target))) {
                $dir_perms = $dir_perms & 0007777;
            } else {
                $dir_perms = 0755;
            }
            if (@mkdir($target, $dir_perms, true)) {
                return true;
            }
        }
        return false;
    }
}

// Define WordPress constants if not defined
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}
if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

// Mock global $wpdb
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new stdClass();
    $wpdb->prefix = 'wp_';
    $wpdb->prepare = function($query, ...$args) { return $query; };
    $wpdb->get_results = function($query) { return []; };
    $wpdb->insert = function($table, $data) { return true; };
    $wpdb->update = function($table, $data, $where) { return true; };
    $wpdb->delete = function($table, $where) { return true; };
}

// Test classes one by one
echo "Testing Class Loading One by One:\n";
$core_classes = [
    'APS\\Analysis\\APS_Plugin',
    'APS\\Core\\APS_Core',
    'APS\\Integration\\BloomIntegration',
    'APS\\DB\\MetricsDB',
    'APS\\Monitoring\\AlertManager',
    'APS\\Queue\\ProcessQueue',
    'APS\\Queue\\QueueManager',
    'APS\\Network\\SyncManager',
    'APS\\Pattern\\PatternGenerator',
    'APS\\Pattern\\PatternStorage',
    'APS\\Comparison\\APS_Comparator',
    'APS\\API\\API',
    'APS\\Admin\\APS_Admin',
    'APS\\Frontend\\PublicFrontend'
];

foreach ($core_classes as $class) {
    echo "\n--- Testing {$class} ---\n";
    
    try {
        echo "   Step 1: Checking if class exists...\n";
        if (class_exists($class)) {
            echo "   Step 1: ✓ Class exists\n";
            
            echo "   Step 2: Testing instantiation...\n";
            if (strpos($class, 'APS_Plugin') !== false || strpos($class, 'APS_Core') !== false || strpos($class, 'API\\API') !== false) {
                echo "   Step 2: ✓ Skipped (Singleton pattern)\n";
            } elseif (strpos($class, 'BloomIntegration') !== false) {
                echo "   Step 2: ✓ Skipped (May have dependencies)\n";
            } else {
                $instance = new $class();
                echo "   Step 2: ✓ Instantiation successful\n";
            }
        } else {
            echo "   Step 1: ✗ Class not found\n";
        }
    } catch (Exception $e) {
        echo "   Step 2: ✗ Exception: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    } catch (Error $e) {
        echo "   Step 2: ✗ Fatal Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    }
    
    echo "--- End {$class} ---\n";
}

echo "\n=== Debug Diagnostic Complete ===\n";