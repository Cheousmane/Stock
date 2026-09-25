<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Ventes" :title="$t('page.pos_sessions.title')" :subtitle="meta ? $t('page.pos_sessions.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchSessions(meta?.current_page)" title="Rafraîchir">
            <span class="text-current"><ArrowPathIcon class="w-4 h-4" /></span>
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.pos_sessions.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="statusFilter" :options="[
          { value: '', label: $t('page.pos_sessions.all_statuses') },
          { value: 'open', label: $t('page.pos_sessions.open') },
          { value: 'closed', label: $t('page.pos_sessions.closed') },
        ]" size="sm" class="w-40" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 20, label: '20 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="sessions.length === 0">
        <BaseEmptyState :title="$t('page.pos_sessions.title')" :description="hasActiveFilters ? $t('page.pos_sessions.no_results') : $t('page.pos_sessions.empty')">
          <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'user', label: $t('page.pos_sessions.user'), sortable: false },
            { key: 'status', label: $t('page.pos_sessions.status'), sortable: false, align: 'center' },
            { key: 'opened_at', label: $t('page.pos_sessions.opening'), sortable: false },
            { key: 'closed_at', label: $t('page.pos_sessions.closing'), sortable: false },
            { key: 'opening_cash_xof', label: $t('page.pos_sessions.opening_cash'), sortable: false, align: 'right' },
            { key: 'sales_count', label: $t('page.pos_sessions.sales_count'), sortable: false, align: 'right' },
            { key: 'total', label: $t('page.pos_sessions.total'), sortable: false, align: 'right' },
            { key: 'difference', label: $t('page.pos_sessions.cash_difference'), sortable: false, align: 'right' },
          ]"
          :rows="sessions"
          :hover="true"
        >
          <template #cell-user="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ row.user?.name || '—' }}</span>
          </template>
          <template #cell-status="{ row }">
            <div class="flex justify-center">
              <BaseBadge :variant="row.status === 'open' ? 'success' : 'default'" size="xs" :dot="true">
                {{ row.status === 'open' ? $t('page.pos_sessions.open') : $t('page.pos_sessions.closed') }}
              </BaseBadge>
            </div>
          </template>
          <template #cell-opened_at="{ row }">
            <span class="text-sm text-text-tertiary">{{ formatDate(row.opened_at) }}</span>
          </template>
          <template #cell-closed_at="{ row }">
            <span class="text-sm text-text-tertiary">{{ row.closed_at ? formatDate(row.closed_at) : '—' }}</span>
          </template>
          <template #cell-opening_cash_xof="{ row }">
            <span class="text-sm tabular-nums text-text-primary">{{ formatXof(row.opening_cash_xof) }}</span>
          </template>
          <template #cell-sales_count="{ row }">
            <span class="text-sm tabular-nums text-text-primary">{{ row.sales_count ?? 0 }}</span>
          </template>
          <template #cell-total="{ row }">
            <span class="text-sm font-medium tabular-nums text-text-primary">{{ formatXof(row.sales_sum_total_xof ?? 0) }}</span>
          </template>
          <template #cell-difference="{ row }">
            <span v-if="row.cash_difference_xof !== null && row.cash_difference_xof !== undefined"
              class="text-sm tabular-nums font-medium"
              :class="row.cash_difference_xof === 0 ? 'text-emerald-600' : 'text-red-600'">
              {{ formatXof(row.cash_difference_xof) }}
            </span>
            <span v-else class="text-sm text-text-tertiary">—</span>
          </template>
          <template #actions="{ row }">
            <button @click.stop="viewSession(row)" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-tertiary rounded-lg transition-colors">
              <span class="text-text-tertiary"><ChevronDownIcon :class="['w-4 h-4', selectedSessionId === row.id ? 'rotate-180' : '']" /></span>
            </button>
          </template>
        </BaseTable>

        <BasePagination
          v-if="meta && meta.last_page > 1"
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :total="meta.total"
          :per-page="meta.per_page"
          @change="changePage"
        />
      </template>

      <BaseCard v-if="selectedSession" padding="none">
        <div class="px-4 py-3 bg-surface-secondary border-b border-border flex items-center justify-between">
          <h3 class="text-sm font-semibold text-text-primary">
            {{ $t('page.pos_sessions.sales_detail', { user: selectedSession.user?.name || '—' }) }}
            <span class="font-normal text-text-tertiary">· {{ formatDate(selectedSession.opened_at) }}</span>
          </h3>
          <span class="text-sm text-text-tertiary">{{ $t('page.pos_sessions.sales_count_label', { count: selectedSales?.length || 0 }) }}</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="bg-surface-secondary/50 border-b border-border">
                <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">{{ $t('page.pos_sessions.receipt') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">{{ $t('page.pos_sessions.date') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">{{ $t('page.pos_sessions.customer') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">{{ $t('page.pos_sessions.payment') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">{{ $t('page.pos_sessions.total') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">{{ $t('page.pos_sessions.paid') }}</th>
                <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">{{ $t('page.pos_sessions.change') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="sale in selectedSales" :key="sale.id" class="hover:bg-surface-tertiary/50">
                <td class="px-4 py-2 text-sm font-mono text-text-primary">{{ sale.receipt_number }}</td>
                <td class="px-4 py-2 text-sm text-text-tertiary">{{ formatDate(sale.created_at) }}</td>
                <td class="px-4 py-2 text-sm text-text-tertiary">{{ sale.customer?.name || '—' }}</td>
                <td class="px-4 py-2 text-sm text-text-tertiary">{{ paymentLabel(sale.payment_method) }}</td>
                <td class="px-4 py-2 text-sm text-right tabular-nums text-text-primary">{{ formatXof(sale.total_xof) }}</td>
                <td class="px-4 py-2 text-sm text-right tabular-nums text-text-primary">{{ formatXof(sale.amount_paid_xof) }}</td>
                <td class="px-4 py-2 text-sm text-right tabular-nums text-text-tertiary">{{ formatXof(sale.change_returned_xof) }}</td>
              </tr>
              <tr v-if="!selectedSales?.length">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-text-tertiary">{{ $t('page.pos_sessions.no_sales') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BasePagination from '../../Components/ui/BasePagination.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseTable from '../../Components/ui/BaseTable.vue';
import { ChevronDownIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();

const loading = ref(true);
const sessions = ref([]);
const meta = ref(null);
const search = ref('');
const statusFilter = ref('');
const perPage = ref(20);
const selectedSessionId = ref(null);
const selectedSession = ref(null);
const selectedSales = ref(null);
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value || statusFilter.value);

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function formatXof(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v || 0);
}

function paymentLabel(method) {
  const labels = { cash: $t('page.pos.pay_cash'), card: $t('page.pos.card_tpe'), mobile_money: $t('page.pos.pay_mobile') };
  return labels[method] || method;
}

async function fetchSessions(page = 1) {
  loading.value = true;
  try {
    const params = { page, per_page: perPage.value };
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    const { data } = await axios.get('/pos/sessions', { params });
    sessions.value = data.data ?? [];
    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    };
  } catch {
    sessions.value = [];
    meta.value = null;
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  fetchSessions(page);
}

function resetFilters() {
  search.value = '';
  statusFilter.value = '';
}

watch([search, statusFilter, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchSessions(), 300);
});

async function viewSession(session) {
  if (selectedSessionId.value === session.id) {
    selectedSessionId.value = null;
    selectedSession.value = null;
    selectedSales.value = null;
    return;
  }
  selectedSessionId.value = session.id;
  selectedSession.value = session;
  selectedSales.value = null;
  try {
    const { data } = await axios.get(`/pos/sessions/${session.id}`);
    selectedSales.value = data.data ?? [];
  } catch {
    selectedSales.value = [];
  }
}

onMounted(() => fetchSessions());

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});
</script>
