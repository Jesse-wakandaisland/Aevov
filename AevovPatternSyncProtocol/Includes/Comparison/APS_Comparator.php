<?php
/**
 * includes/comparison/class-aps-comparator.php
 */

namespace APS\Comparison;

use APS\Analysis\SymbolicPatternAnalyzer;
use BLOOM\Processing\TensorProcessor;

class APS_Comparator {
    private $symbolic_analyzer;
    private $tensor_processor;
    private $rule_engine;
    private $filters;
    private $bloom_integration;

    public function __construct(
        SymbolicPatternAnalyzer $symbolic_analyzer = null,
        TensorProcessor $tensor_processor = null,
        APS_Rule_Engine $rule_engine = null,
        APS_Filters $filters = null,
        APS_BLOOM_Integration $bloom_integration = null
    ) {
        $this->symbolic_analyzer = $symbolic_analyzer ?? new SymbolicPatternAnalyzer();
        $this->tensor_processor = $tensor_processor ?? new TensorProcessor();
        $this->rule_engine = $rule_engine;
        $this->filters = $filters;
        $this->bloom_integration = $bloom_integration;
    }

    public function compare_patterns($items, $options = []) {
        try {
            // Validate and prepare items for comparison
            $prepared_items = $this->prepare_comparison_items($items);

            // Select appropriate comparison engine
            $engine = $this->select_comparison_engine($prepared_items, $options);

            // Apply pre-comparison filters
            $filtered_items = $this->filters->apply_pre_comparison_filters($prepared_items);

            // Execute comparison
            $comparison_result = $engine->compare($filtered_items, $options);

            // Apply post-comparison rules
            $validated_result = $this->rules->validate_comparison_result($comparison_result);

            // Store results
            $stored_result = $this->store_comparison_result($validated_result);

            return $stored_result;

        } catch (Exception $e) {
            $this->log_comparison_error($e, $items);
            throw $e;
        }
    }

    private function prepare_comparison_items($items) {
        $prepared_items = [];

        foreach ($items as $item) {
            // Validate PATTERN STRUCTURE (not comparison result)
            $validation = $this->rule_engine->validate_pattern($item);

            if ($validation !== true) {
                throw new Exception("Invalid pattern structure: " . json_encode($validation));
            }

            $prepared_items[] = $item;
        }

        return $prepared_items;
    }

    private function select_comparison_engine($items, $options) {
        $engine_type = $options['engine'] ?? 'auto';

        if ($engine_type === 'auto') {
            return $this->auto_select_engine($items);
        }

        switch ($engine_type) {
            case 'symbolic':
                return $this->symbolic_analyzer;
            case 'tensor':
                return $this->tensor_processor;
            default:
                throw new Exception("Unknown engine type: {$engine_type}");
        }
    }

    private function auto_select_engine($items) {
        $has_symbolic = false;
        $has_tensor = false;

        foreach ($items as $item) {
            if ($this->is_symbolic_data($item)) {
                $has_symbolic = true;
            } elseif ($this->is_tensor_data($item)) {
                $has_tensor = true;
            }
        }

        if ($has_symbolic && $has_tensor) {
            // For hybrid comparison, we can default to the symbolic analyzer,
            // which can call the tensor processor as needed.
            return $this->symbolic_analyzer;
        } elseif ($has_symbolic) {
            return $this->symbolic_analyzer;
        }
        return $this->tensor_processor;
    }

    private function is_tensor_data($item) {
        return isset($item['values']) && is_array($item['values']);
    }

    private function is_symbolic_.phpdata($item) {
        return isset($item['features']) && isset($item['symbols']);
    }

    public function compose_model( $blueprint ) {
        $patterns = $this->select_patterns( $blueprint );
        $model = $this->assemble_model( $patterns );
        if ( isset( $blueprint['memory'] ) ) {
            $model['memory_system'] = $this->compose_memory_system( $blueprint['memory'] );
        }
        return $model;
    }

    private function compose_memory_system( $memory_blueprint ) {
        // This is a placeholder.
        // In a real implementation, this would use the Aevov network's logic
        // to compose a memory system from the blueprint.
        return [
            'memory_id' => 'composed-memory-' . uniqid(),
            'blueprint' => $memory_blueprint,
        ];
    }

    private function select_patterns( $blueprint ) {
        $catalog = new \AevovNeuroArchitect\NeuralPatternCatalog();
        $patterns = [];
        foreach ( $blueprint['layers'] as $layer ) {
            $patterns = array_merge( $patterns, $catalog->get_patterns_by_type( $layer['type'] ) );
        }
        return $patterns;
    }

    private function assemble_model( $patterns ) {
        // This is a placeholder.
        // In a real implementation, this would assemble the patterns into a new model.
        return [
            'model_id' => 'composed-model-' . uniqid(),
            'patterns' => $patterns,
        ];
    }

