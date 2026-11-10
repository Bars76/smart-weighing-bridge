/**
 * Smart Weighing Bridge - Chart.js
 * Grafik analitik penimbangan
 */

let chartDaily = null;
let chartWeight = null;

function initCharts() {
  const ctxDaily = document.getElementById('chartDaily');
  const ctxWeight = document.getElementById('chartWeight');

  if (!ctxDaily || !ctxWeight) return;

  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { mode: 'index', intersect: false },
    },
    scales: {
      y: { beginAtZero: true, ticks: { stepSize: 1 } },
      x: { ticks: { maxRotation: 45, minRotation: 45 } },
    },
  };

  chartDaily = new Chart(ctxDaily, {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Jumlah Penimbangan', data: [], backgroundColor: '#3b82f6' }] },
    options: commonOptions,
  });

  chartWeight = new Chart(ctxWeight, {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Total Berat (kg)', data: [], borderColor: '#2563eb', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3, pointRadius: 3 }] },
    options: commonOptions,
  });

  // Muat data awal
  loadChartData('day');
}

async function loadChartData(period) {
  try {
    const res = await fetch('api/statistics.php', { cache: 'no-store' });
    if (!res.ok) throw new Error('Gagal memuat grafik');
    const data = await res.json();
    if (!data.success) return;

    const d = data.data;

    let labels = [];
    let countData = [];
    let weightData = [];

    if (period === 'day') {
      const arr = d.chart_daily || [];
      arr.forEach(item => {
        labels.push(item.date);
        countData.push(item.count);
        weightData.push(item.total_weight);
      });
    } else if (period === 'week') {
      const arr = d.chart_weekly || [];
      arr.forEach(item => {
        labels.push('Minggu ' + item.week);
        countData.push(item.count);
        weightData.push(item.total_weight);
      });
    } else {
      const arr = d.chart_monthly || [];
      arr.forEach(item => {
        labels.push(item.month);
        countData.push(item.count);
        weightData.push(item.total_weight);
      });
    }

    updateChart(chartDaily, labels, countData, '#3b82f6');
    updateChart(chartWeight, labels, weightData, '#2563eb');
  } catch (err) {
    console.error('Grafik gagal dimuat:', err);
  }
}

function updateChart(chart, labels, data, color) {
  chart.data.labels = labels;
  chart.data.datasets[0].data = data;
  chart.data.datasets[0].borderColor = color;
  chart.data.datasets[0].backgroundColor = color + (chart.type === 'line' ? '20' : '');
  chart.update();
}

// Expose untuk dashboard.js
window.updateCharts = function(stats) {
  // Grafik diupdate melalui loadChartData saat filter berubah
};

// Event listener filter grafik
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      loadChartData(this.dataset.period);
    });
  });
});