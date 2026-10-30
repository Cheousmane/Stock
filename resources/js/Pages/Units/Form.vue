<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="md" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.units.edit') : $t('page.units.create') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" />
            <BaseInput v-model="form.symbol" :label="$t('form.symbol')" required :error="errors.symbol?.[0] || ''" />
            <BaseInput v-model.number="form.conversion_factor" type="number" :label="$t('form.conversion_factor')" min="0" step="0.0001" :error="errors.conversion_factor?.[0] || ''" />
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
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const form = reactive({ name: '', symbol: '', conversion_factor: 1 });
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Units' });
}

onMounted(async () => {
  if (!isEdit.value) return;
  try {
    const { data } = await axios.get(`/units/${route.params.id}`);
    const item = data.data ?? data;
    form.name = item.name ?? '';
    form.symbol = item.symbol ?? '';
    form.conversion_factor = item.conversion_factor ?? 1;
  } catch {
    showToast($t('page.units.load_error'), 'error');
    router.push({ name: 'Units' });
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    if (isEdit.value) {
      await axios.put(`/units/${route.params.id}`, { ...form });
    } else {
      await axios.post('/units', { ...form });
    }
    showToast(isEdit.value ? $t('page.units.updated') : $t('page.units.created'), 'success');
    router.push({ name: 'Units' });
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

