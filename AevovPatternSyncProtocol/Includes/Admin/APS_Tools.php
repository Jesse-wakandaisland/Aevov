<?php
/**
 * includes/admin/class-aps-tools.php
 */

namespace APS\Admin;

class APS_Tools {
    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        if (function_exists('add_action')) {
            add_action('wp_ajax_aps_clear_cache', [$this, 'handle_clear_cache_ajax']);
        }
    }

    public function render_tools_page() {
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

        // Clear cache tool
        echo '<div class="aps-tool-card">';
        echo '<h3>' . (function_exists('__') ? __('Clear Cache', 'aps') : 'Clear Cache') . '</h3>';
        echo '<p>' . (function_exists('__') ? __('Clear all APS related caches.', 'aps') : 'Clear all APS related caches.') . '</p>';
        echo '<button class="button button-secondary" id="aps-clear-cache">' . (function_exists('__') ? __('Clear Cache', 'aps') : 'Clear Cache') . '</button>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    public function handle_clear_cache_ajax() {
        if (function_exists('check_ajax_referer')) {
            check_ajax_referer('aps-admin', 'nonce');
        }

        if (!function_exists('current_user_can') || !current_user_can('manage_options')) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
            }
            return;
        }

        try {
            // Clear the cache
            $cache = new \APS\DB\APS_Cache();
            $cache->flush();

            if (function_exists('wp_send_json_success')) {
                wp_send_json_success(['message' => 'Cache cleared successfully.']);
            }
        } catch (Exception $e) {
            if (function_exists('wp_send_json_error')) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        }
    }
}
