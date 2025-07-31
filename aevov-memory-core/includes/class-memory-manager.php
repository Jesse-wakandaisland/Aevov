<?php

namespace AevovMemoryCore;

class MemoryManager {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aevov_memory_data';
    }

    public function read_from_memory( $address ) {
        global $wpdb;
        $data = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM $this->table_name WHERE address = %s", $address ) );
        return $data ? json_decode( $data, true ) : null;
    }

    public function write_to_memory( $address, $data ) {
        global $wpdb;
        $wpdb->replace(
            $this->table_name,
            [
                'address' => $address,
                'data' => json_encode( $data ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ]
        );
        return true;
    }

    public function send_calcium_signal( $target, $payload ) {
        do_action( 'aevov_calcium_signal', $target, $payload );
        return true;
    }

    public function send_gliotransmitter_signal( $target, $payload ) {
        $response = apply_filters( 'aevov_gliotransmitter_signal', null, $target, $payload );
        return $response;
    }
}
