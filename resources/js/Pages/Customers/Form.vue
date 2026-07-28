<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.customers.edit') : $t('page.customers.new')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Customers' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <BaseInput v-model="form.company_name" :label="$t('form.company')" :error="errors.company_name?.[0] || ''" :list="suggestions.company_name" />
            <BaseInput v-model="form.name" :label="$t('form.contact_name')" required :error="errors.name?.[0] || ''" :list="suggestions.name" />
            <BaseInput v-model="form.email" type="email" :label="$t('form.email')" required :error="errors.email?.[0] || ''" :list="suggestions.email" />
            <BaseInput v-model="form.phone" type="tel" :label="$t('form.phone')" :error="errors.phone?.[0] || ''" :list="suggestions.phone" />
          </div>
          <div class="mt-4 space-y-1.5">
            <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.address') }}</label>
            <textarea v-model="form.address" rows="2"
              class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <BaseInput v-model="form.city" :label="$t('form.city')" :error="errors.city?.[0] || ''" />
            <BaseInput v-model="form.postal_code" :label="$t('form.postal_code')" :error="errors.postal_code?.[0] || ''" />
            <BaseInput v-model="form.country" :label="$t('form.country')" :error="errors.country?.[0] || ''" />
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Customers' }">{{ $t('common.cancel') }}</BaseButton>
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
const showToast = inject('showToast');
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
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
    saveCompany(form.company_name);
    saveName(form.name);
    saveEmail(form.email);
    savePhone(form.phone);
    router.push({ name: 'Customers' });
  } catch (err) {
    if (err.response?.status === 422) Object.assign(errors, err.response.data.errors || {});
  } finally {
    submitting.value = false;
  }
}
</script>
