<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto space-y-4">
      <BasePageHeader :title="$t('page.payments.title')" :subtitle="meta ? $t('page.payments.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard :title="$t('page.payments.record_title')">
        <form @submit.prevent="recordPayment" class="grid grid-cols-1 sm:grid-cols-5 gap-4">
          <BaseSelect v-model="paymentForm.invoice_id" :options="unpaidInvoices.map(i => ({ value: i.id, label: i.number }))" :placeholder="$t('page.payments.invoice')" required />
          <BaseInput v-model.number="paymentForm.amount_xof" type="number" min="1" step="1" :placeholder="$t('page.payments.amount')" required />
          <BaseSelect v-model="paymentForm.method" :options="[
            { value: 'cash', label: $t('page.payments.method_cash') },
            { value: 'bank', label: $t('page.payments.method_bank') },
            { value: 'mobile_money', label: $t('page.payments.method_mobile_money') },
            { value: 'stripe', label: $t('page.payments.method_card') },
          ]" />
          <BaseInput v-model="paymentForm.payment_date" type="date" required />
          <BaseButton variant="primary" type="submit" :loading="paying">{{ $t('common.pay') }}</BaseButton>
        </form>
      </BaseCard>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.payments.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.payments.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="payments.length === 0">
        <BaseEmptyState :title="$t('page.payments.no_payments')" :description="search ? $t('page.payments.no_results') : $t('page.payments.empty')">
          <button v-if="search" @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'invoice_number', label: $t('page.payments.invoice'), sortable: true },
            { key: 'amount_xof', label: $t('common.amount'), sortable: true, align: 'right', class: 'hidden lg:table-cell' },
            { key: 'method', label: $t('page.payments.method'), sortable: true, class: 'hidden sm:table-cell' },
            { key: 'payment_date', label: $t('common.date'), sortable: true, class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedPayments"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-invoice_number="{ row }">
            <p class="text-sm font-medium text-text-primary">{{ row.invoice?.number || '—' }}</p>
          </template>
          <template #cell-amount_xof="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ formatXOF(row.amount_xof) }}</span>
          </template>
          <template #cell-method="{ row }">
            <span class="text-sm text-text-secondary">{{ methodLabel(row.method) }}</span>
          </template>
          <template #cell-payment_date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.payment_date }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <button @click="confirmDelete(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
                  <span class="text-red-600"><TrashIcon class="w-4 h-4" /></span>{{ $t('common.delete') }}
                </button>
              </template>
            </BaseDropdown>
          </template>
        </BaseTable>
        <BasePagination
          v-if="meta && meta.last_page > 1"
          :current-page="meta.current_page"
          :last-page="meta.last_page"
          :total="meta.total"
          :per-page="perPage"
          @change="changePage"
        />
      </template>
    </div>

    <BaseModal v-model="deleteTarget" :title="$t('common.confirm_delete')" size="sm">
      <p class="text-sm text-text-secondary">{{ $t('page.payments.delete_confirm', { number: deleteTarget?.invoice?.number }) }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTable from '../../Components/ui/BaseTable.vue';
import BasePagination from '../../Components/ui/BasePagination.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseDropdown from '../../Components/ui/BaseDropdown.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import {
  ArrowDownTrayIcon, TrashIcon, EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const showToast = inject('showToast');

const payments = ref([]);
const unpaidInvoices = ref([]);
const loading = ref(true);
const paying = ref(false);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('payment_date');
const sortOrder = ref('desc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
const paymentForm = reactive({
  invoice_id: '', amount_xof: 0, method: 'cash', payment_date: new Date().toISOString().slice(0, 10),
});
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);
const allSelected = computed(() => payments.value.length > 0 && selectedIds.value.length === payments.value.length);

function isSelected(id) { return selectedIds.value.includes(id); }

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = payments.value.map(p => p.id);
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
}

function getSortValue(item, field) {
  if (field === 'invoice_number') return item.invoice?.number ?? '';
  return item[field] ?? '';
}

const sortedPayments = computed(() => {
  if (!sortBy.value) return payments.value;
  return [...payments.value].sort((a, b) => {
    let aVal = getSortValue(a, sortBy.value);
    let bVal = getSortValue(b, sortBy.value);
    if (typeof aVal === 'string') aVal = aVal.toLowerCase();
    if (typeof bVal === 'string') bVal = bVal.toLowerCase();
    if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1;
    if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1;
    return 0;
  });
});

function toggleSort(field) {
  if (sortBy.value === field) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortBy.value = field;
    sortOrder.value = 'asc';
  }
}

function methodLabel(m) {
  const labels = { cash: 'page.payments.method_cash', bank: 'page.payments.method_bank', mobile_money: 'page.payments.method_mobile_money', stripe: 'page.payments.method_card' };
  return labels[m] || m;
}

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchData, 300);
});

onMounted(() => {
  fetchData();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

async function fetchData(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const [pRes, iRes] = await Promise.all([
      axios.get('/payments', { params }),
      axios.get('/invoices?status=sent,overdue,partial&limit=1000'),
    ]);
    payments.value = pRes.data.data ?? pRes.data;
    meta.value = pRes.data.meta ?? null;
    unpaidInvoices.value = iRes.data.data ?? iRes.data;
    selectedIds.value = [];
  } catch {} finally { loading.value = false; }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchData(page);
}

async function recordPayment() {
  paying.value = true;
  try {
    await axios.post('/payments', paymentForm);
    paymentForm.invoice_id = '';
    paymentForm.amount_xof = 0;
    await fetchData(meta.value?.current_page || 1);
  } catch { showToast(t('common.generic_error'), 'error'); } finally { paying.value = false; }
}

function confirmDelete(p) {
  deleteTarget.value = p;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/payments/${id}`);
    showToast(t('page.payments.delete_success'), 'success');
    fetchData(meta.value?.current_page || 1);
  } catch {
    showToast(t('common.generic_error'), 'error');
  }
}

async function bulkDelete() {
  if (!window.confirm(t('page.payments.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/payments/bulk-delete', { ids: selectedIds.value });
    showToast(t('page.payments.bulk_delete_success'), 'success');
    selectedIds.value = [];
    fetchData(meta.value?.current_page || 1);
  } catch {
    showToast(t('common.error'), 'error');
  }
}

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${t('page.payments.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/payments', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/payments/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}
</script>
