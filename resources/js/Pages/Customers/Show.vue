<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="customer">
        <BasePageHeader :title="customer.name" subtitle="Détail du client">
          <template #actions>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'CustomerEdit', params: { id: customer.id } }">
              <span class="text-current"><PencilIcon class="w-4 h-4" /></span>Modifier
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Customers' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>Retour
            </BaseButton>
          </template>
        </BasePageHeader>

        <BaseCard title="Informations" padding="lg">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-text-tertiary">Société</span><p class="font-medium text-text-primary">{{ customer.company || '—' }}</p></div>
            <div><span class="text-text-tertiary">Nom</span><p class="font-medium text-text-primary">{{ customer.name }}</p></div>
            <div><span class="text-text-tertiary">Email</span><p class="text-text-primary">{{ customer.email || '—' }}</p></div>
            <div><span class="text-text-tertiary">Téléphone</span><p class="text-text-primary">{{ customer.phone || '—' }}</p></div>
            <div><span class="text-text-tertiary">Adresse</span><p class="text-text-primary">{{ customer.address || '—' }}</p></div>
            <div><span class="text-text-tertiary">Ville</span><p class="text-text-primary">{{ customer.city || '—' }}</p></div>
            <div><span class="text-text-tertiary">Code postal</span><p class="text-text-primary">{{ customer.postal_code || '—' }}</p></div>
            <div><span class="text-text-tertiary">Pays</span><p class="text-text-primary">{{ customer.country || '—' }}</p></div>
          </div>
        </BaseCard>

        <!-- Tabs -->
        <div class="border-b border-border">
          <button @click="tab = 'invoices'" :class="['px-4 py-3 text-sm font-medium border-b-2 transition-colors', tab === 'invoices' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-text-tertiary hover:text-text-primary']">Factures</button>
          <button @click="tab = 'quotes'" :class="['px-4 py-3 text-sm font-medium border-b-2 transition-colors', tab === 'quotes' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-text-tertiary hover:text-text-primary']">Devis</button>
        </div>

        <div v-if="tabLoading" class="flex justify-center py-8">
          <div class="w-6 h-6 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
        </div>

        <div v-else-if="tab === 'invoices'">
          <div v-if="invoices.length === 0" class="py-8 text-center text-text-tertiary">Aucune facture</div>
          <div v-else class="bg-surface border border-border rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-surface-secondary">
                <tr>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Numéro</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Date</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Total</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-text-tertiary uppercase">Statut</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-surface-secondary">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ inv.number }}</td>
                  <td class="px-6 py-4 text-sm text-text-tertiary">{{ inv.issue_date }}</td>
                  <td class="px-6 py-4 text-sm text-right font-medium text-text-primary">{{ formatXOF(inv.total) }}</td>
                  <td class="px-6 py-4 text-center">
                    <BaseBadge :variant="invoiceBadgeVariant(inv.status)" size="sm">{{ invoiceStatusLabel(inv.status) }}</BaseBadge>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <BaseButton variant="ghost" size="sm" :to="{ name: 'InvoiceShow', params: { id: inv.id } }">Voir</BaseButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-else-if="tab === 'quotes'">
          <div v-if="quotes.length === 0" class="py-8 text-center text-text-tertiary">Aucun devis</div>
          <div v-else class="bg-surface border border-border rounded-xl overflow-hidden">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-surface-secondary">
                <tr>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Numéro</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Date</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Total</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-center text-text-tertiary uppercase">Statut</th>
                  <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="q in quotes" :key="q.id" class="hover:bg-surface-secondary">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ q.number }}</td>
                  <td class="px-6 py-4 text-sm text-text-tertiary">{{ q.issue_date }}</td>
                  <td class="px-6 py-4 text-sm text-right font-medium text-text-primary">{{ formatXOF(q.total) }}</td>
                  <td class="px-6 py-4 text-center">
                    <BaseBadge :variant="quoteBadgeVariant(q.status)" size="sm">{{ quoteStatusLabel(q.status) }}</BaseBadge>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <BaseButton variant="ghost" size="sm" :to="{ name: 'QuoteShow', params: { id: q.id } }">Voir</BaseButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { PencilIcon, ArrowLeftIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const customer = ref(null);
const loading = ref(true);
const error = ref('');
const tab = ref('invoices');
const invoices = ref([]);
const quotes = ref([]);
const tabLoading = ref(false);

const invoiceStatusMap = {
  draft: { variant: 'default', label: 'Brouillon' },
  sent: { variant: 'info', label: 'Envoyée' },
  paid: { variant: 'success', label: 'Payée' },
  cancelled: { variant: 'danger', label: 'Annulée' },
  overdue: { variant: 'warning', label: 'En retard' },
};

const quoteStatusMap = {
  draft: { variant: 'default', label: 'Brouillon' },
  sent: { variant: 'info', label: 'Envoyé' },
  accepted: { variant: 'success', label: 'Accepté' },
  rejected: { variant: 'danger', label: 'Rejeté' },
  converted: { variant: 'purple', label: 'Converti' },
};

function invoiceBadgeVariant(s) { return invoiceStatusMap[s]?.variant || 'default'; }
function invoiceStatusLabel(s) { return invoiceStatusMap[s]?.label || s; }
function quoteBadgeVariant(s) { return quoteStatusMap[s]?.variant || 'default'; }
function quoteStatusLabel(s) { return quoteStatusMap[s]?.label || s; }

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function fetchCustomer() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/customers/${route.params.id}`);
    customer.value = data.data ?? data;
    await Promise.all([fetchInvoices(), fetchQuotes()]);
  } catch {
    error.value = t('page.customers.load_error');
  } finally {
    loading.value = false;
  }
}

async function fetchInvoices() {
  tabLoading.value = true;
  try {
    const { data } = await axios.get('/invoices', { params: { customer_id: route.params.id, limit: 50 } });
    invoices.value = data.data ?? data;
  } catch {} finally { tabLoading.value = false; }
}

async function fetchQuotes() {
  tabLoading.value = true;
  try {
    const { data } = await axios.get('/quotes', { params: { customer_id: route.params.id, limit: 50 } });
    quotes.value = data.data ?? data;
  } catch {} finally { tabLoading.value = false; }
}

onMounted(fetchCustomer);
</script>
