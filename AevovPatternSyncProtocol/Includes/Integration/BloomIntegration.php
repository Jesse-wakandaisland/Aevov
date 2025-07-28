<?php
/**
 * Integration with BLOOM Pattern Recognition System
 * Handles communication between APS and BLOOM plugins
 * 
 * @package APS
 * @subpackage Integration
 */

namespace APS\Integration;

use APS\DB\MetricsDB;
use APS\Monitoring\AlertManager;

class BloomIntegration {
    private static $instance = null;
    private $metrics;
    private $alert_manager;
    private $is_connected = false;
    private $connection_error = null;
    private $sync_interval = 300; // 5 minutes

    private function __construct() {
        $this->metrics = new MetricsDB();
        $this->alert_manager = new AlertManager();
        
        $this->init_hooks();
        $this->check_bloom_availability();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks() {
        // BLOOM Pattern hooks
        if (function_exists('add_action')) {
            add_action('bloom_pattern_processed', [$this, 'handle_bloom_pattern']);
            add_action('bloom_pattern_updated', [$this, 'sync_pattern']);
            add_action('admin_init', [$this, 'check_connection']);
            add_action('aps_sync_bloom', [$this, 'sync_with_bloom']);
            add_action('bloom_processing_error', [$this, 'handle_bloom_error']);
        }
        if (function_exists('add_filter')) {
            add_filter('bloom_pre_pattern_process', [$this, 'prepare_pattern_data']);
        }
    }

    private function check_bloom_availability() {
        // Initial availability check during construction
        $this->check_connection();
    }

    public function is_available() {
        return class_exists('\BLOOM\Core') && $this->is_connected;
    }

    public function check_connection() {
        if (!class_exists('\BLOOM\Core')) {
            $this->is_connected = false;
            $this->connection_error = 'BLOOM plugin not installed';
            error_log('APS BloomIntegration: BLOOM\Core class not found');
            return false;
        }

        try {
            error_log('APS BloomIntegration: Getting BLOOM\Core instance...');
            $bloom_core = \BLOOM\Core::get_instance();
            
            error_log('APS BloomIntegration: BLOOM\Core instance obtained, checking methods...');
            $available_methods = get_class_methods($bloom_core);
            error_log('APS BloomIntegration: Available methods: ' . implode(', ', $available_methods));
            
            if (!method_exists($bloom_core, 'get_system_status')) {
                error_log('APS BloomIntegration: get_system_status method not found, using fallback');
                $status = [
                    'active' => true,
                    'error' => null,
                    'version' => '1.0.0',
                    'message' => 'BLOOM Core available but using fallback status'
                ];
            } else {
                error_log('APS BloomIntegration: Calling get_system_status method...');
                $status = $bloom_core->get_system_status();
                error_log('APS BloomIntegration: get_system_status returned: ' . json_encode($status));
            }
            
            $this->is_connected = $status['active'];
            $this->connection_error = $status['active'] ? null : $status['error'];

            $this->record_connection_status($status);
            return $status['active'];

        } catch (\Exception $e) {
            $this->is_connected = false;
            $this->connection_error = $e->getMessage();
            error_log('APS BloomIntegration: Exception caught: ' . $e->getMessage());
            error_log('APS BloomIntegration: Exception trace: ' . $e->getTraceAsString());
            $this->alert_manager->trigger_alert('bloom_connection_error', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function analyze_pattern($pattern_data) {
        if (!$this->is_available()) {
            throw new \Exception('BLOOM integration not available');
        }

        try {
            $bloom_pattern = $this->convert_to_bloom_format($pattern_data);
            $bloom_core = \BLOOM\Core::get_instance();
            $result = $bloom_core->analyze_pattern($bloom_pattern);

            $this->record_analysis_metrics($result);
            
            return $this->convert_from_bloom_format($result);

        } catch (\Exception $e) {
            $this->handle_bloom_error($e);
            throw $e;
        }
    }

    public function sync_pattern($pattern_data) {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $bloom_core = \BLOOM\Core::get_instance();
            $sync_result = $bloom_core->sync_pattern($pattern_data);

            $this->record_sync_metrics($sync_result);
            
            return $sync_result;

        } catch (\Exception $e) {
            $this->alert_manager->trigger_alert('bloom_sync_error', [
                'pattern_id' => $pattern_data['id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function handle_bloom_pattern($pattern_data) {
        try {
            $converted_pattern = $this->convert_from_bloom_format($pattern_data);
            
            // Queue for APS processing
            $queue = new \APS\Queue\ProcessQueue();
            $job_id = $queue->enqueue_job([
                'type' => 'pattern_analysis',
                'data' => $converted_pattern
            ]);

            $this->record_pattern_metrics($pattern_data, $job_id);
            $this->metrics->flush_batch_data(); // Explicitly flush metrics for testing
            
            return true; // Return true for success

        } catch (\Exception $e) {
            error_log('BloomIntegration::handle_bloom_pattern error: ' . $e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile());
            $this->handle_bloom_error($e);
            return false;
        }
    }

    public function sync_with_bloom() {
        if (!$this->is_available()) {
            return false;
        }

        try {
            $bloom_core = \BLOOM\Core::get_instance();
            $patterns = $bloom_core->get_recent_patterns();
            
            foreach ($patterns as $pattern) {
                $this->sync_pattern($pattern);
            }

            $this->update_sync_status('success');
            return true;

        } catch (\Exception $e) {
            $this->update_sync_status('error', $e->getMessage());
            return false;
        }
    }

    public function prepare_pattern_data($data) {
        // Convert APS format to BLOOM format if needed
        if (isset($data['aps_format']) && $data['aps_format']) {
            return $this->convert_to_bloom_format($data);
        }
        return $data;
    }

    private function convert_to_bloom_format($pattern_data) {
        return [
            'type' => $pattern_data['type'] ?? 'generic',
            'features' => $pattern_data['features'] ?? [],
            'metadata' => array_merge(
                $pattern_data['metadata'] ?? [],
                ['source' => 'aps']
            ),
            'confidence' => $pattern_data['confidence'] ?? 0,
            'timestamp' => time()
        ];
    }

    private function convert_from_bloom_format($bloom_pattern) {
        return [
            'type' => $bloom_pattern['type'] ?? 'generic', // Provide a default value
            'features' => $bloom_pattern['features'],
            'confidence' => $bloom_pattern['confidence'],
            'metadata' => array_merge(
                $bloom_pattern['metadata'] ?? [],
                ['bloom_processed' => true]
            ),
            'relationships' => $bloom_pattern['relationships'] ?? [],
            'timestamp' => $bloom_pattern['timestamp'] ?? time()
        ];
    }

    private function record_connection_status($status) {
        $this->metrics->record_metric('bloom_connection', $status['active'] ? 1 : 0, [
            'error' => $status['error'] ?? null,
            'version' => $status['version'] ?? 'unknown'
        ]);
    }

    private function record_analysis_metrics($result) {
        $this->metrics->record_metric('bloom_analysis', 1, [
            'confidence' => $result['confidence'],
            'type' => $result['type'],
            'processing_time' => $result['metadata']['processing_time'] ?? 0
        ]);
    }

    private function record_sync_metrics($result) {
        $this->metrics->record_metric('bloom_sync', 1, [
            'success' => $result['success'] ? 1 : 0,
            'patterns_synced' => count($result['patterns'] ?? []),
            'sync_time' => $result['sync_time'] ?? 0
        ]);
    }

    private function record_pattern_metrics($pattern_data, $job_id) {
        $this->metrics->record_metric(
            'pattern_received', // Metric type
            'bloom_pattern_received', // Metric name
            1, // Metric value (always 1 for a received pattern)
            [
                'pattern_type' => $pattern_data['type'] ?? 'unknown',
                'confidence' => $pattern_data['confidence'] ?? 0,
                'job_id' => $job_id
            ]
        );
    }

    private function handle_bloom_error(\Exception $e) {
        $this->alert_manager->trigger_alert('bloom_processing_error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        $this->metrics->record_metric('bloom_errors', 1, [
            'error_type' => get_class($e),
            'error_message' => $e->getMessage()
        ]);
    }

    private function update_sync_status($status, $error = null) {
        if (function_exists('update_option') && function_exists('current_time')) {
            update_option('aps_bloom_sync_status', [
                'status' => $status,
                'last_sync' => current_time('mysql'),
                'error' => $error
            ]);
        }

        if ($error) {
            $this->alert_manager->trigger_alert('bloom_sync_status', [
                'status' => $status,
                'error' => $error
            ]);
        }
    }

    public function get_integration_status() {
        return [
            'connected' => $this->is_connected,
            'error' => $this->connection_error,
            'last_sync' => function_exists('get_option') ? get_option('aps_bloom_sync_status') : null,
            'metrics' => $this->get_integration_metrics()
        ];
    }

    private function get_integration_metrics() {
        return [
            'patterns_processed' => $this->metrics->get_metric_sum('bloom_pattern_received'),
            'sync_success_rate' => $this->calculate_sync_success_rate(),
            'average_confidence' => $this->metrics->get_metric_average('bloom_analysis', 'confidence'),
            'error_rate' => $this->calculate_error_rate()
        ];
    }

    private function calculate_sync_success_rate() {
        $total_syncs = $this->metrics->get_metric_sum('bloom_sync');
        if (!$total_syncs) {
            return 0;
        }

        $successful_syncs = $this->metrics->get_metric_sum('bloom_sync', ['success' => 1]);
        return ($successful_syncs / $total_syncs) * 100;
    }

    private function calculate_error_rate() {
        $total_operations = $this->metrics->get_metric_sum('bloom_analysis');
        if (!$total_operations) {
            return 0;
        }

        $errors = $this->metrics->get_metric_sum('bloom_errors');
        return ($errors / $total_operations) * 100;
    }

    public function cleanup() {
        if (function_exists('wp_clear_scheduled_hook')) {
            wp_clear_scheduled_hook('aps_sync_bloom');
        }
    }
}