<?php

/**
 * includes/core/class-aps-activator.php
 */

namespace APS\Core;

use APS\DB\MetricsDB;
use APS\DB\PatternDB;
use APS\DB\SyncLogDB;
use APS\DB\MonitoringDB;

class APS_Activator {
    public static function activate($network_wide = false) {
        if ($network_wide && is_multisite()) {
            self::network_activate();
        } else {
            self::single_site_activate();
        }
    }
    
    private static function single_site_activate() {
        self::check_requirements();
        self::create_database_tables();
        self::set_default_options();
        self::create_required_directories();
        self::setup_scheduled_tasks();
    }
    
    private static function network_activate() {
        $sites = get_sites(['fields' => 'ids']);
        
        foreach ($sites as $site_id) {
            switch_to_blog($site_id);
            self::single_site_activate();
            restore_current_blog();
        }
    }

    private static function check_requirements() {
        if (!class_exists('BLOOM_Core')) {
            wp_die(
                __('BLOOM Pattern Recognition System is required for this plugin.', 'aps'),
                'Plugin Dependency Missing',
                array('back_link' => true)
            );
        }
    }

    private static function create_database_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
    
        // Create metrics tables using the MetricsDB class
        $metrics_db = new MetricsDB();
        $metrics_db->create_tables();
        
        // Create pattern tables using the PatternDB class
        $pattern_db = new PatternDB();
        $pattern_db->create_tables();
        
        // Create pattern cache table using the PatternCacheDB class
        $pattern_cache_db = new PatternCacheDB();
        $pattern_cache_db->create_table();
        
        // Create sync log table using the SyncLogDB class
        $sync_log_db = new SyncLogDB();
        $sync_log_db->create_table();

        $monitoring_db = new MonitoringDB();
        $monitoring_db->create_tables();
    
        // Comparisons table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aps_comparisons (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            comparison_uuid varchar(36) NOT NULL,
            comparison_type varchar(32) NOT NULL,
            items_data longtext NOT NULL,
            settings longtext,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY comparison_uuid (comparison_uuid),
            KEY status (status)
        ) $charset_collate;";
    
        // Results table
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}aps_results (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            comparison_id bigint(20) NOT NULL,
            result_data longtext NOT NULL,
            match_score float NOT NULL,
            pattern_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY comparison_id (comparison_id)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private static function set_default_options() {
        $default_options = array(
            'aps_version' => APS_VERSION,
            'comparison_cache_time' => 3600,
            'min_pattern_confidence' => 0.75,
            'sync_interval' => 300,
            'max_comparison_items' => 10,
            'enable_tensor_cache' => true
        );

        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }

    private static function create_required_directories() {
        $upload_dir = wp_upload_dir();
        $aps_dir = $upload_dir['basedir'] . '/aps-cache';

        if (!file_exists($aps_dir)) {
            wp_mkdir_p($aps_dir);
        }

        // Create .htaccess to protect cache directory
        $htaccess = $aps_dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, 'Deny from all');
        }
    }
    
    private static function setup_scheduled_tasks() {
        if (!wp_next_scheduled('aps_process_queue')) {
            wp_schedule_event(time(), 'minute', 'aps_process_queue');
        }
        
        if (!wp_next_scheduled('aps_sync_network')) {
            wp_schedule_event(time(), 'five_minutes', 'aps_sync_network');
        }
        
        if (!wp_next_scheduled('aps_system_health_check')) {
            wp_schedule_event(time(), 'hourly', 'aps_system_health_check');
        }
        
        if (!wp_next_scheduled('aps_process_metrics')) {
            wp_schedule_event(time(), 'hourly', 'aps_process_metrics');
        }
        
        if (!wp_next_scheduled('aps_aggregate_metrics')) {
            wp_schedule_event(time(), 'daily', 'aps_aggregate_metrics');
        }
        
        if (!wp_next_scheduled('aps_cleanup_metrics')) {
            wp_schedule_event(time(), 'daily', 'aps_cleanup_metrics');
        }
    }
}