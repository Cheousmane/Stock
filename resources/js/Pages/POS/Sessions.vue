<template>
  <AdminLayout>
    <div class="max-w-6xl mx-auto space-y-6">
      <BasePageHeader title="Activités de la caisse" subtitle="Historique des sessions de caisse">
        <template #actions>
          <BaseButton variant="secondary" size="sm" :to="{ name: 'Dashboard' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>Retour
          </BaseButton>
        </template>
      </BasePageHeader>

      <div v-if="loading" class="space-y-3">
        <div v-for="n in 5" :key="n" class="h-16 bg-surface-tertiary rounded-xl animate-pulse"></div>
      </div>

      <template v-else>
        <!-- Sessions Table -->
        <BaseCard padding="none">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-surface-secondary border-b border-border">
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-tertiary uppercase">Utilisateur</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-tertiary uppercase">Statut</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-tertiary uppercase">Ouverture</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-text-tertiary uppercase">Fermeture</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-text-tertiary uppercase">Fond de caisse</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-text-tertiary uppercase">Ventes</th>
                  <th class="px-4 py-3 text-xs font-semibold tracking-wider text-right text-text-tertiary uppercase">Total</th>
                  <th class="w-16 px-4 py-3 text-xs font-semibold tracking-wider text-right text-text-tertiary uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="session in sessions" :key="session.id" class="transition-colors hover:bg-surface-secondary">
                  <td class="px-4 py-3 text-sm font-medium text-text-primary">{{ session.user?.name || '—' }}</td>
                  <td class="px-4 py-3">
                    <BaseBadge :variant="session.status === 'open' ? 'success' : 'default'" size="xs" :dot="true">
                      {{ session.status === 'open' ? 'Ouverte' : 'Fermée' }}
                    </BaseBadge>
                  </td>
                  <td class="px-4 py-3 text-sm text-text-tertiary">{{ formatDate(session.opened_at) }}</td>
                  <td class="px-4 py-3 text-sm text-text-tertiary">{{ session.closed_at ? formatDate(session.closed_at) : '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right tabular-nums text-text-primary">{{ formatXof(session.opening_cash_xof) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-text-primary">{{ session.sales_count ?? 0 }}</td>
                  <td class="px-4 py-3 text-sm text-right tabular-nums font-medium text-text-primary">{{ formatXof(session.sales_sum_total_xof ?? 0) }}</td>
                  <td class="px-4 py-3 text-right">
                    <button @click="viewSession(session)" class="p-1.5 text-text-tertiary hover:text-text-primary hover:bg-surface-tertiary rounded-lg transition-colors">
                      <span class="text-current"><ChevronDownIcon :class="['w-4 h-4 transition-transform', selectedSessionId === session.id ? 'rotate-180' : '']" /></span>
                    </button>
                  </td>
                </tr>
                <tr v-if="sessions.length === 0">
                  <td colspan="8" class="px-4 py-12 text-center text-sm text-text-tertiary">Aucune session de caisse trouvée.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-border">
            <p class="text-sm text-text-tertiary">
              {{ (meta.current_page - 1) * meta.per_page + 1 }}–{{ Math.min(meta.current_page * meta.per_page, meta.total) }} sur {{ meta.total }}
            </p>
            <div class="flex gap-1">
              <BaseButton variant="secondary" size="xs" @click="changePage(meta.current_page - 1)" :disabled="meta.current_page <= 1">Précédent</BaseButton>
              <BaseButton variant="secondary" size="xs" @click="changePage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page">Suivant</BaseButton>
            </div>
          </div>
        </BaseCard>

        <!-- Sales detail for selected session -->
        <BaseCard v-if="selectedSession" padding="none">
          <div class="px-4 py-3 bg-surface-secondary border-b border-border flex items-center justify-between">
            <h3 class="text-sm font-semibold text-text-primary">
              Ventes — {{ selectedSession.user?.name }} —
              <span class="font-normal text-text-tertiary">{{ formatDate(selectedSession.opened_at) }}</span>
            </h3>
            <span class="text-sm text-text-tertiary">{{ (selectedSales?.length || 0) }} vente(s)</span>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="bg-surface-secondary/50 border-b border-border">
                  <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">Reçu</th>
                  <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">Date</th>
                  <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">Client</th>
                  <th class="px-4 py-2 text-xs font-semibold text-left text-text-tertiary uppercase">Paiement</th>
                  <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">Total</th>
                  <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">Payé</th>
                  <th class="px-4 py-2 text-xs font-semibold text-right text-text-tertiary uppercase">Rendu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="sale in selectedSales" :key="sale.id" class="hover:bg-surface-secondary">
                  <td class="px-4 py-2 text-sm font-mono text-text-primary">{{ sale.receipt_number }}</td>
                  <td class="px-4 py-2 text-sm text-text-tertiary">{{ formatDate(sale.created_at) }}</td>
                  <td class="px-4 py-2 text-sm text-text-tertiary">{{ sale.customer?.name || '—' }}</td>
                  <td class="px-4 py-2 text-sm text-text-tertiary">{{ paymentLabel(sale.payment_method) }}</td>
                  <td class="px-4 py-2 text-sm text-right tabular-nums text-text-primary">{{ formatXof(sale.total_xof) }}</td>
                  <td class="px-4 py-2 text-sm text-right tabular-nums text-text-primary">{{ formatXof(sale.amount_paid_xof) }}</td>
                  <td class="px-4 py-2 text-sm text-right tabular-nums text-text-tertiary">{{ formatXof(sale.change_returned_xof) }}</td>
                </tr>
                <tr v-if="!selectedSales?.length">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-text-tertiary">Aucune vente dans cette session.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </BaseCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const loading = ref(true);
const sessions = ref([]);
const meta = ref(null);
const selectedSessionId = ref(null);
const selectedSession = ref(null);
const selectedSales = ref(null);

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function formatXof(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v || 0);
}

function paymentLabel(method) {
  const labels = { cash: 'Espèces', card: 'Carte', mobile_money: 'Mobile Money' };
  return labels[method] || method;
}

async function fetchSessions(page = 1) {
  loading.value = true;
  try {
    const { data } = await axios.get(`/pos/sessions?page=${page}`);
    sessions.value = data.data ?? [];
    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      per_page: data.per_page,
      total: data.total,
    };
  } catch {
    sessions.value = [];
    meta.value = null;
  } finally {
    loading.value = false;
  }
}

function changePage(page) {
  fetchSessions(page);
}

async function viewSession(session) {
  if (selectedSessionId.value === session.id) {
    selectedSessionId.value = null;
    selectedSession.value = null;
    selectedSales.value = null;
    return;
  }
  selectedSessionId.value = session.id;
  selectedSession.value = session;
  selectedSales.value = null;
  try {
    const { data } = await axios.get(`/pos/sessions/${session.id}`);
    selectedSales.value = data.data ?? [];
  } catch {
    selectedSales.value = [];
  }
}

onMounted(() => fetchSessions());
</script>
