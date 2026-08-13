<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="quote">
        <BasePageHeader :title="'Devis ' + quote.number" subtitle="Détail du devis">
          <template #actions>
            <BaseButton variant="secondary" size="sm" @click="downloadPdf">
              <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Télécharger PDF
            </BaseButton>
            <button v-if="quote.status !== 'converted'" @click="convertToInvoice" :disabled="converting"
              class="inline-flex items-center justify-center gap-2 font-medium rounded-lg px-2.5 py-1.5 text-sm bg-purple-600 text-white hover:bg-purple-700 disabled:opacity-50">
              <span class="text-white"><DocumentTextIcon class="w-4 h-4" /></span>{{ converting ? 'Conversion...' : 'Convertir en facture' }}
            </button>
            <BaseButton variant="secondary" size="sm" :loading="sendingEmail" @click="sendEmail">
              <span class="text-current"><EnvelopeIcon class="w-4 h-4" /></span>{{ sendingEmail ? 'Envoi...' : 'Envoyer par email' }}
            </BaseButton>
            <BaseButton v-if="quote.status === 'draft'" size="sm" @click="updateStatus('sent')">
              <span class="text-white"><CheckIcon class="w-4 h-4" /></span>Marquer envoyé
            </BaseButton>
            <button v-if="quote.status === 'sent' || quote.status === 'accepted'" @click="updateStatus('accepted')"
              class="inline-flex items-center justify-center gap-2 font-medium rounded-lg px-2.5 py-1.5 text-sm bg-green-600 text-white hover:bg-green-700">
              <span class="text-white"><CheckIcon class="w-4 h-4" /></span>Accepter
            </button>
            <button v-if="quote.status === 'draft' || quote.status === 'sent'" @click="updateStatus('rejected')"
              class="inline-flex items-center justify-center gap-2 font-medium rounded-lg px-2.5 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700">
              <span class="text-white"><XCircleIcon class="w-4 h-4" /></span>Rejeter
            </button>
            <BaseButton v-if="quote.status !== 'converted'" variant="danger-ghost" size="sm" @click="deleteQuote">
              <span class="text-current"><TrashIcon class="w-4 h-4" /></span>Supprimer
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Quotes' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>Retour
            </BaseButton>
          </template>
        </BasePageHeader>

        <div class="bg-surface border border-border rounded-xl p-8">
          <div class="flex justify-between pb-8 mb-8 border-b border-border">
            <div>
              <h2 class="text-xl font-bold text-text-primary">{{ quote.number }}</h2>
              <p class="mt-1 text-sm text-text-tertiary">Date : {{ quote.issue_date }}</p>
              <p class="text-sm text-text-tertiary">Expire le : {{ quote.expiration_date }}</p>
            </div>
            <div>
              <span :class="['px-3 py-1 text-sm font-medium rounded-full', statusClass(quote.status)]">{{ statusLabel(quote.status) }}</span>
            </div>
          </div>

          <div class="mb-8">
            <h3 class="text-sm font-medium text-text-tertiary">Client</h3>
            <p class="mt-1 font-medium text-text-primary">{{ quote.customer?.name }}</p>
            <p v-if="quote.customer?.email" class="text-sm text-text-tertiary">{{ quote.customer.email }}</p>
          </div>

          <table class="w-full mb-8">
            <thead>
              <tr class="border-b border-border">
                <th class="pb-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Description</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Qté</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Prix unitaire</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in quote.items" :key="item.id" class="border-b border-border">
                <td class="py-3 text-sm text-text-primary">{{ item.description || item.product?.name }}</td>
                <td class="py-3 text-sm text-right text-text-secondary">{{ item.quantity }}</td>
                <td class="py-3 text-sm text-right text-text-secondary">{{ formatXOF(item.unit_price_xof) }}</td>
                <td class="py-3 text-sm text-right font-medium text-text-primary">{{ formatXOF(item.total_xof) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="flex justify-end">
            <div class="w-64 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">Sous-total</span>
                <span class="text-text-primary">{{ formatXOF(quote.subtotal_xof) }}</span>
              </div>
              <div v-if="quote.tax_xof" class="flex justify-between text-sm">
                <span class="text-text-tertiary">Taxe</span>
                <span class="text-text-primary">{{ formatXOF(quote.tax_xof) }}</span>
              </div>
              <div v-if="quote.discount_xof" class="flex justify-between text-sm">
                <span class="text-text-tertiary">Remise</span>
                <span class="text-text-primary">{{ formatXOF(quote.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">Total</span>
                <span class="text-text-primary">{{ formatXOF(quote.total_xof) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon, ArrowDownTrayIcon, EnvelopeIcon, CheckIcon, XCircleIcon, DocumentTextIcon, TrashIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');
const quote = ref(null);
const loading = ref(true);
const error = ref('');
const sendingEmail = ref(false);
const converting = ref(false);

const statusMap = {
  draft: { class: 'bg-surface-tertiary text-text-secondary', label: 'Brouillon' },
  sent: { class: 'bg-blue-50 text-blue-700', label: 'Envoyé' },
  accepted: { class: 'bg-green-50 text-green-700', label: 'Accepté' },
  rejected: { class: 'bg-red-50 text-red-700', label: 'Rejeté' },
  converted: { class: 'bg-purple-50 text-purple-700', label: 'Converti' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-surface-tertiary text-text-secondary'; }
function statusLabel(s) { return statusMap[s]?.label || s; }

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function downloadPdf() {
  try {
    const response = await axios.get(`/quotes/${route.params.id}/pdf`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Devis_${quote.value.number}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch {
    showToast(t('common.download_error'), 'error');
  }
}

async function sendEmail() {
  sendingEmail.value = true;
  try {
    await axios.post(`/quotes/${route.params.id}/send`);
    showToast(t('common.email_sent'), 'success');
  } catch {
    showToast(t('common.email_error'), 'error');
  } finally {
    sendingEmail.value = false;
  }
}

async function fetchQuote() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/quotes/${route.params.id}`);
    quote.value = data.data ?? data;
  } catch { error.value = t('page.quotes.load_error'); } finally { loading.value = false; }
}

async function updateStatus(status) {
  try {
    if (status === 'sent') {
      await axios.patch(`/quotes/${route.params.id}/mark-as-sent`);
    } else if (status === 'accepted') {
      await axios.patch(`/quotes/${route.params.id}/mark-as-accepted`);
    } else if (status === 'rejected') {
      await axios.patch(`/quotes/${route.params.id}/mark-as-rejected`);
    }
    await fetchQuote();
    showToast(t('common.status_updated'), 'success');
  } catch { showToast(t('common.update_error'), 'error'); }
}

async function convertToInvoice() {
  converting.value = true;
  try {
    await axios.post(`/quotes/${route.params.id}/convert-to-invoice`);
    showToast(t('page.quotes.converted_success'), 'success');
    router.push({ name: 'Invoices' });
  } catch { showToast(t('page.quotes.convert_error'), 'error'); } finally { converting.value = false; }
}

async function deleteQuote() {
  if (!window.confirm(t('page.quotes.delete_confirm'))) return;
  try {
    await axios.delete(`/quotes/${route.params.id}`);
    showToast(t('page.quotes.deleted_success'), 'success');
    router.push({ name: 'Quotes' });
  } catch { showToast(t('page.quotes.delete_error'), 'error'); }
}

onMounted(fetchQuote);
</script>
