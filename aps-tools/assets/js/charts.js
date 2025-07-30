(function($) {
    'use strict';

    $(function() {
        // System Status Chart
        var systemStatusCtx = document.getElementById('system-status-chart');
        if (systemStatusCtx) {
            var systemStatusChart = new Chart(systemStatusCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'CPU Usage',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Memory Usage',
                        data: [],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        // Pattern Metrics Chart
        var patternMetricsCtx = document.getElementById('pattern-metrics-chart');
        if (patternMetricsCtx) {
            var patternMetricsChart = new Chart(patternMetricsCtx, {
                type: 'bar',
                data: {
                    labels: ['Sequential', 'Structural', 'Statistical'],
                    datasets: [{
                        label: 'Patterns Processed',
                        data: [0, 0, 0],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Function to update the charts with new data
        function updateCharts() {
            // Fetch new data from the server
            $.ajax({
                url: apsTools.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'get_system_metrics',
                    nonce: apsTools.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var data = response.data;

                        // Update System Status Chart
                        if (systemStatusChart) {
                            systemStatusChart.data.labels.push(new Date().toLocaleTimeString());
                            systemStatusChart.data.datasets[0].data.push(data.cpu_usage);
                            systemStatusChart.data.datasets[1].data.push(data.memory_usage);
                            systemStatusChart.update();
                        }

                        // Update Pattern Metrics Chart
                        if (patternMetricsChart) {
                            patternMetricsChart.data.datasets[0].data = [
                                data.patterns_processed.sequential,
                                data.patterns_processed.structural,
                                data.patterns_processed.statistical
                            ];
                            patternMetricsChart.update();
                        }

                        // Update summary metrics
                        $('#cpu-usage').text(data.cpu_usage + '%');
                        $('#memory-usage').text(data.memory_usage + '%');
                        $('#patterns-processed').text(data.patterns_processed.total);
                        $('#avg-confidence').text(data.avg_confidence + '%');
                    }
                }
            });
        }

        // Update the charts every 5 seconds
        setInterval(updateCharts, 5000);
    });

})(jQuery);
