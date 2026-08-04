<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :title="$t('page.activity_logs.title')" :subtitle="meta ? $t('page.activity_logs.total_count', { count: meta.total }) : undefined" />

      <div class="flex flex-col sm:flex-row gap-3">
        <BaseInput v-model="search" :placeholder="$t('page.activity_logs.search_placeholder')" clearable size="sm" class="flex-1 max-w-xs" />
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
      <template v-else-if="logs.length === 0">
        <BaseEmptyState :title="$t('page.activity_logs.no_logs')" :description="search ? $t('page.activity_logs.no_results') : $t('page.activity_logs.empty')">
          <button v-if="search" @click="resetFilters" class="text-sm text-primary-600">{{ $t('common.clear_filters') }}</button>
        </BaseEmptyState>
      </template>
      <template v-else>
        <BaseTable
          :columns="columns"
          :rows="sortedLogs"
          :sort-by="sortBy"
          :sort-order="sortOrder"
          @sort="toggleSort"
        >
          <template #cell-created_at="{ row }">
            <span class="text-sm text-text-secondary whitespace-nowrap">{{ formatDateTime(row.created_at) }}</span>
          </template>
          <template #cell-causer_name="{ row }">
            <span class="text-sm font-medium text-text-primary">{{ row.causer_name || $t('page.activity_logs.system') }}</span>
          </template>
          <template #cell-description="{ row }">
            <span class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full border"
              :class="logClass(row.description)">{{ translateLog(row.description) }}</span>
          </template>
          <template #cell-subject_description="{ row }">
            <span class="text-sm text-text-secondary truncate max-w-[250px] block">{{ row.subject_description }}</span>
          </template>
          <template #cell-details="{ row }">
            <button v-if="hasDetails(row.properties)" @click="toggleDetails(row.id)"
              class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 transition-colors">
              <span class="text-current"><ChevronDownIcon v-if="expandedLogId === row.id" class="w-4 h-4" /><ChevronRightIcon v-else class="w-4 h-4" /></span>
              {{ expandedLogId === row.id ? $t('common.hide') : $t('common.show') }}
            </button>
            <span v-else class="text-text-tertiary">—</span>
          </template>
        </BaseTable>
        <div v-if="expandedLogId" class="bg-surface-tertiary border-t border-border px-4 py-3 rounded-b-xl">
          <pre class="text-xs text-text-secondary whitespace-pre-wrap font-mono leading-relaxed">{{ formatDetails(getLogById(expandedLogId)?.properties) }}</pre>
        </div>
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
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject, onBeforeUnmount, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePagination from '../../Components/ui/BasePagination.vue';
import BaseTable from '../../Components/ui/BaseTable.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ChevronDownIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const logs = ref([]);
const loading = ref(true);
const search = ref('');
const perPage = ref(25);
const meta = ref(null);
const sortBy = ref('created_at');
const sortOrder = ref('desc');
const expandedLogId = ref(null);
let debounceTimer = null;

const columns = [
  { key: 'created_at', label: $t('page.activity_logs.datetime'), sortable: true },
  { key: 'causer_name', label: $t('page.activity_logs.user'), sortable: true },
  { key: 'description', label: $t('page.activity_logs.action'), sortable: true },
  { key: 'subject_description', label: $t('page.activity_logs.description'), sortable: false },
  { key: 'details', label: 'Détails', sortable: false },
]

