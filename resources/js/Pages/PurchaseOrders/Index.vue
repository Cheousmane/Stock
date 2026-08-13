<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.purchase_orders.title')" :subtitle="meta ? $t('page.purchase_orders.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
          <BaseButton v-if="canCreate" variant="primary" size="sm" :to="{ name: 'PurchaseOrderCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.purchase_orders.new') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.purchase_orders.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.purchase_orders.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="purchaseOrders.length === 0">
        <BaseEmptyState :title="$t('page.purchase_orders.no_purchase_orders')" :description="search ? $t('page.purchase_orders.no_results') : $t('page.purchase_orders.empty')">
          <BaseButton v-if="!search && canCreate" variant="primary" size="sm" :to="{ name: 'PurchaseOrderCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.purchase_orders.create_first') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'number', label: $t('invoice.number'), sortable: true },
            { key: 'supplier_name', label: $t('page.purchase_orders.supplier'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'issue_date', label: $t('invoice.date'), sortable: true, class: 'hidden sm:table-cell' },
            { key: 'total_xof', label: $t('invoice.total_xof'), sortable: true, align: 'right', class: 'hidden lg:table-cell' },
            { key: 'status', label: $t('common.status'), sortable: true, align: 'center', class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedPurchaseOrders"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-number="{ row }">
            <p class="text-sm font-medium text-text-primary">{{ row.number }}</p>
          </template>
          <template #cell-supplier_name="{ row }">
            <span class="text-sm text-text-secondary">{{ row.supplier?.name || '—' }}</span>
          </template>
          <template #cell-issue_date="{ row }">
            <span class="text-sm text-text-secondary">{{ formatDate(row.issue_date) }}</span>
          </template>
          <template #cell-total_xof="{ row }">
            <div class="text-right">
              <p class="text-sm font-medium text-text-primary">{{ formatXOF(row.total_xof) }}</p>
              <p v-if="row.status === 'received'" class="text-xs" :class="balanceDue(row) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-text-tertiary'">
                {{ $t('page.purchase_orders.paid') }}{{ formatXOF(row.paid_xof || 0) }} · {{ $t('page.purchase_orders.due') }}{{ formatXOF(balanceDue(row)) }}
              </p>
            </div>
          </template>
          <template #cell-status="{ row }">
            <BaseBadge :variant="row.status === 'draft' ? 'default' : row.status === 'validated' ? 'info' : row.status === 'received' ? 'success' : 'error'">{{ statusLabel(row.status) }}</BaseBadge>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'PurchaseOrderEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><PencilIcon class="w-4 h-4" /></span>{{ $t('common.edit') }}
                </router-link>
                <button v-if="row.status !== 'received' && row.status !== 'cancelled'" @click="receivePurchaseOrder(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors text-left">
                  <span class="text-text-tertiary"><CheckCircleIcon class="w-4 h-4" /></span>{{ $t('page.purchase_orders.receive') }}
                </button>
                <button v-if="row.status === 'received' && balanceDue(row) > 0" @click="openPayment(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors text-left">
                  <span class="text-emerald-600 dark:text-emerald-400"><BanknotesIcon class="w-4 h-4" /></span>{{ $t('page.purchase_orders.pay') }}
                </button>
                <hr v-if="canDelete && row.status === 'draft'" class="my-1 border-border" />
                <button v-if="canDelete && row.status === 'draft'" @click="confirmDelete(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.purchase_orders.delete_confirm', { number: deleteTarget?.number }) }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>

    <BaseModal v-model="paymentTarget" :title="$t('page.purchase_orders.pay_modal_title', { number: paymentTarget?.number })" size="sm">
      <div class="space-y-4">
        <div class="flex items-center justify-between text-sm">
          <span class="text-text-tertiary">{{ $t('page.purchase_orders.total') }}</span>
          <span class="font-medium text-text-primary">{{ formatXOF(paymentTarget?.total_xof) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-text-tertiary">{{ $t('page.purchase_orders.paid') }}</span>
          <span class="font-medium text-text-primary">{{ formatXOF(paymentTarget?.paid_xof || 0) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm font-medium">
          <span class="text-text-tertiary">{{ $t('page.purchase_orders.due') }}</span>
          <span class="text-amber-600 dark:text-amber-400">{{ formatXOF(paymentTarget ? balanceDue(paymentTarget) : 0) }}</span>
        </div>
        <div class="space-y-1.5">
          <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('page.purchase_orders.payment_amount') }}</label>
          <input v-model.number="paymentForm.amount_xof" type="number" min="1" :max="paymentTarget ? balanceDue(paymentTarget) : undefined"
            class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1.5">
            <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('page.purchase_orders.payment_method') }}</label>
            <select v-model="paymentForm.method"
              class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
              <option value="cash">{{ $t('payment.cash') }}</option>
              <option value="bank">{{ $t('payment.bank') }}</option>
              <option value="mobile_money">{{ $t('payment.mobile_money') }}</option>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('page.purchase_orders.payment_date') }}</label>
            <input v-model="paymentForm.payment_date" type="date"
              class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
          </div>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="paymentTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="paying" @click="executePayment()">{{ $t('page.purchase_orders.pay_confirm') }}</BaseButton>
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
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseDropdown from '../../Components/ui/BaseDropdown.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import {
  PlusIcon, ArrowDownTrayIcon, TrashIcon, PencilIcon, CheckCircleIcon, BanknotesIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const showToast = inject('showToast', (msg) => alert(msg));

