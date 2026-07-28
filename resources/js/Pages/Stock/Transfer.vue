<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="$t('page.transfer.title')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Stock' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <BaseSelect v-model="form.from_warehouse_id" :label="$t('page.transfer.source_warehouse')" required
                :options="warehouses.map(w => ({ value: w.id, label: w.name }))"
                :placeholder="$t('common.select')" :error="errors.from_warehouse_id || ''" />
              <BaseSelect v-model="form.to_warehouse_id" :label="$t('page.transfer.destination_warehouse')" required
                :options="warehouses.map(w => ({ value: w.id, label: w.name }))"
                :placeholder="$t('common.select')" :error="errors.to_warehouse_id || ''" />
            </div>
            <BaseSelect v-model="form.product_id" :label="$t('page.transfer.product')" required
              :options="products.map(p => ({ value: p.id, label: `${p.name} (${p.sku})` }))"
              :placeholder="$t('common.select')" :error="errors.product_id || ''" />
            <BaseInput v-model.number="form.quantity" type="number" :label="$t('common.quantity')" required min="1" :error="errors.quantity || ''" />
            <div class="space-y-1.5">
              <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('page.transfer.reason') }}</label>
              <textarea v-model="form.reason" rows="3" :placeholder="$t('page.transfer.reason_placeholder')"
                class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
            </div>
            <p v-if="submitError" class="text-sm text-red-500">{{ submitError }}</p>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Stock' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('page.transfer.submit') }}</BaseButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast');
const loading = ref(true);
const submitting = ref(false);
const warehouses = ref([]);
const products = ref([]);
const submitError = ref('');
const errors = reactive({});
const form = reactive({
  from_warehouse_id: '', to_warehouse_id: '', product_id: '', quantity: 1, reason: '',
});

onMounted(async () => {
  try {
    const [wRes, pRes] = await Promise.all([
      axios.get('/warehouses', { params: { limit: 1000 } }),
      axios.get('/products', { params: { limit: 1000 } }),
    ]);
    warehouses.value = wRes.data.data ?? wRes.data;
    products.value = pRes.data.data ?? pRes.data;
  } catch {} finally {
    loading.value = false;
  }
});

async function submit() {
  submitting.value = true;
  submitError.value = '';
  Object.keys(errors).forEach(k => delete errors[k]);
  try {
    await axios.post('/stock-transfers', form);
    showToast($t('page.transfer.success'), 'success');
    router.push({ name: 'Stock' });
  } catch (err) {
    if (err.response?.status === 422) {
      const validationErrors = err.response.data.errors;
      if (validationErrors) {
        Object.entries(validationErrors).forEach(([key, msgs]) => {
          errors[key] = msgs.join(', ');
        });
      }
    } else {
      submitError.value = $t('page.transfer.error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>
