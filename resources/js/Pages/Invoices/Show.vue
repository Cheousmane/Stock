<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="invoice">
        <BasePageHeader :title="$t('page.invoices.title_show', { number: invoice.number })" :subtitle="$t('page.invoices.show')">
          <template #actions>
            <BaseButton variant="secondary" size="sm" @click="downloadPdf">
              <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>{{ $t('page.invoices.pdf') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :loading="sendingEmail" @click="sendEmail">
              <span class="text-current"><EnvelopeIcon class="w-4 h-4" /></span>{{ sendingEmail ? $t('common.sending') : $t('common.send_email') }}
            </BaseButton>
            <BaseButton v-if="invoice.status === 'draft'" size="sm" @click="updateStatus('sent')">
              <span class="text-white"><CheckIcon class="w-4 h-4" /></span>{{ $t('page.invoices.mark_sent') }}
            </BaseButton>
            <BaseButton v-if="invoice.status === 'sent' || invoice.status === 'overdue'" variant="secondary" size="sm" :to="{ name: 'Payments' }">
              <span class="text-current"><CreditCardIcon class="w-4 h-4" /></span>{{ $t('page.invoices.record_payment') }}
            </BaseButton>
            <BaseButton v-if="invoice.status !== 'cancelled' && invoice.status !== 'paid'" variant="danger-ghost" size="sm" @click="updateStatus('cancelled')">
              <span class="text-current"><XCircleIcon class="w-4 h-4" /></span>{{ $t('page.invoices.mark_cancelled') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Invoices' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <div class="bg-surface border border-border rounded-xl p-8">
          <div class="flex justify-between pb-8 mb-8 border-b border-border">
            <div>
              <h2 class="text-xl font-bold text-text-primary">{{ invoice.number }}</h2>
              <p class="mt-1 text-sm text-text-tertiary">{{ $t('invoice.issue_date') }} : {{ invoice.issue_date }}</p>
              <p class="text-sm text-text-tertiary">{{ $t('invoice.due_date') }} : {{ invoice.due_date }}</p>
            </div>
            <div>
              <span :class="['px-3 py-1 text-sm font-medium rounded-full', statusClass(invoice.status)]">{{ $t(statusLabel(invoice.status)) }}</span>
            </div>
          </div>

          <div class="mb-8">
            <h3 class="text-sm font-medium text-text-tertiary">{{ $t('page.invoices.customer') }}</h3>
            <p class="mt-1 font-medium text-text-primary">{{ invoice.customer?.name }}</p>
            <p v-if="invoice.customer?.email" class="text-sm text-text-tertiary">{{ invoice.customer.email }}</p>
            <p v-if="invoice.customer?.address" class="text-sm text-text-tertiary">{{ invoice.customer.address }}</p>
            <p v-if="invoice.customer?.city" class="text-sm text-text-tertiary">{{ invoice.customer.city }}</p>
          </div>

          <table class="w-full mb-8">
            <thead>
              <tr class="border-b border-border">
                <th class="pb-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">{{ $t('invoice.description') }}</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('invoice.qty') }}</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('invoice.unit_price') }}</th>
                <th class="pb-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">{{ $t('invoice.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in invoice.items" :key="item.id" class="border-b border-border">
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
                <span class="text-text-tertiary">{{ $t('invoice.subtotal') }}</span>
                <span class="text-text-primary">{{ formatXOF(invoice.subtotal_xof) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('invoice.tax') }}</span>
                <span class="text-text-primary">{{ formatXOF(invoice.tax_xof) }}</span>
              </div>
              <div v-if="invoice.discount_xof" class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('invoice.discount') }}</span>
                <span class="text-text-primary">{{ formatXOF(invoice.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">{{ $t('invoice.total') }}</span>
                <span class="text-text-primary">{{ formatXOF(invoice.total_xof) }}</span>
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
import { ArrowLeftIcon, ArrowDownTrayIcon, EnvelopeIcon, CheckIcon, CreditCardIcon, XCircleIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');
const invoice = ref(null);
const loading = ref(true);
const error = ref('');
const sendingEmail = ref(false);

const statusMap = {
  draft: { class: 'bg-surface-tertiary text-text-secondary', label: 'status.draft' },
  sent: { class: 'bg-blue-50 text-blue-700', label: 'status.sent' },
  partial: { class: 'bg-yellow-50 text-yellow-700', label: 'status.partial' },
  paid: { class: 'bg-green-50 text-green-700', label: 'status.paid' },
  cancelled: { class: 'bg-red-50 text-red-700', label: 'status.cancelled' },
  overdue: { class: 'bg-amber-50 text-amber-700', label: 'status.overdue' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-surface-tertiary text-text-secondary'; }
function statusLabel(s) { return statusMap[s]?.label || s; }

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function downloadPdf() {
  try {
    const response = await axios.get(`/invoices/${route.params.id}/pdf`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Facture_${invoice.value.number}.pdf`);
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
    await axios.post(`/invoices/${route.params.id}/send`);
    showToast(t('common.email_sent'), 'success');
  } catch {
    showToast(t('common.email_error'), 'error');
  } finally {
    sendingEmail.value = false;
  }
}

async function fetchInvoice() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/invoices/${route.params.id}`);
    invoice.value = data.data ?? data;
  } catch {
    error.value = t('page.invoices.load_error');
  } finally {
    loading.value = false;
  }
}

async function updateStatus(status) {
  try {
    if (status === 'sent') {
      await axios.patch(`/invoices/${route.params.id}/mark-as-sent`);
    } else if (status === 'cancelled') {
      await axios.patch(`/invoices/${route.params.id}/mark-as-cancelled`);
    }
    await fetchInvoice();
    showToast(t('common.status_updated'), 'success');
  } catch { showToast(t('common.update_error'), 'error'); }
}

onMounted(fetchInvoice);
</script>
