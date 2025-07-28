<?php
/**
 * Handles REST API endpoints and routing
 */
namespace BLOOM\API;

use BLOOM\Models\PatternModel;
use BLOOM\Monitoring\SystemMonitor;
use BLOOM\Processing\TensorProcessor;
use BLOOM\Monitoring\DataValidator;
use WP_Error;
use WP_REST_Response;

class ApiController {
    private $authenticator;
    private $rate_limiter;
    private $validator;
    private $pattern_model;
    private $system_monitor;
    private $tensor_processor;

    public function __construct() {
        $this->authenticator = new ApiAuthenticator();
        $this->rate_limiter = new ApiRateLimiter();
        $this->validator = new DataValidator();
        $this->pattern_model = new PatternModel();
        $this->system_monitor = new SystemMonitor();
        $this->tensor_processor = new TensorProcessor();

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('bloom/v1', '/patterns', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_patterns'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_patterns_args()
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_pattern'],
                'permission_callback' => [$this, 'check_permission'],
                'args' => $this->get_pattern_creation_args()
            ]
        ]);

        register_rest_route('bloom/v1', '/system/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_system_status'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('bloom/v1', '/process', [
            'methods' => 'POST',
            'callback' => [$this, 'process_tensor'],
            'permission_callback' => [$this, 'check_permission'],
            'args' => $this->get_process_args()
        ]);
    }

    public function get_patterns($request) {
        $params = $request->get_params();
        $patterns = $this->pattern_model->get_patterns($params);
        return new WP_REST_Response($patterns);
    }

    public function create_pattern($request) {
        $params = $request->get_params();
        $pattern = $this->pattern_model->create_pattern($params);
        return new WP_REST_Response($pattern, 201);
    }

    public function get_system_status($request) {
        $status = $this->system_monitor->get_system_health();
        return new WP_REST_Response($status);
    }

    public function process_tensor($request) {
        $params = $request->get_params();
        $job_id = $this->tensor_processor->process_tensor($params['tensor_data']);
        return new WP_REST_Response(['job_id' => $job_id], 202);
    }

    private function check_permission($request) {
        if (!$this->authenticator->verify_request($request)) {
            return false;
        }

        if (!$this->rate_limiter->check_limit($request)) {
            return false;
        }

        return true;
    }

    private function get_patterns_args() {
        return [
            'page' => ['default' => 1, 'sanitize_callback' => 'absint'],
            'per_page' => ['default' => 20, 'sanitize_callback' => 'absint'],
            'type' => ['type' => 'string', 'enum' => ['sequential', 'structural', 'statistical']],
            'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1]
        ];
    }

    private function get_pattern_creation_args() {
        return [
            'type' => ['required' => true, 'type' => 'string'],
            'features' => ['required' => true, 'type' => 'object'],
            'tensor_sku' => ['required' => true, 'type' => 'string']
        ];
    }

    private function get_process_args() {
        return [
            'tensor_data' => ['required' => true, 'type' => 'object']
        ];
    }
}