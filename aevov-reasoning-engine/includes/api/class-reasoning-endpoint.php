<?php

namespace AevovReasoningEngine\API;

use AevovPatternSyncProtocol\Comparison\APS_Comparator;

class ReasoningEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-reasoning/v1', '/infer', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'make_inference' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-reasoning/v1', '/find-analogy', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'find_analogy' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function make_inference( $request ) {
        $pattern = $request->get_param( 'pattern' );
        $comparator = new APS_Comparator();
        $analogous_patterns = $comparator->find_analogous_patterns( $pattern );

        // This is a placeholder.
        // In a real implementation, this would use the analogous patterns
        // to make an inference.
        $inference = 'This is a placeholder inference.';

        return new \WP_REST_Response( [ 'inference' => $inference ] );
    }

    public function find_analogy( $request ) {
        $pattern = $request->get_param( 'pattern' );
        $comparator = new APS_Comparator();
        $analogous_patterns = $comparator->find_analogous_patterns( $pattern );
        return new \WP_REST_Response( [ 'analogous_patterns' => $analogous_patterns ] );
    }
}
