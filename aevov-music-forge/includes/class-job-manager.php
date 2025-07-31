<?php

namespace AevovMusicForge;

class JobManager {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aevov_music_jobs';
    }

    public function create_job( $params ) {
        global $wpdb;
        $job_id = wp_generate_uuid4();
        $wpdb->insert(
            $this->table_name,
            [
                'job_id' => $job_id,
                'user_id' => get_current_user_id(),
                'params' => json_encode( $params ),
                'status' => 'queued',
                'track_url' => '',
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ]
        );
        return $job_id;
    }

    public function get_job( $job_id ) {
        global $wpdb;
        $job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE job_id = %s", $job_id ) );
        if ( $job ) {
            $job->params = json_decode( $job->params, true );
        }
        return $job;
    }

    public function update_job( $job_id, $data ) {
        global $wpdb;
        $wpdb->update(
            $this->table_name,
            [
                'status' => isset( $data['status'] ) ? $data['status'] : null,
                'track_url' => isset( $data['track_url'] ) ? $data['track_url'] : null,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'job_id' => $job_id ]
        );
    }

    public function delete_job( $job_id ) {
        global $wpdb;
        $wpdb->delete( $this->table_name, [ 'job_id' => $job_id ] );
    }
}
