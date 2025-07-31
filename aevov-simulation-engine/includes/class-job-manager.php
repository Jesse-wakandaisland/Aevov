<?php

namespace AevovSimulationEngine;

class JobManager {

    public function create_job( $params ) {
        // This is a placeholder.
        return 'job-id-' . uniqid();
    }

    public function get_job( $job_id ) {
        // This is a placeholder.
        return [
            'job_id' => $job_id,
            'params' => [],
            'status' => 'running',
            'websocket_url' => 'ws://localhost:8080'
        ];
    }

    public function delete_job( $job_id ) {
        // This is a placeholder.
        return true;
    }
}
