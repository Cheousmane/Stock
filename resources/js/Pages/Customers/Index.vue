<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.customers.title')" :subtitle="meta ? $t('page.customers.title') + ' · ' + meta.total : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>{{ $t('common.export_excel') }}
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>{{ $t('common.export_csv') }}
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'CustomerCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.customers.new') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.customers.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: `10 ${$t('common.per_page')}` },
          { value: 25, label: `25 ${$t('common.per_page')}` },
          { value: 50, label: `50 ${$t('common.per_page')}` },
          { value: 100, label: `100 ${$t('common.per_page')}` },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.customers.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="customers.length === 0">
        <BaseEmptyState :title="$t('page.customers.title')" :description="hasActiveFilters ? $t('common.no_results') : $t('page.customers.empty')">
          <BaseButton v-if="!hasActiveFilters" variant="primary" size="sm" :to="{ name: 'CustomerCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.customers.new') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'name', label: $t('form.name'), sortable: true },
            { key: 'email', label: $t('form.email'), sortable: true, class: 'hidden sm:table-cell' },
            { key: 'phone', label: $t('form.phone'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'city', label: $t('form.city'), sortable: true, class: 'hidden lg:table-cell' },
          ]"
          :rows="sortedCustomers"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
          clickable
          @row-click="viewCustomer"
          selectable
          :selected-ids="selectedIds"
          @update:selectedIds="selectedIds = $event"
        >
          <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 w-9 h-9 rounded-lg overflow-hidden border border-border bg-surface-secondary flex items-center justify-center">
                <span class="text-text-tertiary"><UsersIcon class="w-4 h-4" /></span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-text-primary truncate max-w-[200px]">{{ row.name }}</p>
              </div>
            </div>
          </template>
          <template #cell-email="{ row }">
            <span class="text-sm text-text-secondary truncate max-w-[200px]">{{ row.email || '—' }}</span>
          </template>
          <template #cell-phone="{ row }">
            <span class="text-sm text-text-secondary">{{ row.phone || '—' }}</span>
          </template>
          <template #cell-city="{ row }">
            <span class="text-sm text-text-secondary">{{ row.city || '—' }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'CustomerShow', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><EyeIcon class="w-4 h-4" /></span>{{ $t('common.view') }}
                </router-link>
                <router-link :to="{ name: 'CustomerEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.customers.delete_confirm', { name: deleteTarget?.name }) }}</p>
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
  UsersIcon, PlusIcon, ArrowDownTrayIcon, TrashIcon, EyeIcon, PencilIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const router = useRouter();
const showToast = inject('showToast');

const customers = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);

const sortedCustomers = computed(() => {
  if (!sortBy.value) return customers.value;
  return [...customers.value].sort((a, b) => {
    let aVal = a[sortBy.value] ?? '';
    let bVal = b[sortBy.value] ?? '';
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

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchCustomers, 300);
});

onMounted(() => {
  fetchCustomers();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

async function fetchCustomers(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/customers', { params });
    customers.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch {
    showToast($t('page.customers.load_error'), 'error');
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchCustomers(page);
}

function viewCustomer(customer) {
  router.push({ name: 'CustomerShow', params: { id: customer.id } });
}

function confirmDelete(customer) {
  deleteTarget.value = customer;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  const currentPage = meta.value?.current_page || 1;
  deleteTarget.value = null;
  try {
    await axios.delete(`/customers/${id}`);
    showToast($t('page.customers.deleted'), 'success');
    const isLastItemOfLastPage = customers.value.length === 1 && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    fetchCustomers(isLastItemOfLastPage ? currentPage - 1 : currentPage);
  } catch {
    showToast($t('page.customers.delete_error'), 'error');
  }
}

async function bulkDelete() {
  if (!window.confirm($t('page.customers.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  const currentPage = meta.value?.current_page || 1;
  try {
    await axios.post('/customers/bulk-delete', { ids: selectedIds.value });
    showToast($t('common.bulk_delete_success'), 'success');
    const isLastPageCleared = selectedIds.value.length === customers.value.length && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    selectedIds.value = [];
    fetchCustomers(isLastPageCleared ? currentPage - 1 : currentPage);
  } catch {
    showToast($t('common.error'), 'error');
  }
}

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${$t('page.customers.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/customers', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast($t('common.export_excel_unavailable'), 'error'); }
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/customers/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch { showToast($t('common.export_csv_unavailable'), 'error'); }
}
</script>
