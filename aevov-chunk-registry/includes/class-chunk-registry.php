<?php

namespace AevovChunkRegistry;

class ChunkRegistry {

    public function register_chunk( AevovChunk $chunk ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_chunks';

        $data = [
            'chunk_id' => $chunk->id,
            'type' => $chunk->type,
            'cubbit_key' => $chunk->cubbit_key,
            'metadata' => json_encode($chunk->metadata),
            'dependencies' => json_encode($chunk->dependencies)
        ];

        $wpdb->insert($table_name, $data);

        return $wpdb->insert_id;
    }

    public function get_chunk( $chunk_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_chunks';

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE chunk_id = %s", $chunk_id)
        );

        if ($row) {
            return new AevovChunk(
                $row->chunk_id,
                $row->type,
                $row->cubbit_key,
                json_decode($row->metadata, true),
                json_decode($row->dependencies, true)
            );
        }

        return null;
    }

    public function delete_chunk( $chunk_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_chunks';

        return $wpdb->delete($table_name, ['chunk_id' => $chunk_id]);
    }
    public function find_similar_chunks( $pattern ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_chunks';

        // This is a simple search query. A more advanced implementation would
        // use a more sophisticated similarity algorithm.
        $query = "SELECT * FROM $table_name WHERE metadata LIKE %s";
        $search_term = '%' . $wpdb->esc_like(json_encode($pattern->metadata)) . '%';

        $results = $wpdb->get_results(
            $wpdb->prepare($query, $search_term)
        );

        $chunks = [];
        foreach ($results as $row) {
            $chunks[] = new AevovChunk(
                $row->chunk_id,
                $row->type,
                $row->cubbit_key,
                json_decode($row->metadata, true),
                json_decode($row->dependencies, true)
            );
        }

        return $chunks;
    }
}
