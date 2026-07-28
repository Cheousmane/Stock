<template>
  <div class="chart-card">
    <h3 class="text-base font-semibold text-gray-900 mb-4">{{ $t('component.top_products.title') }}</h3>
    <div v-if="!data?.length" class="py-8 text-center text-sm text-gray-400">{{ $t('common.no_data') }}</div>
    <div v-else class="space-y-3">
      <div v-for="(product, i) in data" :key="i" class="product-row" :style="{ animationDelay: `${i * 0.07}s` }">
        <div class="flex items-center gap-3">
          <span class="rank-badge" :class="rankClass(i)">{{ i + 1 }}</span>
          <div class="flex-1 min-w-0">
            <p class="product-name">{{ product.name }}</p>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: getPercent(product.revenue) + '%' }" :class="rankColor(i)"></div>
            </div>
          </div>
          <span class="product-value">{{ format(product.revenue) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({ data: Array });

const maxRevenue = Math.max(...(props.data?.map(d => d.revenue) || [0]), 1);

function getPercent(revenue) {
  return (revenue / maxRevenue) * 100;
}

function format(val) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(val);
}

function rankClass(i) {
  if (i === 0) return 'rank-gold';
  if (i === 1) return 'rank-silver';
  if (i === 2) return 'rank-bronze';
  return 'rank-default';
}

function rankColor(i) {
  if (i === 0) return 'bar-gold';
  if (i === 1) return 'bar-silver';
  if (i === 2) return 'bar-bronze';
  return 'bar-default';
}
</script>

<style scoped>
.chart-card {
  background: var(--color-white);
  border: 1px solid var(--color-gray-100);
  border-radius: 1rem;
  padding: 1.25rem;
  transition: all .35s cubic-bezier(.16,1,.3,1);
  height: 100%;
}
.chart-card:hover {
  box-shadow: 0 8px 32px rgba(0,0,0,.05);
  border-color: var(--color-gray-200);
}

.product-row {
  opacity: 0;
  transform: translateX(-8px);
  animation: rowIn .4s cubic-bezier(.16,1,.3,1) forwards;
  padding: 0.5rem 0.5rem;
  border-radius: 0.75rem;
  transition: background .2s;
}
.product-row:hover {
  background: var(--color-gray-50);
}
@keyframes rowIn {
  to { opacity: 1; transform: translateX(0); }
}

.rank-badge {
  width: 24px; height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  flex-shrink: 0;
}
.rank-gold { background: #fef3c7; color: #d97706; }
.rank-silver { background: #f1f5f9; color: #64748b; }
.rank-bronze { background: #fed7aa; color: #ea580c; }
.rank-default { background: #f0fdf4; color: #059669; }

.dark .rank-gold { background: #78350f; color: #fbbf24; }
.dark .rank-silver { background: #334155; color: #94a3b8; }
.dark .rank-bronze { background: #431407; color: #fb923c; }
.dark .rank-default { background: #022c22; color: #34d399; }

.product-name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-gray-900);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 6px;
}

.bar-track {
  width: 100%;
  height: 6px;
  background: var(--color-gray-100);
  border-radius: 999px;
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 1s cubic-bezier(.16,1,.3,1);
}
.bar-gold { background: linear-gradient(90deg, #f59e0b, #d97706); }
.bar-silver { background: linear-gradient(90deg, #94a3b8, #64748b); }
.bar-bronze { background: linear-gradient(90deg, #fb923c, #ea580c); }
.bar-default { background: linear-gradient(90deg, #34d399, #059669); }

.dark .bar-gold { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.dark .bar-silver { background: linear-gradient(90deg, #64748b, #475569); }
.dark .bar-bronze { background: linear-gradient(90deg, #fb923c, #ea580c); }
.dark .bar-default { background: linear-gradient(90deg, #6ee7b7, #059669); }

.product-value {
  font-size: 0.8125rem;
  font-weight: 700;
  color: var(--color-gray-900);
  white-space: nowrap;
  flex-shrink: 0;
}
</style>
