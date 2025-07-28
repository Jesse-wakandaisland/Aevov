<?php
namespace BLOOM\Models;

/**
 * Data model for pattern operations and storage
 */
class PatternModel {
    private $table = 'bloom_patterns';
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . $this->table;
    }

    public function create($pattern_data) {
        $pattern_hash = $this->generate_pattern_hash($pattern_data);
        error_log('PatternModel: Generated hash for creation: ' . $pattern_hash);

        $data = [
            'pattern_hash' => $pattern_hash,
            'pattern_type' => $pattern_data['type'],
            'features' => json_encode($pattern_data['features']),
            'confidence' => $pattern_data['confidence'],
            'metadata' => json_encode($pattern_data['metadata'] ?? []),
            'tensor_sku' => $pattern_data['tensor_sku'] ?? null,
            'site_id' => get_current_blog_id(),
            'status' => 'active',
            'created_at' => current_time('mysql')
        ];

        error_log('PatternModel: Inserting data: ' . print_r($data, true));
        $result = $this->db->insert($this->table, $data);
        error_log('PatternModel: Insert result: ' . ($result ? 'success, ID: ' . $this->db->insert_id : 'failure, error: ' . $this->db->last_error));
        return $result ? $this->db->insert_id : false;
    }

    public function get($pattern_id) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE id = %d",
                $pattern_id
            ),
            ARRAY_A
        );
    }

    public function get_by_hash($pattern_hash) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE pattern_hash = %s",
                $pattern_hash
            ),
            ARRAY_A
        );
    }

    public function find_similar($pattern_data, $threshold = 0.75) {
        $features = json_encode($pattern_data['features']);
        
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT *, 
                 (MATCH(features) AGAINST(%s IN NATURAL LANGUAGE MODE)) as similarity_score
                 FROM {$this->table} 
                 WHERE pattern_type = %s 
                 AND confidence >= %f
                 AND status = 'active'
                 HAVING similarity_score >= %f
                 ORDER BY similarity_score DESC
                 LIMIT 10",
                $features,
                $pattern_data['type'],
                $threshold,
                $threshold
            ),
            ARRAY_A
        );
    }

    public function update_confidence($pattern_id, $confidence) {
        return $this->db->update(
            $this->table,
            [
                'confidence' => $confidence,
                'updated_at' => current_time('mysql')
            ],
            ['id' => $pattern_id]
        );
    }

    private function generate_pattern_hash($pattern_data) {
        $hash_data = [
            'type' => $pattern_data['type'],
            'features' => $pattern_data['features']
        ];
        $hash = hash('sha256', json_encode($hash_data)); // Encode once for hashing
        error_log('PatternModel: generate_pattern_hash input: ' . json_encode($hash_data) . ' -> hash: ' . $hash);
        return $hash;
    }

    public function create_table() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            pattern_hash varchar(64) NOT NULL,
            pattern_type varchar(50) NOT NULL,
            features text NOT NULL,
            confidence decimal(5,4) NOT NULL,
            metadata text,
            tensor_sku varchar(64),
            site_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY pattern_hash (pattern_hash),
            KEY pattern_type (pattern_type),
            KEY confidence (confidence),
            KEY site_id (site_id),
            KEY status (status),
            FULLTEXT KEY features_search (features)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function get_patterns_by_site($site_id, $limit = 100) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} 
                 WHERE site_id = %d 
                 AND status = 'active'
                 ORDER BY confidence DESC, created_at DESC
                 LIMIT %d",
                $site_id,
                $limit
            ),
            ARRAY_A
        );
    }

    public function get_pattern_statistics() {
        return $this->db->get_results(
            "SELECT 
                pattern_type,
                COUNT(*) as count,
                AVG(confidence) as avg_confidence,
                MAX(confidence) as max_confidence,
                MIN(confidence) as min_confidence
             FROM {$this->table} 
             WHERE status = 'active'
             GROUP BY pattern_type
             ORDER BY count DESC",
            ARRAY_A
        );
    }
    public function get_by_tensor_sku($tensor_sku) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE tensor_sku = %s ORDER BY created_at DESC",
                $tensor_sku
            ),
            ARRAY_A
        );
    }
}