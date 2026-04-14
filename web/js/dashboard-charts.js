/**
 * Dashboard Charts Initialization
 *
 * Handles ApexCharts initialization for the dashboard.
 * Uses data from window.dashboardData populated by PHP.
 *
 * @author Your Name
 * @since 1.0.0
 */

(function () {
    "use strict";

    // ============================================================== //
    // Configuration
    // ============================================================== //

    const CONFIG = {
        colors: {
            primary: "#2563EB",
            success: "#16A34A",
            danger: "#DC2626",
            warning: "#F59E0B",
            info: "#0EA5E9",
            secondary: "#6B7280",
        },
        fontFamily: "Inter, -apple-system, BlinkMacSystemFont, sans-serif",
    };

    // ============================================================== //
    // Utility Functions
    // ============================================================== //

    /**
     * Get CSS variable value or fallback color
     * @param {string} varName - CSS variable name
     * @returns {string} Color value
     */
    function getColor(varName) {
        const root = document.documentElement;
        const value = getComputedStyle(root).getPropertyValue(varName).trim();
        return value || CONFIG.colors.primary;
    }

    /**
     * Format currency value
     * @param {number} value - Amount to format
     * @returns {string} Formatted currency string
     */
    function formatCurrency(value) {
        return new Intl.NumberFormat("en-PK", {
            style: "decimal",
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(value);
    }

    /**
     * Check if element exists
     * @param {string} selector - CSS selector
     * @returns {boolean}
     */
    function elementExists(selector) {
        return document.querySelector(selector) !== null;
    }

    // ============================================================== //
    // Chart Initializers
    // ============================================================== //

    /**
     * Initialize Performance Donut Chart
     * Shows balance vs expense ratio for current month
     */
    function initPerformanceDonutChart() {
        const chartEl = document.querySelector("#performanceDonutChart");
        if (!chartEl || !window.dashboardData) return;

        const { balance, expense } = window.dashboardData.currentMonth;

        // Don't render if no data
        if (balance === 0 && expense === 0) {
            chartEl.innerHTML = '<div class="text-center text-muted py-5">No data available for this month</div>';
            return;
        }

        const options = {
            series: [balance, expense],
            chart: {
                type: "donut",
                height: 320,
                fontFamily: CONFIG.fontFamily,
                toolbar: { show: false },
            },
            labels: ["Balance", "Expense"],
            colors: [CONFIG.colors.success, CONFIG.colors.danger],
            plotOptions: {
                pie: {
                    donut: {
                        size: "65%",
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: "14px",
                                fontWeight: 500,
                            },
                            value: {
                                show: true,
                                fontSize: "24px",
                                fontWeight: 600,
                                formatter: (val) => formatCurrency(val),
                            },
                            total: {
                                show: true,
                                label: "Total",
                                fontSize: "14px",
                                fontWeight: 400,
                                color: CONFIG.colors.secondary,
                                formatter: (w) => {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatCurrency(total);
                                },
                            },
                        },
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            legend: {
                position: "bottom",
                fontSize: "13px",
                markers: {
                    width: 12,
                    height: 12,
                    radius: 3,
                },
                itemMargin: {
                    horizontal: 15,
                    vertical: 5,
                },
                formatter: function (seriesName, opts) {
                    const value = opts.w.globals.series[opts.seriesIndex];
                    return `${seriesName}: ${formatCurrency(value)}`;
                },
            },
            stroke: {
                width: 2,
                colors: ["#fff"],
            },
            tooltip: {
                y: {
                    formatter: (val) => formatCurrency(val),
                },
            },
            responsive: [
                {
                    breakpoint: 480,
                    options: {
                        chart: { height: 280 },
                        legend: { position: "bottom" },
                    },
                },
            ],
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    /**
     * Initialize Category Bar Chart
     * Shows expenses breakdown by category
     */
    function initCategoryBarChart() {
        const chartEl = document.querySelector("#categoryBarChart");
        if (!chartEl || !window.dashboardData) return;

        const { names, values } = window.dashboardData.categories;

        // Don't render if no data
        if (!names.length || !values.length) {
            chartEl.innerHTML = '<div class="text-center text-muted py-5">No expense data available</div>';
            return;
        }

        // Generate colors for each category
        const colorPalette = [
            CONFIG.colors.primary,
            CONFIG.colors.success,
            CONFIG.colors.warning,
            CONFIG.colors.danger,
            CONFIG.colors.info,
            "#8B5CF6", // purple
            "#EC4899", // pink
            "#14B8A6", // teal
            "#F97316", // orange
            "#6366F1", // indigo
        ];

        const options = {
            series: [
                {
                    name: "Expense",
                    data: values,
                },
            ],
            chart: {
                type: "bar",
                height: 350,
                fontFamily: CONFIG.fontFamily,
                toolbar: { show: false },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    distributed: true,
                    borderRadius: 4,
                    barHeight: "70%",
                    dataLabels: {
                        position: "top",
                    },
                },
            },
            colors: colorPalette.slice(0, names.length),
            dataLabels: {
                enabled: true,
                textAnchor: "start",
                offsetX: 5,
                style: {
                    fontSize: "12px",
                    fontWeight: 500,
                    colors: ["#374151"],
                },
                formatter: function (val) {
                    return formatCurrency(val);
                },
            },
            xaxis: {
                categories: names,
                labels: {
                    formatter: (val) => formatCurrency(val),
                    style: {
                        fontSize: "12px",
                        colors: CONFIG.colors.secondary,
                    },
                },
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: "12px",
                        colors: CONFIG.colors.secondary,
                    },
                    maxWidth: 150,
                },
            },
            grid: {
                borderColor: "#E5E7EB",
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } },
            },
            legend: {
                show: false,
            },
            tooltip: {
                y: {
                    formatter: (val) => formatCurrency(val),
                },
            },
        };

        const chart = new ApexCharts(chartEl, options);
        chart.render();
    }

    // ============================================================== //
    // Initialization
    // ============================================================== //

    /**
     * Initialize all dashboard charts
     */
    function initDashboard() {
        // Wait for ApexCharts to be available
        if (typeof ApexCharts === "undefined") {
            console.warn("ApexCharts not loaded");
            return;
        }

        initPerformanceDonutChart();
        initCategoryBarChart();
    }

    // Initialize when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard);
    } else {
        initDashboard();
    }

    // Expose for external use if needed
    window.DashboardCharts = {
        init: initDashboard,
        initPerformanceDonut: initPerformanceDonutChart,
        initCategoryBar: initCategoryBarChart,
    };
})();
