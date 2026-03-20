import './bootstrap';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const chartRegistry = new Map();

const defaultChartOptions = () => {
    const styles = getComputedStyle(document.documentElement);
    const textColor = styles.getPropertyValue('--color-ink').trim() || '#1e2b27';
    const mutedColor = styles.getPropertyValue('--color-ink-muted').trim() || '#61706b';
    const borderColor = styles.getPropertyValue('--color-border').trim() || '#e3ddd2';

    return {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                labels: {
                    color: mutedColor,
                    boxWidth: 10,
                    boxHeight: 10,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: {
                        size: 11,
                        family: "'Inter', sans-serif",
                    },
                },
            },
            tooltip: {
                backgroundColor: '#ffffff',
                titleColor: textColor,
                bodyColor: textColor,
                borderColor,
                borderWidth: 1,
                padding: 10,
                titleFont: {
                    size: 12,
                    family: "'Inter', sans-serif",
                },
                bodyFont: {
                    size: 12,
                    family: "'Inter', sans-serif",
                },
            },
        },
        scales: {
            x: {
                grid: {
                    display: false,
                },
                border: {
                    display: false,
                },
                ticks: {
                    color: mutedColor,
                    font: {
                        size: 11,
                        family: "'Inter', sans-serif",
                    },
                },
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: borderColor,
                    drawBorder: false,
                },
                border: {
                    display: false,
                },
                ticks: {
                    color: mutedColor,
                    font: {
                        size: 11,
                        family: "'IBM Plex Mono', monospace",
                    },
                },
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: {
                    drawOnChartArea: false,
                    drawBorder: false,
                },
                border: {
                    display: false,
                },
                ticks: {
                    color: mutedColor,
                    font: {
                        size: 11,
                        family: "'IBM Plex Mono', monospace",
                    },
                },
            },
        },
    };
};

const destroyCharts = () => {
    chartRegistry.forEach((chart) => chart.destroy());
    chartRegistry.clear();
};

const setChartState = (canvas, state) => {
    const shell = canvas.closest('[data-chart-shell]');

    if (shell) {
        shell.dataset.chartState = state;

        const loadingPanel = shell.querySelector('[data-chart-loading]');

        if (loadingPanel) {
            loadingPanel.hidden = state === 'ready';
        }
    }
};

const initializeCharts = () => {
    destroyCharts();

    document.querySelectorAll('[data-chart-config]').forEach((canvas) => {
        const config = canvas.dataset.chartConfig;

        if (!config) {
            return;
        }

        try {
            const parsed = JSON.parse(config);
            const baseOptions = defaultChartOptions();
            const options = parsed.options ?? {};
            const mergedOptions = {
                ...baseOptions,
                ...options,
                plugins: {
                    ...baseOptions.plugins,
                    ...(options.plugins ?? {}),
                },
                scales: {
                    ...baseOptions.scales,
                    ...(options.scales ?? {}),
                },
            };

            canvas.style.height = `${canvas.dataset.chartHeight ?? 220}px`;

            const chart = new Chart(canvas.getContext('2d'), {
                ...parsed,
                options: mergedOptions,
            });

            chartRegistry.set(canvas.id || `chart-${chartRegistry.size}`, chart);
            setChartState(canvas, 'ready');
        } catch (error) {
            setChartState(canvas, 'error');
            console.error('Unable to initialize dashboard chart.', error);
        }
    });
};

document.addEventListener('DOMContentLoaded', initializeCharts);
document.addEventListener('livewire:navigated', initializeCharts);
