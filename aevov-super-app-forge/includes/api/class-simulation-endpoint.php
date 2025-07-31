<?php

namespace AevovSuperAppForge\API;

use AevovSuperAppForge\SuperAppWeaver;

class SimulationEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-super-app/v1', '/simulate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'simulate_generation' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function simulate_generation( $request ) {
        $url = $request->get_param( 'url' );
        if ( empty( $url ) ) {
            return new \WP_REST_Response( [ 'error' => 'URL is required.' ], 400 );
        }

        $ingestion_engine = new \AevovSuperAppForge\AppIngestionEngine();
        $uad = $ingestion_engine->ingest_app( $url );

        if ( isset( $uad['error'] ) ) {
            return new \WP_REST_Response( $uad, 400 );
        }

        $weaver = new \AevovSuperAppForge\SuperAppWeaver();
        $events = $weaver->simulate_generation( $uad );

        return new \WP_REST_Response( $events );
    }
}
