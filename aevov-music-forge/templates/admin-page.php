<div class="wrap">
    <h1><?php _e( 'Aevov Music Forge', 'aevov-music-forge' ); ?></h1>

    <div id="music-composer">
        <h2><?php _e( 'Compose Music', 'aevov-music-forge' ); ?></h2>
        <form id="music-composer-form">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="genre"><?php _e( 'Genre', 'aevov-music-forge' ); ?></label>
                        </th>
                        <td>
                            <select id="genre" name="genre">
                                <option value="rock"><?php _e( 'Rock', 'aevov-music-forge' ); ?></option>
                                <option value="pop"><?php _e( 'Pop', 'aevov-music-forge' ); ?></option>
                                <option value="jazz"><?php _e( 'Jazz', 'aevov-music-forge' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="mood"><?php _e( 'Mood', 'aevov-music-forge' ); ?></label>
                        </th>
                        <td>
                            <select id="mood" name="mood">
                                <option value="happy"><?php _e( 'Happy', 'aevov-music-forge' ); ?></option>
                                <option value="sad"><?php _e( 'Sad', 'aevov-music-forge' ); ?></option>
                                <option value="energetic"><?php _e( 'Energetic', 'aevov-music-forge' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e( 'Compose', 'aevov-music-forge' ); ?></button>
            </p>
        </form>
    </div>

    <div id="music-player">
        <h2><?php _e( 'Generated Music', 'aevov-music-forge' ); ?></h2>
        <div id="music-player-container"></div>
    </div>

    <h2><?php _e( 'Active Jobs', 'aevov-music-forge' ); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e( 'Job ID', 'aevov-music-forge' ); ?></th>
                <th><?php _e( 'Status', 'aevov-music-forge' ); ?></th>
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

    <h2><?php _e( 'Configuration', 'aevov-music-forge' ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'aevov_music_forge_options' );
        do_settings_sections( 'aevov_music_forge' );
        submit_button();
        ?>
    </form>
</div>
