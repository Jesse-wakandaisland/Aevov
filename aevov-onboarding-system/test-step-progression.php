<?php
/**
 * Test script for onboarding step progression logic
 */

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Mock WordPress functions
function get_option($option, $default = false) {
    static $options = [
        'aevov_current_onboarding_step' => 'system_check',
        'aevov_completed_steps' => ['welcome']
    ];
    return $options[$option] ?? $default;
}

function update_option($option, $value) {
    echo "✅ Updated option: $option = " . (is_array($value) ? implode(', ', $value) : $value) . "\n";
    return true;
}

function is_plugin_active($plugin) {
    // Simulate all plugins being active
    return true;
}

function wp_verify_nonce($nonce, $action) {
    return true;
}

function current_user_can($capability) {
    return true;
}

function wp_send_json_success($data) {
    echo "✅ SUCCESS: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

function wp_send_json_error($message) {
    echo "❌ ERROR: $message\n";
}

function sanitize_text_field($text) {
    return $text;
}

if (!function_exists('array_key_exists')) {
    function array_key_exists($key, $array) {
        return isset($array[$key]);
    }
}

if (!function_exists('array_search')) {
    function array_search($needle, $haystack) {
        return array_search($needle, $haystack);
    }
}

if (!function_exists('version_compare')) {
    function version_compare($version1, $version2, $operator = null) {
        return \version_compare($version1, $version2, $operator);
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show) {
        return '6.0';
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text);
    }
}

// Include the onboarding class (simplified)
class TestAevovOnboardingSystem {
    private $plugin_dependencies = [
        'aps-tools/aps-tools.php' => 'APS Tools',
        'bloom-pattern-recognition/bloom-pattern-system.php' => 'BLOOM Pattern Recognition',
        'AevovPatternSyncProtocol/aevov-pattern-sync-protocol.php' => 'Aevov Pattern Sync Protocol',
        'bloom-chunk-scanner/bloom-chunk-scanner.php' => 'BLOOM Chunk Scanner',
        'APS Chunk Uploader/chunk-uploader.php' => 'APS Chunk Uploader'
    ];
    
    private $onboarding_steps = [
        'welcome' => 'Welcome to Aevov',
        'system_check' => 'System Requirements Check',
        'plugin_activation' => 'Plugin Activation',
        'architecture_overview' => 'System Architecture',
        'initial_setup' => 'Initial Configuration',
        'pattern_creation' => 'Create Your First Pattern',
        'chunk_management' => 'Chunk Management Setup',
        'sync_configuration' => 'Sync Protocol Setup',
        'testing_validation' => 'System Testing',
        'completion' => 'Setup Complete'
    ];
    
    public function handle_ajax_action() {
        echo "\n🔧 Testing handle_ajax_action...\n";
        
        // Simulate POST data
        $_POST = [
            'nonce' => 'test-nonce',
            'step' => 'system_check',
            'action_type' => 'complete'
        ];
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aevov-onboarding-nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $step = sanitize_text_field($_POST['step'] ?? '');
        $action_type = sanitize_text_field($_POST['action_type'] ?? 'start');
        
        echo "📝 Processing step: $step, action: $action_type\n";
        
        // Validate step
        if (empty($step) || !array_key_exists($step, $this->onboarding_steps)) {
            wp_send_json_error('Invalid step: ' . $step);
            return;
        }
        
        if ($action_type === 'complete') {
            $completed_steps = get_option('aevov_completed_steps', []);
            echo "📋 Current completed steps: " . implode(', ', $completed_steps) . "\n";
            
            if (!in_array($step, $completed_steps)) {
                $completed_steps[] = $step;
                update_option('aevov_completed_steps', $completed_steps);
            }
            
            // Move to next step
            $step_keys = array_keys($this->onboarding_steps);
            $current_index = array_search($step, $step_keys);
            echo "📍 Current step index: $current_index\n";
            
            if ($current_index !== false && isset($step_keys[$current_index + 1])) {
                $next_step = $step_keys[$current_index + 1];
                echo "➡️  Moving to next step: $next_step\n";
                update_option('aevov_current_onboarding_step', $next_step);
            } else {
                echo "🏁 Reached final step\n";
            }
        } else {
            update_option('aevov_current_onboarding_step', $step);
        }
        
        wp_send_json_success(['message' => 'Step updated successfully']);
    }
    
