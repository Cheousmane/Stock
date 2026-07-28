<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.credit_notes.edit') : $t('page.credit_notes.new')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'CreditNotes' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput v-model="form.number" :label="$t('page.credit_notes.number')" required :error="errors.number?.[0] || ''" />
            <BaseInput v-model="form.issue_date" type="date" :label="$t('page.credit_notes.date')" required :error="errors.issue_date?.[0] || ''" />
            <BaseSelect v-model="form.customer_id" :label="$t('page.credit_notes.customer')" required
              :options="customers.map(c => ({ value: c.id, label: c.name }))"
              :placeholder="$t('form.select_customer')" :error="errors.customer_id?.[0] || ''" />
            <BaseSelect v-model="form.invoice_id" :label="$t('page.credit_notes.linked_invoice')"
              :options="[{ value: '', label: $t('form.none') }, ...invoices.map(i => ({ value: i.id, label: `${i.number} — ${formatXOF(i.total_xof)}` }))]"
              :error="errors.invoice_id?.[0] || ''" />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-2">
            <div v-for="(line, index) in form.items" :key="index" class="flex flex-wrap gap-2 items-start">
              <select v-model="line.product_id" @change="selectProduct(index)"
                class="w-40 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                <option value="">{{ $t('form.select') }}</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                <option value="other">{{ $t('common.other') }}</option>
              </select>
              <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')"
                class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-model.number="line.quantity" type="number" min="1" @input="calcLine(index)" :placeholder="$t('form.quantity')"
                class="w-20 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-model.number="line.unit_price_xof" type="number" min="0" step="1" @input="calcLine(index)" :placeholder="$t('form.unit_price')"
                class="w-28 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <span class="py-2 text-sm text-text-secondary w-28 text-right">{{ formatXOF(line.total) }}</span>
              <div class="flex items-center gap-1 py-1">
                <button type="button" @click="addLine" class="p-1.5 text-text-tertiary hover:text-emerald-600 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors" :title="$t('common.add_line')">
                  <PlusIcon class="w-4 h-4" />
                </button>
                <button type="button" @click="removeLine(index)" class="p-1.5 text-text-tertiary hover:text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" :title="$t('common.remove_line')">
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="flex justify-end">
            <div class="w-64 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('invoice.subtotal') }}</span>
                <span class="font-medium text-text-primary">{{ formatXOF(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm items-center gap-2">
                <span class="text-text-tertiary">{{ $t('invoice.discount') }}</span>
                <input v-model.number="form.discount_xof" type="number" min="0" step="1" class="w-20 bg-surface border border-border rounded px-2 py-1 text-sm text-text-primary text-right" />
                <span class="font-medium text-text-primary w-24 text-right">{{ formatXOF(form.discount_xof || 0) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">{{ $t('invoice.total') }}</span>
                <span class="text-text-primary">{{ formatXOF(total) }}</span>
              </div>
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-1.5">
            <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.notes') }}</label>
            <textarea v-model="form.notes" rows="3"
              class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'CreditNotes' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ isEdit ? $t('common.update') : $t('page.credit_notes.create') }}</BaseButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');
const isEdit = computed(() => !!route.params.id);

const customers = ref([]);
const invoices = ref([]);
const products = ref([]);
const submitting = ref(false);
const errors = reactive({});

const form = reactive({
  number: '',
  customer_id: '', invoice_id: '',
  issue_date: new Date().toISOString().slice(0, 10),
  discount_xof: 0, notes: '', items: [],
});

const subtotal = computed(() => form.items.reduce((sum, line) => sum + (line.total || 0), 0));
const total = computed(() => Math.max(0, subtotal.value - (form.discount_xof || 0)));

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount || 0);
}

function addLine() {
  form.items.push({ product_id: '', quantity: 1, unit_price_xof: 0, total: 0, custom_name: '' });
}

function removeLine(index) {
  form.items.splice(index, 1);
}

function selectProduct(index) {
  const line = form.items[index];
  if (!line.product_id) return;
  if (line.product_id === 'other') {
    line.custom_name = '';
    line.unit_price_xof = 0;
    calcLine(index);
    return;
  }
  const product = products.value.find(p => p.id === line.product_id);
  if (product) {
    line.unit_price_xof = product.selling_price ?? 0;
    calcLine(index);
  }
}

function calcLine(index) {
  const line = form.items[index];
  line.total = (line.quantity || 0) * (line.unit_price_xof || 0);
}

onMounted(async () => {
  try {
    const [cRes, iRes, pRes] = await Promise.all([
      axios.get('/customers', { params: { per_page: 1000 } }),
      axios.get('/invoices', { params: { per_page: 1000 } }),
      axios.get('/products', { params: { per_page: 1000 } }),
    ]);
    customers.value = cRes.data.data ?? cRes.data;
    invoices.value = iRes.data.data ?? iRes.data;
    products.value = pRes.data.data ?? pRes.data;
  } catch {
    showToast(t('common.load_error'), 'error');
  }

  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/credit-notes/${route.params.id}`);
      const cn = data.data ?? data;
      form.number = cn.number ?? 'CN-' + Date.now();
      form.customer_id = cn.customer_id ?? cn.customer?.id ?? '';
      form.invoice_id = cn.invoice_id ?? cn.invoice?.id ?? '';
      form.issue_date = cn.issue_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10);
      form.discount_xof = cn.discount_xof ?? 0;
      form.notes = cn.notes ?? '';
      if (cn.items?.length) {
        form.items = cn.items.map(i => ({
          product_id: i.product_id ?? '',
          quantity: i.quantity ?? 1,
          unit_price_xof: i.unit_price_xof ?? 0,
          total: (i.quantity ?? 0) * (i.unit_price_xof ?? 0),
          custom_name: '',
        }));
      }
    } catch {
      showToast(t('common.load_error'), 'error');
    }
  } else if (!form.number) {
    form.number = 'CN-' + Date.now();
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const payload = {
      number: form.number, customer_id: form.customer_id,
      invoice_id: form.invoice_id || undefined, issue_date: form.issue_date,
      discount_xof: form.discount_xof || 0, notes: form.notes || undefined,
      items: form.items.map(line => ({
        product_id: line.product_id === 'other' ? null : line.product_id,
        description: line.product_id === 'other' ? line.custom_name : '',
        quantity: line.quantity, unit_price_xof: line.unit_price_xof,
      })),
    };
    if (isEdit.value) {
      await axios.put(`/credit-notes/${route.params.id}`, payload);
      showToast(t('page.credit_notes.updated_success'), 'success');
    } else {
      await axios.post('/credit-notes', payload);
      showToast(t('page.credit_notes.created_success'), 'success');
    }
    router.push({ name: 'CreditNotes' });
  } catch (e) {
    if (e.response?.status === 422) {
      Object.assign(errors, e.response.data.errors || {});
      showToast(e.response.data?.message || t('common.validation_error'), 'error');
    } else {
      showToast(e.response?.data?.message || t('common.error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>
