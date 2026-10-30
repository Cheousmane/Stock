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
              <ArrowLeftIcon class="w-4 h-4" />{{ $t('common.back') }}
            </BaseButton>
          </template>
        </BasePageHeader>

        <BaseCard padding="lg">
          <div class="flex justify-between items-start pb-5 mb-6 border-b border-border/80">
            <div>
              <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.number_label') }}</p>
              <p class="mt-1 text-2xl font-black tracking-tight text-text-primary">{{ cn.number }}</p>
            </div>
            <div class="text-right">
              <BaseBadge :variant="badgeVariant" size="md">{{ statusLabel(cn.status) }}</BaseBadge>
            </div>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-2xl bg-surface-secondary/50 border border-border/60 mb-6">
            <div>
              <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ $t('common.date') }}</p>
              <p class="mt-1 text-sm font-bold text-text-primary">{{ cn.issue_date }}</p>
            </div>
            <div>
              <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.customer') }}</p>
              <p class="mt-1 text-sm font-bold text-text-primary">{{ cn.customer?.name || '—' }}</p>
            </div>
            <div>
              <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.linked_invoice') }}</p>
              <p class="mt-1 text-sm font-bold text-text-primary">{{ cn.invoice?.number || '—' }}</p>
            </div>
            <div v-if="cn.metadata?.restock_warehouse_id">
              <p class="text-[11px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ $t('page.credit_notes.restock_title') }}</p>
              <p class="mt-1 text-sm font-bold text-text-primary">
                {{ cn.metadata.restock_warehouse_name || cn.metadata.restock_warehouse_id }}
                <span class="text-xs text-text-tertiary">({{ cn.metadata.restocked_lines || 0 }} {{ $t('page.credit_notes.lines_unit') }})</span>
              </p>
              <p v-if="cn.metadata.restocked_at" class="text-xs text-text-tertiary">{{ $t('page.credit_notes.on_date', { date: cn.metadata.restocked_at }) }}</p>
            </div>
          </div>

          <div class="pro-table-wrap mb-6">
            <div class="overflow-x-auto">
            <table class="w-full min-w-[480px]">
              <thead>
                <tr class="pro-table-head">
                  <th class="px-5 py-3 text-left">{{ $t('page.products.title') }}</th>
                  <th class="px-5 py-3 text-right">{{ $t('invoice.qty') }}</th>
                  <th class="px-5 py-3 text-right">{{ $t('invoice.unit_price') }}</th>
                  <th class="px-5 py-3 text-right">{{ $t('common.total') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border/60">
                <tr v-for="item in cn.items" :key="item.id" class="hover:bg-emerald-500/[0.03] transition-colors">
                  <td class="px-5 py-3.5 text-sm font-semibold text-text-primary">{{ item.name || item.product?.name }}</td>
                  <td class="px-5 py-3.5 text-sm font-semibold text-right text-text-secondary tabular-nums">{{ item.quantity }}</td>
                  <td class="px-5 py-3.5 text-sm font-semibold text-right text-text-secondary tabular-nums">{{ formatXOF(item.unit_price_xof) }}</td>
                  <td class="px-5 py-3.5 text-sm font-black text-right text-text-primary tabular-nums">{{ formatXOF(item.total_xof ?? item.subtotal_xof) }}</td>
                </tr>
              </tbody>
            </table>
            </div>
          </div>

          <div class="flex justify-end pt-2">
            <div class="w-full max-w-xs space-y-2.5 sm:w-72 p-5 rounded-2xl bg-surface-secondary/40 border border-border/70">
              <div class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('common.subtotal') }}</span>
                <span class="font-mono text-text-primary tabular-nums">{{ formatXOF(cn.subtotal_xof) }}</span>
              </div>
              <div v-if="cn.discount_xof" class="flex justify-between text-xs font-semibold">
                <span class="text-text-tertiary">{{ $t('common.discount') }}</span>
                <span class="font-mono text-emerald-600 dark:text-emerald-400 tabular-nums">-{{ formatXOF(cn.discount_xof) }}</span>
              </div>
              <div class="flex justify-between text-base font-black border-t border-border/80 pt-3">
                <span class="text-text-primary">{{ $t('common.total') }}</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono tabular-nums">{{ formatXOF(cn.total_xof) }}</span>
              </div>
            </div>
          </div>

          <div v-if="cn.notes" class="pt-4 mt-4 border-t border-border">
            <p class="text-xs font-medium text-text-tertiary uppercase tracking-wider">{{ $t('common.notes') }}</p>
            <p class="mt-1 text-sm text-text-secondary">{{ cn.notes }}</p>
          </div>
        </BaseCard>

        <div class="flex flex-wrap items-end gap-3">
          <div v-if="cn.status === 'draft'" class="flex-1 basis-full sm:basis-64 min-w-0">
            <BaseSelect v-model="warehouseId" :label="$t('page.credit_notes.restock_warehouse')">
              <option value="">{{ $t('page.credit_notes.restock_none') }}</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </BaseSelect>
          </div>
          <BaseButton v-if="cn.status === 'draft'" @click="validateCreditNote">
            <CheckIcon class="w-4 h-4" />{{ $t('page.credit_notes.validate') }}
          </BaseButton>
          <BaseButton variant="secondary" :to="{ name: 'CreditNotes' }">
            <ArrowLeftIcon class="w-4 h-4" />{{ $t('common.back_to_list') }}
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
import BaseSelect from '../../Components/ui/BaseSelect.vue';
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
