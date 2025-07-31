<?php

namespace AevovPlayground\API;

class PlaygroundEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-playground/v1', '/proxy', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'proxy_request' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-playground/v1', '/save-pattern', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_pattern' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function proxy_request( $request ) {
        $engine = $request->get_param( 'engine' );
        $payload = $request->get_param( 'payload' );
        $pattern_jitter = $request->get_param( 'pattern_jitter' );
        $cross_modal_synesthesia = $request->get_param( 'cross_modal_synesthesia' );

        switch ( $engine ) {
            case 'language':
                $endpoint = '/aevov-language/v2/generate';
                break;
            case 'image':
                $endpoint = '/aevov-image/v1/generate';
                break;
            case 'music':
                $endpoint = '/aevov-music/v1/compose';
                break;
            case 'stream':
                $endpoint = '/aevov-stream/v1/start-session';
                break;
            case 'application':
                $endpoint = '/aevov-app/v1/spawn';
                break;
            default:
                return new \WP_Error( 'invalid_engine', 'Invalid engine.', [ 'status' => 400 ] );
        }

        $payload['pattern_jitter'] = $pattern_jitter;
        $payload['cross_modal_synesthesia'] = $cross_modal_synesthesia;

        $request = new \WP_REST_Request( 'POST', $endpoint );
        $request->set_body_params( $payload );
        $response = rest_do_request( $request );

        return $response;
    }

    public function save_pattern( $request ) {
        $workflow = $request->get_param( 'workflow' );
        $pattern_id = 'pattern-' . uniqid();

        // This is a placeholder.
        // In a real implementation, this would save the workflow as a new pattern.

        return new \WP_REST_Response( [ 'pattern_id' => $pattern_id ] );
    }
}
