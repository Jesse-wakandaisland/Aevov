<div class="wrap">
    <h1><?php _e( 'Aevov Language Engine', 'aevov-language-engine' ); ?></h1>

    <div id="text-generator">
        <h2><?php _e( 'Generate Text', 'aevov-language-engine' ); ?></h2>
        <form id="text-generator-form">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="prompt"><?php _e( 'Prompt', 'aevov-language-engine' ); ?></label>
                        </th>
                        <td>
                            <textarea id="prompt" name="prompt" rows="5" cols="50"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e( 'Generate', 'aevov-language-engine' ); ?></button>
            </p>
        </form>
    </div>

    <div id="text-result">
        <h2><?php _e( 'Generated Text', 'aevov-language-engine' ); ?></h2>
        <div id="text-result-container"></div>
    </div>
</div>
