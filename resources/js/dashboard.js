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

let chartApi = null;
let range = '1j';

const statusColor = () => getComputedStyle(root).getPropertyValue('--status-fg').trim() || '#5f7078';

function syncChartColor() {
    chartApi?.setColor(statusColor());
}

async function loadChart() {
    if (!chartApi) return;

    try {
        const res = await fetch(`${cfg.riwayatUrl}?rentang=${range}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const { points, thresholds } = await res.json();
        chartApi.setWithDate(range === '7h');
        chartApi.setData(points, thresholds);
    } catch {
        // Biarkan grafik lama; status koneksi sudah ditangani poll().
    }
}

async function initChart() {
    const canvas = root.querySelector('[data-chart]');
    if (!canvas) return;

    // Dimuat dinamis: halaman lain (login dll) tidak menanggung bundle Chart.js.
    const { createCoChart } = await import('./co-chart');
    chartApi = await createCoChart(canvas, { label: cfg.deviceName, tz: cfg.tz, color: statusColor() });
    await loadChart();
}

root.querySelectorAll('[data-range]').forEach((btn) => {
    btn.addEventListener('click', () => {
        range = btn.dataset.range;
        root.querySelectorAll('[data-range]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
        chartApi?.reset('none');
        loadChart();
    });
});
root.querySelector('[data-zoom="in"]')?.addEventListener('click', () => chartApi?.zoomIn());
root.querySelector('[data-zoom="out"]')?.addEventListener('click', () => chartApi?.zoomOut());
root.querySelector('[data-zoom="reset"]')?.addEventListener('click', () => chartApi?.reset());

/* ---------- start ---------- */

if (root.dataset.initial) render(JSON.parse(root.dataset.initial));
timer = setTimeout(poll, cfg.interval);
initChart().catch(() => {});
