<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="full" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.products.edit') : $t('page.products.create') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" :list="nameSuggestions" />
            <div>
              <BaseInput v-model="form.sku" :label="$t('form.sku')" required disabled :error="errors.sku?.[0] || ''" />
              <p class="mt-1 text-xs text-text-tertiary">{{ $t('form.sku_auto') }}</p>
            </div>
            <div class="flex gap-2 items-end">
              <div class="flex-1">
                <BaseInput v-model="form.barcode" :label="$t('form.barcode')" :error="errors.barcode?.[0] || ''" />
              </div>
              <BaseButton variant="secondary" size="md" @click="form.barcode = generateBarcode()" class="mb-0.5">{{ $t('common.generate') }}</BaseButton>
            </div>
            <BaseSelect v-model="form.category_id" :label="$t('form.category')"
              :options="[{ value: '', label: $t('form.select_category') }, ...categories.map(c => ({ value: c.id, label: c.name }))]"
              :error="errors.category_id?.[0] || ''" />
            <BaseTextarea v-model="form.description" :label="$t('form.description')" :rows="3" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <BaseInput v-model.number="form.price_xof" type="number" :label="$t('form.sale_price_xof')" required min="0" step="1" :error="errors.price_xof?.[0] || errors.price?.[0] || ''" />
              <BaseInput v-model.number="form.purchase_price_xof" type="number" :label="$t('form.purchase_price_xof')" min="0" step="1" :error="errors.purchase_price_xof?.[0] || ''" />
              <BaseInput v-model.number="form.cost_price_xof" type="number" :label="$t('form.cost_price_xof')" min="0" step="1" />
              <BaseInput v-model.number="form.wholesale_price_xof" type="number" :label="$t('form.wholesale_price_xof')" min="0" step="1" />
              <BaseInput v-model.number="form.quantity" type="number" :label="$t('form.stock_quantity')" min="0" step="1" />
              <BaseInput v-model.number="form.min_stock" type="number" :label="$t('form.min_stock')" min="0" step="1" />
            </div>
            <div class="space-y-1.5">
              <label class="pro-form-label">{{ $t('form.product_image') }}</label>
              <input type="file" @change="onFileChange" accept="image/jpeg,image/png,image/jpg,image/gif" class="pro-form-file" />
              <img v-if="previewUrl" :src="previewUrl" alt="" class="mt-2 w-28 h-28 object-cover rounded-xl border border-border/70" />
              <p v-if="errors.image?.[0]" class="text-xs font-medium text-red-500">{{ errors.image[0] }}</p>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500/30" />
              <span class="text-sm text-text-primary">{{ $t('form.active_product') }}</span>
            </label>
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
import { ref, reactive, computed, watch, onMounted, inject } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { useSuggestions } from '../../composables/useSuggestions';

const {
  saveValue: saveProductName,
  list: nameSuggestions,
} = useSuggestions('product_name');

const { t: $t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Products' });
}
const selectedFile = ref(null);
const previewUrl = ref(null);
const categories = ref([]);

const form = reactive({
  name: '', sku: '', barcode: '', category_id: '', description: '',
  price_xof: 0, purchase_price_xof: 0, cost_price_xof: 0, wholesale_price_xof: 0,
  quantity: 0, min_stock: 0, is_active: true,
});

const autoSku = ref(!isEdit.value);

function generateSku(name) {
  const initials = (name || 'PDT')
    .split(/\s+/)
    .filter(Boolean)
    .map((w) => w.charAt(0))
    .join('')
    .slice(0, 4)
    .toUpperCase();
  const now = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  const date = `${String(now.getFullYear()).slice(-2)}${pad(now.getMonth() + 1)}${pad(now.getDate())}`;
  const suffix = Math.random().toString(36).slice(2, 6).toUpperCase();
  return `${initials}-${date}-${suffix}`;
}

watch(
  () => form.name,
  () => {
    if (autoSku.value) form.sku = generateSku(form.name);
  }
);

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
  if (!isEdit.value) {
    form.sku = generateSku(form.name);
  }
  if (isEdit.value) {
    autoSku.value = false;
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
    const num = (v) => (v === '' || v === null || v === undefined ? null : Number(v));
    const data = {
      name: form.name, sku: form.sku, barcode: form.barcode || '',
      category_id: form.category_id || null, description: form.description || '',
      price_xof: num(form.price_xof), purchase_price_xof: num(form.purchase_price_xof),
      cost_price_xof: num(form.cost_price_xof), wholesale_price_xof: num(form.wholesale_price_xof),
      quantity: num(form.quantity), min_stock: num(form.min_stock), is_active: form.is_active === true,
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
    if (val === null || val === undefined || val === '') continue;
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


