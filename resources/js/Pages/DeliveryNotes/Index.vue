<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.delivery_notes.title')" :subtitle="meta ? $t('page.delivery_notes.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'DeliveryNoteCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.delivery_notes.new') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.delivery_notes.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="statusFilter" :options="[
          { value: '', label: $t('page.delivery_notes.all_statuses') },
          { value: 'pending', label: $t('status.draft') },
          { value: 'shipped', label: $t('status.shipped') },
          { value: 'delivered', label: $t('status.delivered') },
          { value: 'returned', label: $t('status.returned') },
        ]" size="sm" class="w-40" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.delivery_notes.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="notes.length === 0">
        <BaseEmptyState :title="$t('page.delivery_notes.no_notes')" :description="search || statusFilter ? $t('page.delivery_notes.no_results') : $t('page.delivery_notes.empty')">
          <BaseButton v-if="!search && !statusFilter" variant="primary" size="sm" :to="{ name: 'DeliveryNoteCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.delivery_notes.create_first') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'number', label: $t('page.delivery_notes.number'), sortable: true },
            { key: 'customer_name', label: $t('page.delivery_notes.customer'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'issue_date', label: $t('page.delivery_notes.issue_date'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'delivery_date', label: $t('page.delivery_notes.delivery_date'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'status', label: $t('page.delivery_notes.status'), sortable: true, align: 'center', class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedNotes"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
          clickable
          @row-click="viewNote"
        >
          <template #cell-number="{ row }">
            <p class="text-sm font-medium text-text-primary">{{ row.number }}</p>
          </template>
          <template #cell-customer_name="{ row }">
            <span class="text-sm text-text-secondary">{{ row.customer?.name }}</span>
          </template>
          <template #cell-issue_date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.issue_date?.slice(0, 10) || '-' }}</span>
          </template>
          <template #cell-delivery_date="{ row }">
            <span class="text-sm text-text-secondary">{{ row.delivery_date?.slice(0, 10) || '-' }}</span>
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
                <router-link :to="{ name: 'DeliveryNoteShow', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><EyeIcon class="w-4 h-4" /></span>{{ $t('common.view') }}
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
      <p class="text-sm text-text-secondary">{{ $t('page.delivery_notes.delete_confirm', { number: deleteTarget?.number }) }}</p>
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
  PlusIcon, ArrowDownTrayIcon, TrashIcon, EyeIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const router = useRouter();
const showToast = inject('showToast');

const notes = ref([]);
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
  pending: { class: 'bg-gray-100 text-gray-700 border-gray-200', dot: 'bg-gray-400', label: 'status.draft' },
  shipped: { class: 'bg-blue-100 text-blue-700 border-blue-200', dot: 'bg-blue-500', label: 'status.shipped' },
  delivered: { class: 'bg-green-100 text-green-700 border-green-200', dot: 'bg-green-500', label: 'status.delivered' },
  returned: { class: 'bg-red-100 text-red-700 border-red-200', dot: 'bg-red-500', label: 'status.returned' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-gray-100 text-gray-700 border-gray-200'; }
function statusDotClass(s) { return statusMap[s]?.dot || 'bg-gray-400'; }
function statusLabel(s) { return statusMap[s]?.label || s; }

const hasActiveFilters = computed(() => search.value || statusFilter.value);
const allSelected = computed(() => notes.value.length > 0 && selectedIds.value.length === notes.value.length);

function isSelected(id) { return selectedIds.value.includes(id); }

function toggleAll() {
  if (allSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = notes.value.map(n => n.id);
  }
}

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) selectedIds.value.splice(idx, 1);
  else selectedIds.value.push(id);
}

const sortedNotes = computed(() => {
  if (!sortBy.value) return notes.value;
  return [...notes.value].sort((a, b) => {
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

function resetFilters() {
  search.value = '';
  statusFilter.value = '';
}

watch([search, perPage, statusFilter], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchNotes, 300);
});

onMounted(() => { fetchNotes(); });
onBeforeUnmount(() => { if (debounceTimer) clearTimeout(debounceTimer); });

function exportName(ext) {
  const date = new Date().toISOString().slice(0, 10);
  return `${$t('page.delivery_notes.title')}_${date}.${ext}`;
}

async function exportExcel() {
  try {
    const res = await axios.get('/exports/delivery-notes', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/delivery-notes/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}

async function fetchNotes(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    if (statusFilter.value) params.status = statusFilter.value;
    const { data } = await axios.get('/delivery-notes', { params });
    notes.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch {} finally {
    loading.value = false;
  }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchNotes(page);
}

function viewNote(note) {
  router.push({ name: 'DeliveryNoteShow', params: { id: note.id } });
}

function confirmDelete(note) {
  deleteTarget.value = note;
}

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/delivery-notes/${id}`);
    showToast($t('common.deleted'), 'success');
    fetchNotes(meta.value?.current_page || 1);
  } catch { showToast($t('common.error'), 'error'); }
}

async function bulkDelete() {
  if (!window.confirm($t('page.delivery_notes.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/delivery-notes/bulk-delete', { ids: selectedIds.value });
    showToast($t('common.bulk_delete_success'), 'success');
    selectedIds.value = [];
    fetchNotes(meta.value?.current_page || 1);
  } catch { showToast($t('common.error'), 'error'); }
}
</script>
