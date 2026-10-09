/**
 * Interactive Canvas-based Charts for SIPENSA-MH
 * Self-contained, responsive, works offline without external CDN dependencies
 */

class SipensaCharts {
  constructor() {
    this.colorPalette = [
      '#10b981', // Emerald
      '#f59e0b', // Amber
      '#3b82f6', // Blue
      '#8b5cf6', // Violet
      '#ec4899', // Pink
      '#14b8a6'  // Teal
    ];
  }

  // Draw Revenue Trend Chart (Bar + Line)
  renderRevenueChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.clientWidth || 500;
    const height = canvas.height = 240;

    ctx.clearRect(0, 0, width, height);

    const labels = ['Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt'];
    const dataValues = [4200000, 5100000, 6800000, 7400000, 8900000, 9750000];
    const maxVal = 11000000;

    const padLeft = 70;
    const padRight = 30;
    const padBottom = 40;
    const padTop = 30;

    const chartWidth = width - padLeft - padRight;
    const chartHeight = height - padTop - padBottom;

    // Draw grid lines
    ctx.strokeStyle = document.body.classList.contains('dark-theme') ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
    ctx.lineWidth = 1;
    ctx.font = '11px sans-serif';
    ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#94a3b8' : '#64748b';

    const steps = 4;
    for (let i = 0; i <= steps; i++) {
      const y = padTop + (chartHeight / steps) * i;
      const val = maxVal - (maxVal / steps) * i;
      ctx.beginPath();
      ctx.moveTo(padLeft, y);
      ctx.lineTo(width - padRight, y);
      ctx.stroke();

      ctx.textAlign = 'right';
      ctx.fillText(`Rp ${(val / 1000000).toFixed(1)}Jt`, padLeft - 10, y + 4);
    }

    // Draw Bars
    const barWidth = Math.min(36, (chartWidth / labels.length) * 0.48);
    const stepX = chartWidth / labels.length;

    labels.forEach((label, i) => {
      const x = padLeft + (stepX * i) + (stepX - barWidth) / 2;
      const barHeight = (dataValues[i] / maxVal) * chartHeight;
      const y = padTop + chartHeight - barHeight;

      // Gradient fill for bars
      const grad = ctx.createLinearGradient(x, y, x, padTop + chartHeight);
      grad.addColorStop(0, '#10b981');
      grad.addColorStop(1, '#047857');

      ctx.fillStyle = grad;
      ctx.beginPath();
      ctx.roundRect(x, y, barWidth, barHeight, [6, 6, 0, 0]);
      ctx.fill();

      // Top value label
      ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#e2e8f0' : '#1e293b';
      ctx.font = 'bold 10px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(`${(dataValues[i] / 1000000).toFixed(1)}M`, x + barWidth / 2, y - 6);

      // X Label
      ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#94a3b8' : '#64748b';
      ctx.font = '11px sans-serif';
      ctx.fillText(label, x + barWidth / 2, height - padBottom + 20);
    });
  }

  // Draw Category Breakdown (Donut Chart)
  renderCategoryChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const size = Math.min(canvas.parentElement.clientWidth || 280, 240);
    canvas.width = size;
    canvas.height = size;

    ctx.clearRect(0, 0, size, size);

    const categories = [
      { name: 'SPP / Syahriah', value: 45, color: '#10b981' },
      { name: 'Uang Makan', value: 30, color: '#f59e0b' },
      { name: 'Kitab Kuning', value: 15, color: '#3b82f6' },
      { name: 'Infaq Gedung', value: 10, color: '#8b5cf6' }
    ];

    const centerX = size / 2;
    const centerY = size / 2;
    const radius = size * 0.38;
    const innerRadius = size * 0.22;

    let startAngle = -Math.PI / 2;
    const total = categories.reduce((sum, c) => sum + c.value, 0);

    categories.forEach(item => {
      const sliceAngle = (item.value / total) * 2 * Math.PI;
      const endAngle = startAngle + sliceAngle;

      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, startAngle, endAngle);
      ctx.arc(centerX, centerY, innerRadius, endAngle, startAngle, true);
      ctx.closePath();
      ctx.fillStyle = item.color;
      ctx.fill();

      startAngle = endAngle;
    });

    // Center text
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#ffffff' : '#0f172a';
    ctx.font = 'bold 15px sans-serif';
    ctx.fillText('100%', centerX, centerY - 6);
    ctx.font = '10px sans-serif';
    ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#94a3b8' : '#64748b';
    ctx.fillText('Distribusi Pos', centerX, centerY + 12);
  }

  // Draw Prototype Efficiency Comparison (Before vs After)
  renderEfficiencyChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const width = canvas.width = canvas.parentElement.clientWidth || 500;
    const height = canvas.height = 200;

    ctx.clearRect(0, 0, width, height);

    const metrics = [
      {
        nama: 'Waktu Proses Kasir',
        manual: 15.0, // menit
        sistem: 0.7,  // menit (~42 detik)
        unit: 'Menit / Santri',
        manualLabel: '15 Menit',
        sistemLabel: '42 Detik (-95%)'
      },
      {
        nama: 'Rekap Laporan Kasir',
        manual: 180.0, // 3 jam
        sistem: 0.1,   // 5 detik
        unit: 'Waktu Rekap',
        manualLabel: '3 Hari Kerja',
        sistemLabel: '1 Klik Instan (-99%)'
      },
      {
        nama: 'Risiko Selisih Pembukuan',
        manual: 12.0, // 12% selisih buku
        sistem: 0.0,  // 0% selisih
        unit: 'Tingkat Error',
        manualLabel: '12% Selisih',
        sistemLabel: '0% Akurat (100%)'
      }
    ];

    const padLeft = 160;
    const padRight = 100;
    const rowHeight = 52;
    const barMaxW = width - padLeft - padRight;

    metrics.forEach((m, idx) => {
      const y = 30 + (idx * rowHeight);

      // Label
      ctx.textAlign = 'right';
      ctx.font = 'bold 12px sans-serif';
      ctx.fillStyle = document.body.classList.contains('dark-theme') ? '#e2e8f0' : '#1e293b';
      ctx.fillText(m.nama, padLeft - 15, y + 14);

      // Bar Manual
      const barY1 = y;
      const wManual = barMaxW * 0.9;
      ctx.fillStyle = '#ef4444';
      ctx.beginPath();
      ctx.roundRect(padLeft, barY1, wManual, 12, 4);
      ctx.fill();

      // Label manual
      ctx.textAlign = 'left';
      ctx.font = '10px sans-serif';
      ctx.fillStyle = '#ef4444';
      ctx.fillText(`Manual: ${m.manualLabel}`, padLeft + wManual + 8, barY1 + 10);

      // Bar Digital System
      const barY2 = y + 16;
      const wSistem = Math.max(12, barMaxW * 0.06);
      ctx.fillStyle = '#10b981';
      ctx.beginPath();
      ctx.roundRect(padLeft, barY2, wSistem, 12, 4);
      ctx.fill();

      // Label digital
      ctx.fillStyle = '#10b981';
      ctx.font = 'bold 10px sans-serif';
      ctx.fillText(`Digital: ${m.sistemLabel}`, padLeft + wSistem + 8, barY2 + 10);
    });
  }

  renderAll() {
    this.renderRevenueChart('chartRevenueTrend');
    this.renderCategoryChart('chartCategoryPie');
    this.renderEfficiencyChart('chartEfficiencyCompare');
  }
}

window.sipensaCharts = new SipensaCharts();
window.addEventListener('resize', () => {
  if (window.sipensaCharts) {
    window.sipensaCharts.renderAll();
  }
});
