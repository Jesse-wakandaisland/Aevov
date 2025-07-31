<?php

namespace AevovEmbeddingEngine\API;

use AevovEmbeddingEngine\EmbeddingManager;

class EmbeddingEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-embedding/v1', '/embed', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'embed_data' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function embed_data( $request ) {
        $data = $request->get_param( 'data' );
        $manager = new EmbeddingManager();
        $embedding_chunk = $manager->embed( $data );

        // This is where we would register the chunk in the Aevov Chunk Registry.
        // For now, I'll just return the chunk.
        return new \WP_REST_Response( $embedding_chunk );
    }
}
