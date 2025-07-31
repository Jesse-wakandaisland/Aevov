<?php
/*
Plugin Name: Aevov Music Forge
Plugin URI:
Description: Sophisticated music generation for the Aevov network.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-music-forge
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovMusicForge {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    private $admin_page_hook_suffix;

    public function init() {
        $this->include_dependencies();
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        new \AevovMusicForge\API\MusicEndpoint();
    }

    public function register_settings() {
        register_setting( 'aevov_music_forge_options', 'aevov_music_forge_options' );
        add_settings_section( 'aevov_music_forge_main', __( 'Main Settings', 'aevov-music-forge' ), null, 'aevov_music_forge' );
        add_settings_field( 'aevov_music_forge_instruments', __( 'Instruments', 'aevov-music-forge' ), [ $this, 'render_instruments_field' ], 'aevov_music_forge', 'aevov_music_forge_main' );
    }

    public function render_instruments_field() {
        $options = get_option( 'aevov_music_forge_options' );
        $instruments = isset( $options['instruments'] ) ? $options['instruments'] : 'piano,guitar,drums';
        echo '<input type="text" name="aevov_music_forge_options[instruments]" value="' . esc_attr( $instruments ) . '">';
    }

    public function add_admin_menu() {
        $this->admin_page_hook_suffix = add_menu_page(
            __( 'Aevov Music Forge', 'aevov-music-forge' ),
            __( 'Aevov Music Forge', 'aevov-music-forge' ),
            'manage_options',
            'aevov-music-forge',
            [ $this, 'render_admin_page' ],
            'dashicons-format-audio',
            83
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== $this->admin_page_hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'aevov-music-composer',
            plugin_dir_url( __FILE__ ) . 'assets/js/music-composer.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-music-weaver.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-music-endpoint.php';
    }
}

new AevovMusicForge();
