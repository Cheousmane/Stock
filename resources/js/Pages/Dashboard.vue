<template>
  <AdminLayout>
    <template #header>
      <BasePageHeader :title="$t('page.dashboard.title')" :subtitle="$t('page.dashboard.subtitle')">
        <template #actions>
          <BaseButton variant="secondary" size="sm" :loading="loading" @click="fetchStats()">
            <ArrowPathIcon v-if="!loading" class="w-4 h-4" />
            {{ $t('dashboard.refresh') }}
          </BaseButton>
        </template>
      </BasePageHeader>
    </template>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <div v-for="i in 4" :key="i" class="pro-card p-5">
        <div class="h-10 w-10 rounded-2xl pro-skeleton" />
        <div class="mt-4 h-7 w-2/3 pro-skeleton" />
        <div class="mt-2 h-4 w-1/2 pro-skeleton" />
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="flex flex-col items-center justify-center py-20">
      <div class="w-14 h-14 mb-4 rounded-2xl bg-surface-tertiary flex items-center justify-center">
        <ExclamationTriangleIcon class="w-7 h-7 text-red-500" />
      </div>
      <p class="text-sm font-semibold text-text-secondary mb-4">{{ error }}</p>
      <BaseButton variant="primary" size="sm" @click="fetchStats()">
        <ArrowPathIcon class="w-4 h-4" />
        {{ $t('dashboard.retry') }}
      </BaseButton>
    </div>

    <!-- Dashboard content -->
    <template v-else>
      <div class="space-y-5">
        <!-- Quick Action Shortcuts -->
        <div class="flex items-center gap-2.5 overflow-x-auto pb-1 scrollbar-thin">
          <router-link
            to="/invoices/create"
            class="group inline-flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface border border-border/80 hover:border-emerald-500/40 hover:bg-emerald-500/5 text-text-primary transition-all shadow-xs shrink-0"
          >
            <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <PlusIcon class="w-3.5 h-3.5" />
            </span>
            <span>{{ $t('page.dashboard.shortcut_invoice') }}</span>
          </router-link>
          <router-link
            to="/pos"
            class="group inline-flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface border border-border/80 hover:border-emerald-500/40 hover:bg-emerald-500/5 text-text-primary transition-all shadow-xs shrink-0"
          >
            <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <ShoppingCartIcon class="w-3.5 h-3.5" />
            </span>
            <span>{{ $t('page.dashboard.shortcut_pos') }}</span>
          </router-link>
          <router-link
            to="/products/create"
            class="group inline-flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface border border-border/80 hover:border-emerald-500/40 hover:bg-emerald-500/5 text-text-primary transition-all shadow-xs shrink-0"
          >
            <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <CubeIcon class="w-3.5 h-3.5" />
            </span>
            <span>{{ $t('page.dashboard.shortcut_product') }}</span>
          </router-link>
          <router-link
            to="/customers/create"
            class="group inline-flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface border border-border/80 hover:border-emerald-500/40 hover:bg-emerald-500/5 text-text-primary transition-all shadow-xs shrink-0"
          >
            <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <UsersIcon class="w-3.5 h-3.5" />
            </span>
            <span>{{ $t('page.dashboard.shortcut_customer') }}</span>
          </router-link>
          <router-link
            to="/stock"
            class="group inline-flex items-center gap-2.5 px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface border border-border/80 hover:border-emerald-500/40 hover:bg-emerald-500/5 text-text-primary transition-all shadow-xs shrink-0"
          >
            <span class="w-6 h-6 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
              <ArchiveBoxIcon class="w-3.5 h-3.5" />
            </span>
            <span>{{ $t('page.dashboard.shortcut_stock') }}</span>
          </router-link>
        </div>

        <!-- KPI Bento Cards (4 cartes maîtresses avec bascule optionnelle) -->
        <div>
          <div class="flex items-center justify-between mb-3">
            <h2 class="pro-section-title">{{ $t('page.dashboard.title') }}</h2>
            <button
              @click="showAllKpis = !showAllKpis"
              class="inline-flex items-center gap-1.5 text-xs font-bold text-text-secondary hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors"
            >
              <span>{{ showAllKpis ? $t('page.dashboard.simplified_view') : $t('page.dashboard.more_indicators', { count: kpiCards.length - 4 }) }}</span>
              <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': showAllKpis }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
          </div>

          <TransitionGroup name="stagger" tag="div" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" appear>
            <div v-for="(kpi, index) in visibleKpis" :key="kpi.label" :style="{ '--i': index }"
              :class="[kpi.to ? 'cursor-pointer' : '']" @click="kpi.to && router.push(kpi.to)">
              <div class="group relative overflow-hidden pro-card pro-card--hover card-sweep p-5">
                <div class="absolute top-0 right-0 w-28 h-28 -translate-y-1/2 translate-x-1/2 rounded-full opacity-[0.08] transition-transform duration-500 group-hover:scale-150"
                  :style="{ background: `radial-gradient(circle, ${kpi.glow}, transparent 70%)` }" />
                <div class="relative flex items-start justify-between">
                  <div class="space-y-1.5">
                    <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-[0.12em]">{{ kpi.label }}</p>
                    <p class="text-[24px] font-black font-mono text-text-primary tracking-tight tabular-nums leading-none">{{ kpi.display }}</p>
                    <div v-if="kpi.trend !== null" class="flex items-center gap-1 pt-0.5">
                      <span :class="kpi.trend >= 0 ? 'text-emerald-500' : 'text-red-500'">
                        <ArrowTrendingUpIcon v-if="kpi.trend >= 0" class="w-3.5 h-3.5" />
                        <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
                      </span>
                      <span :class="kpi.trend >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" class="text-xs font-semibold tabular-nums">
                        {{ kpi.trend >= 0 ? '+' : '' }}{{ kpi.trend }}%
                      </span>
                    </div>
                  </div>
                  <div class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 transition-transform duration-300 group-hover:scale-110 shadow-sm" :class="kpi.iconBg">
                    <span :class="kpi.iconColor"><component :is="kpi.icon" class="w-5 h-5" /></span>
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div class="lg:col-span-2 pro-card p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('page.dashboard.revenue_chart_title') }}</h3>
                <p class="text-xs font-medium text-text-tertiary mt-0.5">{{ $t('page.dashboard.revenue_chart_subtitle') }}</p>
              </div>
              <div class="flex items-center gap-1.5 text-xs font-medium text-text-tertiary">
                <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500" />
                {{ $t('page.dashboard.revenue_legend') }}
              </div>
            </div>
            <RevenueChart :data="revenueByMonth" />
          </div>
          <div class="pro-card p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('page.dashboard.top_products_title') }}</h3>
                <p class="text-xs font-medium text-text-tertiary mt-0.5">{{ $t('page.dashboard.top_products_subtitle') }}</p>
              </div>
            </div>
            <TopProductsChart :data="topProducts" />
          </div>
        </div>

        <!-- Bottom row -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <!-- Recent invoices -->
          <div class="lg:col-span-2 pro-card p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('page.dashboard.recent_invoices_title') }}</h3>
              <router-link to="/invoices" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                <span>{{ $t('page.dashboard.view_all') }}</span>
                <ArrowRightIcon class="w-3.5 h-3.5" />
              </router-link>
            </div>
            <div v-if="recentInvoices.length" class="divide-y divide-border/60">
              <div v-for="inv in recentInvoices" :key="inv.id"
                class="group flex items-center gap-3 py-2.5 px-2 rounded-xl hover:bg-surface-secondary transition-colors duration-150 cursor-pointer"
                @click="router.push(`/invoices/${inv.id}`)">
                <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-surface-tertiary shrink-0 transition-transform duration-200 group-hover:scale-105 border border-border/40">
                  <DocumentTextIcon class="w-4 h-4 text-text-secondary" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold font-mono text-text-primary truncate">{{ inv.number }}</p>
                  <p class="text-xs text-text-tertiary truncate">{{ inv.customer?.name }}</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-bold font-mono text-text-primary tabular-nums">{{ formatXOF(inv.total_xof) }}</p>
                  <BaseBadge :variant="statusVariant(inv.status)" size="xs" :dot="true">
                    {{ $t('status.' + inv.status) }}
                  </BaseBadge>
                </div>
              </div>
            </div>
            <div v-else class="flex items-center justify-center py-8">
              <p class="text-sm text-text-tertiary">{{ $t('page.dashboard.no_recent_invoices') }}</p>
            </div>
          </div>

          <!-- Right widgets -->
          <div class="space-y-4">
            <!-- Low stock alert -->
            <div class="pro-card p-5">
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                  <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-sm shadow-amber-500/20">
                    <ExclamationTriangleIcon class="w-5 h-5 text-white" />
                  </div>
                  <div>
                    <p class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('page.dashboard.low_stock_title') }}</p>
                    <p class="text-xs font-medium text-text-tertiary">{{ $t('page.dashboard.low_stock_subtitle') }}</p>
                  </div>
                </div>
                <BaseBadge v-if="lowStockCount > 0" variant="warning" size="xs" dot pulse>
                  {{ $t('status.low_stock') }}
                </BaseBadge>
              </div>
              <div class="flex items-baseline justify-between mt-2">
                <p class="text-3xl font-bold font-mono text-text-primary tabular-nums">{{ lowStockCount }}</p>
                <router-link to="/stock" class="text-xs font-bold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300 transition-colors">
                  {{ $t('page.dashboard.check_stock') }}
                </router-link>
              </div>
            </div>


          </div>
        </div>
      </div>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../Components/AdminLayout.vue';
