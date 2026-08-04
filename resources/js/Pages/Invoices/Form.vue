<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.invoices.edit') : $t('page.invoices.new')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Invoices' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseSelect v-model="form.customer_id" :label="$t('page.invoices.customer')" required
              :options="customers.map(c => ({ value: c.id, label: `${c.name} (${c.email})` }))"
              :placeholder="$t('form.select_customer')" :error="errors.customer_id?.[0] || ''" />
            <div></div>
            <BaseInput v-model="form.issue_date" type="date" :label="$t('invoice.issue_date')" required :error="errors.issue_date?.[0] || ''" />
            <BaseInput v-model="form.due_date" type="date" :label="$t('invoice.due_date')" required :error="errors.due_date?.[0] || ''" />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-2">
            <div v-for="(line, index) in form.items" :key="index">
              <div class="flex flex-wrap gap-2 items-start">
                <select v-model="line.product_id" @change="selectProduct(index)"
                  class="w-40 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                  <option value="">{{ $t('form.select') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  <option value="other">{{ $t('common.other') }}</option>
                </select>
                <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')"
                  class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                <input v-else v-model="line.description" type="text" :placeholder="$t('form.description')"
                  class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                <input v-model.number="line.quantity" type="number" min="1" @input="calcLine(index)" :placeholder="$t('form.quantity')"
                  class="w-20 bg-surface border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:ring-2"
                  :class="stockError(line) ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-border focus:border-primary-500 focus:ring-primary-500/20'" />
                <input v-model.number="line.unit_price" type="number" min="0" step="1" @input="calcLine(index)" :placeholder="$t('form.unit_price')"
                  class="w-28 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                <span class="py-2 text-sm text-text-secondary w-28 text-right">{{ formatXOF(line.total) }}</span>
                <span v-if="line.product_id && line.product_id !== 'other'" class="py-2 text-xs text-text-tertiary">{{ $t('page.invoices.stock_available', { count: stockFor(line) }) }}</span>
                <div class="flex items-center gap-1 py-1">
                  <button type="button" @click="addLine" class="p-1.5 text-text-tertiary hover:text-emerald-600 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-500/10 transition-colors" :title="$t('common.add_line')">
                    <PlusIcon class="w-4 h-4" />
                  </button>
                  <button type="button" @click="removeLine(index)" class="p-1.5 text-text-tertiary hover:text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" :title="$t('common.remove_line')">
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <p v-if="stockError(line)" class="mt-1 text-xs font-medium text-red-500">
                {{ $t('page.invoices.stock_insufficient') }} ({{ $t('page.invoices.stock_available', { count: stockFor(line) }) }})
              </p>
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
                <span class="text-text-tertiary">{{ $t('invoice.tax') }}</span>
                <input v-model.number="form.tax_rate" type="number" min="0" step="0.01" class="w-20 bg-surface border border-border rounded px-2 py-1 text-sm text-text-primary text-right" />
                <span class="font-medium text-text-primary w-24 text-right">{{ formatXOF(tax) }}</span>
              </div>
              <div class="flex justify-between text-sm items-center gap-2">
                <span class="text-text-tertiary">{{ $t('invoice.discount') }}</span>
                <input v-model.number="form.discount" type="number" min="0" step="1" class="w-20 bg-surface border border-border rounded px-2 py-1 text-sm text-text-primary text-right" />
                <span class="font-medium text-text-primary w-24 text-right">{{ formatXOF(form.discount || 0) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">{{ $t('invoice.total') }}</span>
                <span class="text-text-primary">{{ formatXOF(total) }}</span>
              </div>
            </div>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Invoices' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ isEdit ? $t('common.save') : $t('page.invoices.create') }}</BaseButton>
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
const showToast = inject('showToast');

const route = useRoute();
const router = useRouter();
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const customers = ref([]);
const products = ref([]);
const errors = reactive({});

const form = reactive({
  customer_id: '', issue_date: new Date().toISOString().slice(0, 10), due_date: '',
  tax_rate: 0, discount: 0,
  items: [{ product_id: '', description: '', quantity: 1, unit_price: 0, total: 0, custom_name: '' }],
});

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(amount || 0);
}

const subtotal = computed(() => form.items.reduce((s, l) => s + (l.total || 0), 0));
const tax = computed(() => subtotal.value * (form.tax_rate || 0) / 100);
const total = computed(() => subtotal.value + tax.value - (form.discount || 0));

function calcLine(index) {
  const line = form.items[index];
  line.total = (line.quantity || 0) * (line.unit_price || 0);
}

function selectProduct(index) {
  const line = form.items[index];
  if (!line.product_id) return;
  if (line.product_id === 'other') {
    line.description = '';
    line.custom_name = '';
    line.unit_price = 0;
    calcLine(index);
    return;
  }
  const prod = products.value.find(p => p.id === line.product_id);
  if (prod) {
    line.description = prod.name;
    line.unit_price = prod.price_xof;
    calcLine(index);
  }
}

function addLine() {
  form.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0, total: 0, custom_name: '' });
}

