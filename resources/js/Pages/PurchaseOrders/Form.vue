<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <BasePageHeader :title="$t('page.purchase_orders.new_order')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'PurchaseOrders' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseSelect v-model="form.supplier_id" :label="$t('page.purchase_orders.supplier')" required
              :options="suppliers.map(s => ({ value: s.id, label: s.name }))"
              :placeholder="$t('page.purchase_orders.select_supplier')" :error="errors.supplier_id?.[0] || ''" />
            <BaseSelect v-model="form.warehouse_id" :label="$t('page.purchase_orders.warehouse')"
              :options="[{ value: '', label: $t('page.purchase_orders.none') }, ...warehouses.map(w => ({ value: w.id, label: w.name }))]"
              :error="errors.warehouse_id?.[0] || ''" />
            <BaseInput v-model="form.issue_date" type="date" :label="$t('invoice.issue_date')" required :error="errors.issue_date?.[0] || ''" />
            <BaseInput v-model="form.expected_delivery_date" type="date" :label="$t('page.purchase_orders.expected_delivery')" required :error="errors.expected_delivery_date?.[0] || ''" />
          </div>
        </BaseCard>

        <BaseCard>
          <template #actions>
            <BaseButton variant="secondary" size="xs" @click="addLine">
              <span class="text-current"><PlusIcon class="w-4 h-4" /></span>{{ $t('page.purchase_orders.add_line') }}
            </BaseButton>
          </template>
          <div class="space-y-2">
            <div v-for="(line, index) in form.items" :key="index" class="flex flex-wrap gap-2 items-start">
              <select v-model="line.product_id" @change="selectProduct(index)"
                class="w-40 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                <option value="">{{ $t('page.purchase_orders.select_product') }}</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                <option value="other">{{ $t('common.other') }}</option>
              </select>
              <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')" required
                class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-else v-model="line.description" type="text" :placeholder="$t('form.item_description')" required
                class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-model.number="line.quantity" type="number" min="1" @input="calcLine(index)" :placeholder="$t('form.quantity')" required
                class="w-20 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-model.number="line.unit_price" type="number" min="0" step="1" @input="calcLine(index)" :placeholder="$t('form.unit_price')" required
                class="w-28 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <span class="py-2 text-sm text-text-secondary w-28 text-right">{{ formatXOF(line.total) }}</span>
              <button type="button" @click="removeLine(index)" class="py-2 text-red-500 hover:text-red-700">
                <span class="text-red-500"><XMarkIcon class="w-4 h-4" /></span>
              </button>
            </div>
          </div>
        </BaseCard>

        <BaseCard>
          <div class="flex justify-between items-start">
            <div class="flex-1 mr-8">
              <div class="space-y-1.5">
                <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.notes') }}</label>
                <textarea v-model="form.notes" rows="3"
                  class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              </div>
            </div>
            <div class="w-64 space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('invoice.subtotal') }}</span>
                <span class="font-medium text-text-primary">{{ formatXOF(subtotal) }}</span>
              </div>
              <div class="flex justify-between text-sm items-center gap-2">
                <span class="text-text-tertiary">{{ $t('invoice.tax_rate') }}</span>
                <input v-model.number="form.tax_rate" type="number" min="0" step="0.01" class="w-20 bg-surface border border-border rounded px-2 py-1 text-sm text-text-primary text-right" />
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-tertiary">{{ $t('invoice.tax') }}</span>
                <span class="font-medium text-text-primary">{{ formatXOF(tax) }}</span>
              </div>
              <div class="flex justify-between text-sm items-center gap-2">
                <span class="text-text-tertiary">{{ $t('invoice.discount') }}</span>
                <input v-model.number="form.discount" type="number" min="0" step="1" class="w-20 bg-surface border border-border rounded px-2 py-1 text-sm text-text-primary text-right" />
              </div>
              <div class="flex justify-between text-base font-bold border-t border-border pt-2">
                <span class="text-text-primary">{{ $t('invoice.total') }}</span>
                <span class="text-text-primary">{{ formatXOF(total) }}</span>
              </div>
            </div>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'PurchaseOrders' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('page.purchase_orders.create_order') }}</BaseButton>
        </div>
      </form>
    </div>
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
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const router = useRouter();
const showToast = inject('showToast', (msg) => alert(msg));
const submitting = ref(false);
const errors = reactive({});

const suppliers = ref([]);
const products = ref([]);
const warehouses = ref([]);

const form = reactive({
  supplier_id: '', warehouse_id: '', issue_date: new Date().toISOString().slice(0, 10),
  expected_delivery_date: '', tax_rate: 0, discount: 0, notes: '',
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
    line.unit_price = prod.purchase_price_xof || prod.price_xof || 0;
    calcLine(index);
  }
}

function addLine() {
  form.items.push({ product_id: '', description: '', quantity: 1, unit_price: 0, total: 0, custom_name: '' });
}

function removeLine(index) {
  if (form.items.length > 1) form.items.splice(index, 1);
}

onMounted(async () => {
  const nextWeek = new Date();
  nextWeek.setDate(nextWeek.getDate() + 7);
  form.expected_delivery_date = nextWeek.toISOString().slice(0, 10);

  try {
    const [sRes, pRes, wRes] = await Promise.all([
      axios.get('/suppliers?limit=1000'), axios.get('/products?limit=1000'), axios.get('/warehouses?limit=100'),
    ]);
    suppliers.value = sRes.data.data ?? sRes.data;
    products.value = pRes.data.data ?? pRes.data;
    warehouses.value = wRes.data.data ?? wRes.data;
  } catch {
    showToast(t('page.purchase_orders.load_data_error'), 'error');
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const payload = {
      supplier_id: form.supplier_id, warehouse_id: form.warehouse_id || null,
      issue_date: form.issue_date, expected_delivery_date: form.expected_delivery_date,
      notes: form.notes, discount_xof: form.discount || 0, discount_type: 'fixed',
      tax_xof: Math.round(tax.value),
      items: form.items.map(i => ({
        product_id: i.product_id === 'other' ? null : (i.product_id || null),
        description: i.product_id === 'other' ? i.custom_name : i.description,
        quantity: i.quantity, unit_price_xof: i.unit_price,
      })),
    };
    await axios.post('/purchase-orders', payload);
    showToast(t('page.purchase_orders.created'), 'success');
    router.push({ name: 'PurchaseOrders' });
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      showToast(t('page.purchase_orders.validation_error'), 'error');
    } else {
      showToast(t('page.purchase_orders.create_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>
