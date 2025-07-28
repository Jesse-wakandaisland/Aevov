<?php
/**
 * Pattern validation and integrity checking
 * 
 * @package APS
 * @subpackage Pattern
 */

namespace APS\Pattern;

use APS\Core\Logger;

class PatternValidator {
    private $logger;
    private $validation_rules = [];
    private $error_messages = [];
    
    public function __construct() {
        $this->logger = Logger::get_instance();
        $this->init_validation_rules();
    }
    
    private function init_validation_rules() {
        $this->validation_rules = [
            'pattern_structure' => [$this, 'validate_pattern_structure'],
            'chunk_integrity' => [$this, 'validate_chunk_integrity'],
            'tensor_format' => [$this, 'validate_tensor_format'],
            'sequence_validity' => [$this, 'validate_sequence_validity'],
            'attention_mask' => [$this, 'validate_attention_mask'],
            'position_ids' => [$this, 'validate_position_ids'],
            'pattern_metadata' => [$this, 'validate_pattern_metadata'],
            'chunk_relationships' => [$this, 'validate_chunk_relationships']
        ];
    }
    
    public function validate_pattern($pattern_data, $rules = null) {
        $this->error_messages = [];
        
        if ($rules === null) {
            $rules = array_keys($this->validation_rules);
        }
        
        $validation_results = [];
        
        foreach ($rules as $rule) {
            if (isset($this->validation_rules[$rule])) {
                try {
                    $result = call_user_func($this->validation_rules[$rule], $pattern_data);
                    $validation_results[$rule] = $result;
                    
                    if (!$result['valid']) {
                        $this->error_messages[] = $result['message'];
                    }
                } catch (Exception $e) {
                    $validation_results[$rule] = [
                        'valid' => false,
                        'message' => "Validation error for rule '{$rule}': " . $e->getMessage()
                    ];
                    $this->error_messages[] = $validation_results[$rule]['message'];
                }
            }
        }
        
        $is_valid = empty($this->error_messages);
        
        $this->logger->debug("Pattern validation result: " . ($is_valid ? 'VALID' : 'INVALID'));
        if (!$is_valid) {
            $this->logger->warning("Pattern validation errors: " . implode('; ', $this->error_messages));
        }
        
        return [
            'valid' => $is_valid,
            'errors' => $this->error_messages,
            'details' => $validation_results
        ];
    }
    
    private function validate_pattern_structure($pattern_data) {
        $required_fields = ['pattern_id', 'pattern_type', 'chunks'];
        
        foreach ($required_fields as $field) {
            if (!isset($pattern_data[$field])) {
                return [
                    'valid' => false,
                    'message' => "Missing required field: {$field}"
                ];
            }
        }
        
        if (!is_array($pattern_data['chunks']) || empty($pattern_data['chunks'])) {
            return [
                'valid' => false,
                'message' => "Pattern must contain at least one chunk"
            ];
        }
        
        return ['valid' => true, 'message' => 'Pattern structure is valid'];
    }
    
    private function validate_chunk_integrity($pattern_data) {
        if (!isset($pattern_data['chunks']) || !is_array($pattern_data['chunks'])) {
            return [
                'valid' => false,
                'message' => "No chunks found for integrity validation"
            ];
        }
        
        $chunk_ids = [];
        foreach ($pattern_data['chunks'] as $chunk) {
            if (!isset($chunk['chunk_id'])) {
                return [
                    'valid' => false,
                    'message' => "Chunk missing chunk_id"
                ];
            }
            
            if (in_array($chunk['chunk_id'], $chunk_ids)) {
                return [
                    'valid' => false,
                    'message' => "Duplicate chunk_id found: {$chunk['chunk_id']}"
                ];
            }
            
            $chunk_ids[] = $chunk['chunk_id'];
        }
        
        return ['valid' => true, 'message' => 'Chunk integrity validated'];
    }
    
