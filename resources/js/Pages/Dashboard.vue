<template>
  <AdminLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-text-primary">{{ $t('page.dashboard.title', { count: totalCount }) }}</h1>
          <p class="text-sm text-text-tertiary mt-0.5">{{ $t('dashboard.subtitle') }}</p>
        </div>
        <button @click="fetchStats" class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-text-secondary bg-surface border border-border rounded-lg hover:bg-surface-tertiary hover:text-text-primary transition-all duration-200 active:scale-[0.98]">
          <span class="text-text-secondary"><ArrowPathIcon class="w-4 h-4" :class="{ 'animate-spin': loading }" /></span>
          {{ $t('dashboard.refresh') }}
        </button>
      </div>
    </template>

    <!-- Loading skeleton -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div v-for="i in 8" :key="i" class="h-[120px] rounded-xl bg-surface border border-border animate-pulse" />
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="flex flex-col items-center justify-center py-20">
      <div class="w-14 h-14 mb-4 rounded-2xl bg-surface-tertiary flex items-center justify-center">
        <span class="text-red-500"><ExclamationTriangleIcon class="w-7 h-7" /></span>
      </div>
      <p class="text-text-secondary mb-4">{{ error }}</p>
      <button @click="fetchStats" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg hover:from-emerald-600 hover:to-emerald-700 shadow-sm transition-all">
        <span class="text-white"><ArrowPathIcon class="w-4 h-4" /></span>
        {{ $t('dashboard.retry') }}
      </button>
    </div>

    <!-- Dashboard content -->
    <template v-else>
      <!-- KPI Cards -->
      <TransitionGroup name="stagger" tag="div" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" appear>
        <div v-for="(kpi, index) in kpiCards" :key="kpi.label" :style="{ '--i': index }"
          :class="[kpi.to ? 'cursor-pointer' : '']" @click="kpi.to && router.push(kpi.to)">
          <div class="relative overflow-hidden rounded-xl bg-surface border border-border p-5 transition-all duration-300 hover:shadow-card hover:-translate-y-0.5">
            <div class="absolute top-0 right-0 w-32 h-32 -translate-y-1/2 translate-x-1/2 rounded-full opacity-[0.04]"
              :style="{ background: `radial-gradient(circle, ${kpi.glow}, transparent 70%)` }" />
            <div class="flex items-start justify-between">
              <div class="space-y-1.5">
                <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ kpi.label }}</p>
                <p class="text-2xl font-bold text-text-primary tracking-tight">{{ kpi.display }}</p>
                <div v-if="kpi.trend !== null" class="flex items-center gap-1 pt-0.5">
                  <span :class="kpi.trend >= 0 ? 'text-emerald-500' : 'text-red-500'">
                    <ArrowTrendingUpIcon v-if="kpi.trend >= 0" class="w-3.5 h-3.5" />
                    <ArrowTrendingDownIcon v-else class="w-3.5 h-3.5" />
                  </span>
                  <span :class="kpi.trend >= 0 ? 'text-emerald-600' : 'text-red-600'" class="text-xs font-medium">
                    {{ kpi.trend >= 0 ? '+' : '' }}{{ kpi.trend }}%
                  </span>
                </div>
              </div>
              <div class="flex items-center justify-center w-11 h-11 rounded-xl shrink-0" :class="kpi.iconBg">
                <span :class="kpi.iconColor"><component :is="kpi.icon" class="w-5 h-5" /></span>
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>

      <!-- Charts row -->
      <div class="grid grid-cols-1 gap-4 mt-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-xl bg-surface border border-border p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-semibold text-text-primary">Revenus mensuels</h3>
              <p class="text-xs text-text-tertiary mt-0.5">Évolution sur 6 mois</p>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-text-tertiary">
              <span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-500" />
              Revenus
            </div>
          </div>
          <RevenueChart :data="revenueByMonth" />
        </div>
        <div class="rounded-xl bg-surface border border-border p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-semibold text-text-primary">Top produits</h3>
              <p class="text-xs text-text-tertiary mt-0.5">Meilleurs revenus</p>
            </div>
          </div>
          <TopProductsChart :data="topProducts" />
        </div>
      </div>

      <!-- Bottom row -->
      <div class="grid grid-cols-1 gap-4 mt-4 lg:grid-cols-3">
        <!-- Recent invoices -->
        <div class="lg:col-span-2 rounded-xl bg-surface border border-border p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-text-primary">Factures récentes</h3>
            <router-link to="/invoices" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 transition-colors">
              Voir tout →
            </router-link>
          </div>
          <div v-if="recentInvoices.length" class="space-y-1">
            <div v-for="inv in recentInvoices" :key="inv.id"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-surface-secondary transition-colors cursor-pointer"
              @click="router.push(`/invoices/${inv.id}`)">
              <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-surface-tertiary shrink-0">
                <span class="text-text-tertiary"><DocumentTextIcon class="w-4 h-4" /></span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-text-primary truncate">{{ inv.number }}</p>
                <p class="text-xs text-text-tertiary truncate">{{ inv.customer_name }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-text-primary">{{ formatXOF(inv.total_xof) }}</p>
                <BaseBadge :variant="statusVariant(inv.status)" size="xs">
                  {{ inv.status }}
                </BaseBadge>
              </div>
            </div>
          </div>
          <div v-else class="flex items-center justify-center py-8">
            <p class="text-sm text-text-tertiary">Aucune facture récente</p>
          </div>
        </div>

        <!-- Right widgets -->
        <div class="space-y-4">
          <!-- Low stock alert -->
          <div class="rounded-xl bg-surface border border-border p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600">
                <span class="text-white"><ExclamationTriangleIcon class="w-5 h-5" /></span>
              </div>
              <div>
                <p class="text-sm font-semibold text-text-primary">Stock faible</p>
                <p class="text-xs text-text-tertiary">Produits sous le seuil minimum</p>
              </div>
            </div>
            <p class="text-2xl font-bold text-text-primary">{{ lowStockCount }}</p>
          </div>

          <!-- Capital summary -->
          <div class="rounded-xl bg-surface border border-border p-5">
            <div class="flex items-center gap-3 mb-3">
              <div class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600">
                <span class="text-white"><CurrencyDollarIcon class="w-5 h-5" /></span>
              </div>
              <div>
                <p class="text-sm font-semibold text-text-primary">Capital</p>
                <p class="text-xs text-text-tertiary">Valeur nette estimée</p>
              </div>
            </div>
            <p class="text-2xl font-bold text-text-primary">{{ formatXOF(capital) }}</p>
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
import {
  DocumentTextIcon, CurrencyDollarIcon, BanknotesIcon, CreditCardIcon,
  ArrowPathIcon, ExclamationTriangleIcon, CubeIcon, UsersIcon,
  ArrowTrendingUpIcon, ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline';

const router = useRouter();
const { t } = useI18n();

const loading = ref(true);
const error = ref('');
const revenueByMonth = ref([]);
const topProducts = ref([]);
const recentInvoices = ref([]);
const lowStockCount = ref(0);
const capital = ref(0);

const dashboardData = ref({
  total_revenue_xof: 0, outstanding: 0, recent_payments: 0,
  total_expenses: 0, total_invoices: 0, total_customers: 0,
  total_products: 0, credit_notes_amount: 0,
});

const totalCount = computed(() => dashboardData.value.total_invoices || 0);

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount);
}

