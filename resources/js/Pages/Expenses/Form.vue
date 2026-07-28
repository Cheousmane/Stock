<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="$t('page.expenses.create')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Expenses' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.description" :label="$t('form.description')" required :error="errors.description?.[0] || ''" />
            <BaseSelect v-model="form.category" :label="$t('form.category')" required
              :options="[
                { value: 'fournitures', label: $t('page.expenses.category_fournitures') },
                { value: 'loyer', label: $t('page.expenses.category_loyer') },
                { value: 'utilities', label: $t('page.expenses.category_utilities') },
                { value: 'salaire', label: $t('page.expenses.category_salaire') },
                { value: 'transport', label: $t('page.expenses.category_transport') },
                { value: 'marketing', label: $t('page.expenses.category_marketing') },
                { value: 'autre', label: $t('page.expenses.category_autre') },
              ]"
              :placeholder="$t('common.select')" :error="errors.category?.[0] || ''" />
            <BaseInput v-model.number="form.amount" type="number" :label="$t('form.amount')" required min="0" step="1" :error="errors.amount?.[0] || ''" />
            <BaseInput v-model="form.date" type="date" :label="$t('form.date')" required :error="errors.date?.[0] || ''" />
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Expenses' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, inject } from 'vue';
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
const submitting = ref(false);
const errors = reactive({});
const form = reactive({
  description: '', category: '', amount: 0, date: new Date().toISOString().slice(0, 10),
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    await axios.post('/expenses', form);
    showToast($t('page.expenses.created'), 'success');
    router.push({ name: 'Expenses' });
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      const first = Object.values(errors).flat()[0];
      if (first) showToast(first, 'error');
    } else {
      showToast($t('common.validation_error'), 'error');
    }
  } finally { submitting.value = false; }
}
</script>
