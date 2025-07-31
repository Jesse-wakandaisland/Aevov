<?php

namespace AevovSuperAppForge\API;

use AevovSuperAppForge\AppIngestionEngine;
use AevovSuperAppForge\SuperAppWeaver;

class ApplicationEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-super-app/v1', '/weave', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'weave_application' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function weave_application( $request ) {
        $url = $request->get_param( 'url' );
        if ( empty( $url ) ) {
            return new \WP_REST_Response( [ 'error' => 'URL is required.' ], 400 );
        }

        $ingestion_engine = new AppIngestionEngine();
        $uad = $ingestion_engine->ingest_app( $url );

        if ( isset( $uad['error'] ) ) {
            return new \WP_REST_Response( $uad, 400 );
        }

        $weaver = new SuperAppWeaver();
        $result = $weaver->weave_app( $uad );

        if ( isset( $result['error'] ) ) {
            return new \WP_REST_Response( $result, 500 );
        }

        return new \WP_REST_Response( $result );
    }
}
