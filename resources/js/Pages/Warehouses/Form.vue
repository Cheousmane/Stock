<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.warehouses.edit') : $t('page.warehouses.create')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Warehouses' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" />
            <BaseInput v-model="form.code" :label="$t('form.code')" :error="errors.code?.[0] || ''" />
            <BaseInput v-model="form.address" :label="$t('form.address')" :error="errors.address?.[0] || ''" />
            <BaseInput v-model="form.city" :label="$t('page.warehouses.city')" :error="errors.city?.[0] || ''" />
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500/30" />
              <span class="text-sm text-text-primary">{{ $t('page.warehouses.active_label') }}</span>
            </label>
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Warehouses' }">{{ $t('common.cancel') }}</BaseButton>
          <BaseButton variant="primary" type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast');
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const form = reactive({ name: '', code: '', address: '', city: '', is_active: true });

onMounted(async () => {
  if (!isEdit.value) return;
  try {
    const { data } = await axios.get(`/warehouses/${route.params.id}`);
    const item = data.data ?? data;
    form.name = item.name ?? '';
    form.code = item.code ?? '';
    form.address = item.address ?? '';
    form.city = item.city ?? '';
    form.is_active = item.is_active ?? true;
  } catch {
    showToast($t('page.warehouses.load_error'), 'error');
    router.push({ name: 'Warehouses' });
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    if (isEdit.value) {
      await axios.put(`/warehouses/${route.params.id}`, { ...form });
    } else {
      await axios.post('/warehouses', { ...form });
    }
    showToast(isEdit.value ? $t('page.warehouses.updated') : $t('page.warehouses.created'), 'success');
    router.push({ name: 'Warehouses' });
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
