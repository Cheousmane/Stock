<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto space-y-4">
      <BasePageHeader :title="$t('page.credit_notes.title')" :subtitle="meta ? $t('page.credit_notes.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="primary" size="sm" :to="{ name: 'CreditNoteCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('common.create') }}
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.credit_notes.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.credit_notes.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="creditNotes.length === 0">
        <BaseEmptyState :title="$t('page.credit_notes.no_credit_notes')" :description="search ? $t('page.credit_notes.no_results') : $t('page.credit_notes.empty')">
          <button @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'number', label: $t('page.credit_notes.number'), sortable: true },
            { key: 'customer_name', label: $t('page.credit_notes.customer'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'invoice_number', label: $t('page.credit_notes.linked_invoice'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'issue_date', label: $t('page.credit_notes.date'), sortable: true, class: 'hidden sm:table-cell' },
            { key: 'total_xof', label: $t('page.credit_notes.amount'), sortable: true, align: 'right', class: 'hidden lg:table-cell' },
            { key: 'status', label: $t('page.credit_notes.status'), sortable: true, align: 'center', class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedCreditNotes"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-number="{ row }">
            <router-link :to="{ name: 'CreditNoteShow', params: { id: row.id } }" class="text-sm font-medium text-text-primary hover:text-primary-600 transition-colors">{{ row.number }}</router-link>
          </template>
          <template #cell-customer_name="{ row }">
            <span class="text-sm text-text-secondary">{{ row.customer?.name || '—' }}</span>
          </template>
          <template #cell-invoice_number="{ row }">
            <span class="text-sm text-text-secondary">{{ row.invoice?.number || '—' }}</span>
          </template>
          <template #cell-issue_date="{ row }">
            <span class="text-sm text-text-secondary">{{ formatDate(row.issue_date) }}</span>
          </template>
          <template #cell-total_xof="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ formatXOF(row.total_xof) }}</span>
          </template>
          <template #cell-status="{ row }">
            <BaseBadge :variant="row.status === 'draft' ? 'default' : row.status === 'validated' ? 'info' : 'success'">{{ statusLabel(row.status) }}</BaseBadge>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
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
      <p class="text-sm text-text-secondary">{{ $t('page.credit_notes.delete_confirm', { number: deleteTarget?.number }) }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject, onBeforeUnmount, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTable from '../../Components/ui/BaseTable.vue';
import BasePagination from '../../Components/ui/BasePagination.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseDropdown from '../../Components/ui/BaseDropdown.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import {
  PlusIcon, ArrowDownTrayIcon, TrashIcon, EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const showToast = inject('showToast', (msg) => alert(msg));

const creditNotes = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('issue_date');
const sortOrder = ref('desc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
let debounceTimer = null;

const canDelete = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}').permissions?.includes('delete_credit_note');
  } catch { return false; }
});

const hasActiveFilters = computed(() => search.value);
const allSelected = computed(() => creditNotes.value.length > 0 && selectedIds.value.length === creditNotes.value.length);

function isSelected(id) { return selectedIds.value.includes(id); }

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = creditNotes.value.map(cn => cn.id);
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
}

function getSortValue(item, field) {
  if (field === 'customer_name') return item.customer?.name ?? '';
  if (field === 'invoice_number') return item.invoice?.number ?? '';
  return item[field] ?? '';
}

const sortedCreditNotes = computed(() => {
  if (!sortBy.value) return creditNotes.value;
  return [...creditNotes.value].sort((a, b) => {
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
    case 'refunded': return t('status.refunded');
    default: return status;
  }
}

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchCreditNotes, 300);
});

onMounted(() => {
  fetchCreditNotes();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

async function fetchCreditNotes(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/credit-notes', { params });
    creditNotes.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch {
    showToast(t('page.credit_notes.load_error'), 'error');
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchCreditNotes(page);
}

function confirmDelete(cn) {
  deleteTarget.value = cn;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/credit-notes/${id}`);
    showToast(t('page.credit_notes.deleted_success'), 'success');
    fetchCreditNotes(meta.value?.current_page || 1);
  } catch {
    showToast(t('common.delete_error'), 'error');
  }
}

async function bulkDelete() {
  if (!window.confirm(t('page.credit_notes.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/credit-notes/bulk-delete', { ids: selectedIds.value });
    showToast(t('page.credit_notes.bulk_delete_success'), 'success');
    selectedIds.value = [];
    fetchCreditNotes(meta.value?.current_page || 1);
  } catch {
    showToast(t('common.error'), 'error');
  }
}

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${t('page.credit_notes.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/credit-notes', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {
    showToast(t('page.credit_notes.excel_unavailable'), 'error');
  }
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/credit-notes/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {
    showToast(t('page.credit_notes.csv_unavailable'), 'error');
  }
}
</script>
