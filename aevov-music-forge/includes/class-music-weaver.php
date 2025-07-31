<?php

namespace AevovMusicForge;

class MusicWeaver {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function get_pattern_ids( $params ) {
        $catalog = new \AevovNeuroArchitect\NeuralPatternCatalog();
        $patterns = [];
        if ( isset( $params['genre'] ) ) {
            $patterns = array_merge( $patterns, $catalog->get_patterns_by_type( 'music-genre-' . $params['genre'] ) );
        }
        if ( isset( $params['mood'] ) ) {
            $patterns = array_merge( $patterns, $catalog->get_patterns_by_type( 'music-mood-' . $params['mood'] ) );
        }
        $pattern_ids = wp_list_pluck( $patterns, 'id' );
        return $pattern_ids;
    }
}
