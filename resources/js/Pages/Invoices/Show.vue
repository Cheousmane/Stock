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
              <ArrowDownTrayIcon class="w-4 h-4" />{{ $t('page.invoices.pdf') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :loading="sendingEmail" @click="sendEmail">
              <EnvelopeIcon class="w-4 h-4" />{{ sendingEmail ? $t('common.sending') : $t('common.send_email') }}
            </BaseButton>
            <BaseButton v-if="invoice.status === 'draft'" size="sm" @click="updateStatus('sent')">
              <CheckIcon class="w-4 h-4" />{{ $t('page.invoices.mark_sent') }}
            </BaseButton>
            <BaseButton v-if="invoice.status === 'sent' || invoice.status === 'overdue'" variant="secondary" size="sm" :to="{ name: 'Payments' }">
              <CreditCardIcon class="w-4 h-4" />{{ $t('page.invoices.record_payment') }}
            </BaseButton>
            <BaseButton v-if="invoice.status !== 'cancelled' && invoice.status !== 'paid'" variant="danger-ghost" size="sm" @click="updateStatus('cancelled')">
              <XCircleIcon class="w-4 h-4" />{{ $t('page.invoices.mark_cancelled') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Invoices' }">
              <ArrowLeftIcon class="w-4 h-4" />{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <div class="pro-card p-6 sm:p-8 space-y-8">
          <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-border/80">
            <div>
              <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black tracking-tight text-text-primary">{{ invoice.number }}</h2>
                <span :class="['inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border shadow-2xs', statusClass(invoice.status)]">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(invoice.status)"></span>
                  {{ $t(statusLabel(invoice.status)) }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-4 mt-2 text-xs font-semibold text-text-tertiary">
                <span>{{ $t('invoice.issue_date') }} : <strong class="text-text-primary font-bold">{{ invoice.issue_date }}</strong></span>
                <span>•</span>
                <span>{{ $t('invoice.due_date') }} : <strong class="text-text-primary font-bold">{{ invoice.due_date }}</strong></span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5 rounded-2xl bg-surface-secondary/50 border border-border/60">
            <div>
              <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('page.invoices.customer') }}</p>
              <p class="mt-1.5 text-base font-black text-text-primary">{{ invoice.customer?.name }}</p>
              <p v-if="invoice.customer?.email" class="text-xs font-medium text-text-tertiary mt-0.5">{{ invoice.customer.email }}</p>
              <p v-if="invoice.customer?.phone" class="text-xs font-medium text-text-tertiary">{{ invoice.customer.phone }}</p>
              <p v-if="invoice.customer?.address" class="text-xs font-medium text-text-tertiary">{{ invoice.customer.address }}{{ invoice.customer?.city ? ', ' + invoice.customer.city : '' }}</p>
            </div>
            <div class="flex flex-col md:items-end justify-center">
              <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('invoice.total') }}</p>
              <p class="text-3xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 mt-1 tabular-nums">{{ formatXOF(invoice.total_xof) }}</p>
            </div>
          </div>

          <div class="pro-table-wrap">
            <div class="overflow-x-auto">
              <table class="w-full min-w-[540px]">
                <thead>
                  <tr class="pro-table-head">
                    <th class="px-5 py-3 text-left">{{ $t('invoice.description') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('invoice.qty') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('invoice.unit_price') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('invoice.total') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                  <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-emerald-500/[0.03] transition-colors">
                    <td class="px-5 py-3.5 text-sm font-semibold text-text-primary">{{ item.description || item.product?.name }}</td>
                    <td class="px-5 py-3.5 text-sm font-semibold text-right text-text-secondary tabular-nums">{{ item.quantity }}</td>
                    <td class="px-5 py-3.5 text-sm font-semibold text-right text-text-secondary tabular-nums">{{ formatXOF(item.unit_price_xof) }}</td>
                    <td class="px-5 py-3.5 text-sm font-black text-right text-text-primary tabular-nums">{{ formatXOF(item.total_xof) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <div class="w-full max-w-xs space-y-2.5 sm:w-72 p-5 rounded-2xl bg-surface-secondary/40 border border-border/70">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('invoice.subtotal') }}</span>
                <span class="text-text-primary font-mono tabular-nums">{{ formatXOF(invoice.subtotal_xof) }}</span>
              </div>
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('invoice.tax') }}</span>
                <span class="text-text-primary font-mono tabular-nums">{{ formatXOF(invoice.tax_xof) }}</span>
              </div>
              <div v-if="invoice.discount_xof" class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('invoice.discount') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">-{{ formatXOF(invoice.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-black border-t border-border/80 pt-3">
                <span class="text-text-primary">{{ $t('invoice.total') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">{{ formatXOF(invoice.total_xof) }}</span>
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
  draft: { class: 'bg-surface-tertiary text-text-secondary border-border', dot: 'bg-text-tertiary', label: 'status.draft' },
  sent: { class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', dot: 'bg-blue-500', label: 'status.sent' },
  partial: { class: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', dot: 'bg-amber-500', label: 'status.partial' },
  paid: { class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', dot: 'bg-emerald-500', label: 'status.paid' },
  cancelled: { class: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20', dot: 'bg-red-500', label: 'status.cancelled' },
  overdue: { class: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', dot: 'bg-rose-500', label: 'status.overdue' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-surface-tertiary text-text-secondary border-border'; }
function statusDotClass(s) { return statusMap[s]?.dot || 'bg-text-tertiary'; }
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
