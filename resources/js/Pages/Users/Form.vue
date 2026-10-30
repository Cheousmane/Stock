<template>
  <AdminLayout>
    <BaseModal :model-value="open" title="" size="md" :loading="submitting" @update:model-value="onClose">
      <template #header>
        <h3 class="text-base font-extrabold tracking-tight text-text-primary">{{ isEdit ? $t('page.users.edit') : $t('page.users.create') }}</h3>
      </template>
      <form @submit.prevent="submit" class="space-y-5">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" />
            <BaseInput v-model="form.email" type="email" :label="$t('auth.email')" required :error="errors.email?.[0] || ''" />

            <BaseInput v-model="form.password" :type="showPassword ? 'text' : 'password'" :required="!isEdit" minlength="8"
              :label="isEdit ? $t('page.users.password_optional') : $t('form.password')" :error="errors.password?.[0] || ''">
              <template #suffix>
                <button type="button" @click="showPassword = !showPassword" class="p-0.5 text-text-tertiary hover:text-text-secondary">
                  <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </template>
            </BaseInput>

            <BaseInput v-if="!isEdit || form.password" v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'"
              :required="!isEdit && !!form.password" :label="$t('form.password_confirm')" :error="errors.password_confirmation?.[0] || ''">
              <template #suffix>
                <button type="button" @click="showConfirm = !showConfirm" class="p-0.5 text-text-tertiary hover:text-text-secondary">
                  <EyeIcon v-if="!showConfirm" class="w-4 h-4" />
                  <EyeSlashIcon v-else class="w-4 h-4" />
                </button>
              </template>
            </BaseInput>

            <BaseSelect v-model="form.role" :label="$t('page.users.role')" :error="errors.role?.[0] || ''"
              :options="roles.map(r => ({ value: r.name, label: r.name }))" :placeholder="$t('common.select_role')" />

            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="rounded border-border text-primary-600 focus:ring-primary-500/30" />
              <span class="text-sm text-text-primary">{{ $t('page.users.active_label') }}</span>
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
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseModal from '../../Components/ui/BaseModal.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast', (msg, type) => console.log('[toast]', type, msg));
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const errors = reactive({});
const showPassword = ref(false);
const showConfirm = ref(false);
const roles = ref([]);
const form = reactive({
  name: '', email: '', password: '', password_confirmation: '', role: '', is_active: true,
});
const open = ref(true);

function onClose() {
  open.value = false;
  router.push({ name: 'Users' });
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/roles');
    roles.value = data.data ?? data;
  } catch {}
  if (!isEdit.value) return;
  try {
    const { data } = await axios.get(`/users/${route.params.id}`);
    const item = data.data ?? data;
    form.name = item.name ?? '';
    form.email = item.email ?? '';
    form.role = item.roles?.[0]?.name ?? '';
    form.is_active = item.is_active === true;
  } catch {
    showToast($t('page.users.load_error'), 'error');
    router.push({ name: 'Users' });
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    const payload = { ...form };
    if (isEdit.value && !form.password) delete payload.password;
    if (isEdit.value) {
      await axios.put(`/users/${route.params.id}`, payload);
    } else {
      await axios.post('/users', payload);
    }
    showToast(isEdit.value ? $t('page.users.updated') : $t('page.users.created'), 'success');
    router.push({ name: 'Users' });
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

