<?php

namespace AevovTranscriptionEngine\API;

use AevovTranscriptionEngine\TranscriptionManager;

class TranscriptionEndpoint {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        register_rest_route( 'aevov-transcription/v1', '/transcribe', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'transcribe_audio' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function transcribe_audio( $request ) {
        $audio_file = $request->get_file_params()['audio'];
        $manager = new TranscriptionManager();
        $transcription_chunk = $manager->transcribe( $audio_file );

        // This is where we would register the chunk in the Aevov Chunk Registry.
        // For now, I'll just return the chunk.
        return new \WP_REST_Response( $transcription_chunk );
    }
}
