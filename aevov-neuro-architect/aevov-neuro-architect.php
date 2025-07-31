<?php
/*
Plugin Name: Aevov Neuro-Architect
Plugin URI:
Description: A neuro-architect that can compose new models from a library of fundamental neural patterns.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-neuro-architect
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovNeuroArchitect {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    private $admin_page_hook_suffix;

    public function init() {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        $this->include_dependencies();
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        new \AevovNeuroArchitect\API\NeuroArchitectEndpoint();
    }

    public function add_admin_menu() {
        $this->admin_page_hook_suffix = add_menu_page(
            __( 'Aevov Neuro-Architect', 'aevov-neuro-architect' ),
            __( 'Aevov Neuro-Architect', 'aevov-neuro-architect' ),
            'manage_options',
            'aevov-neuro-architect',
            [ $this, 'render_admin_page' ],
            'dashicons-networking',
            88
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== $this->admin_page_hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'aevov-neuro-architect',
            plugin_dir_url( __FILE__ ) . 'assets/js/neuro-architect.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-neural-pattern-catalog.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-neuro-architect-endpoint.php';
    }

    public function activate() {
        $this->create_neural_patterns_table();
    }

    private function create_neural_patterns_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_neural_patterns';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            pattern_id varchar(255) NOT NULL,
            model_source varchar(255) NOT NULL,
            pattern_type varchar(255) NOT NULL,
            metadata text NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

new AevovNeuroArchitect();