    public function find_analogous_patterns( $pattern ) {
        $similar_patterns = $this->query_chunk_registry( $pattern );
        return $similar_patterns;
    }

    private function query_chunk_registry( $pattern ) {
        // This is a placeholder.
        // In a real implementation, this would query the Aevov Chunk Registry
        // to find semantically similar patterns.
        return [];
    }

    public function store_comparison_result($result) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'aps_comparisons',
            [
                'comparison_uuid' => function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('comp_', true),
                'comparison_type' => $result['type'],
                'items_data' => json_encode($result['items']),
                'settings' => json_encode($result['settings']),
                'status' => 'completed'
            ]
        );

        $comparison_id = $wpdb->insert_id;

        $wpdb->insert(
            $wpdb->prefix . 'aps_results',
            [
                'comparison_id' => $comparison_id,
                'result_data' => json_encode($result['results']),
                'match_score' => $result['score'],
                'pattern_data' => json_encode($result['patterns'])
            ]
        );

        return [
            'id' => $comparison_id,
            'uuid' => $result['uuid'],
            'score' => $result['score'],
            'timestamp' => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s')
        ];
    }

    private function log_comparison_error(Exception $e, $context) {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'aps_sync_log',
            [
                'sync_type' => 'comparison_error',
                'sync_data' => json_encode([
                    'error' => $e->getMessage(),
                    'context' => $context
                ]),
                'status' => 'error'
            ]
        );
    }

    private function validate_and_prepare_item($item) {
        // Sanitize and validate each field in chunk
        $prepared_item = [];
        foreach ($item as $key => $value) {
            switch ($key) {
                case 'chunk_id':
                    $prepared_item['chunk_id'] = intval($value);
                    break;
                case 'sequence':
                    if (!is_array($value)) {
                        throw new Exception("Sequence must be an array of tokens");
                    }
                    $prepared_item['sequence'] = array_map('intval', $value);
                    break;
                case 'attention_mask':
                    if (!is_array($value)) {
                        throw new Exception("Attention mask must be an array");
                    }
                    $prepared_item['attention_mask'] = array_map('intval', $value);
                    break;
                case 'position_ids':
                    if (!is_array($value)) {
                        throw new Exception("Position IDs must be an array");
                    }
                    $prepared_item['position_ids'] = array_map('intval', $value);
                    break;
                case 'chunk_size':
                    $prepared_item['chunk_size'] = intval($value);
                    break;
                case 'overlap':
                    $prepared_item['overlap'] = intval($value);
                    break;
                default:
                    throw new Exception("Unknown attribute: {$key}");
            }
        }
        // Validate required fields are present
        $required_fields = ['chunk_id', 'sequence', 'attention_mask', 'position_ids'];
        foreach ($required_fields as $field) {
            if (!isset($prepared_item[$field])) {
                throw new Exception("Missing required field {$field}");
            }
        }
        return $prepared_item;
    }

    private function load_item_by_identifier($identifier) {
        global $wpdb;

        $pattern = $this->get_pattern_by_identifier($identifier);
        if (!$pattern) {
            throw new Exception("Pattern not identified for identifier: {$identifier}");
        }

        $chunks = $this->get_chunk_by_pattern($identifier);
        if (!$chunks) {
            throw new Exception("No chunks found for identifier: {$identifier}");
        }

        return $this->process_chunks($chunks);
    }

    private function get_pattern_by_identifier($identifier) {
        global $wpdb;

        $pattern = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}aps_patterns WHERE pattern_id = %s OR pattern_uuid = %s",
                $identifier,
                $identifier
            ),
            ARRAY_A
        );
        return $pattern;
    }

    private function get_chunk_by_pattern($pattern_id) {
        global $wpdb;

        $chunks = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    chunk_id,
                    sequence,
                    attention_mask,
                    position_ids,
                    chunk_size,
                    overlap
                FROM {$wpdb->prefix}aps_pattern_chunks
                WHERE pattern_id = %d
                ORDER BY chunk_id ASC",
                $pattern_id
            ),
            ARRAY_A
        );
        return $chunks;
    }

    private function process_chunks($chunks) {
        foreach ($chunks as &$chunk) {
            // Convert JSON stored arrays back to PHP arrays
            $chunk['sequence'] = json_decode($chunk['sequence'], true);
            $chunk['attention_mask'] = json_decode($chunk['attention_mask'], true);
            $chunk['position_ids'] = json_decode($chunk['position_ids'], true);

            // Convert numerical fields to integers
            $chunk['chunk_id'] = intval($chunk['chunk_id']);
            $chunk['chunk_size'] = intval($chunk['chunk_size']);
            $chunk['overlap'] = intval($chunk['overlap']);

            // Validate the decoded data
            if (!$chunk['sequence'] || !$chunk['attention_mask'] || !$chunk['position_ids']) {
                throw new Exception("Invalid chunk data format for chunk_id: {$chunk['chunk_id']}");
            }
        }

        return $chunks;
    }
}
