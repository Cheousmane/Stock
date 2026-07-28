<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto space-y-4">
      <BasePageHeader :title="$t('page.expenses.title')" :subtitle="meta ? $t('page.expenses.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'ExpenseCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.expenses.create') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.expenses.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.expenses.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="expenses.length === 0">
        <BaseEmptyState :title="$t('page.expenses.no_expenses')" :description="search ? $t('page.expenses.no_results') : $t('page.expenses.empty')">
          <BaseButton v-if="!search" variant="primary" size="sm" :to="{ name: 'ExpenseCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.expenses.create_first') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'description', label: $t('page.expenses.description'), sortable: true },
            { key: 'category', label: $t('page.expenses.category'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'amount', label: $t('common.amount'), sortable: true, align: 'right', class: 'hidden lg:table-cell' },
            { key: 'date', label: $t('common.date'), sortable: true, class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedExpenses"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-description="{ row }">
            <p class="text-sm font-medium text-text-primary truncate max-w-[250px]">{{ row.description }}</p>
          </template>
          <template #cell-category="{ row }">
            <span class="text-sm text-text-secondary">{{ row.category || '—' }}</span>
          </template>
          <template #cell-amount="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ formatXOF(row.amount) }}</span>
          </template>
          <template #cell-date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.date }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'ExpenseEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.expenses.delete_confirm', { description: deleteTarget?.description }) }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject, onBeforeUnmount, watch } from 'vue';
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
  PlusIcon, ArrowDownTrayIcon, TrashIcon, PencilIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const expenses = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('date');
const sortOrder = ref('desc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);
const allSelected = computed(() => expenses.value.length > 0 && selectedIds.value.length === expenses.value.length);

function isSelected(id) { return selectedIds.value.includes(id); }

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = expenses.value.map(e => e.id);
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
}

function getSortValue(item, field) {
  if (field === 'category') return item.category ?? '';
  return item[field] ?? '';
}

const sortedExpenses = computed(() => {
  if (!sortBy.value) return expenses.value;
  return [...expenses.value].sort((a, b) => {
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

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchExpenses, 300);
});

onMounted(() => {
  fetchExpenses();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

async function fetchExpenses(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/expenses', { params });
    expenses.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch {
    showToast($t('page.expenses.loading_error'), 'error');
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchExpenses(page);
}

function confirmDelete(e) {
  deleteTarget.value = e;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/expenses/${id}`);
    showToast($t('page.expenses.delete_success'), 'success');
    fetchExpenses(meta.value?.current_page || 1);
  } catch {
    showToast($t('page.expenses.delete_error'), 'error');
  }
}

async function bulkDelete() {
  if (!window.confirm($t('page.expenses.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/expenses/bulk-delete', { ids: selectedIds.value });
    showToast($t('page.expenses.bulk_delete_success'), 'success');
    selectedIds.value = [];
    fetchExpenses(meta.value?.current_page || 1);
  } catch {
    showToast($t('common.error'), 'error');
  }
}

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${$t('page.expenses.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/expenses', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/expenses/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}
</script>
