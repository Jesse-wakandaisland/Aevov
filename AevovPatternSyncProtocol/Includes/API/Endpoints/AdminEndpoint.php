<?php
/**
 * Admin API endpoints for plugin management
 * 
 * @package APS
 * @subpackage API\Endpoints
 */

namespace APS\API\Endpoints;

use APS\Core\Logger;
use APS\DB\MetricsDB;
use APS\DB\DBOptimizer;

class AdminEndpoint extends BaseEndpoint {
    protected $base = 'admin';
    private $logger;
    private $metrics;
    private $db_optimizer;

    public function __construct($namespace) {
        parent::__construct($namespace);
        $this->logger = new Logger();
        $this->metrics = new MetricsDB();
        $this->db_optimizer = new DBOptimizer();
    }

    public function register_routes() {
        // Plugin settings
        register_rest_route($this->namespace, '/' . $this->base . '/settings', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_settings'],
                'permission_callback' => [$this, 'check_admin_permission']
            ],
            [
                'methods' => \WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_settings'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => $this->get_settings_args()
            ]
        ]);

        // Database management
        register_rest_route($this->namespace, '/' . $this->base . '/db', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_db_status'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        register_rest_route($this->namespace, '/' . $this->base . '/db/optimize', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'optimize_database'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        // System maintenance
        register_rest_route($this->namespace, '/' . $this->base . '/maintenance', [
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'perform_maintenance'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => $this->get_maintenance_args()
            ]
        ]);

        // Logs management
        register_rest_route($this->namespace, '/' . $this->base . '/logs', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_logs'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => $this->get_logs_args()
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'clear_logs'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        // API key management
        register_rest_route($this->namespace, '/' . $this->base . '/api-keys', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_api_keys'],
                'permission_callback' => [$this, 'check_admin_permission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_api_key'],
                'permission_callback' => [$this, 'check_admin_permission']
            ],
            [
                'methods' => \WP_REST_Server::DELETABLE,
                'callback' => [$this, 'revoke_api_key'],
                'permission_callback' => [$this, 'check_admin_permission'],
                'args' => [
                    'key_id' => [
                        'required' => true,
                        'type' => 'string'
                    ]
                ]
            ]
        ]);

        // Plugin status
        register_rest_route($this->namespace, '/' . $this->base . '/system-info', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_system_info'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);

        // Queue management
        register_rest_route($this->namespace, '/' . $this->base . '/queue', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_queue_status'],
                'permission_callback' => [$this, 'check_admin_permission']
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'clear_queue'],
                'permission_callback' => [$this, 'check_admin_permission']
            ]
        ]);
    }

    public function get_settings($request) {
        try {
            return rest_ensure_response([
                'success' => true,
                'settings' => get_option('aps_settings', [])
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'settings_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function update_settings($request) {
        try {
            $settings = $request->get_params();
            update_option('aps_settings', $settings);
            
            return rest_ensure_response([
                'success' => true,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'settings_update_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_db_status($request) {
        try {
            return rest_ensure_response([
                'success' => true,
                'status' => $this->db_optimizer->get_db_status()
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'db_status_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function optimize_database($request) {
        try {
            $result = $this->db_optimizer->optimize_tables();
            
            return rest_ensure_response([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'optimization_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function perform_maintenance($request) {
        try {
            $tasks = $request['tasks'];
            $results = [];
            
            foreach ($tasks as $task) {
                $results[$task] = $this->execute_maintenance_task($task);
            }
            
            return rest_ensure_response([
                'success' => true,
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'maintenance_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_logs($request) {
        try {
            $logs = $this->logger->get_logs(
                $request['type'] ?? null,
                $request['start_date'] ?? null,
                $request['end_date'] ?? null,
                $request['limit'] ?? 100,
                $request['offset'] ?? 0
            );
            
            return rest_ensure_response([
                'success' => true,
                'logs' => $logs
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'logs_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function clear_logs($request) {
        try {
            $this->logger->clear_logs();
            
            return rest_ensure_response([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'logs_clear_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_api_keys($request) {
        try {
            return rest_ensure_response([
                'success' => true,
                'keys' => get_option('aps_api_keys', [])
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'keys_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function create_api_key($request) {
        try {
            $key = wp_generate_uuid4();
            $keys = get_option('aps_api_keys', []);
            $keys[] = $key;
            update_option('aps_api_keys', $keys);
            
            return rest_ensure_response([
                'success' => true,
                'key' => $key
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'key_creation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function revoke_api_key($request) {
        try {
            $key_id = $request['key_id'];
            $keys = get_option('aps_api_keys', []);
            $keys = array_filter($keys, function($key) use ($key_id) {
                return $key !== $key_id;
            });
            update_option('aps_api_keys', $keys);
            
            return rest_ensure_response([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'key_revocation_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_system_info($request) {
        try {
            global $wp_version;
            
            return rest_ensure_response([
                'success' => true,
                'info' => [
                    'wordpress_version' => $wp_version,
                    'php_version' => PHP_VERSION,
                    'plugin_version' => APS_VERSION,
                    'is_multisite' => is_multisite(),
                    'active_plugins' => get_option('active_plugins'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'max_input_vars' => ini_get('max_input_vars')
                ]
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'info_fetch_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function get_queue_status($request) {
        try {
            return rest_ensure_response([
                'success' => true,
                'status' => $this->get_queue_info()
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'queue_status_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    public function clear_queue($request) {
        try {
            global $wpdb;
            
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}aps_queue");
            
            return rest_ensure_response([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return new \WP_Error(
                'queue_clear_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    private function execute_maintenance_task($task) {
        switch ($task) {
            case 'cleanup_old_data':
                return $this->cleanup_old_data();
            case 'optimize_tables':
                return $this->db_optimizer->optimize_tables();
            case 'clear_cache':
                return $this->clear_plugin_cache();
            case 'rebuild_indices':
                return $this->rebuild_indices();
            default:
                throw new \Exception("Unknown maintenance task: {$task}");
        }
    }

    protected function get_settings_args() {
        return [
            'sync_interval' => ['type' => 'integer'],
            'batch_size' => ['type' => 'integer'],
            'log_level' => ['type' => 'string'],
            'cache_lifetime' => ['type' => 'integer'],
            'api_rate_limit' => ['type' => 'integer']
        ];
    }

    protected function get_maintenance_args() {
        return [
            'tasks' => [
                'required' => true,
                'type' => 'array',
                'items' => [
                    'type' => 'string',
                    'enum' => [
                        'cleanup_old_data',
                        'optimize_tables',
                        'clear_cache',
                        'rebuild_indices'
                    ]
                ]
            ]
        ];
    }

    protected function get_logs_args() {
        return [
            'type' => ['type' => 'string'],
            'start_date' => ['type' => 'string', 'format' => 'date-time'],
            'end_date' => ['type' => 'string', 'format' => 'date-time'],
            'limit' => ['type' => 'integer', 'default' => 100],
            'offset' => ['type' => 'integer', 'default' => 0]
        ];
    }

    private function cleanup_old_data() {
        $this->metrics->cleanup_old_metrics();
        $this->logger->cleanup_old_logs();
        return true;
    }

    private function clear_plugin_cache() {
        wp_cache_delete_group('aps_metrics');
        wp_cache_delete_group('aps_patterns');
        return true;
    }

    private function rebuild_indices() {
        global $wpdb;
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}aps_patterns");
        $wpdb->query("ANALYZE TABLE {$wpdb->prefix}aps_metrics");
        return true;
    }

    private function get_queue_info() {
        global $wpdb;
        
        return [
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aps_queue WHERE status = 'pending'"),
            'processing' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aps_queue WHERE status = 'processing'"),
            'completed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aps_queue WHERE status = 'completed'"),
            'failed' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}aps_queue WHERE status = 'failed'")
        ];
    }
}