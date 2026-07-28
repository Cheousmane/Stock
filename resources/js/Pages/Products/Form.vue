<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.products.edit') : $t('page.products.create')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Products' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" :list="nameSuggestions" />
            <BaseInput v-model="form.sku" :label="$t('form.sku')" required :error="errors.sku?.[0] || ''" />
            <div class="flex gap-2 items-end">
              <div class="flex-1">
                <BaseInput v-model="form.barcode" :label="$t('form.barcode')" :error="errors.barcode?.[0] || ''" />
              </div>
              <BaseButton variant="secondary" size="md" @click="form.barcode = generateBarcode()" class="mb-0.5">{{ $t('common.generate') }}</BaseButton>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <BaseSelect v-model="form.category_id" :label="$t('form.category')"
                :options="[{ value: '', label: $t('form.select_category') }, ...categories.map(c => ({ value: c.id, label: c.name }))]"
                :error="errors.category_id?.[0] || ''" />
            </div>
            <div class="space-y-1.5">
              <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.description') }}</label>
              <textarea v-model="form.description" rows="3"
                class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <BaseInput v-model.number="form.price_xof" type="number" :label="$t('form.sale_price_xof')" required min="0" step="1" :error="errors.price_xof?.[0] || errors.price?.[0] || ''" />
              <BaseInput v-model.number="form.purchase_price_xof" type="number" :label="$t('form.purchase_price_xof')" min="0" step="1" :error="errors.purchase_price_xof?.[0] || ''" />
              <BaseInput v-model.number="form.cost_price_xof" type="number" :label="$t('form.cost_price_xof')" min="0" step="1" />
              <BaseInput v-model.number="form.wholesale_price_xof" type="number" :label="$t('form.wholesale_price_xof')" min="0" step="1" />
              <BaseInput v-model.number="form.quantity" type="number" :label="$t('form.stock_quantity')" min="0" step="1" />
              <BaseInput v-model.number="form.min_stock" type="number" :label="$t('form.min_stock')" min="0" step="1" />
            </div>
            <div class="space-y-1.5">
              <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.product_image') }}</label>
              <input type="file" @change="onFileChange" accept="image/jpeg,image/png,image/jpg,image/gif"
                class="block w-full text-sm text-text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-surface-tertiary file:text-text-primary hover:file:bg-surface-secondary" />
              <img v-if="previewUrl" :src="previewUrl" class="mt-2 w-32 h-32 object-cover rounded-lg border border-border" />
              <p v-if="errors.image?.[0]" class="text-xs text-red-500">{{ errors.image[0] }}</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500/30" />
              <span class="text-sm text-text-primary">{{ $t('form.active_product') }}</span>
            </label>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Products' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { useSuggestions } from '../../composables/useSuggestions';

const {
  saveValue: saveProductName,
  list: nameSuggestions,
} = useSuggestions('product_name');

const { t: $t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast');
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const selectedFile = ref(null);
const previewUrl = ref(null);
const categories = ref([]);

const form = reactive({
  name: '', sku: '', barcode: '', category_id: '', description: '',
  price_xof: 0, purchase_price_xof: 0, cost_price_xof: 0, wholesale_price_xof: 0,
  quantity: 0, min_stock: 0, is_active: true,
});

function generateBarcode() {
  const prefix = '2';
  const timestamp = Date.now().toString().slice(-11);
  const random = Math.floor(Math.random() * 100).toString().padStart(2, '0');
  return prefix + timestamp + random;
}

function onFileChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  selectedFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
}

onMounted(async () => {
  try {
    const { data: catData } = await axios.get('/categories');
    categories.value = catData.data ?? catData;
  } catch {}
  if (!isEdit.value && !form.barcode) {
    form.barcode = generateBarcode();
  }
  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/products/${route.params.id}`);
      const product = data.data ?? data;
      form.name = product.name ?? '';
      form.sku = product.sku ?? '';
      form.barcode = product.barcode ?? '';
      form.category_id = product.category_id ?? '';
      form.description = product.description ?? '';
      form.price_xof = product.price_xof ?? product.price ?? 0;
      form.purchase_price_xof = product.purchase_price_xof ?? 0;
      form.cost_price_xof = product.cost_price_xof ?? 0;
      form.wholesale_price_xof = product.wholesale_price_xof ?? 0;
      form.quantity = product.quantity ?? 0;
      form.min_stock = product.min_stock ?? 0;
      form.is_active = product.is_active === true;
      if (product.image_url) {
        previewUrl.value = product.image_url;
      }
    } catch {
      showToast($t('page.products.load_error'), 'error');
      router.push({ name: 'Products' });
    }
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const data = {
      name: form.name, sku: form.sku, barcode: form.barcode || '',
      category_id: form.category_id || null, description: form.description || '',
      price_xof: form.price_xof, purchase_price_xof: form.purchase_price_xof,
      cost_price_xof: form.cost_price_xof, wholesale_price_xof: form.wholesale_price_xof,
      quantity: form.quantity, min_stock: form.min_stock, is_active: form.is_active === true,
    };
    const payload = selectedFile.value ? buildFormData(data) : data;
    if (isEdit.value) {
      await axios.post(`/products/${route.params.id}`, payload, { params: { _method: 'PUT' } });
    } else {
      await axios.post('/products', payload);
    }
    saveProductName(form.name);
    showToast(isEdit.value ? $t('page.products.update_success') : $t('page.products.create_success'), 'success');
    router.push({ name: 'Products' });
  } catch (err) {
    if (err.response?.status === 422) {
      const msgs = err.response.data.errors || {};
      Object.assign(errors, msgs);
      const first = Object.values(msgs).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast($t('page.products.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}

function buildFormData(data) {
  const fd = new FormData();
  for (const key of Object.keys(data)) {
    const val = data[key];
    if (typeof val === 'boolean') {
      fd.append(key, val ? '1' : '0');
    } else {
      fd.append(key, val);
    }
  }
  if (selectedFile.value) {
    fd.append('image', selectedFile.value);
  }
  return fd;
}
</script>
