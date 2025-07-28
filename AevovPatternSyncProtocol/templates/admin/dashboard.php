<?php
/**
 * templates/admin/dashboard.php
 * Admin dashboard template
 */
?>
<div class="wrap aps-dashboard">
    <h1><?php _e('APS Pattern System Dashboard', 'aps'); ?></h1>

    <div class="aps-dashboard-grid">
        <div class="aps-card aps-stats-card">
            <h2><?php _e('System Overview', 'aps'); ?></h2>
            <div class="aps-stats-grid">
                <div class="aps-stat">
                    <span class="aps-stat-value"><?php echo esc_html($metrics['total_comparisons']); ?></span>
                    <span class="aps-stat-label"><?php _e('Total Comparisons', 'aps'); ?></span>
                </div>
                <div class="aps-stat">
                    <span class="aps-stat-value"><?php echo esc_html($metrics['pattern_stats']['total']); ?></span>
                    <span class="aps-stat-label"><?php _e('Patterns Analyzed', 'aps'); ?></span>
                </div>
                <div class="aps-stat">
                    <span class="aps-stat-value"><?php echo number_format($metrics['pattern_stats']['avg_score'] * 100, 1); ?>%</span>
                    <span class="aps-stat-label"><?php _e('Average Match Score', 'aps'); ?></span>
                </div>
            </div>
        </div>

        <div class="aps-card aps-recent-card">
            <h2><?php _e('Recent Comparisons', 'aps'); ?></h2>
            <?php if (!empty($metrics['recent_comparisons'])): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'aps'); ?></th>
                            <th><?php _e('Type', 'aps'); ?></th>
                            <th><?php _e('Items', 'aps'); ?></th>
                            <th><?php _e('Score', 'aps'); ?></th>
                            <th><?php _e('Date', 'aps'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metrics['recent_comparisons'] as $comparison): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url($this->get_comparison_url($comparison->id)); ?>">
                                        <?php echo esc_html($comparison->id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($comparison->comparison_type); ?></td>
                                <td><?php echo count(json_decode($comparison->items_data)); ?></td>
                                <td><?php echo number_format($comparison->match_score * 100, 1); ?>%</td>
                                <td><?php echo esc_html(human_time_diff(strtotime($comparison->created_at), current_time('timestamp'))); ?> ago</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="aps-no-data"><?php _e('No recent comparisons found.', 'aps'); ?></p>
            <?php endif; ?>
        </div>

        <div class="aps-card aps-integration-card">
            <h2><?php _e('BLOOM Integration Status', 'aps'); ?></h2>
            <div class="aps-integration-status">
                <?php if ($metrics['integration_status']['connected']): ?>
                    <div class="aps-status-item aps-status-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span class="aps-status-text"><?php _e('Connected to BLOOM', 'aps'); ?></span>
                    </div>
                    <div class="aps-status-details">
                        <p>
                            <?php printf(
                                __('Last sync: %s ago', 'aps'),
                                human_time_diff(strtotime($metrics['integration_status']['last_sync']), current_time('timestamp'))
                            ); ?>
                        </p>
                        <p>
                            <?php printf(
                                __('Patterns synced: %s', 'aps'),
                                number_format($metrics['integration_status']['synced_patterns'])
                            ); ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="aps-status-item aps-status-error">
                        <span class="dashicons dashicons-warning"></span>
                        <span class="aps-status-text"><?php _e('Not connected to BLOOM', 'aps'); ?></span>
                    </div>
                    <div class="aps-status-details">
                        <p><?php _e('Please check your integration settings and ensure BLOOM is properly configured.', 'aps'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=aps-settings')); ?>" class="button">
                            <?php _e('Configure Integration', 'aps'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

