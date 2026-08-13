<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.delivery_notes.edit') : $t('page.delivery_notes.new')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'DeliveryNotes' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseSelect v-model="form.customer_id" :label="$t('page.delivery_notes.customer')" required
              :options="customers.map(c => ({ value: c.id, label: c.name }))"
              :placeholder="$t('form.select_customer')" :error="errors.customer_id?.[0] || ''" />
            <BaseInput v-model="form.issue_date" type="date" :label="$t('page.delivery_notes.issue_date')" required :error="errors.issue_date?.[0] || ''" />
          </div>
          <div class="mt-4">
            <BaseSelect v-model="form.invoice_id" :label="$t('page.delivery_notes.linked_invoice')"
              :options="[{ value: '', label: $t('page.delivery_notes.none') }, ...invoices.map(i => ({ value: i.id, label: `${i.number} - ${i.customer?.name}` }))]"
              :error="errors.invoice_id?.[0] || ''" />
          </div>
          <div class="mt-4">
            <BaseInput v-model="form.notes" :label="$t('common.notes')" :error="errors.notes?.[0] || ''" />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-2">
            <div v-for="(line, index) in form.items" :key="index" class="flex flex-wrap gap-2 items-start">
              <select v-model="line.product_id" @change="selectProduct(index)"
                class="w-40 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20">
                <option :value="null">{{ $t('form.select') }}</option>
                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                <option value="other">{{ $t('common.other') }}</option>
              </select>
              <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')"
                class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-else v-model="line.description" :placeholder="$t('form.description')"
                class="flex-1 min-w-[100px] bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
              <input v-model.number="line.quantity" type="number" min="1" :placeholder="$t('form.quantity')"
                class="w-20 bg-surface border border-border rounded-lg px-2.5 py-2 text-sm text-text-primary focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
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

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'DeliveryNotes' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('page.delivery_notes.create') }}</BaseButton>
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

const router = useRouter();
const route = useRoute();
const submitting = ref(false);
const customers = ref([]);
const products = ref([]);
const invoices = ref([]);
const errors = reactive({});
const isEdit = computed(() => Boolean(route.params.id));

const form = reactive({
  customer_id: '', issue_date: new Date().toISOString().slice(0, 10),
  invoice_id: '', notes: '',
  items: [{ product_id: null, description: '', quantity: 1, custom_name: '' }],
});

function addLine() { form.items.push({ product_id: null, description: '', quantity: 1, custom_name: '' }); }

function selectProduct(index) {
  const line = form.items[index];
  if (!line.product_id) return;
  if (line.product_id === 'other') {
    line.description = '';
    line.custom_name = '';
    return;
  }
  const prod = products.value.find(p => p.id === line.product_id);
  if (prod) line.description = prod.name;
}

function removeLine(index) { if (form.items.length > 1) form.items.splice(index, 1); }

onMounted(async () => {
  try {
    const [cRes, pRes, iRes] = await Promise.all([
      axios.get('/customers?limit=1000'), axios.get('/products?limit=1000'), axios.get('/invoices?limit=1000'),
    ]);
    customers.value = cRes.data.data ?? cRes.data;
    products.value = pRes.data.data ?? pRes.data;
    invoices.value = iRes.data.data ?? iRes.data;
  } catch {}

  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/delivery-notes/${route.params.id}`);
      const note = data.data ?? data;
      form.customer_id = note.customer_id ?? note.customer?.id ?? '';
      form.issue_date = (note.issue_date || '').slice(0, 10);
      form.invoice_id = note.invoice_id ?? '';
      form.notes = note.notes || '';
      form.items = (note.items ?? []).map(i => ({
        product_id: i.product_id ?? 'other',
        description: i.description || '',
        quantity: i.quantity,
        custom_name: '',
      }));
      if (form.items.length === 0) {
        form.items.push({ product_id: null, description: '', quantity: 1, custom_name: '' });
      }
    } catch {
      showToast($t('page.delivery_notes.load_error'), 'error');
    }
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const payload = {
      customer_id: form.customer_id, issue_date: form.issue_date,
      invoice_id: form.invoice_id || null, notes: form.notes || '',
      items: form.items.filter(i => i.product_id).map(i => ({
        product_id: i.product_id === 'other' ? null : i.product_id,
        description: i.product_id === 'other' ? i.custom_name : i.description,
        quantity: i.quantity,
      })),
    };
    if (isEdit.value) {
      await axios.patch(`/delivery-notes/${route.params.id}`, payload);
      showToast(t('page.delivery_notes.updated'), 'success');
      router.push({ name: 'DeliveryNoteShow', params: { id: route.params.id } });
    } else {
      await axios.post('/delivery-notes', payload);
      showToast(t('page.delivery_notes.created'), 'success');
      router.push({ name: 'DeliveryNotes' });
    }
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      showToast(t('common.validation_error'), 'error');
    } else {
      showToast(t('common.save_error'), 'error');
    }
  } finally { submitting.value = false; }
}
</script>
