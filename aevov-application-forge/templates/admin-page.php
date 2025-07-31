<div class="wrap">
    <h1><?php _e( 'Aevov Application Forge', 'aevov-application-forge' ); ?></h1>

    <div id="application-viewer">
        <h2><?php _e( 'Application Viewer', 'aevov-application-forge' ); ?></h2>
        <div id="application-container"></div>
    </div>

    <div id="application-controls">
        <h2><?php _e( 'Controls', 'aevov-application-forge' ); ?></h2>
        <button id="spawn-application"><?php _e( 'Spawn Application', 'aevov-application-forge' ); ?></button>
        <button id="evolve-application"><?php _e( 'Evolve Application', 'aevov-application-forge' ); ?></button>
    </div>

    <h2><?php _e( 'Active Jobs', 'aevov-application-forge' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e( 'Job ID', 'aevov-application-forge' ); ?></th>
                <th><?php _e( 'Status', 'aevov-application-forge' ); ?></th>
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

    <h2><?php _e( 'Configuration', 'aevov-application-forge' ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'aevov_application_forge_options' );
        do_settings_sections( 'aevov_application_forge' );
        submit_button();
        ?>
    </form>
</div>
