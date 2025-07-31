<div class="wrap">
    <h1><?php _e( 'Aevov Simulation Engine', 'aevov-simulation-engine' ); ?></h1>

    <div id="simulation-viewer">
        <h2><?php _e( 'Simulation Viewer', 'aevov-simulation-engine' ); ?></h2>
        <canvas id="simulation-canvas"></canvas>
    </div>

    <div id="simulation-controls">
        <h2><?php _e( 'Controls', 'aevov-simulation-engine' ); ?></h2>
        <button id="start-simulation"><?php _e( 'Start Simulation', 'aevov-simulation-engine' ); ?></button>
        <button id="stop-simulation"><?php _e( 'Stop Simulation', 'aevov-simulation-engine' ); ?></button>
    </div>

    <h2><?php _e( 'Active Jobs', 'aevov-simulation-engine' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e( 'Job ID', 'aevov-simulation-engine' ); ?></th>
                <th><?php _e( 'Status', 'aevov-simulation-engine' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // This is where we would get the active jobs from the JobManager.
            // For now, I'll just display some dummy data.
            ?>
            <tr>
                <td>job-id-123</td>
                <td>running</td>
            </tr>
        </tbody>
    </table>

    <h2><?php _e( 'Configuration', 'aevov-simulation-engine' ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'aevov_simulation_engine_options' );
        do_settings_sections( 'aevov_simulation_engine' );
        submit_button();
        ?>
    </form>
</div>
