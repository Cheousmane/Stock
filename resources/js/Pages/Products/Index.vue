<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.products.title')" :subtitle="meta ? $t('page.products.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchProducts(meta?.current_page)" title="Rafraîchir">
            <span class="text-current"><ArrowPathIcon class="w-4 h-4" /></span>
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportExcel">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Excel
          </BaseButton>
          <BaseButton variant="secondary" size="sm" @click="exportCsv">
            <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>CSV
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'ProductCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.products.create') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.products.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="categoryFilter" :options="[
          { value: '', label: $t('page.products.all_categories') },
          ...categories.map(c => ({ value: c.id, label: c.name })),
        ]" size="sm" class="w-44" />
        <BaseSelect v-model="statusFilter" :options="[
          { value: '', label: $t('page.products.all_statuses') },
          { value: 'active', label: $t('status.active') },
          { value: 'inactive', label: $t('status.inactive') },
        ]" size="sm" class="w-36" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <div v-if="selectedIds.length > 0" class="flex items-center gap-3 px-4 py-2 bg-surface-secondary rounded-lg">
        <span class="text-sm font-medium text-text-primary">{{ $t('page.products.n_selected', { n: selectedIds.length }) }}</span>
        <BaseButton variant="danger-ghost" size="xs" @click="bulkDelete">{{ $t('common.bulk_delete') }}</BaseButton>
        <BaseButton variant="ghost" size="xs" @click="selectedIds = []">{{ $t('common.deselect') }}</BaseButton>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="products.length === 0">
        <BaseEmptyState :title="$t('page.products.no_products')" :description="search || categoryFilter || statusFilter ? $t('page.products.no_results') : $t('page.products.empty')">
          <BaseButton v-if="!search && !categoryFilter && !statusFilter" variant="primary" size="sm" :to="{ name: 'ProductCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.products.create_first') }}
          </BaseButton>
          <button v-else @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="columns"
          :rows="sortedProducts"
          row-key="id"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          clickable
          @sort="toggleSort"
          @row-click="viewProduct"
        >
          <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg overflow-hidden border border-border bg-surface-tertiary shrink-0">
                <img v-if="row.image_url" :src="row.image_url" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                  <CubeIcon class="w-4 h-4 text-text-tertiary" />
                </div>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-text-primary truncate max-w-[200px]">{{ row.name }}</p>
                <p v-if="row.barcode" class="text-xs text-text-tertiary font-mono truncate max-w-[140px]">{{ row.barcode }}</p>
              </div>
            </div>
          </template>

          <template #cell-sku="{ row }">
            <span class="text-sm text-text-secondary font-mono">{{ row.sku }}</span>
          </template>

          <template #cell-category_name="{ row }">
            <BaseBadge v-if="row.category_name" variant="default" size="xs">{{ row.category_name }}</BaseBadge>
            <span v-else class="text-text-tertiary">—</span>
          </template>

          <template #cell-price_xof="{ row }">
            <span class="text-sm font-medium text-text-primary tabular-nums">{{ row.price_xof > 0 ? formatXOF(row.price_xof) : '—' }}</span>
          </template>

          <template #cell-quantity="{ row }">
            <div class="flex items-center gap-2 justify-end">
              <div class="w-16 h-1.5 bg-surface-tertiary rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300"
                  :class="stockBarClass(row)" :style="{ width: stockPercent(row) + '%' }"></div>
              </div>
              <span class="text-sm font-medium tabular-nums"
                :class="row.quantity <= 0 ? 'text-red-600' : row.quantity <= (row.min_stock || 5) ? 'text-amber-600' : 'text-text-primary'">{{ row.quantity }}</span>
            </div>
          </template>

          <template #cell-is_active="{ row }">
            <div class="flex justify-center" @click.stop>
              <button @click="toggleStatus(row)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border transition-all duration-200"
                :class="row.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20 hover:bg-emerald-100' : 'bg-surface-tertiary text-text-tertiary border-border hover:bg-surface-secondary'">
                <span class="w-1.5 h-1.5 rounded-full" :class="row.is_active ? 'bg-emerald-500' : 'bg-text-tertiary'"></span>
                {{ row.is_active ? $t('status.active') : $t('status.inactive') }}
              </button>
            </div>
          </template>

          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'ProductShow', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><EyeIcon class="w-4 h-4" /></span>{{ $t('common.view') }}
                </router-link>
                <router-link :to="{ name: 'ProductEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><PencilIcon class="w-4 h-4" /></span>{{ $t('common.edit') }}
                </router-link>
                <button @click="duplicateProduct(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><DocumentDuplicateIcon class="w-4 h-4" /></span>{{ $t('common.duplicate') }}
                </button>
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

    <BaseModal v-model="deleteTarget" title="Confirmer la suppression" size="sm">
      <p class="text-sm text-text-secondary">Êtes-vous sûr de vouloir supprimer <strong class="text-text-primary">{{ deleteTarget?.name }}</strong> ? Cette action est irréversible.</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseTable from '../../Components/ui/BaseTable.vue';
