<?php
namespace BLOOM\Models;

/**
 * Data model for tensor operations and storage
 */
class TensorModel {
    private $table = 'bloom_tensors';
    private $db;
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . $this->table;
    }

    public function create($tensor_data) {
        $data = [
            'tensor_sku' => $this->generate_sku(),
            'tensor_shape' => $tensor_data['shape'], // Already JSON encoded from preprocess
            'dtype' => $tensor_data['dtype'],
            'total_chunks' => 0,
            'total_size' => count($tensor_data['values']) * (isset($tensor_data['dtype']) && strpos($tensor_data['dtype'], 'float') !== false ? 4 : 1), // Estimate size based on count of values
            'status' => 'pending',
            'created_at' => current_time('mysql')
        ];

        $result = $this->db->insert($this->table, $data);
        return $result ? $data['tensor_sku'] : false;
    }

    public function update_status($tensor_sku, $status, $meta = []) {
        return $this->db->update(
            $this->table,
            array_merge(
                ['status' => $status],
                $meta,
                ['updated_at' => current_time('mysql')]
            ),
            ['tensor_sku' => $tensor_sku]
        );
    }

    public function get($tensor_sku) {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE tensor_sku = %s",
                $tensor_sku
            ),
            ARRAY_A
        );
    }

    private function generate_sku() {
        return 'TNS-' . uniqid() . '-' . substr(md5(random_bytes(16)), 0, 8);
    }

    public function create_table() {
        $charset_collate = $this->db->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            tensor_sku varchar(64) NOT NULL,
            tensor_shape text NOT NULL,
            dtype varchar(32) NOT NULL,
            total_chunks int NOT NULL DEFAULT 0,
            total_size bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY tensor_sku (tensor_sku),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function preprocess($tensor_data) {
        // Validate input
        if (!isset($tensor_data['values']) || !is_array($tensor_data['values'])) {
            throw new \InvalidArgumentException("Tensor data must contain 'values' array");
        }

        // Normalize tensor and get metadata
        $normalized = $this->normalize_tensor($tensor_data);
        
        return [
            'values' => $normalized['values'],
            'shape' => $tensor_data['shape'] ?? [count($tensor_data['values'])],
            'dtype' => $tensor_data['dtype'] ?? 'float32',
            'metadata' => [
                'min_value' => $normalized['min_value'],
                'max_value' => $normalized['max_value']
            ]
        ];
    }

    private function normalize_tensor($tensor_data) {
        $values = $tensor_data['values'];
        $max_value = max($values);
        $min_value = min($values);

        // Avoid division by zero
        if ($max_value == $min_value) {
            return [
                'values' => array_fill(0, count($values), 0), // Normalize to 0
                'min_value' => $min_value,
                'max_value' => $max_value
            ];
        }

        $normalized_values = ($max_value != $min_value) ? 
            array_map(function ($val) use ($min_value, $max_value) {
                return ($val - $min_value) / ($max_value - $min_value);
            }, $values) : 
            $values;

        return [
            'values' => $normalized_values,
            'min_value' => $min_value,
            'max_value' => $max_value
        ];
    }

    public function postprocess($processed_chunks) {
        if (!is_array($processed_chunks) || empty($processed_chunks)) {
            throw new \InvalidArgumentException("Invalid or empty processed chunks");
        }
    
        $reconstructed_values = [];
        $metadata = null;
    
        foreach ($processed_chunks as $chunk) {
            if (!isset($chunk['values'])) {
                throw new \InvalidArgumentException("Invalid chunk format");
            }
            $reconstructed_values = array_merge($reconstructed_values, $chunk['values']);
            if (!$metadata && isset($chunk['metadata'])) {
                $metadata = $chunk['metadata'];
            }
        }
    
        if (!$metadata) {
            throw new \InvalidArgumentException("No metadata found in processed chunks");
        }
    
        // Handle denormalization
        if (isset($metadata['min_value']) && isset($metadata['max_value'])) {
            $reconstructed_values = array_map(
                function ($val) use ($metadata) {
                    return $val * ($metadata['max_value'] - $metadata['min_value']) + $metadata['min_value'];
                }, 
                $reconstructed_values
            );
        }
    
        return [
            'values' => $reconstructed_values,
            'shape' => $metadata['shape'] ?? [count($reconstructed_values)], // Ensure shape is retained
            'dtype' => $metadata['dtype'] ?? 'float32'
        ];
    }
    
}