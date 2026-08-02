import {
    Chart,
    LineController,
    BarController,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    BarController,
    LineElement,
    BarElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
);

const currencyFormatter = (currency) => new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: currency || 'USD',
    maximumFractionDigits: 0,
});

const initDashboardCharts = () => {
    document.querySelectorAll('[data-chart-labels]').forEach((canvas) => {
        const labels = JSON.parse(canvas.dataset.chartLabels || '[]');
        const values = JSON.parse(canvas.dataset.chartValues || '[]');
        const type = canvas.dataset.chartType || 'line';
        const label = canvas.dataset.chartLabel || 'Value';
        const color = canvas.dataset.chartColor || '#2563eb';
        const currency = canvas.dataset.chartCurrency;

        const isLine = type === 'line';

        new Chart(canvas, {
            type,
            data: {
                labels,
                datasets: [{
                    label,
                    data: values,
                    borderColor: color,
                    backgroundColor: isLine ? `${color}22` : `${color}cc`,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: isLine,
                    borderRadius: 6,
                    maxBarThickness: 36,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = context.parsed.y;

                                if (currency) {
                                    return `${label}: ${currencyFormatter(currency).format(value)}`;
                                }

                                return `${label}: ${value}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 6,
                        },
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.15)',
                        },
                        ticks: {
                            callback(value) {
                                if (currency) {
                                    return currencyFormatter(currency).format(value);
                                }

                                return value;
                            },
                        },
                    },
                },
            },
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
    initDashboardCharts();
}
