@if ($applied && $visualization && ! $visualization['empty'] && $visualization['type'] !== 'list')
@if ($visualization['type'] !== 'map')
<script src="{{ asset('app-assets/libs/chart-js/Chart.bundle.min.js') }}"></script>
@endif
@include('reports.partials.chart-tip-script')
<script>
(function () {
    var visual = @json($visualization);
    var chart = visual.chart;
    var text = ReportTip.text;
    var format = ReportTip.format;
    var statusOf = ReportTip.statusOf;
    var showTip = ReportTip.show;
    var hideTip = ReportTip.hide;

    if (visual.type === 'map') {
        var shades = @json(\App\Services\ReportVisualizationService::MAP_SHADES);
        var regionCount = visual.map.regions.filter(function (region) { return region.tracked; }).length;

        document.querySelectorAll('.me-map-region').forEach(function (path) {
            path.addEventListener('mousemove', function (event) {
                var data = path.dataset;
                var collections = Number(data.collections);
                var card = { title: data.region, color: shades[Number(data.shade)], rows: [] };

                if (data.tracked !== '1') {
                    card.color = '#cbd5e1';
                    card.foot = text.untracked;
                } else {
                    card.rows.push({ label: text.collections, value: format(collections, 0), tone: 'blue' });
                    card.rows.push({ label: text.indicatorsReported, value: format(Number(data.indicators), 0) });

                    if (collections > 0) {
                        card.rows.push({ label: text.shareOfCollections, value: format(Number(data.share)) + '%' });
                        card.rows.push({ label: text.rank, value: text.rankOf.replace(':rank', data.rank).replace(':total', regionCount) });
                    } else {
                        card.foot = text.noCollections;
                    }
                }

                showTip(card, event.clientX, event.clientY);
            });
            path.addEventListener('mouseleave', hideTip);
        });

        return;
    }

    var canvas = document.getElementById('report-visual-chart');

    if (! canvas || ! window.Chart) {
        return;
    }

    Chart.defaults.global.defaultFontColor = '#64748b';
    Chart.defaults.global.defaultFontFamily = 'Inter, sans-serif';

    var ctx = canvas.getContext('2d');
    var width = canvas.width || canvas.clientWidth || 400;
    var height = canvas.height || canvas.clientHeight || 300;
    var percentTick = function (value) { return value + '%'; };
    var sum = ReportTip.sum;

    var gradient = function (from, to, horizontal) {
        var fill = horizontal ? ctx.createLinearGradient(0, 0, width, 0) : ctx.createLinearGradient(0, 0, 0, height);
        fill.addColorStop(0, from);
        fill.addColorStop(1, to);

        return fill;
    };

    var describe = {
        bar: function (index) {
            var value = chart.values[index];

            return {
                title: (chart.titles && chart.titles[index]) || chart.labels[index],
                color: statusOf(value).color,
                rows: [{ label: text.achievement, value: format(value) + '%', tone: 'blue' }],
                status: statusOf(value)
            };
        },
        histogram: function (index) {
            var count = chart.values[index];
            var total = sum(chart.values);

            return {
                title: text.band.replace(':band', chart.labels[index]),
                color: chart.colors[index],
                rows: [
                    { label: text.indicators, value: format(count, 0) },
                    { label: text.shareOfScored, value: format(total ? count / total * 100 : 0) + '%' }
                ]
            };
        },
        pie: function (index) {
            var count = chart.values[index];
            var total = sum(chart.values);

            return {
                title: chart.labels[index],
                color: chart.colors[index],
                rows: [
                    { label: text.indicators, value: format(count, 0) },
                    { label: text.share, value: format(total ? count / total * 100 : 0) + '%' }
                ]
            };
        },
        line: function (index) {
            var value = chart.values[index];

            if (value === null) {
                return { title: chart.labels[index], color: '#94a3b8', rows: [{ label: text.average, value: text.noData }] };
            }

            var gap = Math.max(0, 100 - value);

            return {
                title: chart.labels[index],
                color: '#188ae2',
                rows: [
                    { label: text.average, value: format(value) + '%', tone: 'blue' },
                    { label: text.target, value: '100%' },
                    { label: text.gap, value: gap > 0 ? text.points.replace(':value', format(gap)) : '—' }
                ],
                status: statusOf(value)
            };
        },
        area: function (index) {
            var value = chart.values[index];
            var rows = [{ label: text.collections, value: format(value, 0), tone: 'blue' }];

            if (index > 0) {
                rows.push(ReportTip.change(value, chart.values[index - 1]));
            }

            return { title: chart.labels[index], color: '#188ae2', rows: rows };
        }
    };
    describe.doughnut = describe.pie;
    describe.radar = describe.bar;

    var config;

    if (visual.type === 'bar') {
        var blueBarFill = gradient('#188ae2', '#3b82f6', true);
        var redBarFill = gradient('#dc2626', '#b91c1c', true);

        config = {
            type: 'horizontalBar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: @json(__('Achievement %')),
                    data: chart.values,
                    backgroundColor: chart.colors.map(function (color) {
                        if (color === '#188ae2') {
                            return blueBarFill;
                        }

                        return color === '#dc2626' ? redBarFill : color;
                    }),
                    hoverBackgroundColor: '#2563eb',
                    barPercentage: 0.72,
                    categoryPercentage: 0.7,
                    maxBarThickness: 22
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, suggestedMax: 100, callback: percentTick }, gridLines: { display: false } }],
                    yAxes: [{ ticks: { autoSkip: false }, gridLines: { display: false } }]
                }
            }
        };
    } else if (visual.type === 'histogram') {
        config = {
            type: 'bar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: text.indicators,
                    data: chart.values,
                    backgroundColor: chart.colors,
                    barPercentage: 1,
                    categoryPercentage: 0.96
                }]
            },
            options: {
                legend: { display: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#eef2f7' } }]
                }
            }
        };
    } else if (visual.type === 'pie' || visual.type === 'doughnut') {
        config = {
            type: visual.type,
            data: {
                labels: chart.labels,
                datasets: [{
                    data: chart.values,
                    backgroundColor: chart.colors,
                    hoverBackgroundColor: ['#16a34a', '#b45309', '#991b1b', '#64748b'],
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                legend: { position: 'bottom' },
                cutoutPercentage: visual.type === 'doughnut' ? 62 : 0
            }
        };
    } else if (visual.type === 'line') {
        config = {
            type: 'line',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: text.average,
                    data: chart.values,
                    borderColor: '#188ae2',
                    backgroundColor: 'rgba(24, 138, 226, 0.08)',
                    pointBackgroundColor: '#188ae2',
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    lineTension: 0.3,
                    spanGaps: true,
                    fill: false
                }, {
                    label: text.target,
                    data: chart.labels.map(function () { return 100; }),
                    borderColor: '#22c55e',
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    fill: false
                }]
            },
            options: {
                legend: { position: 'bottom' },
                hover: { mode: 'index', intersect: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true, suggestedMax: 100, callback: percentTick }, gridLines: { color: '#eef2f7' } }]
                }
            }
        };
    } else if (visual.type === 'area') {
        config = {
            type: 'line',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: text.collections,
                    data: chart.values,
                    borderColor: '#188ae2',
                    backgroundColor: gradient('rgba(24, 138, 226, 0.35)', 'rgba(24, 138, 226, 0.02)', false),
                    pointBackgroundColor: '#188ae2',
                    pointHoverRadius: 7,
                    lineTension: 0.3,
                    fill: true
                }]
            },
            options: {
                legend: { display: false },
                hover: { mode: 'index', intersect: false },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{ ticks: { beginAtZero: true, precision: 0 }, gridLines: { color: '#eef2f7' } }]
                }
            }
        };
    } else if (visual.type === 'radar') {
        config = {
            type: 'radar',
            data: {
                labels: chart.labels,
                datasets: [{
                    label: @json(__('Achievement %')),
                    data: chart.values,
                    borderColor: '#188ae2',
                    backgroundColor: 'rgba(24, 138, 226, 0.18)',
                    pointBackgroundColor: '#188ae2',
                    pointHoverRadius: 7
                }]
            },
            options: {
                legend: { display: false },
                scale: { ticks: { beginAtZero: true, suggestedMax: 100, stepSize: 25, callback: percentTick, backdropColor: 'transparent' } }
            }
        };
    }

    if (config) {
        var lineLike = visual.type === 'line' || visual.type === 'area';
        config.options.responsive = true;
        config.options.maintainAspectRatio = false;
        config.options.tooltips = ReportTip.chartTooltips(canvas, describe[visual.type], lineLike);
        new Chart(ctx, config);
    }
})();
</script>
@endif
