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
        $cubbit_key = $wpdb->get_var( $wpdb->prepare( "SELECT data FROM $this->table_name WHERE address = %s", $address ) );

        if ( ! $cubbit_key ) {
            return null;
        }

        if ( ! class_exists( 'AevovCubbitCDN' ) ) {
            $cdn_plugin_path = WP_PLUGIN_DIR . '/aevov-cubbit-cdn/aevov-cubbit-cdn.php';
            if ( file_exists( $cdn_plugin_path ) ) {
                require_once $cdn_plugin_path;
            } else {
                return null;
            }
        }

        $cdn_plugin = new \AevovCubbitCDN();
        $presigned_url = $cdn_plugin->generate_cubbit_presigned_url( $cubbit_key );

        return $presigned_url;
    }

    public function write_to_memory( $address, $data ) {
        global $wpdb;

        if ( ! class_exists( 'CubbitDirectoryManager' ) ) {
            $cubbit_plugin_path = WP_PLUGIN_DIR . '/Cubbit DS3/Cubbit Directory Manager Extension/cubbit-directory-manager-extension.php';
            if ( file_exists( $cubbit_plugin_path ) ) {
                require_once $cubbit_plugin_path;
            } else {
                return false;
            }
        }

        $cubbit_manager = new \CubbitDirectoryManager();
        $temp_dir = get_temp_dir();
        $temp_file = wp_tempnam( 'memory', $temp_dir );
        file_put_contents( $temp_file, json_encode( $data ) );
        $cubbit_key = 'memory/' . $address . '.json';
        $upload_result = $cubbit_manager->upload_file( $temp_file, $cubbit_key, 'application/json', 'private' );
        unlink( $temp_file );

        if ( ! $upload_result ) {
            return false;
        }

        $wpdb->replace(
            $this->table_name,
            [
                'address' => $address,
                'data' => $cubbit_key,
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
