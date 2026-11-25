// Halaman Riwayat: data sudah dirender server (statis per filter), JS hanya menggambar grafik.
import { createCoChart } from './co-chart';

const root = document.querySelector('[data-riwayat]');
const canvas = root.querySelector('[data-chart]');

async function init() {
    const { points, thresholds } = JSON.parse(canvas.dataset.chart);

    const api = await createCoChart(canvas, {
        label: root.dataset.deviceName,
        tz: root.dataset.timezone || 'Asia/Jakarta',
        color: getComputedStyle(document.documentElement).getPropertyValue('--color-brand').trim() || '#0f7f7a',
    });

    // Rentang > ±1,5 hari: tick sumbu-x perlu tanggal, bukan hanya jam.
    api.setWithDate(points.length > 1 && points[points.length - 1][0] - points[0][0] > 36 * 3600 * 1000);
    api.setData(points, thresholds);

    root.querySelector('[data-zoom="in"]')?.addEventListener('click', () => api.zoomIn());
    root.querySelector('[data-zoom="out"]')?.addEventListener('click', () => api.zoomOut());
    root.querySelector('[data-zoom="reset"]')?.addEventListener('click', () => api.reset());
}

init().catch(() => {});
