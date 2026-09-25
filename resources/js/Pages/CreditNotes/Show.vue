<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <div v-if="loading" class="space-y-4">
        <div v-for="n in 4" :key="n" class="h-16 bg-surface-tertiary rounded-xl animate-pulse"></div>
      </div>

      <template v-else-if="cn">
        <BasePageHeader :title="$t('page.credit_notes.title') + ' ' + cn.number" :subtitle="$t('page.credit_notes.detail')">
          <template #actions>
            <BaseButton variant="secondary" size="sm" :to="{ name: 'CreditNotes' }">
              <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <BaseCard padding="lg">
          <div class="flex justify-between items-start mb-6">
            <div>
              <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.number_label') }}</p>
              <p class="mt-1 text-sm font-semibold text-text-primary">{{ cn.number }}</p>
            </div>
            <div class="text-right">
              <BaseBadge :variant="badgeVariant" size="sm">{{ statusLabel(cn.status) }}</BaseBadge>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4 pb-6 mb-6 border-b border-border">
            <div>
              <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('common.date') }}</p>
              <p class="mt-1 text-sm text-text-primary">{{ cn.issue_date }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.customer') }}</p>
              <p class="mt-1 text-sm text-text-primary">{{ cn.customer?.name || '—' }}</p>
            </div>
            <div>
              <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.linked_invoice') }}</p>
              <p class="mt-1 text-sm text-text-primary">{{ cn.invoice?.number || '—' }}</p>
            </div>
            <div v-if="cn.metadata?.restock_warehouse_id">
              <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.restock_title') }}</p>
              <p class="mt-1 text-sm text-text-primary">
                {{ cn.metadata.restock_warehouse_name || cn.metadata.restock_warehouse_id }}
                <span class="text-xs text-text-tertiary">({{ cn.metadata.restocked_lines || 0 }} {{ $t('page.credit_notes.lines_unit') }})</span>
              </p>
              <p v-if="cn.metadata.restocked_at" class="text-xs text-text-tertiary">{{ $t('page.credit_notes.on_date', { date: cn.metadata.restocked_at }) }}</p>
            </div>
          </div>

          <div class="mb-6">
            <h3 class="text-sm font-medium text-text-secondary mb-3">{{ $t('page.credit_notes.lines_word') }}</h3>
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-border">
                  <th class="py-2 text-left font-medium text-text-tertiary">{{ $t('page.products.title') }}</th>
                  <th class="py-2 text-right font-medium text-text-tertiary">{{ $t('invoice.qty') }}</th>
                  <th class="py-2 text-right font-medium text-text-tertiary">{{ $t('invoice.unit_price') }}</th>
                  <th class="py-2 text-right font-medium text-text-tertiary">{{ $t('common.total') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in cn.items" :key="item.id" class="border-b border-border">
                  <td class="py-2 text-text-primary">{{ item.name || item.product?.name }}</td>
                  <td class="py-2 text-right text-text-secondary">{{ item.quantity }}</td>
                  <td class="py-2 text-right text-text-secondary">{{ formatXOF(item.unit_price_xof) }}</td>
                  <td class="py-2 text-right font-medium text-text-primary">{{ formatXOF(item.total_xof ?? item.subtotal_xof) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-end pt-4 border-t border-border">
            <div class="w-64 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('common.subtotal') }}</span>
                <span class="font-medium text-text-primary">{{ formatXOF(cn.subtotal_xof) }}</span>
              </div>
              <div v-if="cn.discount_xof" class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('common.discount') }}</span>
                <span class="font-medium text-text-primary">-{{ formatXOF(cn.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">{{ $t('common.total') }}</span>
                <span class="text-text-primary">{{ formatXOF(cn.total_xof) }}</span>
              </div>
            </div>
          </div>

          <div v-if="cn.notes" class="pt-4 mt-4 border-t border-border">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('common.notes') }}</p>
            <p class="mt-1 text-sm text-text-secondary">{{ cn.notes }}</p>
          </div>
        </BaseCard>

        <div class="flex flex-wrap items-end gap-3">
          <div v-if="cn.status === 'draft'" class="space-y-1.5" style="flex: 1; min-width: 240px;">
            <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('page.credit_notes.restock_warehouse') }}</label>
            <select v-model="warehouseId"
              class="w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
              <option value="">{{ $t('page.credit_notes.restock_none') }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>
          <BaseButton v-if="cn.status === 'draft'" @click="validateCreditNote">
            <span class="text-white"><CheckIcon class="w-4 h-4" /></span>{{ $t('page.credit_notes.validate') }}
          </BaseButton>
          <BaseButton variant="secondary" :to="{ name: 'CreditNotes' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.back_to_list') }}
          </BaseButton>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, inject, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');

const cn = ref(null);
const loading = ref(true);
const warehouses = ref([]);
const warehouseId = ref('');

const badgeVariant = computed(() => {
  const map = { draft: 'default', validated: 'info', refunded: 'success' };
  return map[cn.value?.status] || 'default';
});

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount || 0);
}

function statusLabel(status) {
  const map = { draft: t('status.draft'), validated: t('status.validated'), refunded: t('status.refunded') };
  return map[status] || status;
}

async function validateCreditNote() {
  try {
    const payload = { warehouse_id: warehouseId.value || null };
    const res = await axios.post(`/credit-notes/${cn.value.id}/validate`, payload);
    cn.value = res.data.credit_note;
    showToast(res.data.message || t('page.credit_notes.validated_success'), 'success');
  } catch (e) {
    showToast(e.response?.data?.message || t('common.error'), 'error');
  }
}

onMounted(async () => {
  try {
    const [{ data }, wRes] = await Promise.all([
      axios.get(`/credit-notes/${route.params.id}`),
      axios.get('/warehouses', { params: { per_page: 1000 } }),
    ]);
    cn.value = data.data ?? data;
    warehouses.value = wRes.data.data ?? wRes.data;
  } catch {
    showToast(t('common.load_error'), 'error');
    router.push({ name: 'CreditNotes' });
  } finally {
    loading.value = false;
  }
});
</script>
