@unless ($analysis && $analysis['total'] > 0)
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
@endunless
@include('reports.partials.chart-tip-script')
<script>
(function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    var trend = @json($insights['trend']);
    var comparison = @json($insights['comparisonChart']);
    var text = ReportTip.text;
    var format = ReportTip.format;
    var approvedLabel = text.approved;
    var pendingLabel = text.pending;

    function describeTrend(index) {
        var rows = [
            { label: approvedLabel, value: format(trend.approved[index], 0), tone: 'blue' },
            { label: pendingLabel, value: format(trend.pending[index], 0) }
        ];

        if (index > 0) {
            rows.push(ReportTip.change(trend.approved[index], trend.approved[index - 1]));
        }

        return { title: trend.labels[index], color: '#188ae2', rows: rows };
    }

    function describeWorkstation(index) {
        var approved = comparison.approved[index];
        var rows = [
            { label: approvedLabel, value: format(approved, 0), tone: 'blue' },
            { label: pendingLabel, value: format(comparison.pending[index], 0) },
            { label: text.rejected, value: format(comparison.rejected[index], 0) },
            { label: text.indicatorsReported, value: text.rankOf.replace(':rank', comparison.indicators_reported[index]).replace(':total', comparison.total_indicators) },
            { label: text.shareOfApproved, value: format(comparison.total_approved ? approved / comparison.total_approved * 100 : 0) + '%' }
        ];
        var title = comparison.names[index] + (comparison.types[index] ? ' · ' + comparison.types[index] : '');

        return {
            title: title,
            color: approved > 0 ? '#188ae2' : '#fbbf24',
            rows: rows,
            foot: comparison.last_approved[index] ? text.lastApproved.replace(':date', comparison.last_approved[index]) : null
        };
    }
    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';

    var trendCanvas = document.getElementById('report-trend-chart');
    if (trendCanvas) {
        var trendCtx = trendCanvas.getContext('2d');
        var trendFill = trendCtx.createLinearGradient(0, 0, 0, trendCanvas.clientHeight || 240);
        trendFill.addColorStop(0, 'rgba(24, 138, 226, 0.28)');
        trendFill.addColorStop(1, 'rgba(24, 138, 226, 0)');

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [{
                    label: approvedLabel,
                    data: trend.approved,
                    borderColor: '#188ae2',
                    backgroundColor: trendFill,
                    pointBackgroundColor: '#188ae2',
                    borderWidth: 2,
                    lineTension: 0.3,
                    fill: true
                }, {
                    label: pendingLabel,
                    data: trend.pending,
                    borderColor: '#d97706',
                    backgroundColor: 'transparent',
                    pointBackgroundColor: '#d97706',
                    borderDash: [5, 4],
                    borderWidth: 2,
                    lineTension: 0.3,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#f1f5f9' } }]
                },
                hover: { mode: 'index', intersect: false },
                tooltips: ReportTip.chartTooltips(trendCanvas, describeTrend, true)
            }
        });
    }

    var workstationCanvas = document.getElementById('report-workstation-chart');
    if (workstationCanvas) {
        var workstationCtx = workstationCanvas.getContext('2d');
        var approvedFill = workstationCtx.createLinearGradient(0, 0, workstationCanvas.width || workstationCanvas.clientWidth || 400, 0);
        approvedFill.addColorStop(0, '#188ae2');
        approvedFill.addColorStop(1, '#3b82f6');

        new Chart(workstationCtx, {
            type: 'horizontalBar',
            data: {
                labels: comparison.labels,
                datasets: [{
                    label: approvedLabel,
                    data: comparison.approved,
                    backgroundColor: approvedFill,
                    hoverBackgroundColor: '#2563eb',
                    maxBarThickness: 18
                }, {
                    label: pendingLabel,
                    data: comparison.pending,
                    backgroundColor: '#fbbf24',
                    hoverBackgroundColor: '#d97706',
                    maxBarThickness: 18
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                scales: {
                    xAxes: [{ stacked: true, ticks: { beginAtZero: true, precision: 0 }, gridLines: { display: false } }],
                    yAxes: [{ stacked: true, ticks: { autoSkip: false }, gridLines: { display: false } }]
                },
                tooltips: ReportTip.chartTooltips(workstationCanvas, describeWorkstation, true)
            }
        });
    }
})();
</script>
