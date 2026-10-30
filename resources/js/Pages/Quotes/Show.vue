<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>
      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="quote">
        <BasePageHeader :title="$t('page.quotes.title_show', { number: quote.number })" :subtitle="$t('page.quotes.detail')">
          <template #actions>
            <BaseButton variant="secondary" size="sm" @click="downloadPdf">
              <ArrowDownTrayIcon class="w-4 h-4" />{{ $t('common.download_pdf') }}
            </BaseButton>
            <BaseButton v-if="quote.status !== 'converted' && can('convert_quote')" size="sm" :loading="converting" @click="convertToInvoice">
              <DocumentTextIcon class="w-4 h-4" />{{ converting ? $t('page.quotes.converting') : $t('page.quotes.convert_to_invoice') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :loading="sendingEmail" @click="sendEmail">
              <EnvelopeIcon class="w-4 h-4" />{{ sendingEmail ? $t('common.sending') : $t('common.send_email') }}
            </BaseButton>
            <BaseButton v-if="quote.status === 'draft' && can('update_quote')" size="sm" @click="updateStatus('sent')">
              <CheckIcon class="w-4 h-4" />{{ $t('page.quotes.mark_sent') }}
            </BaseButton>
            <BaseButton v-if="(quote.status === 'sent' || quote.status === 'accepted') && can('validate_quote')" variant="primary" size="sm" @click="updateStatus('accepted')">
              <CheckIcon class="w-4 h-4" />{{ $t('page.quotes.accept') }}
            </BaseButton>
            <BaseButton v-if="(quote.status === 'draft' || quote.status === 'sent') && can('update_quote')" variant="danger" size="sm" @click="updateStatus('rejected')">
              <XCircleIcon class="w-4 h-4" />{{ $t('page.quotes.reject') }}
            </BaseButton>
            <BaseButton v-if="quote.status !== 'converted' && can('delete_quote')" variant="danger-ghost" size="sm" @click="deleteQuote">
              <TrashIcon class="w-4 h-4" />{{ $t('common.delete') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'Quotes' }">
              <ArrowLeftIcon class="w-4 h-4" />{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <div class="pro-card p-6 sm:p-8 space-y-8">
          <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-border/80">
            <div>
              <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black tracking-tight text-text-primary">{{ quote.number }}</h2>
                <span :class="['inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full border shadow-2xs', statusClass(quote.status)]">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(quote.status)"></span>
                  {{ statusLabel(quote.status) }}
                </span>
              </div>
              <div class="flex flex-wrap items-center gap-4 mt-2 text-xs font-semibold text-text-tertiary">
                <span>{{ $t('common.date') }} : <strong class="text-text-primary font-bold">{{ quote.issue_date }}</strong></span>
                <span>•</span>
                <span>{{ $t('page.quotes.expires') }} : <strong class="text-text-primary font-bold">{{ quote.expiration_date }}</strong></span>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5 rounded-2xl bg-surface-secondary/50 border border-border/60">
            <div>
              <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('page.quotes.customer') }}</p>
              <p class="mt-1.5 text-base font-black text-text-primary">{{ quote.customer?.name }}</p>
              <p v-if="quote.customer?.email" class="text-xs font-medium text-text-tertiary mt-0.5">{{ quote.customer.email }}</p>
            </div>
            <div class="flex flex-col md:items-end justify-center">
              <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('common.total') }}</p>
              <p class="text-3xl font-black font-mono tracking-tight text-emerald-600 dark:text-emerald-400 mt-1 tabular-nums">{{ formatXOF(quote.total_xof) }}</p>
            </div>
          </div>

          <div class="pro-table-wrap">
            <div class="overflow-x-auto">
              <table class="w-full min-w-[540px]">
                <thead>
                  <tr class="pro-table-head">
                    <th class="px-5 py-3 text-left">{{ $t('common.description') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('invoice.qty') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('invoice.unit_price') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('common.total') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                  <tr v-for="item in quote.items" :key="item.id" class="hover:bg-emerald-500/[0.03] transition-colors">
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
                <span class="text-text-tertiary">{{ $t('common.subtotal') }}</span>
                <span class="font-mono text-text-primary tabular-nums">{{ formatXOF(quote.subtotal_xof) }}</span>
              </div>
              <div v-if="quote.tax_xof" class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('common.tax') }}</span>
                <span class="font-mono text-text-primary tabular-nums">{{ formatXOF(quote.tax_xof) }}</span>
              </div>
              <div v-if="quote.discount_xof" class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('common.discount') }}</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400 tabular-nums">-{{ formatXOF(quote.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-black border-t border-border/80 pt-3">
                <span class="text-text-primary">{{ $t('common.total') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">{{ formatXOF(quote.total_xof) }}</span>
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
import { toastApiError } from '../../utils/api';
import { usePermissions } from '../../composables/usePermissions';

const { can } = usePermissions();
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
  draft: { class: 'bg-surface-tertiary text-text-secondary border-border', dot: 'bg-text-tertiary', label: 'status.draft' },
  sent: { class: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20', dot: 'bg-blue-500', label: 'status.sent' },
  accepted: { class: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', dot: 'bg-emerald-500', label: 'status.accepted' },
  rejected: { class: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20', dot: 'bg-red-500', label: 'status.rejected' },
  converted: { class: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20', dot: 'bg-purple-500', label: 'status.converted' },
};

function statusClass(s) { return statusMap[s]?.class || 'bg-surface-tertiary text-text-secondary border-border'; }
function statusDotClass(s) { return statusMap[s]?.dot || 'bg-text-tertiary'; }
function statusLabel(s) { return statusMap[s]?.label ? t(statusMap[s].label) : s; }

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
  } catch (err) { toastApiError(err, showToast, t, 'page.quotes.convert_error'); } finally { converting.value = false; }
}

async function deleteQuote() {
  if (!window.confirm(t('page.quotes.delete_confirm'))) return;
  try {
    await axios.delete(`/quotes/${route.params.id}`);
    showToast(t('page.quotes.deleted_success'), 'success');
    router.push({ name: 'Quotes' });
  } catch (err) { toastApiError(err, showToast, t, 'page.quotes.delete_error'); }
}

onMounted(fetchQuote);
</script>
