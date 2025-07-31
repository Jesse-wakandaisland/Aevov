<?php

namespace AevovImageEngine;

class ImageWorker {

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
        $table_name = $wpdb->prefix . 'aevov_image_jobs';
        $jobs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE status = %s", 'queued' ) );
        return $jobs;
    }

    private function process_job( $job ) {
        $job_manager = new JobManager();
        $job_manager->update_job( $job->job_id, [ 'status' => 'processing' ] );

        // Simulate the image generation process.
        $image_data = $this->generate_image($job->params);
        $image_url = $this->save_image($image_data);

        $job_manager->update_job( $job->job_id, [
            'status' => 'complete',
            'image_url' => $image_url,
        ] );
    }

    private function generate_image($params) {
        $width = isset($params['width']) ? $params['width'] : 512;
        $height = isset($params['height']) ? $params['height'] : 512;
        $text = isset($params['text']) ? $params['text'] : 'Aevov Image';

        // Create a blank image
        $image = imagecreatetruecolor($width, $height);

        // Allocate colors
        $bg_color = imagecolorallocate($image, 240, 240, 240);
        $text_color = imagecolorallocate($image, 50, 50, 50);

        // Fill the background
        imagefill($image, 0, 0, $bg_color);

        // Add the text
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2;
        imagestring($image, $font_size, $x, $y, $text, $text_color);

        return $image;
    }

    private function save_image($image) {
        $upload_dir = wp_upload_dir();
        $filename = 'aevov-image-' . uniqid() . '.png';
        $filepath = $upload_dir['path'] . '/' . $filename;
        $fileurl = $upload_dir['url'] . '/' . $filename;

        imagepng($image, $filepath);
        imagedestroy($image);

        return $fileurl;
    }
}
