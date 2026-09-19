@if ($applied && $analysis && $analysis['total'] > 0)
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
<script>
(function () {
    var chart = @json($analysis['chart']);
    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';

    var bar = document.getElementById('report-achievement-chart');
    if (bar) {
        var barCtx = bar.getContext('2d');
        var blueBarFill = barCtx.createLinearGradient(0, 0, bar.width || bar.clientWidth || 400, 0);
        blueBarFill.addColorStop(0, '#188ae2');
        blueBarFill.addColorStop(1, '#3b82f6');
        var redBarFill = barCtx.createLinearGradient(0, 0, bar.width || bar.clientWidth || 400, 0);
        redBarFill.addColorStop(0, '#dc2626');
        redBarFill.addColorStop(1, '#b91c1c');

        new Chart(barCtx, {
            type: 'horizontalBar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: @json(__('Achievement %')),
                    data: chart.values,
                    backgroundColor: (chart.bar_colors && chart.bar_colors.length)
                        ? chart.bar_colors.map(function (color) {
                            if (color === '#188ae2') {
                                return blueBarFill;
                            }

                            return color === '#dc2626' ? redBarFill : color;
                        })
                        : blueBarFill,
                    hoverBackgroundColor: '#2563eb',
                    barPercentage: 0.72,
                    categoryPercentage: 0.7,
                    maxBarThickness: 22
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, suggestedMax: 100, callback: function (value) { return value + '%'; } }, gridLines: { display: false } }],
                    yAxes: [{ ticks: { autoSkip: false }, gridLines: { display: false } }]
                },
                tooltips: {
                    callbacks: {
                        label: function (item) { return item.xLabel + '%'; }
                    }
                }
            }
        });
    }

    var status = document.getElementById('report-status-chart');
    if (status) {
        var statusCtx = status.getContext('2d');
        var offTrackSlice = statusCtx.createLinearGradient(0, 0, 180, 180);
        offTrackSlice.addColorStop(0, '#dc2626');
        offTrackSlice.addColorStop(1, '#b91c1c');

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: chart.status_labels,
                datasets: [{
                    data: chart.status_values,
                    backgroundColor: ['#22c55e', '#d97706', offTrackSlice, '#94a3b8'],
                    hoverBackgroundColor: ['#16a34a', '#b45309', '#991b1b', '#64748b'],
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                cutoutPercentage: 62
            }
        });
    }
})();
</script>
@endif
