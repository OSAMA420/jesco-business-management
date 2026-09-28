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

Alpine.data('salesDashboard', () => {
    // Chart.js instances stay outside Alpine's reactive object on purpose:
    // Alpine deep-proxies returned state, and Chart.js instances hold circular
    // internal references that blow the call stack when proxied.
    let salesChart = null;
    let statusChart = null;

    const datasets = {
        '7d': { labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [62000, 81000, 74000, 95000, 88000, 132000, 118000] },
        '30d': { labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'], data: [420000, 380000, 510000, 465000] },
        '12m': { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'], data: [780000, 820000, 910000, 860000, 940000, 1010000, 980000, 1120000, 1245000] },
    };

    return {
        period: '7d',

        init() {
            const d = datasets[this.period];

            salesChart = new Chart(this.$refs.salesCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: d.labels,
                    datasets: [{
                        label: 'Sales',
                        data: d.data,
                        borderColor: '#e90f08',
                        backgroundColor: 'rgba(233,15,8,0.08)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#e90f08',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (c) => 'Rs. ' + c.parsed.y.toLocaleString('en-US') } },
                    },
                    scales: {
                        y: { ticks: { callback: (v) => (v / 1000) + 'k' }, grid: { color: '#f3f4f6' } },
                        x: { grid: { display: false } },
                    },
                },
            });

            statusChart = new Chart(this.$refs.statusCanvas.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Delivered', 'Processing', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [68, 24, 21, 15],
                        backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b', '#f43f5e'],
                        borderWidth: 0,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 10, padding: 14, font: { size: 11 } } },
                    },
                },
            });
        },

        setPeriod(p) {
            this.period = p;
            const d = datasets[p];
            salesChart.data.labels = d.labels;
            salesChart.data.datasets[0].data = d.data;
            salesChart.update();
        },
    };
});

Alpine.start();
