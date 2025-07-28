<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap">
    <h1><?php _e('APS Tools Dashboard', 'aps-tools'); ?></h1>

    <div class="aps-metrics-grid">
        <div class="metric-card">
            <h3><?php _e('System Status', 'aps-tools'); ?></h3>
            <div id="system-status-chart" class="chart-container"></div>
            <div class="metric-summary">
                <div class="metric">
                    <span class="label"><?php _e('CPU Usage', 'aps-tools'); ?></span>
                    <span class="value" id="cpu-usage">0%</span>
                </div>
                <div class="metric">
                    <span class="label"><?php _e('Memory Usage', 'aps-tools'); ?></span>
                    <span class="value" id="memory-usage">0%</span>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <h3><?php _e('Pattern Analysis', 'aps-tools'); ?></h3>
            <div id="pattern-metrics-chart" class="chart-container"></div>
            <div class="metric-summary">
                <div class="metric">
                    <span class="label"><?php _e('Patterns Processed', 'aps-tools'); ?></span>
                    <span class="value" id="patterns-processed">0</span>
                </div>
                <div class="metric">
                    <span class="label"><?php _e('Average Confidence', 'aps-tools'); ?></span>
                    <span class="value" id="avg-confidence">0%</span>
                </div>
            </div>
        </div>
    </div>
</div>