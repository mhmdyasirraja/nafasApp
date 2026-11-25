// Dashboard: SATU renderer (render(snapshot)) dipakai untuk data awal & tiap polling.
// Semua teks datang dari server (lang/id/dashboard.php); JS tidak berisi copy status.

const root = document.querySelector('[data-dashboard]');

const cfg = {
    snapshotUrl: root.dataset.snapshotUrl,
    riwayatUrl: root.dataset.riwayatUrl,
    interval: Number(root.dataset.interval) || 5000,
    tz: root.dataset.timezone || 'Asia/Jakarta',
    msgLost: root.dataset.msgLost || 'Terputus dari server',
    deviceName: root.dataset.deviceName || '',
};

const cssVar = (name, el = document.documentElement) => getComputedStyle(el).getPropertyValue(name).trim();
const binds = (name) => root.querySelectorAll(`[data-bind="${name}"]`);

function setText(name, value) {
    binds(name).forEach((el) => {
        el.textContent = value ?? ''; // textContent, bukan innerHTML: tidak ada celah XSS
        if (el.hasAttribute('data-hide-empty')) el.hidden = !value;
    });
}

/* ---------- render ---------- */

const DOT = { aman: 'bg-aman', waspada: 'bg-waspada', bahaya: 'bg-bahaya' };

function activityItem(a) {
    const li = document.createElement('li');
    li.className = 'relative pl-4';

    const dot = document.createElement('span');
    dot.className = `absolute left-0 top-1.5 size-1.5 rounded-full ${DOT[a.level] ?? 'bg-muted'}`;

    const head = document.createElement('div');
    head.className = 'flex items-baseline justify-between gap-3';
    const title = document.createElement('p');
    title.className = 'text-sm font-medium';
    title.textContent = a.title;
    const time = document.createElement('span');
    time.className = 'shrink-0 text-xs text-muted';
    time.textContent = a.time;
    head.append(title, time);

    const detail = document.createElement('p');
    detail.className = 'mt-0.5 text-xs text-muted';
    detail.textContent = a.detail;

    li.append(dot, head, detail);

    return li;
}

function setLinkLost(lost) {
    binds('server_link').forEach((el) => {
        el.dataset.lost = String(lost);
        if (lost) el.textContent = cfg.msgLost;
    });
}

function render(s) {
    root.dataset.status = s.state;
    root.style.setProperty('--frac', String(s.fraction));

    Object.entries(s.text).forEach(([key, value]) => setText(key, value));

    root.querySelectorAll('[data-state-icon]').forEach((el) => {
        el.classList.toggle('hidden', !el.dataset.stateIcon.split(' ').includes(s.state));
    });

    root.querySelector('[data-list="activities"]').replaceChildren(...s.activities.map(activityItem));
    root.querySelector('[data-empty="activities"]').hidden = s.activities.length > 0;

    setLinkLost(false);
    syncChartColor();
}

/* ---------- polling ---------- */

let timer = null;
let failures = 0;
let ticks = 0;

async function poll() {
    clearTimeout(timer);
    if (document.hidden) return; // tab di background: berhenti; dilanjut oleh visibilitychange

    try {
        const res = await fetch(cfg.snapshotUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (res.status === 401) {
            location.reload(); // sesi habis -> biar server yang mengarahkan ke login
            return;
        }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        render(await res.json());
        failures = 0;
        if (++ticks % 6 === 0) loadChart(); // grafik disegarkan tiap ~30 dtk
    } catch {
        // Jangan biarkan angka lama terlihat seperti "langsung": tandai setelah 2x gagal berturut-turut.
        if (++failures >= 2) setLinkLost(true);
    }

    // setTimeout berantai (bukan setInterval) => request tidak pernah menumpuk saat server lambat.
    timer = setTimeout(poll, cfg.interval);
}

document.addEventListener('visibilitychange', () => {
    if (!document.hidden) poll();
});

/* ---------- grafik ---------- */

let chart = null;
let range = '1j';

const fmtTime = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: cfg.tz });
const fmtFull = new Intl.DateTimeFormat('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', hour12: false, timeZone: cfg.tz });

const statusColor = () => cssVar('--status-fg', root) || '#5f7078';

function syncChartColor() {
    if (!chart) return;
    chart.data.datasets[0].borderColor = statusColor();
    chart.update('none');
}

async function loadChart() {
    if (!chart) return;

    try {
        const res = await fetch(`${cfg.riwayatUrl}?rentang=${range}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const { points, thresholds } = await res.json();
        const data = points.map(([x, y]) => ({ x, y }));
        const span = data.length ? [data[0].x, data[data.length - 1].x] : [Date.now() - 3600000, Date.now()];
        const line = (v) => span.map((x) => ({ x, y: v ?? null }));

        chart.data.datasets[0].data = data;
        chart.data.datasets[1].data = line(thresholds.waspada);
        chart.data.datasets[2].data = line(thresholds.bahaya);
        chart.options.scales.y.suggestedMax = Math.max((thresholds.bahaya ?? 35) * 1.5, ...data.map((p) => p.y * 1.1));
        chart.update('none');
    } catch {
        // Biarkan grafik lama; status koneksi sudah ditangani poll().
    }
}

async function initChart() {
    const canvas = root.querySelector('[data-chart]');
    if (!canvas) return;

    // Dimuat dinamis: halaman lain (login dll) tidak menanggung bundle Chart.js.
    const [{ default: Chart }, { default: zoom }] = await Promise.all([
        import('chart.js/auto'),
        import('chartjs-plugin-zoom'),
    ]);
    Chart.register(zoom);

    const dashed = (label, color) => ({
        label, data: [], borderColor: color, borderDash: [6, 5], borderWidth: 1, pointRadius: 0, pointHitRadius: 0, fill: false,
    });

    chart = new Chart(canvas, {
        type: 'line',
        data: {
            datasets: [
                { label: cfg.deviceName, data: [], borderColor: statusColor(), borderWidth: 2.5, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, fill: false },
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
                    ticks: { maxTicksLimit: 5, callback: (v) => (range === '7h' ? fmtFull : fmtTime).format(v) },
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

    await loadChart();
}

root.querySelectorAll('[data-range]').forEach((btn) => {
    btn.addEventListener('click', () => {
        range = btn.dataset.range;
        root.querySelectorAll('[data-range]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
        chart?.resetZoom('none');
        loadChart();
    });
});
root.querySelector('[data-zoom="in"]')?.addEventListener('click', () => chart?.zoom(1.3));
root.querySelector('[data-zoom="out"]')?.addEventListener('click', () => chart?.zoom(0.77));
root.querySelector('[data-zoom="reset"]')?.addEventListener('click', () => chart?.resetZoom());

/* ---------- start ---------- */

if (root.dataset.initial) render(JSON.parse(root.dataset.initial));
timer = setTimeout(poll, cfg.interval);
initChart().catch(() => {});
