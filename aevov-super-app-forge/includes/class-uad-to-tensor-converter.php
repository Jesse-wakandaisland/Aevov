<?php

namespace AevovSuperAppForge;

class UAD_to_Tensor_Converter {

    public function convert_uad_to_tensor( $uad ) {
        if ( ! isset( $uad['features'] ) || ! isset( $uad['metrics'] ) ) {
            return [ 'error' => 'Invalid UAD format. Missing features or metrics.' ];
        }

        $values = [];

        // Extract values from features.structural
        if (isset($uad['features']['structural'])) {
            $structural = $uad['features']['structural'];
            $values[] = (float) ($structural['depth'] ?? 0);
            $values[] = (float) ($structural['breadth'] ?? 0);
            $values[] = (float) ($structural['density'] ?? 0);
            $values[] = (float) ($structural['symmetry'] ?? 0);
            $values[] = (float) ($structural['entropy'] ?? 0);
            $values[] = (float) count($structural['type_distribution'] ?? []);
        }

        // Extract values from features.semantic
        if (isset($uad['features']['semantic'])) {
            $semantic = $uad['features']['semantic'];
            $values[] = (float) count($semantic['key_frequency'] ?? []);
            $values[] = (float) count($semantic['value_patterns']['numeric_sequences'] ?? []);
            $values[] = (float) count($semantic['value_patterns']['string_patterns'] ?? []);
            $values[] = (float) ($semantic['naming_consistency'] ?? 0);
            $values[] = (float) ($semantic['contextual_relevance'] ?? 0);
        }

        // Extract values from features.relational
        if (isset($uad['features']['relational'])) {
            $relational = $uad['features']['relational'];
            $values[] = (float) count($relational['dependencies'] ?? []);
            $values[] = (float) count($relational['correlations'] ?? []);
            $values[] = (float) count($relational['clusters'] ?? []);
        }

        // Extract values from metrics
        if (isset($uad['metrics'])) {
            $metrics = $uad['metrics'];
            $values[] = (float) ($metrics['complexity'] ?? 0);
            $values[] = (float) ($metrics['coherence'] ?? 0);
            $values[] = (float) ($metrics['stability'] ?? 0);
            $values[] = (float) ($metrics['modularity'] ?? 0);
            $values[] = (float) ($metrics['maintainability'] ?? 0);
        }

        $tensor = [
            'values' => $values,
            'shape'  => [ count( $values ) ],
            'dtype'  => 'float32',
        ];

        return $tensor;
    }
}
