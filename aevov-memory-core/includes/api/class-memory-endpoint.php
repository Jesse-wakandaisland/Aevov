<?php

namespace AevovMemoryCore\API;

use AevovMemoryCore\MemoryManager;

class MemoryEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-memory/v1', '/memory', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'write_to_memory' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-memory/v1', '/memory/(?P<address>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'read_from_memory' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function write_to_memory( $request ) {
        $address = $request->get_param( 'address' );
        $data = $request->get_param( 'data' );
        $memory_manager = new MemoryManager();
        $result = $memory_manager->write_to_memory( $address, $data );
        return new \WP_REST_Response( [ 'success' => $result ] );
    }

    public function read_from_memory( $request ) {
        $address = $request['address'];
        $memory_manager = new MemoryManager();
        $data = $memory_manager->read_from_memory( $address );
        return new \WP_REST_Response( [ 'data' => $data ] );
    }
}
