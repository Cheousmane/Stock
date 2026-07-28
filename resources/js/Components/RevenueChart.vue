<template>
  <div class="chart-card">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-900">{{ $t('component.revenue_chart.title') }}</h3>
      <span class="text-xs text-gray-400">{{ data?.length || 0 }} {{ $t('component.revenue_chart.months') }}</span>
    </div>
    <div class="relative">
      <canvas ref="chartRef"></canvas>
      <div v-if="!data?.length" class="absolute inset-0 flex items-center justify-center">
        <p class="text-sm text-gray-400">{{ $t('common.no_data') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const { t: $t } = useI18n();
const props = defineProps({ data: Array });
const chartRef = ref(null);
let chart = null;

function formatXOF(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

function renderChart() {
  if (!chartRef.value || !props.data?.length) return;
  if (chart) chart.destroy();
  const ctx = chartRef.value.getContext('2d');
  const isDark = document.documentElement.classList.contains('dark');

  chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.data.map(d => d.month),
      datasets: [{
        label: $t('component.revenue_chart.title'),
        data: props.data.map(d => d.revenue),
        borderColor: '#059669',
        backgroundColor: (ctx) => {
          const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height);
          g.addColorStop(0, isDark ? 'rgba(5,150,105,0.3)' : 'rgba(5,150,105,0.15)');
          g.addColorStop(1, isDark ? 'rgba(5,150,105,0)' : 'rgba(5,150,105,0)');
          return g;
        },
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 7,
        pointBackgroundColor: '#fff',
        pointBorderColor: '#059669',
        pointBorderWidth: 2.5,
        pointHoverBackgroundColor: '#059669',
        pointHoverBorderColor: '#fff',
        borderWidth: 2.5,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.2,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: isDark ? '#1e293b' : '#fff',
          titleColor: isDark ? '#f1f5f9' : '#0a0a0a',
          bodyColor: isDark ? '#94a3b8' : '#6b7280',
          borderColor: isDark ? '#334155' : '#f0f0f0',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 10,
          displayColors: false,
          callbacks: {
            label: (ctx) => formatXOF(ctx.parsed.y),
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: '#9ca3af', font: { size: 11 } },
        },
        y: {
          beginAtZero: true,
          grid: { color: isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.05)' },
          ticks: { color: '#9ca3af', font: { size: 11 }, callback: (v) => formatXOF(v) },
        },
      },
      animation: {
        duration: 1000,
        easing: 'easeOutQuart',
      },
    },
  });
}

onMounted(() => { nextTick(renderChart); });
watch(() => props.data, () => { nextTick(renderChart); });
</script>

<style scoped>
.chart-card {
  background: var(--color-white);
  border: 1px solid var(--color-gray-100);
  border-radius: 1rem;
  padding: 1.25rem;
  transition: all .35s cubic-bezier(.16,1,.3,1);
}
.chart-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,.05);
  border-color: var(--color-gray-200);
}
</style>