function removeLine(index) {
  if (form.items.length > 1) form.items.splice(index, 1);
}

function productFor(id) {
  return products.value.find(p => String(p.id) === String(id));
}

function stockFor(line) {
  if (!line.product_id || line.product_id === 'other') return Infinity;
  const prod = productFor(line.product_id);
  return prod ? (Number(prod.quantity) || 0) : Infinity;
}

function usedQty(line) {
  if (!line.product_id || line.product_id === 'other') return 0;
  return form.items.reduce((sum, l) => sum + (String(l.product_id) === String(line.product_id) ? (Number(l.quantity) || 0) : 0), 0);
}

function stockError(line) {
  if (!line.product_id || line.product_id === 'other') return false;
  const available = stockFor(line);
  const needed = usedQty(line);
  return needed > 0 && needed > available;
}

onMounted(async () => {
  try {
    const [cRes, pRes] = await Promise.all([axios.get('/customers?limit=1000'), axios.get('/products?limit=1000')]);
    customers.value = cRes.data.data ?? cRes.data;
    products.value = pRes.data.data ?? pRes.data;
  } catch {}
  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/invoices/${route.params.id}`);
      const inv = data.data ?? data;
      form.customer_id = inv.customer_id ?? inv.customer?.id ?? '';
      form.issue_date = inv.issue_date?.slice(0, 10) ?? '';
      form.due_date = inv.due_date?.slice(0, 10) ?? '';
      form.tax_rate = inv.tax_rate ?? 0;
      form.discount = inv.discount ?? inv.discount_xof ?? 0;
      if (inv.items?.length) {
        form.items = inv.items.map(i => ({
          product_id: i.product_id ?? '',
          description: i.description ?? '',
          quantity: i.quantity ?? 1,
          unit_price: i.unit_price ?? i.unit_price_xof ?? 0,
          total: (i.quantity ?? 0) * (i.unit_price ?? i.unit_price_xof ?? 0),
          custom_name: '',
        }));
      }
    } catch {
      showToast(t('page.invoices.load_error'), 'error');
    }
  }
});

function validate() {
  Object.assign(errors, {});
  let valid = true;
  if (!form.customer_id) { errors.customer_id = [t('form.required')]; valid = false; }
  if (!form.issue_date) { errors.issue_date = [t('form.required')]; valid = false; }
  if (!form.due_date) { errors.due_date = [t('form.required')]; valid = false; }
  form.items.forEach((line, i) => {
    if (!line.quantity || line.quantity < 1) {
      errors[`items.${i}.quantity`] = [t('form.required')]; valid = false;
    }
    if (line.unit_price === undefined || line.unit_price === null || line.unit_price < 0) {
      errors[`items.${i}.unit_price_xof`] = [t('form.required')]; valid = false;
    }
    if (stockError(line)) {
      errors[`items.${i}.quantity`] = [t('page.invoices.stock_insufficient')]; valid = false;
    }
  });
  return valid;
}

async function submit() {
  if (!validate()) {
    showToast(t('common.validation_error'), 'error');
    return;
  }
  submitting.value = true;
  try {
    const payload = {
      customer_id: form.customer_id, issue_date: form.issue_date, due_date: form.due_date,
      discount_xof: form.discount || 0, discount_type: 'fixed',
      items: form.items.map(i => ({
        product_id: i.product_id === 'other' ? null : i.product_id,
        description: i.product_id === 'other' ? i.custom_name : i.description,
        quantity: i.quantity, unit_price_xof: i.unit_price,
      })),
    };
    if (isEdit.value) {
      await axios.put(`/invoices/${route.params.id}`, payload);
    } else {
      await axios.post('/invoices', payload);
    }
    showToast(isEdit.value ? t('page.invoices.updated') : t('page.invoices.created'), 'success');
    router.push({ name: 'Invoices' });
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      const first = Object.values(err.response.data.errors || {}).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast(t('common.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>
