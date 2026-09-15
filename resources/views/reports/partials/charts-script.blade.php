@if ($applied && $analysis && $analysis['total'] > 0)
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
<script>
(function () {
    var chart = @json($analysis['chart']);
    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';

    var bar = document.getElementById('report-achievement-chart');
    if (bar) {
        new Chart(bar.getContext('2d'), {
            type: 'horizontalBar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: 'Achievement %',
                    data: chart.values,
                    backgroundColor: '#3b82f6',
                    hoverBackgroundColor: '#2563eb'
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
        new Chart(status.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chart.status_labels,
                datasets: [{
                    data: chart.status_values,
                    backgroundColor: ['#22c55e', '#d97706', '#dc2626', '#94a3b8'],
                    hoverBackgroundColor: ['#16a34a', '#b45309', '#b91c1c', '#64748b'],
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
