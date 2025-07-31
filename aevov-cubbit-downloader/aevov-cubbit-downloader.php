<?php
/*
Plugin Name: Aevov Cubbit Downloader
Plugin URI:
Description: Integrates the Cubbit Authenticated Downloader with the Aevov Pattern Sync Protocol.
Version: 1.0.0
Author: Your Name/Company
Author URI:
License: GPL2
Text Domain: acd
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AevovCubbitDownloader {

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ], 20 );
    }

    public function init() {
        // Check if the required plugins are active.
        if ( ! class_exists( 'APS_Plugin' ) || ! class_exists( 'CubbitAuthenticatedDownloader' ) ) {
            add_action( 'admin_notices', [ $this, 'missing_plugins_notice' ] );
            return;
        }

        // Add the "Download" button to the pattern list.
        add_filter( 'aps_pattern_actions', [ $this, 'add_download_button' ], 10, 2 );

        // Enqueue the necessary scripts.
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Handle the download request.
        add_action( 'wp_ajax_acd_download_pattern', [ $this, 'handle_download_request' ] );
    }

    public function missing_plugins_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'Aevov Cubbit Downloader requires the Aevov Pattern Sync Protocol and Cubbit Authenticated Downloader plugins to be active.', 'acd' ); ?></p>
        </div>
        <?php
    }

    public function add_download_button( $actions, $pattern ) {
        $actions['download'] = '<a href="#" class="button acd-download-button" data-pattern-id="' . $pattern->id . '">' . __( 'Download', 'acd' ) . '</a>';
        return $actions;
    }

    public function enqueue_scripts( $hook ) {
        if ( 'toplevel_page_aps-dashboard' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'acd-admin',
            plugin_dir_url( __FILE__ ) . 'assets/js/admin.js',
            [ 'jquery' ],
            '1.0.0',
            true
        );

        wp_localize_script(
            'acd-admin',
            'acdAdmin',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'acd-download-nonce' ),
            ]
        );
    }

    public function handle_download_request() {
        check_ajax_referer( 'acd-download-nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Insufficient permissions' ] );
            return;
        }

        $pattern_id = isset( $_POST['pattern_id'] ) ? intval( $_POST['pattern_id'] ) : 0;

        if ( ! $pattern_id ) {
            wp_send_json_error( [ 'message' => 'Invalid pattern ID' ] );
            return;
        }

        // Get the pattern data.
        $pattern = \APS\DB\APS_Pattern_DB::get_instance()->get( $pattern_id );

        if ( ! $pattern ) {
            wp_send_json_error( [ 'message' => 'Pattern not found' ] );
            return;
        }

        // Create a temporary file with the pattern data.
        $temp_dir = get_temp_dir();
        $temp_file = wp_tempnam( 'pattern', $temp_dir );
        file_put_contents( $temp_file, $pattern->pattern_data );

        // Initiate the download using the Cubbit Authenticated Downloader.
        $cubbit_downloader = new CubbitAuthenticatedDownloader();
        $download_id = $cubbit_downloader->ajax_auth_download( [ $temp_file ] );

        // Clean up the temporary file.
        unlink( $temp_file );

        wp_send_json_success( [ 'download_id' => $download_id ] );
    }
}

new AevovCubbitDownloader();
