<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto space-y-4">
      <BasePageHeader :title="$t('page.categories.title')" :subtitle="meta ? $t('common.total') + ': ' + meta.total : undefined">
        <template #actions>
          <BaseButton variant="primary" size="sm" :to="{ name: 'CategoryCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.categories.create') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" placeholder="Rechercher par nom..." clearable size="sm" class="flex-1 max-w-xs" />
        <BaseButton v-if="hasActiveFilters" variant="ghost" size="sm" @click="resetFilters">{{ $t('common.clear') }}</BaseButton>
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
      <template v-else-if="categories.length === 0">
        <BaseEmptyState :title="$t('page.categories.title')" :description="$t('page.categories.empty')">
          <BaseButton variant="primary" size="sm" :to="{ name: 'CategoryCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.categories.create') }}
          </BaseButton>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'name', label: $t('page.categories.name'), sortable: true },
            { key: 'description', label: $t('common.description'), sortable: true, class: 'hidden md:table-cell' },
          ]"
          :rows="sortedCategories"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-name="{ row }">
            <p class="text-sm font-medium text-text-primary">{{ row.name }}</p>
          </template>
          <template #cell-description="{ row }">
            <span class="text-sm text-text-secondary">{{ row.description || '—' }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'CategoryEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.categories.delete_confirm') }}</p>
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
import {
  PlusIcon, TrashIcon, PencilIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const categories = ref([]);
const loading = ref(true);
const meta = ref(null);
const perPage = ref(25);
const sortBy = ref('name');
const sortOrder = ref('asc');
const deleteTarget = ref(null);
const search = ref('');
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);

const sortedCategories = computed(() => {
  if (!sortBy.value) return categories.value;
  return [...categories.value].sort((a, b) => {
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
  if (sortBy.value === field) { sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'; }
  else { sortBy.value = field; sortOrder.value = 'asc'; }
}

onMounted(() => { fetchCategories(); });
onBeforeUnmount(() => { clearTimeout(debounceTimer); });

async function fetchCategories(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/categories', { params });
    categories.value = data.data ?? data;
    meta.value = data.meta ?? null;
  } catch {} finally { loading.value = false; }
}

function resetFilters() { search.value = ''; }

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchCategories, 300);
});

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchCategories(page);
}

function confirmDelete(cat) { deleteTarget.value = cat; }

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  deleteTarget.value = null;
  try {
    await axios.delete(`/categories/${id}`);
    showToast($t('page.categories.deleted'), 'success');
    fetchCategories(meta.value?.current_page || 1);
  } catch { showToast($t('common.delete_error'), 'error'); }
}
</script>
