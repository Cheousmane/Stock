<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Système" :title="$t('page.users.title')" :subtitle="meta ? $t('page.users.title') + ' · ' + meta.total : undefined">
        <template #actions>
          <BaseButton v-if="canManage" variant="primary" size="sm" :to="{ name: 'UserCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.users.create') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('common.search')" clearable size="sm" class="flex-1 max-w-xs" />
        <BaseSelect v-model="perPage" :options="[
          { value: 10, label: `10 ${$t('common.per_page')}` },
          { value: 25, label: `25 ${$t('common.per_page')}` },
          { value: 50, label: `50 ${$t('common.per_page')}` },
          { value: 100, label: `100 ${$t('common.per_page')}` },
        ]" size="sm" class="w-28" />
        <button v-if="hasActiveFilters" @click="resetFilters" class="text-sm text-text-tertiary hover:text-text-secondary self-center">{{ $t('common.clear_filters') }}</button>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>
      <template v-else-if="users.length === 0">
        <BaseEmptyState :title="$t('page.users.title')" :description="hasActiveFilters ? $t('common.no_results') : $t('page.users.empty')">
          <BaseButton v-if="!hasActiveFilters && canManage" variant="primary" size="sm" :to="{ name: 'UserCreate' }">
            <span class="text-white"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.users.create') }}
          </BaseButton>
          <button v-else-if="hasActiveFilters" @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="[
            { key: 'name', label: $t('page.users.name'), sortable: true },
            { key: 'email', label: $t('page.users.email'), sortable: true, class: 'hidden sm:table-cell' },
            { key: 'is_online', label: $t('common.status'), sortable: true, align: 'center', class: 'hidden sm:table-cell' },
            { key: 'roles', label: $t('page.users.roles'), class: 'hidden md:table-cell' },
            { key: 'last_login_at', label: $t('page.users.last_login'), sortable: true, class: 'hidden lg:table-cell' },
          ]"
          :rows="sortedUsers"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
          clickable
          @row-click="viewUser"
        >
          <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0 w-9 h-9 rounded-lg overflow-hidden border border-border bg-surface-secondary flex items-center justify-center">
                <span class="text-text-tertiary"><UserCircleIcon class="w-5 h-5" /></span>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-medium text-text-primary truncate max-w-[200px]">{{ row.name }}</p>
              </div>
            </div>
          </template>
          <template #cell-email="{ row }">
            <span class="text-sm text-text-secondary truncate max-w-[200px] block">{{ row.email }}</span>
          </template>
          <template #cell-is_online="{ row }">
            <span v-if="row.is_online" class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700">
              <span class="w-2 h-2 bg-green-500 rounded-full"></span>{{ $t('status.online') }}
            </span>
            <span v-else class="inline-flex items-center gap-1.5 text-xs font-medium text-text-tertiary">
              <span class="w-2 h-2 bg-gray-300 rounded-full"></span>{{ $t('status.offline') }}
            </span>
          </template>
          <template #cell-roles="{ row }">
            <div class="flex flex-wrap gap-1">
              <span v-for="role in row.roles" :key="role"
                class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 rounded-full">
                {{ $t(roleLabel(role)) }}
              </span>
              <span v-if="!row.roles.length" class="text-xs text-text-tertiary">—</span>
            </div>
          </template>
          <template #cell-last_login_at="{ row }">
            <span class="text-sm text-text-secondary">{{ formatLastLogin(row.last_login_at) }}</span>
          </template>
          <template #actions="{ row }">
            <BaseDropdown align="right">
              <template #default="{ toggle }">
                <button @click.stop="toggle" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-secondary rounded-lg transition-colors">
                  <span class="text-text-tertiary"><EllipsisVerticalIcon class="w-4 h-4" /></span>
                </button>
              </template>
              <template #menu>
                <router-link :to="{ name: 'UserEdit', params: { id: row.id } }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-primary hover:bg-surface-secondary transition-colors">
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
      <p class="text-sm text-text-secondary">{{ $t('page.users.delete_confirm', { name: deleteTarget?.name }) }}</p>
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
  PlusIcon, TrashIcon, PencilIcon, UserCircleIcon,
  EllipsisVerticalIcon,
} from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const router = useRouter();
const showToast = inject('showToast');

const canManage = computed(() => {
  try { return JSON.parse(localStorage.getItem('user') || '{}').permissions?.includes('manage_users'); } catch { return false; }
});

const users = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('name');
const sortOrder = ref('asc');
const deleteTarget = ref(null);
let debounceTimer = null;

const hasActiveFilters = computed(() => search.value);

const sortedUsers = computed(() => {
  if (!sortBy.value) return users.value;
  return [...users.value].sort((a, b) => {
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

function roleLabel(role) {
  const labels = {
    admin: 'roles.admin',
    manager: 'roles.manager',
    accountant: 'roles.accountant',
    warehouse_manager: 'roles.warehouse_manager',
    sales: 'roles.sales',
    employee: 'roles.employee',
  };
  return labels[role] || role;
}

function formatDate(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatLastLogin(date) {
  if (!date) return $t('common.never');
  const diff = Date.now() - new Date(date).getTime();
  const minutes = Math.floor(diff / 60000);
  if (minutes < 1) return $t('common.just_now');
  if (minutes < 60) return $t('common.minutes_ago', { minutes });
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return $t('common.hours_ago', { hours });
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchUsers, 300);
});

onMounted(() => { fetchUsers(); });
onBeforeUnmount(() => { if (debounceTimer) clearTimeout(debounceTimer); });

async function fetchUsers(page) {
  loading.value = true;
  try {
    const params = { page: page || 1, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/users', { params });
    users.value = data.data ?? data;
    meta.value = data.meta ?? null;
  } catch { showToast($t('page.users.load_error'), 'error'); } finally { loading.value = false; }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchUsers(page);
}

function viewUser(user) {
  router.push({ name: 'UserEdit', params: { id: user.id } });
}

function confirmDelete(user) { deleteTarget.value = user; }

async function executeDelete() {
  if (!deleteTarget.value) return;
  const id = deleteTarget.value.id;
  const currentPage = meta.value?.current_page || 1;
  deleteTarget.value = null;
  try {
    await axios.delete(`/users/${id}`);
    showToast($t('page.users.deleted'), 'success');
    const isLastItemOfLastPage = users.value.length === 1 && meta.value?.last_page > 1 && currentPage === meta.value.last_page;
    fetchUsers(isLastItemOfLastPage ? currentPage - 1 : currentPage);
  } catch { showToast($t('common.delete_error'), 'error'); }
}
</script>
