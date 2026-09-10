!function(e) {
    "use strict";
    var a = function() {
        this.$realData = [];
    };

    a.prototype.createBarChart = function(e, a, r, t, o, i) {
        Morris.Bar({
            element: e,
            data: a,
            xkey: r,
            ykeys: t,
            labels: o,
            hideHover: "auto",
            resize: true,
            gridLineColor: "rgba(108, 120, 151, 0.1)",
            barSizeRatio: .2,
            barColors: i,
            postUnits: ""
        });
    };

    a.prototype.createLineChart = function(e, a, r, t, o, i, n, s, l) {
        Morris.Line({
            element: e,
            data: a,
            xkey: r,
            ykeys: t,
            labels: o,
            fillOpacity: i,
            pointFillColors: n,
            pointStrokeColors: s,
            behaveLikeLine: true,
            gridLineColor: "rgba(108, 120, 151, 0.1)",
            hideHover: "auto",
            resize: true,
            pointSize: 4,
            lineColors: l,
            postUnits: ""
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

        // Fetch dashboard data from Laravel backend
        $.ajax({
            url: '/dashboard/chart-data', 
            method: 'GET',
            success: function(response) {
                // 1. Donut Chart: Victim Gender
                var victimGenderData = [
                    { label: "Male", value: response.victims.male },
                    { label: "Female", value: response.victims.female },
                    { label: "Undisclosed", value: response.victims.not_provided }
                ];
                self.createDonutChart(
                    "morris-donut-example",
                    victimGenderData,
                    ["#3bafda", "#f5707a", "#4bd396"]
                );

                // 2. Bar Chart: Reports (App vs Non-App)
                var reportData = [
                    { y: "App", a: response.reports.app },
                    { y: "Others", a: response.reports.non_app }
                ];
                self.createBarChart(
                    "morris-bar-example",
                    reportData,
                    "y",
                    ["a"],
                    ["Reports"],
                    ["#10c469", "#188ae2"]
                );

                
                self.createLineChart(
                    "morris-line-example",
                    response.suspects, 
                    "y",
                    ["male", "female"],
                    ["Male", "Female"],
                    ["0.1"],
                    ["#ffffff"],
                    ["#999999"],
                    ["#3bafda", "#f5707a"]
                );
            },
            error: function(xhr) {
                console.error('Error fetching chart data:', xhr);
            }
        });
    };

    e.Dashboard1 = new a;
    e.Dashboard1.Constructor = a;
}(window.jQuery);

(function(e) {
    "use strict";
    window.jQuery.Dashboard1.init();
})();