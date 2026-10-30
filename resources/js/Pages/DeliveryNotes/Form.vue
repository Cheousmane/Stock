<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="full" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.delivery_notes.edit') : $t('page.delivery_notes.new') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
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
            <BaseTextarea v-model="form.notes" :label="$t('common.notes')" :rows="2" :error="errors.notes?.[0] || ''" />
          </div>
        </BaseCard>

        <BaseCard>
          <div class="space-y-3">
            <div class="hidden md:grid grid-cols-[minmax(0,1.6fr)_minmax(0,2fr)_5.5rem_auto] gap-2.5 px-3.5 pb-1 text-[10px] font-extrabold tracking-[0.1em] uppercase text-text-tertiary">
              <span>{{ $t('page.stock.product') }}</span>
              <span>{{ $t('form.description') }}</span>
              <span class="text-right">{{ $t('form.quantity') }}</span>
              <span />
            </div>
            <div v-for="(line, index) in form.items" :key="index" class="p-3 rounded-2xl bg-surface-secondary/40 border border-border/60">
              <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1.6fr)_minmax(0,2fr)_5.5rem_auto] gap-2.5 md:items-center">
                <select v-model="line.product_id" @change="selectProduct(index)" :aria-label="$t('page.stock.product')" class="pro-form-control font-semibold">
                  <option :value="null">{{ $t('form.select') }}</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  <option value="other">{{ $t('common.other') }}</option>
                </select>
                <input v-if="line.product_id === 'other'" v-model="line.custom_name" type="text" :placeholder="$t('form.name')" :aria-label="$t('form.name')" class="pro-form-control" />
                <input v-else v-model="line.description" :placeholder="$t('form.description')" :aria-label="$t('form.description')" class="pro-form-control" />
                <input v-model.number="line.quantity" type="number" min="1" :placeholder="$t('form.quantity')" :aria-label="$t('form.quantity')" class="pro-form-control text-right font-mono font-semibold" />
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

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" @click="onClose">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('page.delivery_notes.create') }}</BaseButton>
        </div>
      </form>
    </BaseModal>
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
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));

const router = useRouter();
const route = useRoute();
const submitting = ref(false);
const customers = ref([]);
const products = ref([]);
const invoices = ref([]);
const errors = reactive({});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'DeliveryNotes' });
}
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