const purchaseOrders = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('issue_date');
const sortOrder = ref('desc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
const paymentTarget = ref(null);
const paying = ref(false);
const paymentForm = reactive({
  amount_xof: 0,
  method: 'cash',
  payment_date: new Date().toISOString().slice(0, 10),
});
let debounceTimer = null;

const canCreate = computed(() => {
  try { return JSON.parse(localStorage.getItem('user') || '{}').permissions?.includes('create_purchase_order'); } catch { return false; }
});
const canDelete = computed(() => {
  try { return JSON.parse(localStorage.getItem('user') || '{}').permissions?.includes('delete_purchase_order'); } catch { return false; }
});

const hasActiveFilters = computed(() => search.value);
const allSelected = computed(() => purchaseOrders.value.length > 0 && selectedIds.value.length === purchaseOrders.value.length);

function isSelected(id) { return selectedIds.value.includes(id); }

function toggleAll() {
  if (allSelected.value) { selectedIds.value = []; }
  else { selectedIds.value = purchaseOrders.value.map(po => po.id); }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
}

function getSortValue(item, field) {
  if (field === 'supplier_name') return item.supplier?.name ?? '';
  return item[field] ?? '';
}

const sortedPurchaseOrders = computed(() => {
  if (!sortBy.value) return purchaseOrders.value;
  return [...purchaseOrders.value].sort((a, b) => {
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
  if (sortBy.value === field) { sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'; }
  else { sortBy.value = field; sortOrder.value = 'asc'; }
}

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

function formatDate(dateString) {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('fr-FR');
}

function statusLabel(status) {
  switch (status) {
    case 'draft': return t('status.draft');
    case 'validated': return t('status.validated');
    case 'received': return t('status.received');
    case 'cancelled': return t('status.cancelled');
    default: return status;
  }
}

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchPurchaseOrders, 300);
});

onMounted(() => { fetchPurchaseOrders(); });
onBeforeUnmount(() => { if (debounceTimer) clearTimeout(debounceTimer); });

async function fetchPurchaseOrders(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/purchase-orders', { params });
    purchaseOrders.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch { showToast(t('page.purchase_orders.load_error'), 'error'); } finally { loading.value = false; }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchPurchaseOrders(page);
}

function confirmDelete(po) { deleteTarget.value = po; }

function balanceDue(po) {
  return Math.max(0, (Number(po?.total_xof) || 0) - (Number(po?.paid_xof) || 0));
}

async function receivePurchaseOrder(po) {
  try {
    await axios.post(`/purchase-orders/${po.id}/receive`);
    showToast(t('page.purchase_orders.received_success'), 'success');
    fetchPurchaseOrders(meta.value?.current_page || 1);
  } catch (e) {
    showToast(e.response?.data?.message || t('common.error'), 'error');
  }
}

function openPayment(po) {
  paymentTarget.value = po;
  paymentForm.amount_xof = balanceDue(po);
  paymentForm.payment_date = new Date().toISOString().slice(0, 10);
}

async function executePayment() {
  if (!paymentTarget.value) return;
  const amount = Number(paymentForm.amount_xof) || 0;
  const max = balanceDue(paymentTarget.value);
  if (amount < 1 || amount > max) {
    showToast(t('page.purchase_orders.payment_invalid'), 'error');
    return;
  }
  paying.value = true;
  try {
    await axios.post('/supplier-payments', {
      supplier_id: paymentTarget.value.supplier_id,
      purchase_order_id: paymentTarget.value.id,
      amount_xof: amount,
      method: paymentForm.method,
      payment_date: paymentForm.payment_date,
    });
    paymentTarget.value = null;
    showToast(t('page.purchase_orders.payment_success'), 'success');
    fetchPurchaseOrders(meta.value?.current_page || 1);
  } catch (e) {
    const first = Object.values(e.response?.data?.errors || {}).flat()[0];
    showToast(first || e.response?.data?.message || t('common.error'), 'error');
  } finally {
    paying.value = false;
  }
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/purchase-orders/${id}`);
    showToast(t('page.purchase_orders.deleted'), 'success');
    fetchPurchaseOrders(meta.value?.current_page || 1);
  } catch { showToast(t('common.delete_error'), 'error'); }
}

async function bulkDelete() {
  if (!window.confirm(t('page.purchase_orders.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/purchase-orders/bulk-delete', { ids: selectedIds.value });
    showToast(t('page.purchase_orders.bulk_delete_success'), 'success');
    selectedIds.value = [];
    fetchPurchaseOrders(meta.value?.current_page || 1);
  } catch { showToast(t('common.error'), 'error'); }
}

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${t('page.purchase_orders.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/purchase-orders', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast(t('common.export_excel_unavailable'), 'error'); }
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/purchase-orders/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast(t('common.export_csv_unavailable'), 'error'); }
}
</script>
