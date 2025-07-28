<?php

namespace APS\Pattern;

class PatternStorage {
    private $pattern_table;

    public function __construct() {
        global $wpdb;
        $this->pattern_table = $wpdb->prefix . 'aps_patterns';
    }

    public function store_patterns($patterns) {
        $stored_patterns = [];
        foreach ($patterns as $pattern) {
            $stored_id = $this->store_single_pattern($pattern);
            if ($stored_id) {
                $pattern['stored_id'] = $stored_id;
                $stored_patterns[] = $pattern;
            }
        }
        return $stored_patterns;
    }

    private function store_single_pattern($pattern) {
        global $wpdb;
        
        $data = [
            'pattern_hash' => function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('pattern_', true),
            'pattern_type' => $pattern['type'],
            'pattern_data' => json_encode($pattern),
            'confidence' => $pattern['confidence'] ?? 1.0,
            'created_at' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
            'updated_at' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s')
        ];

        $result = $wpdb->insert($this->pattern_table, $data);
        return $result ? $wpdb->insert_id : false;
    }

    public function get_pattern($pattern_id) {
        global $wpdb;
        $pattern = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->pattern_table} WHERE id = %d",
                $pattern_id
            ),
            ARRAY_A
        );
        
        if ($pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
        }
        
        return $pattern;
    }
}