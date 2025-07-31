<?php

namespace AevovApplicationForge;

class ApplicationWorker {

    public function __construct() {
        // Initialize the application worker
        $this->log('Application worker started.');
    }

    public function run_application_loop( $job_id ) {
        $this->log("Starting application loop for job: $job_id");

        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );

        if (!$job) {
            $this->log("Job not found: $job_id");
            return;
        }

        $weaver = new ApplicationWeaver();
        $state = $weaver->get_genesis_state( $job['params'] );

        $websocket_server = new WebSocketServer();

        while ( $this->should_continue_loop($job_id) ) {
            // Update the application state
            $state = $this->update_state($state);

            // Broadcast the current state to connected clients
            $websocket_server->broadcast( json_encode( $state ) );

            // Trigger the application tick action for other plugins to hook into
            do_action( 'aevov_application_tick', $state );

            // Sleep for a short interval to prevent high CPU usage
            sleep( 1 );
        }

        $this->log("Application loop finished for job: $job_id");
    }

    private function should_continue_loop($job_id) {
        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );
        return $job && $job['status'] === 'running';
    }

    private function update_state($state) {
        // This is where the core application logic would go.
        // For now, we'll just increment a counter to show that the state is changing.
        if (!isset($state['tick_count'])) {
            $state['tick_count'] = 0;
        }
        $state['tick_count']++;
        return $state;
    }

    private function log($message) {
        // In a real implementation, this would use a proper logging library.
        error_log('[AevovApplicationForge] ' . $message);
    }
}