    private function validate_tensor_format($pattern_data) {
        if (!isset($pattern_data['chunks'])) {
            return ['valid' => true, 'message' => 'No chunks to validate tensor format'];
        }
        
        foreach ($pattern_data['chunks'] as $chunk) {
            $tensor_fields = ['sequence', 'attention_mask', 'position_ids'];
            
            foreach ($tensor_fields as $field) {
                if (!isset($chunk[$field])) {
                    return [
                        'valid' => false,
                        'message' => "Chunk {$chunk['chunk_id']} missing tensor field: {$field}"
                    ];
                }
                
                if (!is_array($chunk[$field])) {
                    return [
                        'valid' => false,
                        'message' => "Chunk {$chunk['chunk_id']} tensor field {$field} must be an array"
                    ];
                }
            }
        }
        
        return ['valid' => true, 'message' => 'Tensor format validated'];
    }
    
    private function validate_sequence_validity($pattern_data) {
        if (!isset($pattern_data['chunks'])) {
            return ['valid' => true, 'message' => 'No chunks to validate sequences'];
        }
        
        foreach ($pattern_data['chunks'] as $chunk) {
            if (!isset($chunk['sequence']) || !is_array($chunk['sequence'])) {
                continue;
            }
            
            // Check for valid token IDs (should be integers)
            foreach ($chunk['sequence'] as $token) {
                if (!is_int($token) || $token < 0) {
                    return [
                        'valid' => false,
                        'message' => "Invalid token in chunk {$chunk['chunk_id']}: {$token}"
                    ];
                }
            }
            
            // Check sequence length
            if (count($chunk['sequence']) === 0) {
                return [
                    'valid' => false,
                    'message' => "Empty sequence in chunk {$chunk['chunk_id']}"
                ];
            }
            
            if (count($chunk['sequence']) > 10000) { // Max reasonable sequence length
                return [
                    'valid' => false,
                    'message' => "Sequence too long in chunk {$chunk['chunk_id']}"
                ];
            }
        }
        
        return ['valid' => true, 'message' => 'Sequence validity validated'];
    }
    
    private function validate_attention_mask($pattern_data) {
        if (!isset($pattern_data['chunks'])) {
            return ['valid' => true, 'message' => 'No chunks to validate attention masks'];
        }
        
        foreach ($pattern_data['chunks'] as $chunk) {
            if (!isset($chunk['attention_mask']) || !isset($chunk['sequence'])) {
                continue;
            }
            
            $mask_length = count($chunk['attention_mask']);
            $sequence_length = count($chunk['sequence']);
            
            if ($mask_length !== $sequence_length) {
                return [
                    'valid' => false,
                    'message' => "Attention mask length mismatch in chunk {$chunk['chunk_id']}: mask={$mask_length}, sequence={$sequence_length}"
                ];
            }
            
            // Check mask values (should be 0 or 1)
            foreach ($chunk['attention_mask'] as $mask_value) {
                if (!in_array($mask_value, [0, 1], true)) {
                    return [
                        'valid' => false,
                        'message' => "Invalid attention mask value in chunk {$chunk['chunk_id']}: {$mask_value}"
                    ];
                }
            }
        }
        
        return ['valid' => true, 'message' => 'Attention mask validated'];
    }
    
    private function validate_position_ids($pattern_data) {
        if (!isset($pattern_data['chunks'])) {
            return ['valid' => true, 'message' => 'No chunks to validate position IDs'];
        }
        
        foreach ($pattern_data['chunks'] as $chunk) {
            if (!isset($chunk['position_ids']) || !isset($chunk['sequence'])) {
                continue;
            }
            
            $position_length = count($chunk['position_ids']);
            $sequence_length = count($chunk['sequence']);
            
            if ($position_length !== $sequence_length) {
                return [
                    'valid' => false,
                    'message' => "Position IDs length mismatch in chunk {$chunk['chunk_id']}: positions={$position_length}, sequence={$sequence_length}"
                ];
            }
            
            // Check position values (should be sequential integers starting from 0)
            foreach ($chunk['position_ids'] as $index => $position) {
                if (!is_int($position) || $position !== $index) {
                    return [
                        'valid' => false,
                        'message' => "Invalid position ID in chunk {$chunk['chunk_id']} at index {$index}: expected {$index}, got {$position}"
                    ];
                }
            }
        }
        
        return ['valid' => true, 'message' => 'Position IDs validated'];
    }
    
