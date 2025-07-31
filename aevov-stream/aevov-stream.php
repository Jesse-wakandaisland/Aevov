<?php
/*
Plugin Name: Aevov Stream
Plugin URI:
Description: Real-time streaming of Aevov patterns.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-stream
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovStream {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        $this->include_dependencies();
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_shortcode( 'aevov_stream_player', [ $this, 'render_stream_player' ] );
        new \AevovStream\API\StreamEndpoint();
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Aevov Stream', 'aevov-stream' ),
            __( 'Aevov Stream', 'aevov-stream' ),
            'manage_options',
            'aevov-stream',
            [ $this, 'render_admin_page' ],
            'dashicons-video-alt3',
            81
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    public function render_stream_player() {
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'templates/stream-player.php';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'video-js', 'https://vjs.zencdn.net/7.17.0/video-js.css' );
        wp_enqueue_script( 'video-js', 'https://vjs.zencdn.net/7.17.0/video.min.js', [], '7.17.0', true );
        wp_enqueue_script(
            'aevov-stream-player',
            plugin_dir_url( __FILE__ ) . 'assets/js/stream-player.js',
            [ 'jquery', 'video-js' ],
            '1.0.0',
            true
        );
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-playlist-generator.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-session-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-stream-endpoint.php';
    }
}

new AevovStream();
