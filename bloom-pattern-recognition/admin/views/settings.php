
// File: admin/views/settings.php
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form method="post" action="edit.php?action=bloom_save_settings">
        <?php wp_nonce_field('bloom_save_settings'); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="chunk_size"><?php _e('Chunk Size (MB)', 'bloom-pattern-system'); ?></label>
                </th>
                <td>
                    <input type="number" name="chunk_size" id="chunk_size" value="7" min="1" max="50">
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
```