const sortedLogs = computed(() => {
  if (!sortBy.value) return logs.value;
  return [...logs.value].sort((a, b) => {
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

function resetFilters() {
  search.value = '';
}

watch([search, perPage], () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchLogs, 300);
});

function logClass(description) {
  if (!description) return 'bg-gray-50 text-gray-600 border-gray-200';
  const d = description.toLowerCase();
  if (d.includes('créé') || d.includes('created') || d.includes('ajout')) return 'bg-green-50 text-green-700 border-green-200';
  if (d.includes('modifié') || d.includes('updated') || d.includes('édit')) return 'bg-blue-50 text-blue-700 border-blue-200';
  if (d.includes('supprimé') || d.includes('deleted') || d.includes('retir')) return 'bg-red-50 text-red-700 border-red-200';
  if (d.includes('connect') || d.includes('login') || d.includes('auth')) return 'bg-purple-50 text-purple-700 border-purple-200';
  return 'bg-gray-50 text-gray-600 border-gray-200';
}

function getLogById(id) { return logs.value.find(l => l.id === id); }

function hasDetails(properties) {
  if (!properties) return false;
  if (typeof properties === 'string') properties = JSON.parse(properties);
  if (properties.attributes || properties.old) return true;
  return Object.keys(properties).length > 0;
}

function toggleDetails(id) { expandedLogId.value = expandedLogId.value === id ? null : id; }

function formatVal(val) {
  if (typeof val === 'object' && val !== null) return JSON.stringify(val, null, 2).replace(/\n/g, '\n  ');
  return val;
}

function translateLog(desc) {
  if (!desc) return '';
  const exactMatches = { 'payment received': 'Paiement reçu', 'invoice paid': 'Facture payée' };
  const lowerDesc = desc.toLowerCase();
  if (exactMatches[lowerDesc]) return exactMatches[lowerDesc];
  const statusMatch = desc.match(/Invoice status changed from (\w+) to (\w+)/i);
  if (statusMatch) {
    const statuses = { draft: 'brouillon', sent: 'envoyée', paid: 'payée', overdue: 'en retard', cancelled: 'annulée' };
    const from = statuses[statusMatch[1].toLowerCase()] || statusMatch[1];
    const to = statuses[statusMatch[2].toLowerCase()] || statusMatch[2];
    return `Statut de la facture passé de ${from} à ${to}`;
  }
  const entities = {
    Product: 'Produit', Customer: 'Client', Invoice: 'Facture', Quote: 'Devis',
    User: 'Utilisateur', Category: 'Catégorie', Warehouse: 'Dépôt',
    DeliveryNote: 'Bon de livraison', PurchaseOrder: 'Bon de commande',
    Supplier: 'Fournisseur', PosSession: 'Session de caisse', PosSale: 'Vente caisse',
  };
  const actions = { created: 'créé(e)', updated: 'modifié(e)', deleted: 'supprimé(e)', opened: 'ouverte', closed: 'fermée' };
  const parts = desc.split(' ');
  if (parts.length >= 2) {
    const entity = parts[0];
    const action = parts[1];
    if (entities[entity] && actions[action]) return `${entities[entity]} ${actions[action]}`;
  }
  return desc;
}

function formatDetails(properties) {
  if (!properties) return '';
  if (typeof properties === 'string') properties = JSON.parse(properties);
  const lines = [];
  if (properties.changed_attributes) {
    lines.push('Changements :');
    for (const [key, val] of Object.entries(properties.changed_attributes)) lines.push(`  ${key}: ${formatVal(val)}`);
    return lines.join('\n');
  }
  if (properties.attributes) {
    lines.push($t('page.activity_logs.new_values'));
    for (const [key, val] of Object.entries(properties.attributes)) lines.push(`  ${key}: ${formatVal(val)}`);
  }
  if (properties.old) {
    lines.push($t('page.activity_logs.old_values'));
    for (const [key, val] of Object.entries(properties.old)) lines.push(`  ${key}: ${formatVal(val)}`);
  }
  if (!properties.attributes && !properties.old) {
    for (const [key, val] of Object.entries(properties)) lines.push(`  ${key}: ${formatVal(val)}`);
  }
  return lines.join('\n');
}

function formatDateTime(date) {
  if (!date) return '—';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

async function fetchLogs(page = 1) {
  loading.value = true;
  try {
    const params = { page, per_page: perPage.value };
    if (search.value) params.search = search.value;
    const { data } = await axios.get('/activity-logs', { params });
    logs.value = data.data ?? data;
    meta.value = data.meta ?? null;
  } catch { showToast($t('page.activity_logs.load_error'), 'error'); } finally { loading.value = false; }
}

function changePage(page) {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  fetchLogs(page);
}

onMounted(() => fetchLogs());
onBeforeUnmount(() => { if (debounceTimer) clearTimeout(debounceTimer); });
</script>
