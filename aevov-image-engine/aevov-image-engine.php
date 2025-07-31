<?php
/*
Plugin Name: Aevov Image Engine
Plugin URI:
Description: Sophisticated image generation for the Aevov network.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-image-engine
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovImageEngine {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    private $admin_page_hook_suffix;

    public function init() {
        $this->include_dependencies();
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        new \AevovImageEngine\API\ImageEndpoint();
    }

    public function register_settings() {
        register_setting( 'aevov_image_engine_options', 'aevov_image_engine_options' );
        add_settings_section( 'aevov_image_engine_main', __( 'Main Settings', 'aevov-image-engine' ), null, 'aevov_image_engine' );
        add_settings_field( 'aevov_image_engine_upscaling', __( 'Enable Upscaling', 'aevov-image-engine' ), [ $this, 'render_upscaling_field' ], 'aevov_image_engine', 'aevov_image_engine_main' );
    }

    public function render_upscaling_field() {
        $options = get_option( 'aevov_image_engine_options' );
        $upscaling = isset( $options['upscaling'] ) ? $options['upscaling'] : 0;
        echo '<input type="checkbox" name="aevov_image_engine_options[upscaling]" value="1" ' . checked( 1, $upscaling, false ) . '>';
    }

    public function add_admin_menu() {
        $this->admin_page_hook_suffix = add_menu_page(
            __( 'Aevov Image Engine', 'aevov-image-engine' ),
            __( 'Aevov Image Engine', 'aevov-image-engine' ),
            'manage_options',
            'aevov-image-engine',
            [ $this, 'render_admin_page' ],
            'dashicons-format-image',
            82
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== $this->admin_page_hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'aevov-image-generator',
            plugin_dir_url( __FILE__ ) . 'assets/js/image-generator.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-image-weaver.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-job-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-image-endpoint.php';
    }
}

new AevovImageEngine();
