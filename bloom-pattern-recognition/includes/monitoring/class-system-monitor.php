<?php
/**
 * Handles system monitoring and health checks
 */
// includes/monitoring/class-system-monitor.php
namespace BLOOM\Monitoring;

class SystemMonitor {
    private $metrics_collector;
    private $alert_thresholds;

    public function __construct() {
        $this->metrics_collector = new MetricsCollector();
        $this->init_thresholds();
        $this->init_monitoring();
    }

    public function init_monitoring() {
        add_action('bloom_system_check', [$this, 'perform_system_check']);
        add_action('bloom_health_alert', [$this, 'process_health_alert']);
        wp_schedule_event(time(), 'minute', 'bloom_system_check');
    }

    public function perform_system_check() {
        $system_state = $this->metrics_collector->collect_system_metrics();
        $health_status = $this->check_system_health($system_state);
        $this->process_health_status($health_status);
        return $health_status;
    }

    public function get_system_health() {
        $system_state = $this->metrics_collector->collect_system_metrics();
        return $this->check_system_health($system_state);
    }

    private function check_system_health($state) {
        // Basic health check implementation
        $health_status = [
            'active' => true,
            'error' => null,
            'version' => '1.0.0',
            'timestamp' => time(),
            'components' => [
                'core' => 'active',
                'network' => 'active',
                'processing' => 'active',
                'storage' => 'active'
            ],
            'metrics' => $state ?? []
        ];

        // Check for any critical issues
        if (isset($state['error_rate']) && $state['error_rate'] > $this->alert_thresholds['error_rate']) {
            $health_status['active'] = false;
            $health_status['error'] = 'High error rate detected';
        }

        return $health_status;
    }

    private function init_thresholds() {
        $this->alert_thresholds = [
            'cpu' => 0.85,
            'memory' => 0.85,
            'disk' => 0.90,
            'queue_size' => 1000,
            'error_rate' => 0.05
        ];
    }
}
