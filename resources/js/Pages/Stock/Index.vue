<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.stock.title')" :subtitle="meta ? $t('page.stock.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchData(meta?.current_page || 1)" title="Rafraîchir">
            <span class="text-current"><ArrowPathIcon class="w-4 h-4" /></span>
          </BaseButton>
          <BaseButton variant="primary" size="sm" :to="{ name: 'StockTransfer' }">
            <span class="text-white"><ArrowRightIcon class="w-4 h-4" /></span>{{ $t('page.stock.transfer') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.stock.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: '10 / page' },
          { value: 25, label: '25 / page' },
          { value: 50, label: '50 / page' },
          { value: 100, label: '100 / page' },
        ]" size="sm" class="w-28" />
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="products.length === 0">
        <BaseEmptyState :title="$t('page.stock.no_products')" :description="search ? $t('page.stock.no_results') : $t('page.stock.empty')">
          <button v-if="search" @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'name', label: $t('page.stock.product'), sortable: true },
            { key: 'quantity', label: $t('page.stock.quantity'), sortable: true, align: 'right', class: 'hidden sm:table-cell' },
            { key: 'min_stock', label: $t('page.stock.min_stock'), sortable: true, align: 'right', class: 'hidden md:table-cell' },
            { key: 'status', label: $t('common.status'), align: 'center', class: 'hidden sm:table-cell' },
          ]"
          :rows="sortedProducts"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
              <div class="min-w-0">
                <p class="text-sm font-medium text-text-primary truncate max-w-[200px]">{{ row.name }}</p>
                <p v-if="row.sku" class="text-xs text-text-tertiary font-mono truncate max-w-[140px]">{{ row.sku }}</p>
              </div>
            </div>
          </template>
          <template #cell-quantity="{ row }">
            <span class="text-sm font-medium tabular-nums" :class="row.quantity <= 0 ? 'text-red-600' : row.quantity <= (row.min_stock || 5) ? 'text-amber-600' : 'text-text-primary'">{{ row.quantity }}</span>
          </template>
          <template #cell-min_stock="{ row }">
            <span class="text-sm text-text-secondary tabular-nums">{{ row.min_stock ?? '—' }}</span>
          </template>
          <template #cell-status="{ row }">
            <span :class="['px-2.5 py-1 text-xs font-medium rounded-full border', stockStatus(row).class]">{{ $t(stockStatus(row).label) }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <button @click="showMovementForm(row)" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
                  <span class="text-text-tertiary"><AdjustmentsHorizontalIcon class="w-4 h-4" /></span>{{ $t('page.stock.adjust') }}
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

      <div v-if="movements.length > 0" class="mt-10">
        <h2 class="text-xl font-bold text-text-primary">{{ $t('page.stock.movements_title') }}</h2>
        <div class="mt-4 bg-surface border border-border rounded-xl overflow-hidden shadow-sm">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-surface-secondary border-b border-border">
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-secondary uppercase">{{ $t('page.stock.product') }}</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-text-secondary uppercase">{{ $t('page.stock.movement_type') }}</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-text-secondary uppercase">{{ $t('common.quantity') }}</th>
                  <th class="hidden md:table-cell px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-secondary uppercase">{{ $t('page.stock.reason') }}</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-secondary uppercase">{{ $t('common.date') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="m in movements" :key="m.id" class="hover:bg-surface-secondary/80 transition-colors">
                  <td class="px-4 py-3 text-sm text-text-primary">{{ m.product?.name }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="['px-2.5 py-1 text-xs font-medium rounded-full border', m.type === 'in' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200']">
                      {{ m.type === 'in' ? $t('page.stock.type_in') : $t('page.stock.type_out') }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium tabular-nums" :class="m.type === 'in' ? 'text-green-600' : 'text-red-600'">{{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }}</td>
                  <td class="hidden md:table-cell px-4 py-3 text-sm text-text-secondary">{{ m.reason || '—' }}</td>
                  <td class="px-4 py-3 text-sm text-text-secondary whitespace-nowrap">{{ m.created_at }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <BaseModal v-model="selectedProduct" :title="$t('page.stock.adjust_stock') + ' — ' + (selectedProduct?.name || '')" size="sm">
      <form @submit.prevent="recordMovement" class="space-y-4">
        <BaseSelect v-model="movement.type" :options="[
          { value: 'in', label: $t('page.stock.type_in') },
          { value: 'out', label: $t('page.stock.type_out') },
        ]" :label="$t('common.type')" />
        <BaseInput v-model.number="movement.quantity" type="number" min="1" required :label="$t('common.quantity')" />
        <BaseInput v-model="movement.reason" :label="$t('page.stock.reason')" :placeholder="$t('page.stock.reason_placeholder')" />
      </form>
      <template #footer>
        <BaseButton variant="ghost" @click="selectedProduct = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="moving" @click="recordMovement">{{ $t('common.save') }}</BaseButton>
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
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseDropdown from '../../Components/ui/BaseDropdown.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import { usePolling } from '../../composables/usePolling';
import {
  ArrowRightIcon, ArrowPathIcon, EllipsisVerticalIcon, AdjustmentsHorizontalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const products = ref([]);
const movements = ref([]);
const loading = ref(true);
const moving = ref(false);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('name');
const sortOrder = ref('asc');
const selectedProduct = ref(null);
const movement = reactive({ type: 'in', quantity: 1, reason: '' });
let debounceTimer = null;

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
  debounceTimer = setTimeout(fetchData, 300);
});

usePolling(() => fetchData(meta.value?.current_page || 1, { silent: true }));

function stockStatus(p) {
  if (!p.min_stock) return { class: 'bg-green-50 text-green-700 border-green-200', label: 'status.ok' };
  if (p.quantity <= 0) return { class: 'bg-red-50 text-red-700 border-red-200', label: 'status.out_of_stock' };
  if (p.quantity <= p.min_stock) return { class: 'bg-orange-50 text-orange-700 border-orange-200', label: 'status.low_stock' };
  return { class: 'bg-green-50 text-green-700 border-green-200', label: 'status.ok' };
}

async function fetchData(page = 1, { silent = false } = {}) {
  if (!silent) loading.value = true;
  try {
    const params = { page, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const [pRes, mRes] = await Promise.all([
      axios.get('/products', { params }),
      axios.get('/stock-movements', { params: { limit: 20 } }),
    ]);
    products.value = pRes.data.data ?? pRes.data;
    meta.value = pRes.data.meta ?? null;
    movements.value = mRes.data.data ?? mRes.data;
  } catch {} finally { loading.value = false; }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchData(page);
}

function showMovementForm(product) {
  selectedProduct.value = product;
  movement.type = 'in';
  movement.quantity = 1;
  movement.reason = '';
}

async function recordMovement() {
  moving.value = true;
  try {
    await axios.post('/stock-movements', {
      product_id: selectedProduct.value.id,
      type: movement.type,
      quantity: movement.quantity,
      reason: movement.reason,
    });
    selectedProduct.value = null;
    showToast($t('page.stock.movement_saved'), 'success');
    await fetchData(meta.value?.current_page || 1);
  } catch (e) {
    const msg = e.response?.data?.message || $t('common.save_error');
    showToast(msg, 'error');
  } finally { moving.value = false; }
}

onMounted(() => {
  fetchData();
});

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer);
});
</script>
