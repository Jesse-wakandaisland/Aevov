<?php
/*
Plugin Name: Aevov Language Engine
Plugin URI:
Description: Aevov's Large Language Model (LLM) engine, powered by chunked, pre-existing models.
Version: 1.0.0
Author: Jules
Author URI:
License: GPL2
Text Domain: aevov-language-engine
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovLanguageEngine {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public function init() {
        $this->include_dependencies();
    }

    private function include_dependencies() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-llm-ingestion-engine.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-language-weaver.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-language-worker.php';
        require_once plugin_dir_path( __FILE__ ) . 'includes/api/class-language-endpoint.php';
    }

    private $admin_page_hook_suffix;

    public function init() {
        $this->include_dependencies();
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        new \AevovLanguageEngine\API\LanguageEndpoint();
    }

    public function add_admin_menu() {
        $this->admin_page_hook_suffix = add_menu_page(
            __( 'Aevov Language Engine', 'aevov-language-engine' ),
            __( 'Aevov Language Engine', 'aevov-language-engine' ),
            'manage_options',
            'aevov-language-engine',
            [ $this, 'render_admin_page' ],
            'dashicons-text',
            87
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== $this->admin_page_hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'aevov-text-generator',
            plugin_dir_url( __FILE__ ) . 'assets/js/text-generator.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );
    }

    public function render_admin_page() {
        include plugin_dir_path( __FILE__ ) . 'templates/admin-page.php';
    }
}

new AevovLanguageEngine();
