<?php

namespace AevovImageEngine;

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
            'status' => 'complete',
            'image_url' => 'https://via.placeholder.com/150'
        ];
    }

    public function delete_job( $job_id ) {
        // This is a placeholder.
        return true;
    }
}
