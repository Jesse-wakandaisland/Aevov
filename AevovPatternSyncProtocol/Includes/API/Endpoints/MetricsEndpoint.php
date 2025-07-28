<?php
/**
 * Metrics API endpoints
 * Handles metrics data retrieval and aggregation
 * 
 * @package APS
 * @subpackage API\Endpoints
 */

namespace APS\API\Endpoints;

use APS\DB\MetricsDB;
use APS\Monitoring\SystemMonitor;

class MetricsEndpoint extends BaseEndpoint {
    protected $base = 'metrics';
    private $metrics;
    private $system_monitor;
    private $cache_duration = 60; // 1 minute

    public function __construct($namespace) {
        parent::__construct($namespace);
        $this->metrics = new MetricsDB();
        $this->system_monitor = new SystemMonitor();
    }

    public function register_routes() {
        // Get all metrics
        register_rest_route($this->namespace, '/' . $this->base, [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_metrics'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_collection_params()
            ]
        ]);

        // Get specific metric type
        register_rest_route($this->namespace, '/' . $this->base . '/(?P<type>[a-zA-Z_]+)', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_metric_type'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => array_merge(
                    $this->get_collection_params(),
                    [
                        'type' => [
                            'required' => true,
                            'type' => 'string',
                            'enum' => [
                                'system',
                                'pattern',
                                'network',
                                'performance',
                                'integration'
                            ]
                        ]
                    ]
                )
            ]
        ]);

        // Aggregate metrics
        register_rest_route($this->namespace, '/' . $this->base . '/aggregate', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_aggregated_metrics'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_aggregation_params()
            ]
        ]);

        // Get metric trends
        register_rest_route($this->namespace, '/' . $this->base . '/trends', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_metric_trends'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => $this->get_trend_params()
            ]
        ]);

        // Get metric statistics
        register_rest_route($this->namespace, '/' . $this->base . '/stats', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_metric_stats'],
                'permission_callback' => [$this, 'check_read_permission']
            ]
        ]);

        // Export metrics
        register_rest_route($this->namespace, '/' . $this->base . '/export', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'export_metrics'],
                'permission_callback' => [$this, 'check_write_permission'],
                'args' => $this->get_export_params()
            ]
        ]);

        // Record custom metric
        register_rest_route($this->namespace, '/' . $this->base . '/record', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'record_metric'],
                'permission_callback' => [$this, 'check_write_permission'],
                'args' => $this->get_record_params()
            ]
        ]);
    }

    public function get_metrics($request) {
        try {
            $params = $this->prepare_query_params($request);
            $metrics = $this->get_cached_metrics($params);

            if ($metrics === false) {
                $metrics = $this->metrics->get_metrics($params);
                $this->cache_metrics($params, $metrics);
            }

            return rest_ensure_response([
                'success' => true,
                'metrics' => $metrics,
                'params' => $params
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'metrics_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_metric_type($request) {
        try {
            $type = $request['type'];
            $params = $this->prepare_query_params($request);
            
            $metrics = $this->get_cached_metrics(['type' => $type] + $params);

            if ($metrics === false) {
                $metrics = $this->metrics->get_metrics_by_type($type, $params);
                $this->cache_metrics(['type' => $type] + $params, $metrics);
            }

            return rest_ensure_response([
                'success' => true,
                'type' => $type,
                'metrics' => $metrics,
                'summary' => $this->calculate_metrics_summary($metrics)
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'metric_type_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_aggregated_metrics($request) {
        try {
            $params = [
                'interval' => $request['interval'],
                'start_date' => $request['start_date'],
                'end_date' => $request['end_date'],
                'type' => $request['type'],
                'function' => $request['function']
            ];

            $aggregated = $this->metrics->aggregate_metrics($params);

            return rest_ensure_response([
                'success' => true,
                'aggregated' => $aggregated,
                'params' => $params
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'aggregation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_metric_trends($request) {
        try {
            $trends = $this->metrics->calculate_trends([
                'metrics' => $request['metrics'],
                'period' => $request['period'],
                'comparison' => $request['comparison']
            ]);

            return rest_ensure_response([
                'success' => true,
                'trends' => $trends
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'trend_calculation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_metric_stats($request) {
        try {
            $stats = $this->metrics->get_statistics();
            
            return rest_ensure_response([
                'success' => true,
                'stats' => $stats,
                'system_status' => $this->system_monitor->get_metrics_status()
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'stats_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function export_metrics($request) {
        try {
            $format = $request['format'] ?? 'json';
            $params = $this->prepare_query_params($request);
            
            $metrics = $this->metrics->get_metrics($params);
            $exported = $this->format_export($metrics, $format);

            return rest_ensure_response([
                'success' => true,
                'data' => $exported,
                'format' => $format
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'export_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function record_metric($request) {
        try {
            $recorded = $this->metrics->record_metric(
                $request['type'],
                $request['name'],
                $request['value'],
                $request['dimensions'] ?? null
            );

            return rest_ensure_response([
                'success' => true,
                'recorded' => $recorded
            ]);

        } catch (\Exception $e) {
            return new \WP_Error(
                'metric_recording_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    protected function get_collection_params() {
        return [
            'start_date' => [
                'type' => 'string',
                'format' => 'date-time'
            ],
            'end_date' => [
                'type' => 'string',
                'format' => 'date-time'
            ],
            'type' => [
                'type' => 'string'
            ],
            'name' => [
                'type' => 'string'
            ],
            'dimensions' => [
                'type' => 'object'
            ],
            'limit' => [
                'type' => 'integer',
                'minimum' => 1,
                'maximum' => 1000,
                'default' => 100
            ],
            'order' => [
                'type' => 'string',
                'enum' => ['asc', 'desc'],
                'default' => 'desc'
            ]
        ];
    }

    protected function get_aggregation_params() {
        return [
            'interval' => [
                'type' => 'string',
                'required' => true,
                'enum' => ['minute', 'hour', 'day', 'week', 'month']
            ],
            'function' => [
                'type' => 'string',
                'enum' => ['avg', 'sum', 'min', 'max', 'count'],
                'default' => 'avg'
            ]
        ];
    }

    protected function get_trend_params() {
        return [
            'metrics' => [
                'type' => 'array',
                'required' => true,
                'items' => [
                    'type' => 'string'
                ]
            ],
            'period' => [
                'type' => 'string',
                'enum' => ['hour', 'day', 'week', 'month'],
                'default' => 'day'
            ],
            'comparison' => [
                'type' => 'string',
                'enum' => ['previous_period', 'previous_year'],
                'default' => 'previous_period'
            ]
        ];
    }

    private function prepare_query_params($request) {
        return array_filter([
            'start_date' => $request['start_date'] ?? null,
            'end_date' => $request['end_date'] ?? null,
            'type' => $request['type'] ?? null,
            'name' => $request['name'] ?? null,
            'dimensions' => $request['dimensions'] ?? null,
            'limit' => $request['limit'] ?? 100,
            'order' => $request['order'] ?? 'desc'
        ]);
    }

    private function get_cached_metrics($params) {
        $cache_key = 'metrics_' . md5(serialize($params));
        return wp_cache_get($cache_key, 'aps_metrics');
    }

    private function cache_metrics($params, $metrics) {
        $cache_key = 'metrics_' . md5(serialize($params));
        wp_cache_set($cache_key, $metrics, 'aps_metrics', $this->cache_duration);
    }

    private function calculate_metrics_summary($metrics) {
        if (empty($metrics)) {
            return [];
        }

        $values = array_column($metrics, 'value');
        return [
            'count' => count($metrics),
            'min' => min($values),
            'max' => max($values),
            'avg' => array_sum($values) / count($values),
            'last_value' => end($values)
        ];
    }

    private function format_export($metrics, $format) {
        switch ($format) {
            case 'csv':
                return $this->format_csv($metrics);
            case 'json':
                return json_encode($metrics);
            default:
                throw new \Exception('Unsupported export format');
        }
    }

    private function format_csv($metrics) {
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, ['timestamp', 'type', 'name', 'value', 'dimensions']);
        
        // Data
        foreach ($metrics as $metric) {
            fputcsv($output, [
                $metric['timestamp'],
                $metric['type'],
                $metric['name'],
                $metric['value'],
                json_encode($metric['dimensions'])
            ]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}