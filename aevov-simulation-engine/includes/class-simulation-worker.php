<?php

namespace AevovSimulationEngine;

class SimulationWorker {

    public function __construct() {
        // Initialize the simulation worker
        $this->log('Simulation worker started.');
    }

    public function run_simulation_loop( $job_id ) {
        $this->log("Starting simulation loop for job: $job_id");

        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );

        if (!$job) {
            $this->log("Job not found: $job_id");
            return;
        }

        $weaver = new SimulationWeaver();
        $state = $weaver->get_initial_state( $job['params'] );
        $websocket_server = new WebSocketServer();

        while ( $this->should_continue_loop($job_id) ) {
            // Update the simulation state
            $state = $this->update_state($state);

            // Broadcast the current state to connected clients
            $websocket_server->broadcast( json_encode( $state ) );

            // Trigger the simulation tick action for other plugins to hook into
            do_action( 'aevov_simulation_tick', $state );

            // Sleep for a short interval to prevent high CPU usage
            sleep( 1 );
        }

        $this->log("Simulation loop finished for job: $job_id");
    }

    private function should_continue_loop($job_id) {
        $job_manager = new JobManager();
        $job = $job_manager->get_job( $job_id );
        return $job && $job['status'] === 'running';
    }

    private function update_state($state) {
        // This is where the core simulation logic would go.
        foreach ( $state['entities'] as &$entity ) {
            if ( $entity['type'] === 'agent' ) {
                // More complex agent behavior
                $this->update_agent_position($entity, $state);
                $this->interact_with_environment($entity, $state);
            }
        }
        return $state;
    }

    private function update_agent_position(&$agent, $state) {
        $direction = rand(0, 3);
        switch ($direction) {
            case 0: // Up
                $agent['y'] = ($agent['y'] - 1 + $state['grid_size']) % $state['grid_size'];
                break;
            case 1: // Down
                $agent['y'] = ($agent['y'] + 1) % $state['grid_size'];
                break;
            case 2: // Left
                $agent['x'] = ($agent['x'] - 1 + $state['grid_size']) % $state['grid_size'];
                break;
            case 3: // Right
                $agent['x'] = ($agent['x'] + 1) % $state['grid_size'];
                break;
        }
    }

    private function interact_with_environment(&$agent, &$state) {
        foreach ($state['entities'] as &$other_entity) {
            if ($agent['id'] !== $other_entity['id'] && $agent['x'] === $other_entity['x'] && $agent['y'] === $other_entity['y']) {
                if ($other_entity['type'] === 'resource') {
                    // Agent consumes the resource
                    $other_entity['value']--;
                    if ($other_entity['value'] <= 0) {
                        // Remove the resource
                        $other_entity['type'] = 'empty';
                    }
                }
            }
        }
    }

    private function log($message) {
        // In a real implementation, this would use a proper logging library.
        error_log('[AevovSimulationEngine] ' . $message);
    }
}
