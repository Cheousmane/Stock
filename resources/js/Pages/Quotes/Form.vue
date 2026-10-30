<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="full" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ $t('page.quotes.new') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseSelect v-model="form.customer_id" :label="$t('page.quotes.customer')" required class="sm:col-span-2"
              :options="customers.map(c => ({ value: c.id, label: c.name }))"
              :placeholder="$t('form.select_customer')" :error="errors.customer_id?.[0] || ''" />
            <BaseInput v-model="form.issue_date" type="date" :label="$t('quote.issue_date')" required :error="errors.issue_date?.[0] || ''" />
            <BaseInput v-model="form.expiration_date" type="date" :label="$t('quote.expiration_date')" required :error="errors.expiration_date?.[0] || ''" />
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
            <div v-for="(line, index) in form.items" :key="index" class="p-3 rounded-2xl bg-surface-secondary/40 border border-border/60">
              <div class="pro-line-grid">
                <select v-model="line.product_id" @change="selectProduct(index)" :aria-label="$t('page.stock.product')" class="pro-form-control font-semibold">
                  <option value="">{{ $t('form.select') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  <option value="other">{{ $t('common.other') }}</option>
                </select>
                <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')" :aria-label="$t('form.name')" class="pro-form-control" />
                <input v-else v-model="line.description" :placeholder="$t('form.description')" :aria-label="$t('form.description')" class="pro-form-control" />
                <input v-model.number="line.quantity" type="number" min="1" @input="calcLine(index)" :placeholder="$t('form.quantity')" :aria-label="$t('form.quantity')" class="pro-form-control text-right font-mono font-semibold" />
                <input v-model.number="line.unit_price" type="number" min="0" step="1" @input="calcLine(index)" :placeholder="$t('form.unit_price')" :aria-label="$t('form.unit_price')" class="pro-form-control text-right font-mono font-semibold" />
                <span class="px-1 text-sm font-mono font-black text-text-primary text-left md:text-right tabular-nums">{{ formatXOF(line.total) }}</span>
                <div class="flex items-center gap-1 md:justify-end">
                  <button type="button" @click="addLine" class="pro-icon-btn" :title="$t('common.add_line')">
                    <PlusIcon class="w-4 h-4" />
                  </button>
                  <button type="button" @click="removeLine(index)" class="pro-icon-btn hover:!text-red-600 hover:!bg-red-500/10" :title="$t('common.remove_line')">
                    <XMarkIcon class="w-4 h-4" />
                  </button>
                </div>
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
          <BaseButton variant="ghost" @click="onClose">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('page.quotes.create') }}</BaseButton>
        </div>
      </form>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));

const router = useRouter();
const submitting = ref(false);
const customers = ref([]);
const products = ref([]);
const errors = reactive({});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Quotes' });
}

const form = reactive({
  customer_id: '', issue_date: new Date().toISOString().slice(0, 10), expiration_date: '',
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

function addLine() { form.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0, total: 0, custom_name: '' }); }
function removeLine(index) { if (form.items.length > 1) form.items.splice(index, 1); }

onMounted(async () => {
  try {
    const [cRes, pRes] = await Promise.all([axios.get('/customers?limit=1000'), axios.get('/products?limit=1000')]);
    customers.value = cRes.data.data ?? cRes.data;
    products.value = pRes.data.data ?? pRes.data;
  } catch { showToast(t('common.load_error'), 'error'); }
});

function validate() {
  Object.assign(errors, {});
  let valid = true;
  if (!form.customer_id) { errors.customer_id = [t('form.required')]; valid = false; }
  if (!form.issue_date) { errors.issue_date = [t('form.required')]; valid = false; }
  if (!form.expiration_date) { errors.expiration_date = [t('form.required')]; valid = false; }
  form.items.forEach((line, i) => {
    if (!line.quantity || line.quantity < 1) {
      errors[`items.${i}.quantity`] = [t('form.required')]; valid = false;
    }
    if (line.unit_price === undefined || line.unit_price === null || line.unit_price < 0) {
      errors[`items.${i}.unit_price_xof`] = [t('form.required')]; valid = false;
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
      customer_id: form.customer_id, issue_date: form.issue_date, expiration_date: form.expiration_date,
      discount_xof: form.discount || 0, discount_type: 'fixed',
      items: form.items.map(i => ({
        product_id: i.product_id === 'other' ? null : i.product_id,
        description: i.product_id === 'other' ? i.custom_name : i.description,
        quantity: i.quantity, unit_price_xof: i.unit_price,
      })),
    };
    await axios.post('/quotes', payload);
    showToast(t('page.quotes.created'), 'success');
    router.push({ name: 'Quotes' });
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



