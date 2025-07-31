<?php

namespace AevovNeuroArchitect;

class NeuralPatternCatalog {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aevov_neural_patterns';
    }

    public function add_pattern( $pattern_id, $model_source, $pattern_type, $metadata ) {
        $post_id = wp_insert_post( [
            'post_title'   => $pattern_id,
            'post_type'    => 'astrocyte',
            'post_status'  => 'publish',
        ] );
        add_post_meta( $post_id, 'model_source', $model_source );
        add_post_meta( $post_id, 'pattern_type', $pattern_type );
        add_post_meta( $post_id, 'metadata', $metadata );
    }

    public function get_memory_patterns( $type ) {
        $args = [
            'post_type' => 'astrocyte',
            'meta_query' => [
                [
                    'key' => 'pattern_type',
                    'value' => $type,
                ]
            ]
        ];
        $posts = get_posts( $args );
        $memory_patterns = [];
        foreach ( $posts as $post ) {
            $metadata = get_post_meta( $post->ID, 'metadata', true );
            $memory_patterns[] = new \AevovMemoryCore\MemoryPattern(
                $post->post_title,
                get_post_meta( $post->ID, 'pattern_type', true ),
                $metadata['capacity'],
                $metadata['decay_rate'],
                $metadata
            );
        }
        return $memory_patterns;
    }

    public function get_pattern( $pattern_id ) {
        global $wpdb;
        $pattern = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE pattern_id = %s", $pattern_id ) );
        if ( $pattern ) {
            $pattern->metadata = json_decode( $pattern->metadata, true );
        }
        return $pattern;
    }

    public function get_patterns_by_type( $type ) {
        global $wpdb;
        $patterns = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE pattern_type = %s", $type ) );
        return $patterns;
    }
}
