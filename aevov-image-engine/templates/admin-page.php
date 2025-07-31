<div class="wrap">
    <h1><?php _e( 'Aevov Image Engine', 'aevov-image-engine' ); ?></h1>

    <div id="image-generator">
        <h2><?php _e( 'Generate Image', 'aevov-image-engine' ); ?></h2>
        <form id="image-generator-form">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="prompt"><?php _e( 'Prompt', 'aevov-image-engine' ); ?></label>
                        </th>
                        <td>
                            <textarea id="prompt" name="prompt" rows="5" cols="50"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e( 'Generate', 'aevov-image-engine' ); ?></button>
            </p>
        </form>
    </div>

    <div id="image-gallery">
        <h2><?php _e( 'Generated Images', 'aevov-image-engine' ); ?></h2>
        <div id="image-gallery-container"></div>
    </div>

    <h2><?php _e( 'Active Jobs', 'aevov-image-engine' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e( 'Job ID', 'aevov-image-engine' ); ?></th>
                <th><?php _e( 'Status', 'aevov-image-engine' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // This is where we would get the active jobs from the JobManager.
            // For now, I'll just display some dummy data.
            ?>
            <tr>
                <td>job-id-123</td>
                <td>complete</td>
            </tr>
            <tr>
                <td>job-id-456</td>
                <td>processing</td>
            </tr>
        </tbody>
    </table>

    <h2><?php _e( 'Configuration', 'aevov-image-engine' ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'aevov_image_engine_options' );
        do_settings_sections( 'aevov_image_engine' );
        submit_button();
        ?>
    </form>
</div>