import BasePagination from '../../Components/ui/BasePagination.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseDropdown from '../../Components/ui/BaseDropdown.vue';
import { usePolling } from '../../composables/usePolling';
import {
  CubeIcon, PlusIcon, ArrowDownTrayIcon, ArrowPathIcon, TrashIcon, EyeIcon, PencilIcon,
  DocumentDuplicateIcon, EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const router = useRouter();
const showToast = inject('showToast');

const products = ref([]);
const categories = ref([]);
const loading = ref(true);
const search = ref('');
const categoryFilter = ref('');
const statusFilter = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedIds = ref([]);
const deleteTarget = ref(null);
let debounceTimer = null;

const columns = [
  { key: 'name', label: 'Nom', sortable: true },
  { key: 'sku', label: 'SKU', sortable: true, class: 'hidden sm:table-cell' },
  { key: 'category_name', label: 'Catégorie', sortable: true, class: 'hidden md:table-cell' },
  { key: 'price_xof', label: 'Prix', sortable: true, align: 'right', class: 'hidden lg:table-cell' },
  { key: 'quantity', label: 'Stock', sortable: true, align: 'right' },
  { key: 'is_active', label: 'Statut', sortable: true, align: 'center', class: 'hidden sm:table-cell' },
];

const hasActiveFilters = computed(() => search.value || categoryFilter.value || statusFilter.value);

function isSelected(id) { return selectedIds.value.includes(id); }
function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id);
  idx > -1 ? selectedIds.value.splice(idx, 1) : selectedIds.value.push(id);
}

const sortedProducts = computed(() => {
  if (!sortBy.value) return products.value;
  return [...products.value].sort((a, b) => {
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
  if (sortBy.value === field) sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  else { sortBy.value = field; sortOrder.value = 'asc'; }
}

function stockPercent(product) {
  const max = Math.max(product.quantity, product.min_stock || 5, 20);
  return Math.min((product.quantity / max) * 100, 100);
}
function stockBarClass(product) {
  if (product.quantity <= 0) return 'bg-red-500';
  if (product.quantity <= (product.min_stock || 5)) return 'bg-amber-400';
  return 'bg-emerald-400';
}

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

function resetFilters() { search.value = ''; categoryFilter.value = ''; statusFilter.value = ''; }

watch([search, categoryFilter, statusFilter, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => fetchProducts(), 300);
});

usePolling(() => fetchProducts(undefined, { silent: true }));

onMounted(() => {
  fetchCategories();
  fetchProducts();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});

async function fetchCategories() {
  try { const { data } = await axios.get('/categories'); categories.value = data.data ?? data ?? []; } catch {}
}

async function fetchProducts(page, { silent = false } = {}) {
  if (!silent) loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    if (categoryFilter.value) params.category_id = categoryFilter.value;
    if (statusFilter.value) params.is_active = statusFilter.value === 'active' ? '1' : '0';
    const { data } = await axios.get('/products', { params });
    products.value = data.data ?? data;
    meta.value = data.meta ?? null;
    selectedIds.value = [];
  } catch { showToast($t('page.products.loading_error'), 'error'); }
  finally { loading.value = false; }
}

function changePage(page) { if (page < 1 || (meta.value && page > meta.value.last_page)) return; fetchProducts(page); }
function viewProduct(product) { router.push({ name: 'ProductShow', params: { id: product.id } }); }

async function toggleStatus(product) {
  try {
    await axios.patch(`/products/${product.id}`, { is_active: !product.is_active });
    product.is_active = !product.is_active;
    showToast(product.is_active ? $t('page.products.activated') : $t('page.products.deactivated'), 'success');
  } catch { showToast($t('common.error'), 'error'); }
}

async function duplicateProduct(product) {
  try {
    await axios.post(`/products/${product.id}/duplicate`);
    showToast($t('page.products.duplicated'), 'success');
    fetchProducts(meta.value?.current_page || 1);
  } catch { showToast($t('common.error'), 'error'); }
}

function confirmDelete(product) { deleteTarget.value = product; }

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id; deleteTarget.value = null;
  try { await axios.delete(`/products/${id}`); showToast($t('page.products.delete_success'), 'success'); fetchProducts(meta.value?.current_page || 1); }
  catch { showToast($t('page.products.delete_error'), 'error'); }
}

async function bulkDelete() {
  if (!confirm($t('page.products.bulk_delete_confirm', { n: selectedIds.value.length }))) return;
  try {
    await axios.post('/products/bulk-delete', { ids: selectedIds.value });
    showToast($t('page.products.bulk_delete_success'), 'success');
    selectedIds.value = []; fetchProducts(meta.value?.current_page || 1);
  } catch { showToast($t('common.error'), 'error'); }
}

function exportName(ext) { return `${$t('page.products.title')}_${new Date().toISOString().slice(0, 10)}.${ext}`; }

async function exportExcel() {
  try {
    const res = await axios.get('/exports/products', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('xlsx');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}

async function exportCsv() {
  try {
    const res = await axios.get('/exports/products/csv', { responseType: 'blob' });
    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement('a'); a.href = url; a.download = exportName('csv');
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
  } catch {}
}
</script>
