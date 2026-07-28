<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <div v-else-if="error" class="py-12 text-center text-text-tertiary">{{ error }}</div>

      <template v-else-if="note">
        <BasePageHeader :title="'Bon de livraison ' + note.number" subtitle="Détail du bon de livraison">
          <template #actions>
            <BaseButton variant="secondary" size="sm" @click="downloadPdf">
              <span class="text-current"><ArrowDownTrayIcon class="w-4 h-4" /></span>Télécharger PDF
            </BaseButton>
            <BaseButton v-if="note.status === 'pending'" size="sm" @click="markAs('shipped')">
              <span class="text-white"><TruckIcon class="w-4 h-4" /></span>Marquer expédié
            </BaseButton>
            <button v-if="note.status === 'shipped'" @click="markAs('delivered')"
              class="inline-flex items-center justify-center gap-2 font-medium transition-all duration-150 rounded-lg px-2.5 py-1.5 text-sm bg-green-600 text-white hover:bg-green-700">
              <span class="text-white"><CheckIcon class="w-4 h-4" /></span>Marquer livré
            </button>
            <button v-if="note.status === 'delivered'" @click="markAs('returned')"
              class="inline-flex items-center justify-center gap-2 font-medium transition-all duration-150 rounded-lg px-2.5 py-1.5 text-sm bg-red-600 text-white hover:bg-red-700">
              <span class="text-white"><ArrowUturnLeftIcon class="w-4 h-4" /></span>Marquer retourné
            </button>
            <BaseBadge :variant="badgeVariant" size="md">{{ statusLabel }}</BaseBadge>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'DeliveryNotes' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>Retour
            </BaseButton>
          </template>
        </BasePageHeader>

        <BaseCard title="Informations" padding="lg">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-text-tertiary">Date d'émission :</span><p class="font-medium text-text-primary">{{ note.issue_date || note.date }}</p></div>
            <div><span class="text-text-tertiary">Date de livraison :</span><p class="text-text-primary">{{ note.delivery_date || '—' }}</p></div>
          </div>
        </BaseCard>

        <BaseCard title="Client" padding="lg">
          <p class="font-medium text-text-primary">{{ note.customer?.name }}</p>
          <p v-if="note.customer?.email" class="text-sm text-text-tertiary">{{ note.customer.email }}</p>
          <p v-if="note.customer?.phone" class="text-sm text-text-tertiary">{{ note.customer.phone }}</p>
          <p v-if="note.customer?.address" class="text-sm text-text-tertiary">{{ note.customer.address }}{{ note.customer?.city ? ', ' + note.customer.city : '' }}</p>
        </BaseCard>

        <BaseCard v-if="note.invoice" title="Facture liée" padding="lg">
          <BaseButton variant="ghost" size="sm" :to="{ name: 'InvoiceShow', params: { id: note.invoice.id } }">
            <span class="text-current"><DocumentTextIcon class="w-4 h-4" /></span>
            {{ note.invoice.number }} — {{ formatXOF(note.invoice.total_xof) }}
          </BaseButton>
        </BaseCard>

        <BaseCard title="Articles" padding="lg">
          <div v-if="!note.items || note.items.length === 0" class="text-sm text-text-tertiary">Aucun article</div>
          <table v-else class="min-w-full divide-y divide-border">
            <thead class="bg-surface-secondary">
              <tr>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-text-tertiary uppercase">Description</th>
                <th class="px-4 py-3 text-xs font-medium tracking-wider text-right text-text-tertiary uppercase">Quantité</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="item in note.items" :key="item.id" class="hover:bg-surface-secondary">
                <td class="px-4 py-3 text-sm text-text-primary">{{ item.description || item.product?.name }}</td>
                <td class="px-4 py-3 text-sm text-right">{{ item.quantity }}</td>
              </tr>
            </tbody>
          </table>
        </BaseCard>

        <BaseCard v-if="note.signature" title="Signature" padding="lg">
          <img :src="note.signature" alt="Signature" class="max-w-xs border border-border rounded-lg" />
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
import { ArrowLeftIcon, ArrowDownTrayIcon, TruckIcon, CheckIcon, ArrowUturnLeftIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
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
  const map = { pending: 'En attente', shipped: 'Expédié', delivered: 'Livré', returned: 'Retourné' };
  return map[note.value?.status] || note.value?.status;
});

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

async function markAs(status) {
  try {
    await axios.patch(`/delivery-notes/${route.params.id}/mark-as-${status}`);
    showToast(t('common.status_updated'), 'success');
    await fetchNote();
  } catch {
    showToast(t('common.update_error'), 'error');
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
    showToast(t('common.download_error'), 'error');
  }
}

async function fetchNote() {
  loading.value = true;
  try {
    const { data } = await axios.get(`/delivery-notes/${route.params.id}`);
    note.value = data.data ?? data;
  } catch {
    error.value = t('page.delivery_notes.load_error');
  } finally {
    loading.value = false;
  }
}

onMounted(fetchNote);
</script>
