<template>
  <div class="w-full">
    <div v-if="!data?.length" class="py-8 text-center text-sm text-text-tertiary font-medium">{{ $t('common.no_data') }}</div>
    <div v-else class="space-y-2.5">
      <div v-for="(product, i) in data" :key="i" class="product-row" :style="{ animationDelay: `${i * 0.07}s` }">
        <div class="flex items-center gap-3">
          <span class="rank-badge shadow-xs" :class="rankClass(i)">{{ i + 1 }}</span>
          <div class="flex-1 min-w-0">
            <p class="product-name font-semibold text-xs text-text-primary truncate mb-1">{{ product.name }}</p>
            <div class="bar-track">
              <div class="bar-fill" :style="{ width: getPercent(product.revenue) + '%' }" :class="rankColor(i)"></div>
            </div>
          </div>
          <span class="product-value font-bold font-mono text-xs text-text-primary tabular-nums">{{ format(product.revenue) }}</span>
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
.product-row {
  opacity: 0;
  transform: translateX(-8px);
  animation: rowIn .4s cubic-bezier(.16,1,.3,1) forwards;
  padding: 0.5rem;
  border-radius: 0.75rem;
  transition: background-color .2s ease, transform .2s ease;
}
.product-row:hover {
  background-color: var(--color-surface-secondary);
  transform: translateX(2px);
}
@keyframes rowIn {
  to { opacity: 1; transform: translateX(0); }
}

.rank-badge {
  width: 26px; height: 26px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  flex-shrink: 0;
}
.rank-gold { background: #fef3c7; color: #d97706; }
.rank-silver { background: #f1f5f9; color: #64748b; }
.rank-bronze { background: #fed7aa; color: #ea580c; }
.rank-default { background: #ecfdf5; color: #059669; }

.dark .rank-gold { background: #78350f; color: #fbbf24; }
.dark .rank-silver { background: #334155; color: #94a3b8; }
.dark .rank-bronze { background: #431407; color: #fb923c; }
.dark .rank-default { background: #022c22; color: #34d399; }

.bar-track {
  width: 100%;
  height: 6px;
  background: var(--color-surface-tertiary);
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
</style>
