<?php

namespace AevovMusicForge\API;

use AevovMusicForge\MusicWeaver;
use AevovMusicForge\JobManager;

class MusicEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-music/v1', '/compose', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'compose_music' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-music/v1', '/status/(?P<job_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_status' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-music/v1', '/track/(?P<job_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_track' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function compose_music( $request ) {
        $params = $request->get_params();
        $job_manager = new JobManager();
        $job_id = $job_manager->create_job( $params );

        // This is where we would trigger the backend worker process.
        // For now, I'll just return the job ID.
        return new \WP_REST_Response( [ 'job_id' => $job_id ] );
    }

    public function get_status( $request ) {
        $job_id = $request['job_id'];
        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );
        $response = new \WP_REST_Response( [ 'status' => $job['status'] ] );
        $response->set_headers( [
            'Cache-Control' => 'no-cache',
        ] );
        return $response;
    }

    public function get_track( $request ) {
        $job_id = $request['job_id'];
        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );

        if ( $job['status'] !== 'complete' ) {
            return new \WP_Error( 'track_not_ready', 'Track is not ready yet.', [ 'status' => 404 ] );
        }

        // This is where we would get the pre-signed URL for the track.
        // For now, I'll just redirect to a placeholder track.
        wp_redirect( $job['track_url'] );
        exit;
    }
}