    public function get_system_status() {
        echo "\n🔍 Testing get_system_status...\n";
        
        // Simulate POST data
        $_POST = [
            'nonce' => 'test-nonce',
            'check_completion' => true
        ];
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'aevov-onboarding-nonce')) {
            wp_send_json_error('Invalid nonce');
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $check_completion = isset($_POST['check_completion']) && $_POST['check_completion'];
        echo "🎯 Check completion mode: " . ($check_completion ? 'YES' : 'NO') . "\n";
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $php_ok = version_compare($php_version, '7.4', '>=');
        echo "🐘 PHP Version: $php_version (" . ($php_ok ? 'OK' : 'FAIL') . ")\n";
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        $wp_ok = version_compare($wp_version, '5.0', '>=');
        echo "📝 WordPress Version: $wp_version (" . ($wp_ok ? 'OK' : 'FAIL') . ")\n";
        
        // Check plugins
        $inactive_plugins = [];
        $active_plugins = [];
        
        foreach ($this->plugin_dependencies as $plugin_file => $plugin_name) {
            $is_active = is_plugin_active($plugin_file);
            
            if ($is_active) {
                $active_plugins[] = $plugin_name;
            } else {
                $inactive_plugins[] = $plugin_name;
            }
        }
        
        echo "✅ Active plugins: " . implode(', ', $active_plugins) . "\n";
        echo "❌ Inactive plugins: " . implode(', ', $inactive_plugins) . "\n";
        
        // Determine if system check can be completed
        $all_requirements_met = $php_ok && $wp_ok && empty($inactive_plugins);
        echo "🎯 All requirements met: " . ($all_requirements_met ? 'YES' : 'NO') . "\n";
        
        wp_send_json_success([
            'html' => '<div>System status HTML would be here</div>',
            'can_complete' => $all_requirements_met,
            'auto_complete' => $check_completion && $all_requirements_met
        ]);
    }
    
    public function test_step_accessibility() {
        echo "\n🔐 Testing step accessibility logic...\n";
        
        $current_step = 'system_check';
        $completed_steps = ['welcome'];
        $step_keys = array_keys($this->onboarding_steps);
        $current_step_index = array_search($current_step, $step_keys);
        
        echo "📍 Current step: $current_step (index: $current_step_index)\n";
        echo "✅ Completed steps: " . implode(', ', $completed_steps) . "\n";
        
        foreach ($this->onboarding_steps as $step_key => $step_title) {
            $is_completed = in_array($step_key, $completed_steps);
            $is_active = $current_step === $step_key;
            $step_index = array_search($step_key, $step_keys);
            
            // Allow access to current step, completed steps, or next step if current is completed
            $is_accessible = $is_active || $is_completed || 
                           ($current_step_index !== false && $step_index <= $current_step_index + 1);
            
            $status = $is_completed ? 'COMPLETED' : ($is_active ? 'ACTIVE' : ($is_accessible ? 'ACCESSIBLE' : 'LOCKED'));
            echo "📋 $step_key: $status\n";
        }
    }
}

// Run tests
echo "🚀 Starting Aevov Onboarding System Tests\n";
echo "==========================================\n";

$tester = new TestAevovOnboardingSystem();

// Test 1: Step accessibility logic
$tester->test_step_accessibility();

// Test 2: System status check
$tester->get_system_status();

// Test 3: Step completion and progression
$tester->handle_ajax_action();

echo "\n✅ All tests completed!\n";