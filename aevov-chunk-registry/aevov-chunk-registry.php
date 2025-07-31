<?php
/*
Plugin Name: Aevov Chunk Registry
Plugin URI:
Description: A central registry for all Aevov chunks.
Version: 1.0.0
Author: Aevov
Author URI:
License: GPL2
Text Domain: aevov-chunk-registry
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovChunkRegistry {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        $this->include_dependencies();
        new \AevovChunkRegistry\ChunkRegistry();
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-aevov-chunk.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-chunk-registry.php';
    }
}

new AevovChunkRegistry();
