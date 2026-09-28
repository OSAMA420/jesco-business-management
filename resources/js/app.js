import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('counter', (target = 0, prefix = '', suffix = '') => ({
    display: prefix + '0' + suffix,
    init() {
        const duration = 1000;
        const startTime = performance.now();
        const format = (n) => Math.round(n).toLocaleString('en-US');

        const step = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            this.display = prefix + format(eased * target) + suffix;
            if (progress < 1) requestAnimationFrame(step);
        };

        requestAnimationFrame(step);
    },
}));

/**
 * Line chart with a 7 days / 30 days / 12 months toggle.
 * periods: { '7d': { labels: [...], series: { key: [numbers] } }, ... }
 * series:  [{ key, label, color, money }] in categorical order.
 */
Alpine.data('trendChart', (periods, series) => {
    // The Chart.js instance stays outside Alpine's reactive object on purpose:
    // Alpine deep-proxies returned state, and Chart.js instances hold circular
    // internal references that blow the call stack when proxied.
    let chart = null;
    const format = (s, v) => (s.money ? 'Rs. ' : '') + Number(v).toLocaleString('en-US') + (s.money ? '' : ' units');
    const build = (p) => series.map((s) => ({
        label: s.label,
        data: periods[p].series[s.key],
        borderColor: s.color,
        backgroundColor: series.length === 1 ? s.color + '14' : 'transparent',
        fill: series.length === 1,
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: s.color,
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        borderWidth: 2,
    }));

    return {
        period: '7d',

        init() {
            chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: 'line',
                data: { labels: periods[this.period].labels, datasets: build(this.period) },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: series.length > 1, position: 'top', align: 'end', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } },
                        tooltip: { callbacks: { label: (c) => series[c.datasetIndex].label + ': ' + format(series[c.datasetIndex], c.parsed.y) } },
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: (v) => (v >= 1000 ? (v / 1000) + 'k' : v), color: '#9ca3af', precision: 0 }, grid: { color: '#f3f4f6' }, border: { display: false } },
                        x: { grid: { display: false }, ticks: { color: '#6b7280' } },
                    },
                },
            });
        },

        setPeriod(p) {
            this.period = p;
            chart.data.labels = periods[p].labels;
            chart.data.datasets = build(p);
            chart.update();
        },
    };
});

Alpine.start();
