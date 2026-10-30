<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="md" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.events.edit') : $t('page.events.create') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.title" :label="$t('form.title')" required :error="errors.title?.[0] || ''" />
            <BaseSelect v-model="form.type" :label="$t('form.type')" required
              :options="[
                { value: 'meeting', label: $t('page.events.type_meeting') },
                { value: 'appointment', label: $t('page.events.type_appointment') },
                { value: 'call', label: $t('page.events.type_call') },
                { value: 'task', label: $t('page.events.type_task') },
                { value: 'other', label: $t('page.events.type_other') },
              ]" :error="errors.type?.[0] || ''" />
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <BaseInput v-model="form.start_date" type="datetime-local" :label="$t('form.start_date')" required :error="errors.start_date?.[0] || ''" />
              <BaseInput v-model="form.end_date" type="datetime-local" :label="$t('form.end_date')" :error="errors.end_date?.[0] || ''" />
            </div>
            <BaseTextarea v-model="form.description" :label="$t('form.description')" :rows="3" />
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
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseTextarea from '../../Components/ui/BaseTextarea.vue';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const form = reactive({ title: '', type: 'meeting', start_date: '', end_date: '', description: '' });
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Events' });
}

onMounted(async () => {
  if (!isEdit.value) return;
  try {
    const { data } = await axios.get(`/events/${route.params.id}`);
    const item = data.data ?? data;
    form.title = item.title ?? '';
    form.type = item.type ?? 'meeting';
    form.start_date = item.start_date ?? '';
    form.end_date = item.end_date ?? '';
    form.description = item.description ?? '';
  } catch {
    showToast($t('page.events.load_error'), 'error');
    router.push({ name: 'Events' });
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    if (isEdit.value) {
      await axios.put(`/events/${route.params.id}`, { ...form });
    } else {
      await axios.post('/events', { ...form });
    }
    showToast(isEdit.value ? $t('page.events.updated') : $t('page.events.created'), 'success');
    router.push({ name: 'Events' });
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

