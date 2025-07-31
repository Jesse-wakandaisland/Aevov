<?php

namespace AevovStream\API;

use AevovStream\PlaylistGenerator;
use AevovStream\SessionManager;
use AevovCubbitCDN;

class StreamEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-stream/v1', '/start-session', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'start_session' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-stream/v1', '/playlist/(?P<session_id>[a-zA-Z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_playlist' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'aevov-stream/v1', '/chunk/(?P<chunk_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_chunk' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function start_session( $request ) {
        $params = $request->get_params();
        $session_manager = new SessionManager();
        $session_id = $session_manager->create_session( $params );

        if ( ! class_exists( 'APS\DB\APS_Pattern_DB' ) ) {
            $pattern_db_path = WP_PLUGIN_DIR . '/AevovPatternSyncProtocol/Includes/DB/APS_Pattern_DB.php';
            if ( file_exists( $pattern_db_path ) ) {
                require_once $pattern_db_path;
            } else {
                return new \WP_Error( 'dependency_missing', 'APS_Pattern_DB class not found.' );
            }
        }

        $pattern_db = new \APS\DB\APS_Pattern_DB();
        $patterns = $pattern_db->get_patterns_by_type( 'tensor_pattern' );
        $pattern_ids = wp_list_pluck( $patterns, 'id' );

        $playlist_generator = new PlaylistGenerator();
        $playlist = $playlist_generator->generate( $pattern_ids );

        // We would store the playlist in the session.
        // $session_manager->update_session( $session_id, [ 'playlist' => $playlist ] );

        return new \WP_REST_Response( [ 'session_id' => $session_id, 'playlist_url' => site_url( '/wp-json/aevov-stream/v1/playlist/' . $session_id ) ] );
    }

    public function get_playlist( $request ) {
        $session_id = $request['session_id'];
        $session_manager = new SessionManager();
        $session = $session_manager->get_session( $session_id );

        // This is where we would get the playlist from the session.
        // For now, I'll just generate a dummy playlist.
        $pattern_ids = [ 1, 2, 3, 4, 5 ];
        $playlist_generator = new PlaylistGenerator();
        $playlist = $playlist_generator->generate( $pattern_ids );

        $response = new \WP_REST_Response( $playlist );
        $response->set_headers( [
            'Content-Type' => 'application/vnd.apple.mpegurl',
            'Cache-Control' => 'public, max-age=3600',
        ] );

        return $response;
    }

    public function get_chunk( $request ) {
        $chunk_id = $request['chunk_id'];

        if ( ! class_exists( 'AevovCubbitCDN' ) ) {
            return new \WP_Error( 'dependency_missing', 'AevovCubbitCDN plugin is not active.' );
        }

        $cdn_plugin = new AevovCubbitCDN();
        $cubbit_key = $cdn_plugin->get_cubbit_key_for_chunk( $chunk_id );

        if ( is_wp_error( $cubbit_key ) ) {
            return $cubbit_key;
        }

        $presigned_url = $cdn_plugin->generate_cubbit_presigned_url( $cubbit_key );

        if ( is_wp_error( $presigned_url ) ) {
            return $presigned_url;
        }

        // Redirect to the pre-signed URL.
        wp_redirect( $presigned_url );
        exit;
    }
}
