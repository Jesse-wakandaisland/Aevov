<?php

namespace AevovNeuroArchitect;

class NeuralPatternCatalog {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aevov_neural_patterns';
    }

    public function add_pattern( $pattern_id, $model_source, $pattern_type, $metadata ) {
        global $wpdb;
        $wpdb->insert(
            $this->table_name,
            [
                'pattern_id' => $pattern_id,
                'model_source' => $model_source,
                'pattern_type' => $pattern_type,
                'metadata' => json_encode( $metadata ),
                'created_at' => current_time( 'mysql' ),
            ]
        );
    }

    public function get_pattern( $pattern_id ) {
        global $wpdb;
        $pattern = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE pattern_id = %s", $pattern_id ) );
        if ( $pattern ) {
            $pattern->metadata = json_decode( $pattern->metadata, true );
        }
        return $pattern;
    }
}
