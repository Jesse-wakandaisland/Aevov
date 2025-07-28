<?php
namespace BLOOM\Models;

/**
 * Data model for tensor chunks
 */
class ChunkModel {
    private $table = 'bloom_chunks';
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . $this->table;
    }

    public function store_chunk($chunk_data) {
        $data = [
            'tensor_sku' => $chunk_data['tensor_sku'],
            'chunk_index' => $chunk_data['chunk_index'],
            'chunk_data' => $chunk_data['data'],
            'chunk_size' => strlen($chunk_data['data']),
            'checksum' => hash('sha256', $chunk_data['data']),
            'site_id' => get_current_blog_id(),
            'status' => 'active',
            'created_at' => current_time('mysql')
        ];

        return $this->db->insert($this->table, $data);
    }

    public function get_chunks($tensor_sku) {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table} 
                 WHERE tensor_sku = %s 
                 ORDER BY chunk_index ASC",
                $tensor_sku
            ),
            ARRAY_A
        );
    }

    public function verify_chunk($chunk_id, $checksum) {
        $stored_chunk = $this->db->get_row(
            $this->db->prepare(
                "SELECT checksum FROM {$this->table} WHERE id = %d",
                $chunk_id
            )
        );

        return $stored_chunk && hash_equals($stored_chunk->checksum, $checksum);
    }

    public function create_table() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tensor_sku varchar(64) NOT NULL,
            chunk_index int NOT NULL,
            chunk_data longtext NOT NULL,
            chunk_size bigint(20) NOT NULL,
            checksum varchar(64) NOT NULL,
            site_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_accessed datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY tensor_chunk (tensor_sku, chunk_index),
            KEY site_id (site_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}