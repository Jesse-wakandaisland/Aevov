<?php

namespace AevovImageEngine;

class ImageWeaver {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function get_pattern_ids( $params ) {
        $catalog = new \AevovNeuroArchitect\NeuralPatternCatalog();
        $patterns = [];
        if ( isset( $params['prompt'] ) ) {
            // This is a placeholder for a more sophisticated prompt analysis.
            $keywords = explode( ' ', $params['prompt'] );
            foreach ( $keywords as $keyword ) {
                $patterns = array_merge( $patterns, $catalog->get_patterns_by_type( 'image-tag-' . $keyword ) );
            }
        }
        $pattern_ids = wp_list_pluck( $patterns, 'id' );
        return $pattern_ids;
    }
}
