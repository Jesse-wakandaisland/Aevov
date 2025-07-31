<?php

namespace AevovLanguageEngineV2\API;

use AevovLanguageEngineV2\LanguageWeaver;

class LanguageEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-language/v2', '/generate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'generate_text' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function generate_text( $request ) {
        $prompt = $request->get_param( 'prompt' );
        $weaver = new LanguageWeaver();
        $playlist = $weaver->get_playlist( $prompt );
        return new \WP_REST_Response( $playlist );
    }
}
