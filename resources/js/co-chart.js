// Grafik CO bersama (Dashboard & Riwayat). Chart.js + plugin zoom dimuat dinamis.

const cssVar = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

export async function createCoChart(canvas, { label, tz, color }) {
    const [{ default: Chart }, { default: zoom }] = await Promise.all([
        import('chart.js/auto'),
        import('chartjs-plugin-zoom'),
    ]);
    Chart.register(zoom);

    const fmtTime = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: tz });
    const fmtFull = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: tz });
    let withDate = false; // rentang > 1 hari: tick sumbu-x perlu tanggal

    const dashed = (name, c) => ({
        label: name, data: [], borderColor: c, borderDash: [6, 5], borderWidth: 1, pointRadius: 0, pointHitRadius: 0, fill: false,
    });

    const chart = new Chart(canvas, {
        type: 'line',
        data: {
            datasets: [
                { label, data: [], borderColor: color, borderWidth: 2.5, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, fill: false },
                dashed('Ambang waspada', cssVar('--color-waspada') || '#a56b0a'),
                dashed('Ambang bahaya', cssVar('--color-bahaya') || '#c53e42'),
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            interaction: { mode: 'nearest', axis: 'x', intersect: false },
            scales: {
                x: {
                    type: 'linear',
                    grid: { display: false },
                    ticks: { maxTicksLimit: 5, callback: (v) => (withDate ? fmtFull : fmtTime).format(v) },
                },
                y: { beginAtZero: true, ticks: { maxTicksLimit: 4 }, grid: { color: '#e8eef0' }, border: { display: false } },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    filter: (item) => item.datasetIndex === 0,
                    callbacks: { title: (items) => fmtFull.format(items[0].parsed.x), label: (item) => `${item.parsed.y} ppm` },
                },
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: { wheel: { enabled: false }, pinch: { enabled: true }, mode: 'x' }, // wheel off: jangan bajak scroll halaman
                    limits: { x: { min: 'original', max: 'original' } },
                },
            },
        },
    });

    return {
        setWithDate(v) {
            withDate = v;
        },
        setColor(c) {
            chart.data.datasets[0].borderColor = c;
            chart.update('none');
        },
        setData(points, thresholds = {}) {
            const data = points.map(([x, y]) => ({ x, y }));
            const span = data.length ? [data[0].x, data[data.length - 1].x] : [Date.now() - 3600000, Date.now()];
            const line = (v) => span.map((x) => ({ x, y: v ?? null }));

            chart.data.datasets[0].data = data;
            chart.data.datasets[1].data = line(thresholds.waspada);
            chart.data.datasets[2].data = line(thresholds.bahaya);
            chart.options.scales.y.suggestedMax = Math.max((thresholds.bahaya ?? 35) * 1.5, ...data.map((p) => p.y * 1.1));
            chart.update('none');
        },
        zoomIn: () => chart.zoom(1.3),
        zoomOut: () => chart.zoom(0.77),
        reset: (mode) => chart.resetZoom(mode),
    };
}
