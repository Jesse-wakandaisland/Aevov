<?php

namespace AevovMusicForge;

class MusicWorker {

    public function __construct() {
        // We will add our hooks and filters here.
    }

    public function run() {
        $job_manager = new JobManager();
        $jobs = $this->get_queued_jobs();
        foreach ( $jobs as $job ) {
            $this->process_job( $job );
        }
    }

    private function get_queued_jobs() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aevov_music_jobs';
        $jobs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE status = %s", 'queued' ) );
        return $jobs;
    }

    private function process_job( $job ) {
        $job_manager = new JobManager();
        $job_manager->update_job( $job->job_id, [ 'status' => 'processing' ] );

        // Simulate the music generation process.
        $audio_data = $this->generate_audio($job->params);
        $track_url = $this->save_audio($audio_data);

        $job_manager->update_job( $job->job_id, [
            'status' => 'complete',
            'track_url' => $track_url,
        ] );
    }

    private function generate_audio($params) {
        $duration = isset($params['duration']) ? $params['duration'] : 5; // in seconds
        $sample_rate = 44100;
        $num_samples = $duration * $sample_rate;
        $frequency = isset($params['frequency']) ? $params['frequency'] : 440; // A4 note

        $data = '';
        for ($i = 0; $i < $num_samples; $i++) {
            $sample = sin($i * 2 * M_PI * $frequency / $sample_rate);
            // Convert to 16-bit signed integer
            $data .= pack('s', $sample * 32767);
        }

        return $data;
    }

    private function save_audio($audio_data) {
        $upload_dir = wp_upload_dir();
        $filename = 'aevov-music-' . uniqid() . '.wav';
        $filepath = $upload_dir['path'] . '/' . $filename;
        $fileurl = $upload_dir['url'] . '/' . $filename;

        // Create a WAV file header
        $header = $this->get_wav_header(strlen($audio_data));

        file_put_contents($filepath, $header . $audio_data);

        return $fileurl;
    }

    private function get_wav_header($data_size) {
        $sample_rate = 44100;
        $bits_per_sample = 16;
        $channels = 1;

        $header = 'RIFF';
        $header .= pack('V', 36 + $data_size);
        $header .= 'WAVE';
        $header .= 'fmt ';
        $header .= pack('V', 16); // PCM
        $header .= pack('v', 1); // Audio format
        $header .= pack('v', $channels);
        $header .= pack('V', $sample_rate);
        $header .= pack('V', $sample_rate * $channels * $bits_per_sample / 8); // Byte rate
        $header .= pack('v', $channels * $bits_per_sample / 8); // Block align
        $header .= pack('v', $bits_per_sample);
        $header .= 'data';
        $header .= pack('V', $data_size);

        return $header;
    }
}
