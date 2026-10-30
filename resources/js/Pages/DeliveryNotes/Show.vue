<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="note">
        <BasePageHeader :title="$t('page.delivery_notes.title_show', { number: note.number })" :subtitle="$t('page.delivery_notes.detail')">
          <template #actions>
            <BaseButton variant="secondary" size="sm" @click="downloadPdf">
              <ArrowDownTrayIcon class="w-4 h-4" />{{ $t('common.download_pdf') }}
            </BaseButton>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'DeliveryNoteEdit', params: { id: note.id } }">
              <PencilIcon class="w-4 h-4" />{{ $t('common.edit') }}
            </BaseButton>
            <BaseButton v-if="note.status === 'pending'" size="sm" @click="markAs('shipped')">
              <TruckIcon class="w-4 h-4" />{{ $t('page.delivery_notes.mark_shipped') }}
            </BaseButton>
            <BaseButton v-if="note.status === 'shipped'" size="sm" @click="markAs('delivered')">
              <CheckIcon class="w-4 h-4" />{{ $t('page.delivery_notes.mark_delivered') }}
            </BaseButton>
            <BaseButton v-if="note.status === 'delivered'" size="sm" variant="danger" @click="markAs('returned')">
              <ArrowUturnLeftIcon class="w-4 h-4" />{{ $t('page.delivery_notes.mark_returned') }}
            </BaseButton>
            <BaseBadge :variant="badgeVariant" size="md">{{ statusLabel }}</BaseBadge>
            <BaseButton variant="ghost" size="sm" :to="{ name: 'DeliveryNotes' }">
              <ArrowLeftIcon class="w-4 h-4" />{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <BaseCard :title="$t('page.delivery_notes.information')" padding="lg">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-text-tertiary">{{ $t('page.delivery_notes.issue_date') }} :</span>
              <p class="font-medium text-text-primary">{{ formatDate(note.issue_date || note.date) }}</p>
            </div>
            <div>
              <span class="text-text-tertiary">{{ $t('page.delivery_notes.delivery_date') }} :</span>
              <p class="font-medium text-text-primary">{{ formatDate(note.delivery_date) }}</p>
            </div>
          </div>
          <div v-if="note.notes" class="mt-4 text-sm">
            <span class="text-text-tertiary">{{ $t('common.notes') }} :</span>
            <p class="mt-1 text-text-primary whitespace-pre-wrap">{{ note.notes }}</p>
          </div>
        </BaseCard>

        <BaseCard :title="$t('page.delivery_notes.customer_label')" padding="lg">
          <p class="font-medium text-text-primary">{{ note.customer?.name }}</p>
          <p v-if="note.customer?.email" class="text-sm text-text-tertiary">{{ note.customer.email }}</p>
          <p v-if="note.customer?.phone" class="text-sm text-text-tertiary">{{ note.customer.phone }}</p>
          <p v-if="note.customer?.address" class="text-sm text-text-tertiary">{{ note.customer.address }}{{ note.customer?.city ? ', ' + note.customer.city : '' }}</p>
        </BaseCard>

        <BaseCard v-if="note.invoice" :title="$t('page.delivery_notes.linked_invoice_label')" padding="lg">
          <BaseButton variant="ghost" size="sm" :to="{ name: 'InvoiceShow', params: { id: note.invoice.id } }">
            <DocumentTextIcon class="w-4 h-4" />
            {{ note.invoice.number }} — {{ formatXOF(note.invoice.total_xof) }}
          </BaseButton>
        </BaseCard>

        <BaseCard :title="$t('page.delivery_notes.items')" padding="none">
          <div v-if="!note.items || note.items.length === 0" class="p-6 text-sm text-text-tertiary">{{ $t('page.delivery_notes.no_items') }}</div>
          <div v-else class="pro-table-wrap !border-0 !rounded-none">
            <div class="overflow-x-auto">
              <table class="w-full">
                <thead>
                  <tr class="pro-table-head">
                    <th class="px-5 py-3 text-left">{{ $t('page.delivery_notes.description_col') }}</th>
                    <th class="px-5 py-3 text-right">{{ $t('page.delivery_notes.quantity') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border/60">
                  <tr v-for="item in note.items" :key="item.id" class="hover:bg-emerald-500/[0.03] transition-colors">
                    <td class="px-5 py-3.5 text-sm font-semibold text-text-primary">{{ item.description || item.product?.name }}</td>
                    <td class="px-5 py-3.5 text-sm font-semibold text-right text-text-secondary tabular-nums">{{ item.quantity }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </BaseCard>

        <BaseCard v-if="note.signature" :title="$t('page.delivery_notes.signature')" padding="lg">
          <img :src="note.signature" :alt="$t('page.delivery_notes.signature')" class="max-w-xs border border-border rounded-lg" />
        </BaseCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon, ArrowDownTrayIcon, TruckIcon, CheckIcon, ArrowUturnLeftIcon, DocumentTextIcon, PencilIcon } from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const route = useRoute();
const note = ref(null);
const loading = ref(true);
const error = ref('');

const badgeVariant = computed(() => {
  const map = { pending: 'default', shipped: 'info', delivered: 'success', returned: 'danger' };
  return map[note.value?.status] || 'default';
});

const statusLabel = computed(() => {
  const map = { pending: 'status.pending', shipped: 'status.shipped', delivered: 'status.delivered', returned: 'status.returned' };
  return $t(map[note.value?.status] || note.value?.status);
});

function formatDate(value) {
  if (!value) return '—';
  return value.slice(0, 10);
}

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function markAs(status) {
  try {
    await axios.patch(`/delivery-notes/${route.params.id}/mark-as-${status}`);
    showToast($t('common.status_updated'), 'success');
    await fetchNote();
  } catch {
    showToast($t('common.update_error'), 'error');
  }
}

async function downloadPdf() {
  try {
    const response = await axios.get(`/delivery-notes/${route.params.id}/pdf`, { responseType: 'blob' });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `BonLivraison_${note.value.number}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch {
    showToast($t('common.download_error'), 'error');
  }
}

async function fetchNote() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/delivery-notes/${route.params.id}`);
    note.value = data.data ?? data;
  } catch {
    error.value = $t('page.delivery_notes.load_error');
  } finally {
    loading.value = false;
  }
}

onMounted(fetchNote);
</script>