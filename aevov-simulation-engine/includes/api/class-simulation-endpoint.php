<?php

namespace AevovSimulationEngine\API;

use AevovSimulationEngine\SimulationWeaver;
use AevovSimulationEngine\JobManager;

class SimulationEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-sim/v1', '/start', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'start_simulation' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-sim/v1', '/stop/(?P<job_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'stop_simulation' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-sim/v1', '/interact/(?P<job_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'interact_with_simulation' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-sim/v1', '/visualize', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'visualize_model' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-sim/v1', '/visualize-memory', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'visualize_memory' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function start_simulation( $request ) {
        $params = $request->get_params();
        $job_manager = new JobManager();
        $job_id = $job_manager->create_job( $params );

        // This is where we would trigger the backend worker process.
        // For now, I'll just return the job ID and a dummy WebSocket URL.
        return new \WP_REST_Response( [ 'job_id' => $job_id, 'websocket_url' => 'ws://localhost:8080' ] );
    }

    public function stop_simulation( $request ) {
        $job_id = $request['job_id'];
        $job_manager = new JobManager();
        $job_manager->delete_job( $job_id );
        return new \WP_REST_Response( [ 'status' => 'stopped' ] );
    }

    public function interact_with_simulation( $request ) {
        $job_id = $request['job_id'];
        $params = $request->get_params();

        // This is where we would send the interaction to the backend worker.
        // For now, I'll just return a success message.
        return new \WP_REST_Response( [ 'status' => 'interaction_received' ] );
    }

    public function visualize_model( $request ) {
        $model = $request->get_param( 'model' );
        $engine = new \AevovSimulationEngine\AevovSimulationEngine();
        $visualization = $engine->render_virtual_brain( $model );
        return new \WP_REST_Response( [ 'visualization' => $visualization ] );
    }

    public function visualize_memory( $request ) {
        $memory_system = $request->get_param( 'memory_system' );
        $engine = new \AevovSimulationEngine\AevovSimulationEngine();
        $visualization = $engine->render_virtual_hippocampus( $memory_system );
        return new \WP_REST_Response( [ 'visualization' => $visualization ] );
    }
}
