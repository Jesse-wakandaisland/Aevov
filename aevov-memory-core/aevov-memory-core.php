<?php
/*
Plugin Name: Aevov Memory Core
Plugin URI:
Description: A dynamic, biologically-inspired memory system for the Aevov network.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-memory-core
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovMemoryCore {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        $this->include_dependencies();
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-memory-pattern.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-memory-manager.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-memory-endpoint.php';
    }

    private $admin_page_hook_suffix;

    public function init() {
        $this->include_dependencies();
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        new \AevovMemoryCore\API\MemoryEndpoint();
    }

    public function add_admin_menu() {
        $this->admin_page_hook_suffix = add_menu_page(
            __( 'Aevov Memory Core', 'aevov-memory-core' ),
            __( 'Aevov Memory Core', 'aevov-memory-core' ),
            'manage_options',
            'aevov-memory-core',
            [ $this, 'render_admin_page' ],
            'dashicons-database',
            89
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== $this->admin_page_hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'aevov-memory-designer',
            plugin_dir_url( __FILE__ ) . 'assets/js/memory-designer.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }
}

new AevovMemoryCore();
