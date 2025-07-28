<?php
/**
 * Chunk Processor for Pattern Generation
 */

namespace APS\Pattern;

class ChunkProcessor {
    private $chunk_size;
    private $overlap;
    private $logger;
    
    public function __construct($chunk_size = 512, $overlap = 50) {
        $this->chunk_size = $chunk_size;
        $this->overlap = $overlap;
        $this->logger = \APS\Core\Logger::get_instance();
    }
    
    /**
     * Process raw data into chunks for pattern analysis
     */
    public function process_data($data, $options = []) {
        $this->logger->info('Starting chunk processing', ['data_size' => strlen($data)]);
        
        $chunk_size = $options['chunk_size'] ?? $this->chunk_size;
        $overlap = $options['overlap'] ?? $this->overlap;
        
        $chunks = [];
        $position = 0;
        $chunk_id = 0;
        
        while ($position < strlen($data)) {
            $chunk_data = substr($data, $position, $chunk_size);
            
            if (empty($chunk_data)) {
                break;
            }
            
            $chunk = [
                'chunk_id' => $chunk_id++,
                'data' => $chunk_data,
                'position' => $position,
                'size' => strlen($chunk_data),
                'overlap' => $overlap,
                'sequence' => $this->tokenize($chunk_data),
                'attention_mask' => $this->generate_attention_mask($chunk_data),
                'position_ids' => $this->generate_position_ids($chunk_data)
            ];
            
            $chunks[] = $chunk;
            
            // Move position forward, accounting for overlap
            $position += $chunk_size - $overlap;
        }
        
        $this->logger->info('Chunk processing complete', ['chunks_created' => count($chunks)]);
        
        return $chunks;
    }
    
    /**
     * Tokenize chunk data into sequence
     */
    private function tokenize($data) {
        // Simple tokenization - convert to array of character codes
        $tokens = [];
        for ($i = 0; $i < strlen($data); $i++) {
            $tokens[] = ord($data[$i]);
        }
        
        // Pad or truncate to standard size
        $target_length = 512;
        if (count($tokens) < $target_length) {
            $tokens = array_pad($tokens, $target_length, 0);
        } else {
            $tokens = array_slice($tokens, 0, $target_length);
        }
        
        return $tokens;
    }
    
    /**
     * Generate attention mask for the chunk
     */
    private function generate_attention_mask($data) {
        $length = min(strlen($data), 512);
        $mask = array_fill(0, $length, 1);
        
        // Pad to standard size
        if ($length < 512) {
            $mask = array_pad($mask, 512, 0);
        }
        
        return $mask;
    }
    
    /**
     * Generate position IDs for the chunk
     */
    private function generate_position_ids($data) {
        $length = min(strlen($data), 512);
        $position_ids = range(0, $length - 1);
        
        // Pad to standard size
        if ($length < 512) {
            $position_ids = array_pad($position_ids, 512, 0);
        }
        
        return $position_ids;
    }
    
    /**
     * Merge overlapping chunks back into continuous data
     */
    public function merge_chunks($chunks) {
        if (empty($chunks)) {
            return '';
        }
        
        $this->logger->info('Merging chunks', ['chunk_count' => count($chunks)]);
        
        // Sort chunks by position
        usort($chunks, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });
        
        $merged_data = '';
        $last_position = 0;
        
        foreach ($chunks as $chunk) {
            $chunk_data = $chunk['data'];
            $position = $chunk['position'];
            $overlap = $chunk['overlap'] ?? 0;
            
            if ($position > $last_position) {
                // No overlap, append directly
                $merged_data .= $chunk_data;
            } else {
                // Handle overlap
                $overlap_start = $last_position - $position;
                if ($overlap_start < strlen($chunk_data)) {
                    $merged_data .= substr($chunk_data, $overlap_start);
                }
            }
            
            $last_position = $position + strlen($chunk_data);
        }
        
        $this->logger->info('Chunk merging complete', ['merged_size' => strlen($merged_data)]);
        
        return $merged_data;
    }
    
    /**
     * Validate chunk structure
     */
    public function validate_chunk($chunk) {
        $required_fields = ['chunk_id', 'data', 'position', 'size', 'sequence', 'attention_mask', 'position_ids'];
        
        foreach ($required_fields as $field) {
            if (!isset($chunk[$field])) {
                return "Missing required field: {$field}";
            }
        }
        
        // Validate sequence, attention_mask, and position_ids are arrays of correct length
        if (!is_array($chunk['sequence']) || count($chunk['sequence']) !== 512) {
            return "Invalid sequence length";
        }
        
        if (!is_array($chunk['attention_mask']) || count($chunk['attention_mask']) !== 512) {
            return "Invalid attention mask length";
        }
        
        if (!is_array($chunk['position_ids']) || count($chunk['position_ids']) !== 512) {
            return "Invalid position IDs length";
        }
        
        return true;
    }
    
    /**
     * Get chunk statistics
     */
    public function get_chunk_stats($chunks) {
        if (empty($chunks)) {
            return [];
        }
        
        $total_size = array_sum(array_column($chunks, 'size'));
        $avg_size = $total_size / count($chunks);
        
        return [
            'total_chunks' => count($chunks),
            'total_size' => $total_size,
            'average_size' => $avg_size,
            'min_size' => min(array_column($chunks, 'size')),
            'max_size' => max(array_column($chunks, 'size'))
        ];
    }
    
    /**
     * Process chunks by IDs (for integration with PatternGenerator)
     */
    public function process_chunks($chunk_ids) {
        $this->logger->info('Processing chunks by IDs', ['chunk_ids' => $chunk_ids]);
        
        $processed_chunks = [];
        
        foreach ($chunk_ids as $chunk_id) {
            // Mock chunk data for integration testing
            $mock_chunk_data = [
                'id' => $chunk_id,
                'tensor_name' => "tensor_chunk_{$chunk_id}",
                'shape' => [10, 10],
                'dtype' => 'float32',
                'tensor_data' => [
                    'data' => array_fill(0, 100, 0.5) // Mock tensor data
                ]
            ];
            
            $processed_chunks[] = $mock_chunk_data;
        }
        
        $this->logger->info('Chunk processing by IDs complete', ['processed_count' => count($processed_chunks)]);
        
        return $processed_chunks;
    }
}