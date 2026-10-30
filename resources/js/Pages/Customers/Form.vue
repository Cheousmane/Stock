<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="lg" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.customers.edit') : $t('page.customers.new') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput v-model="form.company_name" :label="$t('form.company')" :error="errors.company_name?.[0] || ''" :list="suggestions.company_name" />
            <BaseInput v-model="form.name" :label="$t('form.contact_name')" required :error="errors.name?.[0] || ''" :list="suggestions.name" />
            <BaseInput v-model="form.email" type="email" :label="$t('form.email')" required :error="errors.email?.[0] || ''" :list="suggestions.email" />
            <BaseInput v-model="form.phone" type="tel" :label="$t('form.phone')" :error="errors.phone?.[0] || ''" :list="suggestions.phone" />
          </div>
          <div class="mt-4">
            <BaseTextarea v-model="form.address" :label="$t('form.address')" :rows="2" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <BaseInput v-model="form.city" :label="$t('form.city')" :error="errors.city?.[0] || ''" />
            <BaseInput v-model="form.postal_code" :label="$t('form.postal_code')" :error="errors.postal_code?.[0] || ''" />
            <BaseInput v-model="form.country" :label="$t('form.country')" :error="errors.country?.[0] || ''" />
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
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';
import { useSuggestions } from '../../composables/useSuggestions';

const {
  saveValue: saveCompany,
  list: companySuggestions,
} = useSuggestions('customer_company');
const {
  saveValue: saveName,
  list: nameSuggestions,
} = useSuggestions('customer_name');
const {
  saveValue: saveEmail,
  list: emailSuggestions,
} = useSuggestions('customer_email');
const {
  saveValue: savePhone,
  list: phoneSuggestions,
} = useSuggestions('customer_phone');
const suggestions = {
  company_name: companySuggestions,
  name: nameSuggestions,
  email: emailSuggestions,
  phone: phoneSuggestions,
};

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Customers' });
}
const form = reactive({
  company_name: '', name: '', email: '', phone: '', address: '', city: '', postal_code: '', country: '',
});

onMounted(async () => {
  if (isEdit.value) {
    try {
      const { data } = await axios.get(`/customers/${route.params.id}`);
      Object.assign(form, data.data ?? data);
    } catch { router.push({ name: 'Customers' }); }
  }
});

async function submit() {
  submitting.value = true;
  Object.keys(errors).forEach(k => delete errors[k]);
  try {
    if (isEdit.value) {
      await axios.put(`/customers/${route.params.id}`, form);
      showToast($t('page.customers.updated'), 'success');
    } else {
      await axios.post('/customers', form);
      showToast($t('page.customers.created'), 'success');
    }
    open.value = false;
    saveCompany(form.company_name);
    saveName(form.name);
    saveEmail(form.email);
    savePhone(form.phone);
    setTimeout(() => router.push({ name: 'Customers' }), 100);
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      const first = Object.values(errors).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast($t('common.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>