import RevenueChart from '../Components/RevenueChart.vue';
import TopProductsChart from '../Components/TopProductsChart.vue';
import BaseBadge from '../Components/ui/BaseBadge.vue';
import BaseButton from '../Components/ui/BaseButton.vue';
import BasePageHeader from '../Components/ui/BasePageHeader.vue';
import {
  DocumentTextIcon, CurrencyDollarIcon, BanknotesIcon, CreditCardIcon,
  ArrowPathIcon, ExclamationTriangleIcon, CubeIcon, UsersIcon,
  ArrowTrendingUpIcon, ArrowTrendingDownIcon, PlusIcon, ShoppingCartIcon, ArchiveBoxIcon,
  ArrowRightIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { t } = useI18n();

const loading = ref(true);
const error = ref('');
const revenueByMonth = ref([]);
const topProducts = ref([]);
const recentInvoices = ref([]);
const lowStockCount = ref(0);

const showAllKpis = ref(false);

const dashboardData = ref({
  total_revenue_xof: 0, outstanding: 0, recent_payments: 0,
  total_expenses: 0, total_invoices: 0, total_customers: 0,
  total_products: 0, credit_notes_amount: 0,
});

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
}

const kpiCards = computed(() => [
  {
    label: t('dashboard.stats.revenue'), value: dashboardData.value.total_revenue_xof,
    display: formatXOF(dashboardData.value.total_revenue_xof), icon: CurrencyDollarIcon,
    iconBg: 'bg-gradient-to-br from-emerald-500 to-emerald-600', iconColor: 'text-white',
    glow: '#10b981',
  },
  {
    label: t('dashboard.stats.invoices'), value: dashboardData.value.total_invoices,
    display: dashboardData.value.total_invoices.toLocaleString(), icon: DocumentTextIcon,
    iconBg: 'bg-gradient-to-br from-blue-500 to-blue-600', iconColor: 'text-white',
    glow: '#3b82f6',
  },
  {
    label: t('dashboard.stats.customers'), value: dashboardData.value.total_customers,
    display: dashboardData.value.total_customers.toLocaleString(), icon: UsersIcon,
    iconBg: 'bg-gradient-to-br from-purple-500 to-purple-600', iconColor: 'text-white',
    glow: '#8b5cf6',
  },
  {
    label: t('dashboard.stats.products'), value: dashboardData.value.total_products,
    display: dashboardData.value.total_products.toLocaleString(), icon: CubeIcon,
    iconBg: 'bg-gradient-to-br from-amber-500 to-amber-600', iconColor: 'text-white',
    glow: '#f59e0b',
  },
  {
    label: t('dashboard.stats.outstanding'), value: dashboardData.value.outstanding,
    display: formatXOF(dashboardData.value.outstanding), icon: BanknotesIcon,
    iconBg: 'bg-gradient-to-br from-red-500 to-red-600', iconColor: 'text-white',
    glow: '#ef4444', to: '/invoices',
  },
  {
    label: t('dashboard.stats.payments_30d'), value: dashboardData.value.recent_payments,
    display: dashboardData.value.recent_payments.toLocaleString(), icon: CreditCardIcon,
    iconBg: 'bg-gradient-to-br from-cyan-500 to-cyan-600', iconColor: 'text-white',
    glow: '#06b6d4', to: '/payments',
  },
  {
    label: t('dashboard.stats.expenses_30d'), value: dashboardData.value.total_expenses,
    display: formatXOF(dashboardData.value.total_expenses), icon: BanknotesIcon,
    iconBg: 'bg-gradient-to-br from-orange-500 to-orange-600', iconColor: 'text-white',
    glow: '#f97316', to: '/expenses',
  },
  {
    label: t('dashboard.stats.credit_notes'), value: dashboardData.value.credit_notes_amount,
    display: formatXOF(dashboardData.value.credit_notes_amount), icon: DocumentTextIcon,
    iconBg: 'bg-gradient-to-br from-indigo-500 to-indigo-600', iconColor: 'text-white',
    glow: '#6366f1', to: '/credit-notes',
  },
].map(k => ({ ...k, trend: k.trend ?? null })));

