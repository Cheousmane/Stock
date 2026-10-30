<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="full" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.credit_notes.edit') : $t('page.credit_notes.new') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput :model-value="displayedNumber" :label="$t('page.credit_notes.number')" disabled :error="errors.number?.[0] || ''">
              <template #suffix>
                <span class="text-xs text-text-tertiary">{{ $t('page.credit_notes.auto') }}</span>
              </template>
            </BaseInput>
            <BaseSelect v-model="form.invoice_id" :label="$t('page.credit_notes.invoice')" required
              :options="invoices.map(i => ({ value: i.id, label: `${i.number} - ${i.customer_name}` }))"
              :placeholder="$t('form.select')" :error="errors.invoice_id?.[0] || ''" />
            <BaseInput v-model="form.date" type="date" :label="$t('form.date')" required :error="errors.date?.[0] || ''" />
            <BaseInput v-model="form.reason" :label="$t('page.credit_notes.reason')" :error="errors.reason?.[0] || ''" />
          </div>
          <div class="mt-4">
            <BaseTextarea v-model="form.description" :label="$t('form.description')" :rows="2" />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-3">
            <div class="pro-line-head">
              <span>{{ $t('page.stock.product') }}</span>
              <span>{{ $t('form.description') }}</span>
              <span class="text-right">{{ $t('form.quantity') }}</span>
              <span class="text-right">{{ $t('form.unit_price') }}</span>
              <span class="text-right">{{ $t('invoice.total') }}</span>
              <span />
            </div>
            <div v-for="(line, index) in form.items" :key="index" class="p-3 rounded-2xl bg-surface-secondary/40 border border-border/60 space-y-2">
              <div class="pro-line-grid">
                <div class="min-w-0 space-y-1">
                  <select v-model="line.product_id" @change="selectProduct(index)" :aria-label="$t('page.stock.product')" class="pro-form-control font-semibold">
                    <option value="">{{ $t('form.select') }}</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                    <option value="other">{{ $t('common.other') }}</option>
                  </select>
                </div>
                <div class="min-w-0">
                  <BaseInput v-model="line.description" :placeholder="$t('form.description')" />
                </div>
                <BaseInput v-model.number="line.quantity" type="number" min="1" step="1" />
                <BaseInput v-model.number="line.unit_price" type="number" min="0" step="1" />
                <div class="flex items-center justify-end text-sm font-extrabold text-text-primary tabular-nums">
                  {{ formatNumber(line.quantity * line.unit_price) }}
                </div>
                <button type="button" @click="removeLine(index)" class="pro-icon-btn text-rose-500 hover:bg-rose-500/10" :aria-label="$t('common.delete')">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
            <button type="button" @click="addLine" class="inline-flex items-center gap-1.5 text-xs font-extrabold text-primary-600 hover:text-primary-700">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
              {{ $t('common.add_line') }}
            </button>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="flex items-center justify-between">
            <span class="text-sm font-bold text-text-tertiary">{{ $t('invoice.total') }}</span>
            <span class="text-xl font-black text-text-primary tabular-nums">{{ formatNumber(total) }}</span>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" @click="onClose">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const form = reactive({
  invoice_id: '', date: new Date().toISOString().slice(0, 10), reason: '', description: '',
  items: [{ product_id: '', description: '', quantity: 1, unit_price: 0 }],
});
const invoices = ref([]);
const products = ref([]);
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'CreditNotes' });
}

const displayedNumber = computed(() => isEdit.value ? (form.number || '') : 'CN-' + Date.now().toString().slice(-6));

const total = computed(() => form.items.reduce((s, l) => s + (l.quantity || 0) * (l.unit_price || 0), 0));

function formatNumber(n) { return new Intl.NumberFormat('fr-FR').format(n || 0); }

function addLine() { form.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0 }); }
function removeLine(i) { if (form.items.length > 1) form.items.splice(i, 1); }

function selectProduct(index) {
  const line = form.items[index];
  const p = products.value.find(x => x.id === line.product_id);
  if (p) { line.description = p.name; line.unit_price = p.price || 0; }
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/invoices');
    invoices.value = data.data ?? data;
  } catch {}
  try {
    const { data } = await axios.get('/products');
    products.value = data.data ?? data;
  } catch {}
  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/credit-notes/${route.params.id}`);
      const item = data.data ?? data;
      form.invoice_id = item.invoice_id ?? '';
      form.date = item.date ?? '';
      form.reason = item.reason ?? '';
      form.description = item.description ?? '';
      if (item.items?.length) form.items = item.items;
    } catch {
      showToast($t('page.credit_notes.load_error'), 'error');
      router.push({ name: 'CreditNotes' });
    }
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const payload = { ...form, items: form.items };
    if (isEdit.value) {
      await axios.put(`/credit-notes/${route.params.id}`, payload);
    } else {
      await axios.post('/credit-notes', payload);
    }
    showToast(isEdit.value ? $t('page.credit_notes.updated') : $t('page.credit_notes.created'), 'success');
    router.push({ name: 'CreditNotes' });
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      const first = Object.values(errors).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast($t('common.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>

