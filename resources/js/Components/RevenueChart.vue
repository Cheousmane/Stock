<template>
  <div class="relative min-h-[240px] w-full">
    <canvas ref="chartRef"></canvas>
    <div v-if="!data?.length" class="absolute inset-0 flex items-center justify-center">
      <p class="text-sm text-text-tertiary font-medium">{{ $t('common.no_data') }}</p>
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
        borderColor: '#10b981',
        backgroundColor: (ctx) => {
          const g = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height || 220);
          g.addColorStop(0, isDark ? 'rgba(16, 185, 129, 0.35)' : 'rgba(16, 185, 129, 0.18)');
          g.addColorStop(0.7, isDark ? 'rgba(16, 185, 129, 0.08)' : 'rgba(16, 185, 129, 0.03)');
          g.addColorStop(1, 'rgba(16, 185, 129, 0)');
          return g;
        },
        fill: true,
        tension: 0.42,
        pointRadius: 4,
        pointHoverRadius: 7,
        pointBackgroundColor: isDark ? '#141414' : '#ffffff',
        pointBorderColor: '#10b981',
        pointBorderWidth: 2.5,
        pointHoverBackgroundColor: '#10b981',
        pointHoverBorderColor: '#ffffff',
        borderWidth: 2.5,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      aspectRatio: 2.2,
      // Évite les erreurs « ResizeObserver loop » pendant l'animation du sidebar.
      resizeDelay: 100,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: isDark ? '#1e1e1e' : '#ffffff',
          titleColor: isDark ? '#f0f0f0' : '#171717',
          bodyColor: isDark ? '#10b981' : '#059669',
          bodyFont: { weight: 'bold', size: 13 },
          borderColor: isDark ? '#2a2a2a' : '#e5e5e5',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 12,
          displayColors: false,
          boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1)',
          callbacks: {
            label: (ctx) => formatXOF(ctx.parsed.y),
          },
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: isDark ? '#6b7280' : '#9ca3af', font: { size: 11, family: 'Inter' } },
        },
        y: {
          beginAtZero: true,
          grid: { color: isDark ? 'rgba(255, 255, 255, 0.04)' : 'rgba(0, 0, 0, 0.04)' },
          ticks: { color: isDark ? '#6b7280' : '#9ca3af', font: { size: 11, family: 'Inter' }, callback: (v) => formatXOF(v) },
        },
      },
      animation: {
        duration: 900,
        easing: 'easeOutQuart',
      },
    },
  });
}

onMounted(() => { nextTick(renderChart); });
watch(() => props.data, () => { nextTick(renderChart); });
</script>
