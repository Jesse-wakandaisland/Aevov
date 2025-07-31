<?php

namespace AevovImageEngine;

class ImageWeaver {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function get_pattern_ids( $params ) {
        if ( ! class_exists( 'APS\DB\APS_Pattern_DB' ) ) {
            $pattern_db_path = WP_PLUGIN_DIR . '/AevovPatternSyncProtocol/Includes/DB/APS_Pattern_DB.php';
            if ( file_exists( $pattern_db_path ) ) {
                require_once $pattern_db_path;
            } else {
                return new \WP_Error( 'dependency_missing', 'APS_Pattern_DB class not found.' );
            }
        }

        $pattern_db = new \APS\DB\APS_Pattern_DB();
        $patterns = $pattern_db->get_patterns_by_type( 'tensor_pattern' );
        $pattern_ids = wp_list_pluck( $patterns, 'id' );

        return $pattern_ids;
    }
}