const kpiCards = computed(() => [
  {
    label: t('dashboard.stats.revenue'), value: dashboardData.value.total_revenue_xof,
    display: formatXOF(dashboardData.value.total_revenue_xof), icon: CurrencyDollarIcon,
    iconBg: 'bg-gradient-to-br from-emerald-500 to-emerald-600', iconColor: 'text-white',
    glow: '#10b981', trend: 12,
  },
  {
    label: t('dashboard.stats.invoices'), value: dashboardData.value.total_invoices,
    display: dashboardData.value.total_invoices.toLocaleString(), icon: DocumentTextIcon,
    iconBg: 'bg-gradient-to-br from-blue-500 to-blue-600', iconColor: 'text-white',
    glow: '#3b82f6', trend: 5,
  },
  {
    label: t('dashboard.stats.customers'), value: dashboardData.value.total_customers,
    display: dashboardData.value.total_customers.toLocaleString(), icon: UsersIcon,
    iconBg: 'bg-gradient-to-br from-purple-500 to-purple-600', iconColor: 'text-white',
    glow: '#8b5cf6', trend: 8,
  },
  {
    label: t('dashboard.stats.products'), value: dashboardData.value.total_products,
    display: dashboardData.value.total_products.toLocaleString(), icon: CubeIcon,
    iconBg: 'bg-gradient-to-br from-amber-500 to-amber-600', iconColor: 'text-white',
    glow: '#f59e0b', trend: 3,
  },
  {
    label: t('dashboard.stats.outstanding'), value: dashboardData.value.outstanding,
    display: formatXOF(dashboardData.value.outstanding), icon: BanknotesIcon,
    iconBg: 'bg-gradient-to-br from-red-500 to-red-600', iconColor: 'text-white',
    glow: '#ef4444', trend: -3, to: '/invoices',
  },
  {
    label: t('dashboard.stats.payments_30d'), value: dashboardData.value.recent_payments,
    display: dashboardData.value.recent_payments.toLocaleString(), icon: CreditCardIcon,
    iconBg: 'bg-gradient-to-br from-cyan-500 to-cyan-600', iconColor: 'text-white',
    glow: '#06b6d4', trend: 15, to: '/payments',
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
    capital.value = data.capital ?? 0;
  } catch (err) {
    if (!silent) error.value = t('dashboard.error');
  } finally {
    if (!silent) loading.value = false;
  }
}

let pollTimer;
onMounted(() => { fetchStats(); pollTimer = setInterval(() => fetchStats(true), 300000); });
onBeforeUnmount(() => { if (pollTimer) clearInterval(pollTimer); });
</script>

<style>
.stagger-enter-active {
  transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  transition-delay: calc(var(--i, 0) * 0.06s);
}
.stagger-enter-from {
  opacity: 0;
  transform: translateY(12px);
}
</style>
