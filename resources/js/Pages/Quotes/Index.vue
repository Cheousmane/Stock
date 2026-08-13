<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.quotes.title')" :subtitle="meta ? $t('page.quotes.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>{{ $t('common.export_excel') }}
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>{{ $t('common.export_csv') }}
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'QuoteCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.quotes.new') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.quotes.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="statusFilter" :options="[
          { value: '', label: $t('page.quotes.all_statuses') },
          { value: 'draft', label: $t('status.draft') },
          { value: 'sent', label: $t('status.sent') },
          { value: 'accepted', label: $t('status.accepted') },
          { value: 'rejected', label: $t('status.rejected') },
          { value: 'converted', label: $t('status.converted') },
        ]" size="sm" class="w-40" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: `10 ${$t('common.per_page')}` },
          { value: 25, label: `25 ${$t('common.per_page')}` },
          { value: 50, label: `50 ${$t('common.per_page')}` },
          { value: 100, label: `100 ${$t('common.per_page')}` },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.quotes.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="quotes.length === 0">
        <BaseEmptyState :title="$t('page.quotes.no_quotes')" :description="search || statusFilter ? $t('page.quotes.no_results') : $t('page.quotes.empty')">
          <BaseButton v-if="!search && !statusFilter" variant="primary" size="sm" :to="{ name: 'QuoteCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.quotes.create_first') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'number', label: $t('page.quotes.number'), sortable: true },
            { key: 'customer_name', label: $t('page.quotes.customer'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'issue_date', label: $t('page.quotes.date'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'expiration_date', label: $t('page.quotes.expires'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'total', label: $t('invoice.total'), sortable: true, align: 'right', class: 'hidden lg:table-cell' },
            { key: 'status', label: $t('page.quotes.status'), sortable: true, align: 'center', class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedQuotes"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
          clickable
          @row-click="viewQuote"
          selectable
          :selected-ids="selectedIds"
          @update:selectedIds="selectedIds = $event"
        >
          <template #cell-number="{ row }">
            <p class="text-sm font-medium text-text-primary">{{ row.number }}</p>
          </template>
          <template #cell-customer_name="{ row }">
            <span class="text-sm text-text-secondary">{{ row.customer?.name }}</span>
          </template>
          <template #cell-issue_date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.issue_date }}</span>
          </template>
          <template #cell-expiration_date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.expiration_date }}</span>
          </template>
          <template #cell-total="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ formatXOF(row.total) }}</span>
          </template>
          <template #cell-status="{ row }">
            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border', statusClass(row.status)]">
              <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(row.status)"></span>
              {{ $t(statusLabel(row.status)) }}
            </span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'QuoteShow', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><EyeIcon class="w-4 h-4" /></span>{{ $t('common.view') }}
                </router-link>
                <router-link :to="{ name: 'QuoteEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><PencilIcon class="w-4 h-4" /></span>{{ $t('common.edit') }}
                </router-link>
                <hr class="my-1 border-border" />
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
      <p class="text-sm text-text-secondary">{{ $t('page.quotes.delete_confirm', { number: deleteTarget?.number }) }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, inject, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
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
import {
  PlusIcon, ArrowDownTrayIcon, TrashIcon, EyeIcon, PencilIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const router = useRouter();
const showToast = inject('showToast');

const quotes = ref([]);
const loading = ref(true);
const search = ref('');
const statusFilter = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('number');
const sortOrder = ref('asc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
let debounceTimer = null;

const statusMap = {
  draft: { class: 'bg-gray-100 text-gray-700 border-gray-200', dot: 'bg-gray-400', label: 'status.draft' },
  sent: { class: 'bg-blue-100 text-blue-700 border-blue-200', dot: 'bg-blue-500', label: 'status.sent' },
  accepted: { class: 'bg-green-100 text-green-700 border-green-200', dot: 'bg-green-500', label: 'status.accepted' },
  rejected: { class: 'bg-red-100 text-red-700 border-red-200', dot: 'bg-red-500', label: 'status.rejected' },
  converted: { class: 'bg-purple-100 text-purple-700 border-purple-200', dot: 'bg-purple-500', label: 'status.converted' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-gray-100 text-gray-700 border-gray-200'; }
function statusDotClass(s) { return statusMap[s]?.dot || 'bg-gray-400'; }
function statusLabel(s) { return statusMap[s]?.label || s; }

const hasActiveFilters = computed(() => search.value || statusFilter.value);

const sortedQuotes = computed(() => {
  if (!sortBy.value) return quotes.value;
  return [...quotes.value].sort((a, b) => {
    let aVal = sortBy.value === 'customer_name' ? (a.customer?.name ?? '') : (a[sortBy.value] ?? '');
    let bVal = sortBy.value === 'customer_name' ? (b.customer?.name ?? '') : (b[sortBy.value] ?? '');
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

function resetFilters() {
  search.value = '';
  statusFilter.value = '';
}

watch([search, statusFilter, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchQuotes(), 300);
});

onMounted(() => {
  fetchQuotes();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${$t('page.quotes.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/quotes', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast($t('common.export_excel_unavailable'), 'error'); }
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/quotes/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast($t('common.export_csv_unavailable'), 'error'); }
}

async function fetchQuotes(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    const { data } = await axios.get('/quotes', { params });
    quotes.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch { showToast($t('page.quotes.load_error'), 'error'); } finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchQuotes(page);
}

function viewQuote(q) {
  router.push({ name: 'QuoteShow', params: { id: q.id } });
}

function confirmDelete(q) {
  deleteTarget.value = q;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  const currentPage = meta.value?.current_page || 1;
  deleteTarget.value = null;
  try {
    await axios.delete(`/quotes/${id}`);
    showToast($t('page.quotes.deleted_success'), 'success');
    const isLastItemOfLastPage = quotes.value.length === 1 && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    fetchQuotes(isLastItemOfLastPage ? currentPage - 1 : currentPage);
  } catch {
    showToast($t('common.delete_error'), 'error');
  }
}

async function bulkDelete() {
  if (!window.confirm($t('page.quotes.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  const currentPage = meta.value?.current_page || 1;
  try {
    await axios.post('/quotes/bulk-delete', { ids: selectedIds.value });
    showToast($t('common.bulk_delete_success'), 'success');
    const isLastPageCleared = selectedIds.value.length === quotes.value.length && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    selectedIds.value = [];
    fetchQuotes(isLastPageCleared ? currentPage - 1 : currentPage);
  } catch {
    showToast($t('common.error'), 'error');
  }
}
</script>
