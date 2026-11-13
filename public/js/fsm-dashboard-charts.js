(function () {
    'use strict';

    // Apply these options before each FSM chart's first render, including charts
    // initialized on DOMContentLoaded. Other dashboards keep their own options.
    Chart.plugins.register({
        beforeInit: function (chart) {
            if (!chart.canvas.closest('.fsm-dashboard')) {
                return;
            }

            chart.options.animation = { duration: 0 };
            chart.options.hover.animationDuration = 0;
            chart.options.responsiveAnimationDuration = 0;
            chart.options.responsive = true;
            chart.options.maintainAspectRatio = false;
        }
    });
}());
