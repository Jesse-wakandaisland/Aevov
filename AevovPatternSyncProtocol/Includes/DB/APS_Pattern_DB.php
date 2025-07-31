<?php

namespace APS\DB;

class APS_Pattern_DB {
    private $wpdb;
    private $table_name;
    
    public function __construct($wpdb = null) {
        $this->wpdb = $wpdb ?? $GLOBALS['wpdb'];
        $this->table_name = $this->wpdb->prefix . 'aps_patterns';
    }

    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $this->wpdb->get_charset_collate();
    
        $this->create_patterns_table($charset_collate);
        $this->create_chunks_table($charset_collate);
        $this->create_relationships_table($charset_collate);
        $this->create_symbolic_patterns_table($charset_collate);
    }
    
    private function create_patterns_table($charset_collate) {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_hash varchar(64) NOT NULL,
            pattern_type varchar(32) NOT NULL,
            pattern_data longtext NOT NULL,
            confidence float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            sync_status varchar(20) DEFAULT 'pending',
            distribution_count int DEFAULT 0,
            last_accessed datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY pattern_hash (pattern_hash),
            KEY pattern_type (pattern_type),
            KEY sync_status (sync_status),
            cubbit_key varchar(255) DEFAULT NULL
        ) $charset_collate;";
    
        dbDelta($sql);
    }
    
    private function create_chunks_table($charset_collate) {
        $table_name = $this->wpdb->prefix . 'aps_pattern_chunks';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pattern_id BIGINT(20) NOT NULL,
            chunk_id INT UNSIGNED NOT NULL,
            sequence LONGTEXT NOT NULL,
            attention_mask LONGTEXT NOT NULL,
            position_ids LONGTEXT NOT NULL,
            chunk_size INT UNSIGNED NOT NULL,
            overlap INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pattern_id (pattern_id),
            KEY chunk_id (chunk_id),
            FOREIGN KEY (pattern_id) REFERENCES {$this->table_name}(id) ON DELETE CASCADE
        ) $charset_collate;";
    
        dbDelta($sql);
    }
    
    private function create_relationships_table($charset_collate) {
        $table_name = $this->wpdb->prefix . 'aps_pattern_relationships';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pattern_id_a BIGINT(20) NOT NULL,
            pattern_id_b BIGINT(20) NOT NULL,
            relationship_type VARCHAR(32) NOT NULL,
            similarity_score FLOAT NOT NULL,
            metadata JSON,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pattern_id_a (pattern_id_a),
            KEY pattern_id_b (pattern_id_b),
            KEY relationship_type (relationship_type),
            FOREIGN KEY (pattern_id_a) REFERENCES {$this->table_name}(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (pattern_id_b) REFERENCES {$this->table_name}(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) $charset_collate;";
    
        dbDelta($sql);
    }
    
    private function create_symbolic_patterns_table($charset_collate) {
        $table_name = $this->wpdb->prefix . 'aps_symbolic_patterns';
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_id bigint(20) NOT NULL,
            symbols longtext NOT NULL,
            relations longtext NOT NULL,
            rules longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY pattern_id (pattern_id),
            FOREIGN KEY (pattern_id) REFERENCES {$this->table_name}(id) ON DELETE CASCADE
        ) $charset_collate;";
    
        dbDelta($sql);
    }

    public function insert_pattern($pattern_data) {
        // Upload to Cubbit
        $cubbit_key = $this->upload_to_cubbit($pattern_data);

        return $this->wpdb->insert(
            $this->table_name,
            [
                'pattern_hash' => $pattern_data['hash'],
                'pattern_type' => $pattern_data['type'],
                'pattern_data' => json_encode($pattern_data['data']),
                'confidence' => $pattern_data['confidence'],
                'cubbit_key' => $cubbit_key
            ],
            ['%s', '%s', '%s', '%f', '%s']
        );
    }

    private function upload_to_cubbit($pattern_data) {
        if (!class_exists('CubbitDirectoryManager')) {
            // This is a bit of a hack, but it's the only way to ensure the class is available.
            $cubbit_plugin_path = WP_PLUGIN_DIR . '/Cubbit DS3/Cubbit Directory Manager Extension/cubbit-directory-manager-extension.php';
            if (file_exists($cubbit_plugin_path)) {
                require_once($cubbit_plugin_path);
            } else {
                return null;
            }
        }

        $cubbit_manager = new \CubbitDirectoryManager();
        $temp_dir = get_temp_dir();
        $temp_file = wp_tempnam('pattern', $temp_dir);
        file_put_contents($temp_file, json_encode($pattern_data['data']));
        $cubbit_key = 'patterns/' . $pattern_data['hash'] . '.json';
        $upload_result = $cubbit_manager->upload_file($temp_file, $cubbit_key, 'application/json', 'private');
        unlink($temp_file);

        return $upload_result ? $cubbit_key : null;
    }

    public function update_pattern($pattern_hash, $pattern_data) {
        return $this->wpdb->update(
            $this->table_name,
            [
                'pattern_data' => json_encode($pattern_data['data']),
                'confidence' => $pattern_data['confidence'],
                'updated_at' => current_time('mysql')
            ],
            ['pattern_hash' => $pattern_hash],
            ['%s', '%f', '%s'],
            ['%s']
        );
    }

    public function get_pattern($pattern_hash) {
        $pattern = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE pattern_hash = %s",
                $pattern_hash
            ),
            ARRAY_A
        );

        if ($pattern) {
            $pattern['pattern_data'] = json_decode($pattern['pattern_data'], true);
            $this->update_access_time($pattern_hash);
        }

        return $pattern;
    }

    public function get_patterns_by_type($type, $limit = 100, $offset = 0) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE pattern_type = %s 
                 ORDER BY confidence DESC 
                 LIMIT %d OFFSET %d",
                $type, $limit, $offset
            ),
            ARRAY_A
        );
    }

    public function get_pending_sync_patterns($limit = 50) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                 WHERE sync_status = 'pending' 
                 ORDER BY updated_at ASC 
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    public function update_sync_status($pattern_hash, $status) {
        return $this->wpdb->update(
            $this->table_name,
            ['sync_status' => $status],
            ['pattern_hash' => $pattern_hash],
            ['%s'],
            ['%s']
        );
    }

    public function increment_distribution($pattern_hash) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table_name} 
                 SET distribution_count = distribution_count + 1 
                 WHERE pattern_hash = %s",
                $pattern_hash
            )
        );
    }

    public function delete_pattern($pattern_hash) {
        return $this->wpdb->delete(
            $this->table_name,
            ['pattern_hash' => $pattern_hash],
            ['%s']
        );
    }

    public function cleanup_old_patterns($days = 30) {
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                 WHERE last_accessed < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }

    private function update_access_time($pattern_hash) {
        $this->wpdb->update(
            $this->table_name,
            ['last_accessed' => current_time('mysql')],
            ['pattern_hash' => $pattern_hash],
            ['%s'],
            ['%s']
        );
    }

    public function get_pattern_stats() {
        return [
            'total' => $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}"),
            'by_type' => $this->wpdb->get_results(
                "SELECT pattern_type, COUNT(*) as count 
                 FROM {$this->table_name} 
                 GROUP BY pattern_type",
                ARRAY_A
            ),
            'avg_confidence' => $this->wpdb->get_var(
                "SELECT AVG(confidence) FROM {$this->table_name}"
            )
        ];
    }
    
    public function insert_symbolic_pattern($pattern_data) {
        // First insert the base pattern
        $pattern_id = $this->insert_pattern([
            'hash' => $pattern_data['pattern_hash'],
            'type' => 'symbolic_pattern',
            'data' => [
                'features' => $pattern_data['features'],
                'metrics' => $pattern_data['metrics']
            ],
            'confidence' => $pattern_data['confidence']
        ]);
        
        if (!$pattern_id) {
            return false;
        }
        
        // Then insert the symbolic pattern details
        $table_name = $this->wpdb->prefix . 'aps_symbolic_patterns';
        $result = $this->wpdb->insert(
            $table_name,
            [
                'pattern_id' => $pattern_id,
                'symbols' => json_encode($pattern_data['symbols']),
                'relations' => json_encode($pattern_data['relations']),
                'rules' => json_encode($pattern_data['rules'])
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        return $result ? $pattern_id : false;
    }
    
    public function get_symbolic_pattern($pattern_hash) {
        // First get the base pattern
        $pattern = $this->get_pattern($pattern_hash);
        
        if (!$pattern || $pattern['pattern_type'] !== 'symbolic_pattern') {
            return null;
        }
        
        // Then get the symbolic pattern details
        $table_name = $this->wpdb->prefix . 'aps_symbolic_patterns';
        $symbolic_data = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE pattern_id = %d",
                $pattern['id']
            ),
            ARRAY_A
        );
        
        if (!$symbolic_data) {
            return $pattern;
        }
        
        // Merge the data
        $pattern['symbols'] = json_decode($symbolic_data['symbols'], true);
        $pattern['relations'] = json_decode($symbolic_data['relations'], true);
        $pattern['rules'] = json_decode($symbolic_data['rules'], true);
        
        return $pattern;
    }
    
    public function update_symbolic_pattern($pattern_hash, $pattern_data) {
        // First update the base pattern
        $result = $this->update_pattern($pattern_hash, [
            'data' => [
                'features' => $pattern_data['features'],
                'metrics' => $pattern_data['metrics']
            ],
            'confidence' => $pattern_data['confidence']
        ]);
        
        if (!$result) {
            return false;
        }
        
        // Get the pattern ID
        $pattern = $this->get_pattern($pattern_hash);
        if (!$pattern) {
            return false;
        }
        
        // Then update the symbolic pattern details
        $table_name = $this->wpdb->prefix . 'aps_symbolic_patterns';
        return $this->wpdb->update(
            $table_name,
            [
                'symbols' => json_encode($pattern_data['symbols']),
                'relations' => json_encode($pattern_data['relations']),
                'rules' => json_encode($pattern_data['rules']),
                'updated_at' => current_time('mysql')
            ],
            ['pattern_id' => $pattern['id']],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );
    }
    
    public function delete_symbolic_pattern($pattern_hash) {
        // The symbolic pattern will be deleted automatically due to the foreign key constraint
        return $this->delete_pattern($pattern_hash);
    }
}