<?php
/**
 * Implements the main plugin dashboard
 */
class BLOOM_Dashboard {
    private $metrics_collector;
    
    public function __construct() {
        $this->metrics_collector = new BLOOM_Metrics_Collector();
        add_action('wp_ajax_bloom_get_dashboard_data', [$this, 'get_dashboard_data']);
    }

    public function render() {
        $initial_data = $this->get_initial_data();
        include BLOOM_PATH . 'admin/templates/dashboard/main.php';
    }

    public function get_dashboard_data() {
        check_ajax_referer('bloom_admin');

        wp_send_json_success([
            'system_status' => $this->get_system_status(),
            'processing_stats' => $this->get_processing_stats(),
            'network_health' => $this->get_network_health(),
            'recent_activity' => $this->get_recent_activity()
        ]);
    }

    private function get_system_status() {
        return [
            'status' => $this->get_overall_status(),
            'memory_usage' => $this->metrics_collector->get_memory_usage(),
            'cpu_usage' => $this->metrics_collector->get_cpu_usage(),
            'processing_queue' => $this->get_queue_status()
        ];
    }

    private function get_processing_stats() {
        global $wpdb;
        
        return [
            'patterns_processed' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}bloom_patterns 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            ),
            'average_processing_time' => $this->get_avg_processing_time(),
            'success_rate' => $this->calculate_success_rate(),
            'active_jobs' => $this->get_active_jobs_count()
        ];
    }

    private function get_network_health() {
        $sites = get_sites(['fields' => 'ids']);
        $health = [];
        
        foreach ($sites as $site_id) {
            switch_to_blog($site_id);
            $health[$site_id] = [
                'status' => $this->get_site_status($site_id),
                'last_sync' => $this->get_last_sync_time($site_id),
                'pattern_count' => $this->get_site_pattern_count($site_id)
            ];
            restore_current_blog();
        }
        
        return $health;
    }
}