const visibleKpis = computed(() => {
  return showAllKpis.value ? kpiCards.value : kpiCards.value.slice(0, 4);
});

function statusVariant(status) {
  const map = { paid: 'success', sent: 'info', overdue: 'danger', draft: 'default', cancelled: 'warning' };
  return map[status] || 'default';
}

async function fetchStats(silent = false) {
  if (!silent) { loading.value = true; error.value = ''; }
  try {
    const { data } = await axios.get('/dashboard');
    dashboardData.value = data;
    revenueByMonth.value = data.revenue_by_month ?? [];
    topProducts.value = data.top_products ?? [];
    recentInvoices.value = data.recent_invoices ?? [];
    lowStockCount.value = data.low_stock_products ?? 0;

  } catch (err) {
    if (!silent) error.value = t('dashboard.error');
  } finally {
    if (!silent) loading.value = false;
  }
}

let pollTimer;
function onVisibilityChange() {
  if (document.visibilityState === 'visible') fetchStats(true);
}

onMounted(() => {
  fetchStats();
  pollTimer = setInterval(() => fetchStats(true), 60000);
  document.addEventListener('visibilitychange', onVisibilityChange);
});
onBeforeUnmount(() => {
  if (pollTimer) clearInterval(pollTimer);
  document.removeEventListener('visibilitychange', onVisibilityChange);
});
</script>

<style>
.stagger-enter-active {
transition: opacity 0.15s ease;
}
.stagger-enter-from {
  opacity: 0;
}
</style>