    private function validate_pattern_metadata($pattern_data) {
        $metadata_fields = ['created_at', 'confidence_score', 'source'];
        
        foreach ($metadata_fields as $field) {
            if (isset($pattern_data[$field])) {
                switch ($field) {
                    case 'confidence_score':
                        $score = floatval($pattern_data[$field]);
                        if ($score < 0 || $score > 1) {
                            return [
                                'valid' => false,
                                'message' => "Invalid confidence score: {$score} (must be between 0 and 1)"
                            ];
                        }
                        break;
                        
                    case 'created_at':
                        if (!strtotime($pattern_data[$field])) {
                            return [
                                'valid' => false,
                                'message' => "Invalid created_at timestamp: {$pattern_data[$field]}"
                            ];
                        }
                        break;
                }
            }
        }
        
        return ['valid' => true, 'message' => 'Pattern metadata validated'];
    }
    
    private function validate_chunk_relationships($pattern_data) {
        if (!isset($pattern_data['chunks']) || count($pattern_data['chunks']) < 2) {
            return ['valid' => true, 'message' => 'Not enough chunks to validate relationships'];
        }
        
        $chunks = $pattern_data['chunks'];
        
        // Sort chunks by chunk_id for sequential validation
        usort($chunks, function($a, $b) {
            return $a['chunk_id'] <=> $b['chunk_id'];
        });
        
        for ($i = 1; $i < count($chunks); $i++) {
            $prev_chunk = $chunks[$i - 1];
            $curr_chunk = $chunks[$i];
            
            // Check for overlap validation if overlap field exists
            if (isset($prev_chunk['overlap']) && isset($curr_chunk['overlap'])) {
                $expected_overlap = $prev_chunk['overlap'];
                
                if (isset($prev_chunk['sequence']) && isset($curr_chunk['sequence'])) {
                    $prev_sequence = $prev_chunk['sequence'];
                    $curr_sequence = $curr_chunk['sequence'];
                    
                    if ($expected_overlap > 0) {
                        $prev_end = array_slice($prev_sequence, -$expected_overlap);
                        $curr_start = array_slice($curr_sequence, 0, $expected_overlap);
                        
                        if ($prev_end !== $curr_start) {
                            return [
                                'valid' => false,
                                'message' => "Chunk overlap mismatch between chunks {$prev_chunk['chunk_id']} and {$curr_chunk['chunk_id']}"
                            ];
                        }
                    }
                }
            }
        }
        
        return ['valid' => true, 'message' => 'Chunk relationships validated'];
    }
    
    public function get_validation_errors() {
        return $this->error_messages;
    }
    
    public function add_custom_rule($name, $callback) {
        if (is_callable($callback)) {
            $this->validation_rules[$name] = $callback;
            return true;
        }
        return false;
    }
    
    public function remove_rule($name) {
        if (isset($this->validation_rules[$name])) {
            unset($this->validation_rules[$name]);
            return true;
        }
        return false;
    }
    
    public function get_available_rules() {
        return array_keys($this->validation_rules);
    }
    
    public function validate_single_chunk($chunk_data) {
        $pattern_data = [
            'pattern_id' => 'single_chunk_validation',
            'pattern_type' => 'validation',
            'chunks' => [$chunk_data]
        ];
        
        $rules = ['chunk_integrity', 'tensor_format', 'sequence_validity', 'attention_mask', 'position_ids'];
        return $this->validate_pattern($pattern_data, $rules);
    }
}