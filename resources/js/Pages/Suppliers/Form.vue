<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="lg" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.suppliers.edit') : $t('page.suppliers.new') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput v-model="form.name" :label="$t('form.company_name')" required :error="errors.name?.[0] || ''" :list="suggestions.name" />
            <BaseInput v-model="generatedCode" :label="$t('form.code')" disabled>
              <template #suffix>
                <span class="text-xs text-text-tertiary">{{ $t('page.suppliers.code_auto') }}</span>
              </template>
            </BaseInput>
            <BaseInput v-model="form.email" type="email" :label="$t('form.email')" :error="errors.email?.[0] || ''" :list="suggestions.email" />
            <BaseInput v-model="form.phone" type="tel" :label="$t('form.phone')" :error="errors.phone?.[0] || ''" :list="suggestions.phone" />
          </div>
          <div class="mt-4">
            <BaseTextarea v-model="form.address" :label="$t('form.address')" :rows="2" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <BaseInput v-model="form.city" :label="$t('form.city')" :error="errors.city?.[0] || ''" />
            <BaseInput v-model="form.country" :label="$t('form.country')" :error="errors.country?.[0] || ''" />
            <BaseInput v-model="form.tax_number" :label="$t('page.suppliers.tax_number')" :error="errors.tax_number?.[0] || ''" />
            <BaseInput v-model="form.registration_number" :label="$t('page.suppliers.registration_number')" :error="errors.registration_number?.[0] || ''" />
          </div>
          <div class="mt-4">
            <BaseTextarea v-model="form.notes" :label="$t('form.notes')" :rows="2" />
          </div>
          <div class="mt-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500/30" />
              <span class="text-sm text-text-primary">{{ $t('page.suppliers.active_label') }}</span>
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
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { useSuggestions } from '../../composables/useSuggestions';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const showToast = inject('showToast', (msg) => alert(msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Suppliers' });
}
const $t = t;

const {
  saveValue: saveName,
  list: nameSuggestions,
} = useSuggestions('supplier_name');
const {
  saveValue: saveEmail,
  list: emailSuggestions,
} = useSuggestions('supplier_email');
const {
  saveValue: savePhone,
  list: phoneSuggestions,
} = useSuggestions('supplier_phone');
const suggestions = {
  name: nameSuggestions,
  email: emailSuggestions,
  phone: phoneSuggestions,
};

const generatedCode = computed(() => {
  if (isEdit.value && form.code) return form.code;
  return 'SUP-******';
});

const form = reactive({
  code: '', name: '', email: '', phone: '', address: '', city: '', country: '',
  tax_number: '', registration_number: '', notes: '', is_active: true,
});

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/suppliers/${route.params.id}`);
      const supplier = data.data ?? data;
      Object.keys(form).forEach(key => {
        if (supplier[key] !== undefined) form[key] = supplier[key];
      });
      form.is_active = supplier.is_active === true;
    } catch {
      showToast(t('page.suppliers.load_error'), 'error');
      router.push({ name: 'Suppliers' });
    }
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    if (isEdit.value) {
      await axios.put(`/suppliers/${route.params.id}`, form);
      showToast(t('page.suppliers.updated'), 'success');
    } else {
      await axios.post('/suppliers', form);
      showToast(t('page.suppliers.created'), 'success');
    }
    saveName(form.name);
    saveEmail(form.email);
    savePhone(form.phone);
    router.push({ name: 'Suppliers' });
  } catch (err) {
    if (err.response?.status === 422) {
      const msgs = err.response.data.errors || {};
      Object.assign(errors, msgs);
      const first = Object.values(msgs).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast(t('page.suppliers.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>


