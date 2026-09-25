<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Gestion" :title="$t('page.events.title')" :subtitle="meta ? $t('page.events.total_count', { count: meta.total }) : undefined">
        <template #actions>
          <BaseButton variant="primary" size="sm" :to="{ name: 'EventCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.events.create') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.events.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: `10 ${$t('common.per_page')}` },
          { value: 25, label: `25 ${$t('common.per_page')}` },
          { value: 50, label: `50 ${$t('common.per_page')}` },
          { value: 100, label: `100 ${$t('common.per_page')}` },
        ]" size="sm" class="w-28" />
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="events.length === 0">
        <BaseEmptyState :title="$t('page.events.title')" :description="$t('page.events.empty')">
          <BaseButton variant="primary" size="sm" :to="{ name: 'EventCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.events.create') }}
          </BaseButton>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'title', label: $t('form.title'), sortable: true },
            { key: 'type', label: $t('form.type'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'start_date', label: $t('form.start_date'), sortable: true, class: 'hidden md:table-cell' },
            { key: 'end_date', label: $t('form.end_date'), sortable: true, class: 'hidden lg:table-cell' },
            { key: 'description', label: $t('common.description'), class: 'hidden lg:table-cell' },
          ]"
          :rows="sortedEvents"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-title="{ row }">
            <p class="text-sm font-medium text-text-primary truncate max-w-[250px]">{{ row.title }}</p>
          </template>
          <template #cell-type="{ row }">
            <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border', 'bg-gray-100 text-gray-700 border-gray-200']">{{ $t(typeMap[row.type] || 'page.events.type_other') }}</span>
          </template>
          <template #cell-start_date="{ row }">
            <span class="text-sm text-text-secondary">{{ formatDate(row.start_date) }}</span>
          </template>
          <template #cell-end_date="{ row }">
            <span class="text-sm text-text-secondary">{{ formatDate(row.end_date) }}</span>
          </template>
          <template #cell-description="{ row }">
            <span class="text-sm text-text-secondary truncate max-w-xs block">{{ row.description || '—' }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'EventEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.events.delete_confirm') }}</p>
      <template #footer>
        <BaseButton variant="ghost" @click="deleteTarget = null">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="danger" @click="executeDelete()">{{ $t('common.delete') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, inject, watch } from 'vue';
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

const events = ref([]);
const loading = ref(true);
const meta = ref(null);
const perPage = ref(25);
const sortBy = ref('title');
const sortOrder = ref('asc');
const deleteTarget = ref(null);
const search = ref('');
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);

const typeMap = {
  meeting: 'page.events.type_meeting',
  appointment: 'page.events.type_appointment',
  call: 'page.events.type_call',
  task: 'page.events.type_task',
  other: 'page.events.type_other',
};

function formatDate(value) {
  return value ? value.slice(0, 10) : '—';
}

const sortedEvents = computed(() => {
  if (!sortBy.value) return events.value;
  return [...events.value].sort((a, b) => {
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

onMounted(() => { fetchEvents(); });
onBeforeUnmount(() => { clearTimeout(debounceTimer); });

async function fetchEvents(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/events', { params });
    events.value = data.data ?? data;
    meta.value = data.meta ?? null;
  } catch { showToast($t('page.events.load_error'), 'error'); } finally { loading.value = false; }
}

function resetFilters() { search.value = ''; }

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchEvents, 300);
});

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchEvents(page);
}

function confirmDelete(e) { deleteTarget.value = e; }

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  const currentPage = meta.value?.current_page || 1;
  deleteTarget.value = null;
  try {
    await axios.delete(`/events/${id}`);
    showToast($t('page.events.deleted'), 'success');
    const isLastItemOfLastPage = events.value.length === 1 && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    fetchEvents(isLastItemOfLastPage ? currentPage - 1 : currentPage);
  } catch { showToast($t('common.delete_error'), 'error'); }
}
</script>
