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

        if (empty($analogous_patterns)) {
            return new \WP_REST_Response( [ 'inference' => 'No analogous patterns found.' ] );
        }

        // Find the best matching pattern
        $best_match = null;
        $highest_score = -1;

        foreach ($analogous_patterns as $analogous_pattern) {
            $comparison_result = $comparator->compare_patterns([$pattern, $analogous_pattern]);
            if ($comparison_result['score'] > $highest_score) {
                $highest_score = $comparison_result['score'];
                $best_match = $analogous_pattern;
            }
        }

        // Generate an inference based on the best match
        $inference = 'Based on the best matching pattern, the inference is: ' . json_encode($best_match['features']);

        return new \WP_REST_Response( [ 'inference' => $inference ] );
    }

    public function find_analogy( $request ) {
        $pattern = $request->get_param( 'pattern' );
        $comparator = new APS_Comparator();
        $analogous_patterns = $comparator->find_analogous_patterns( $pattern );
        return new \WP_REST_Response( [ 'analogous_patterns' => $analogous_patterns ] );
    }
}
