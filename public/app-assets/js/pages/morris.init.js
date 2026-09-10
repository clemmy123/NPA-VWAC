!function(e) {
    "use strict";
    var a = function() {};

    a.prototype.createBarChart = function(e, a, r, t, i, o) {
        Morris.Bar({
            element: e,
            data: a,
            xkey: r,
            ykeys: t,
            labels: i,
            hideHover: "auto",
            resize: true,
            gridLineColor: "rgba(108, 120, 151, 0.1)",
            barSizeRatio: .4,
            barColors: o
        });
    };

    a.prototype.createStackedChart = function(e, a, r, t, i, o) {
        Morris.Bar({
            element: e,
            data: a,
            xkey: r,
            ykeys: t,
            stacked: true,
            labels: i,
            hideHover: "auto",
            resize: true,
            gridLineColor: "rgba(108, 120, 151, 0.1)",
            barColors: o
        });
    };

    a.prototype.createDonutChart = function(e, a, r) {
        Morris.Donut({
            element: e,
            data: a,
            resize: true,
            colors: r
        });
    };

    a.prototype.init = function() {
        var self = this;

        // Setup CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Donut Chart: Message Status
        $.ajax({
            url: '/dashboard/message-status',
            method: 'GET',
            success: function(response) {
                var messageStatusData = [
                    { label: "Pending", value: response.pending },
                    { label: "On Progress", value: response.on_progress },
                    { label: "Reported", value: response.reported }
                ];
                self.createDonutChart(
                    "morris-donut-example",
                    messageStatusData,
                    ["#3bafda", "#f7b84b", "#4bd396"]
                );
            },
            error: function(xhr) {
                console.error('Error fetching message status:', xhr.status, xhr.statusText);
            }
        });

        // Bar Chart: Daily Counts
        $.ajax({
            url: '/dashboard/daily-counts',
            method: 'GET',
            success: function(response) {
                var dailyData = [
                    { y: "Victims", a: response.victims },
                    { y: "Suspects", a: response.suspects },
                    { y: "Reporters", a: response.reporters }
                ];
                self.createBarChart(
                    "morris-bar-example",
                    dailyData,
                    "y",
                    ["a"],
                    ["Count"],
                    ["#188ae2", "#f5707a", "#26a69a"]
                );
            },
            error: function(xhr) {
                console.error('Error fetching daily counts:', xhr.status, xhr.statusText);
            }
        });

        // Stacked Bar Chart: Monthly Reports by Gender
        $.ajax({
            url: '/dashboard/monthly-reports',
            method: 'GET',
            success: function(response) {
                self.createStackedChart(
                    "morris-bar-stacked",
                    response,
                    "y",
                    ["male", "female"],
                    ["Male", "Female"],
                    ["#3bafda", "#f5707a"]
                );
            },
            error: function(xhr) {
                console.error('Error fetching monthly reports:', xhr.status, xhr.statusText);
            }
        });
    };

    e.MorrisCharts = new a;
    e.MorrisCharts.Constructor = a;
}(window.jQuery);

(function(e) {
    "use strict";
    window.jQuery.MorrisCharts.init();
})();