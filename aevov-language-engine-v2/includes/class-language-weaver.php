<?php

namespace AevovLanguageEngineV2;

use AevovEmbeddingEngine\EmbeddingManager;
use AevovChunkRegistry\ChunkRegistry;
use AevovChunkRegistry\AevovChunk;

require_once dirname(__FILE__) . '/../../../aevov-embedding-engine/includes/class-embedding-manager.php';
require_once dirname(__FILE__) . '/../../../aevov-chunk-registry/includes/class-chunk-registry.php';
require_once dirname(__FILE__) . '/../../../aevov-chunk-registry/includes/class-aevov-chunk.php';

class LanguageWeaver {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function get_playlist( $prompt ) {
        $embedding = $this->get_embedding( $prompt );
        $patterns = $this->find_similar_patterns( $embedding );
        return [
            'patterns' => $patterns,
        ];
    }

    private function get_embedding( $prompt ) {
        $embedding_manager = new EmbeddingManager();
        $embedding = $embedding_manager->embed($prompt);
        return $embedding['metadata']['vector'];
    }

    private function find_similar_patterns( $embedding ) {
        $chunk_registry = new ChunkRegistry();
        $chunk = new AevovChunk('temp-id', 'embedding', '', ['vector' => $embedding]);
        $similar_chunks = $chunk_registry->find_similar_chunks($chunk);

        $patterns = [];
        foreach ($similar_chunks as $similar_chunk) {
            $patterns[] = [
                'id' => $similar_chunk->id,
                'cubbit_key' => $similar_chunk->cubbit_key
            ];
        }

        return $patterns;
    }
}
