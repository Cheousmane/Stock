<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader :title="isEdit ? $t('page.users.edit') : $t('page.users.create')" subtitle="">
        <template #actions>
          <BaseButton variant="ghost" size="sm" :to="{ name: 'Users' }">
            <span class="text-current"><ArrowLeftIcon class="w-4 h-4" /></span>{{ $t('common.cancel') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <form @submit.prevent="submit" class="space-y-6">
        <BaseCard>
          <div class="space-y-4">
            <BaseInput v-model="form.name" :label="$t('form.name')" required :error="errors.name?.[0] || ''" />
            <BaseInput v-model="form.email" type="email" :label="$t('auth.email')" required :error="errors.email?.[0] || ''" />

            <div class="space-y-1.5">
              <label class="block text-xs font-medium text-text-secondary tracking-wide">
                {{ isEdit ? $t('page.users.password_optional') : $t('form.password') }}
                <span v-if="!isEdit" class="text-red-500 ml-0.5">*</span>
              </label>
              <div class="relative">
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" :required="!isEdit" minlength="8"
                  class="block w-full bg-surface border border-border rounded-lg px-3 py-2 pr-10 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-text-tertiary hover:text-text-secondary">
                  <span class="text-current"><EyeIcon v-if="!showPassword" class="w-4 h-4" /><EyeSlashIcon v-else class="w-4 h-4" /></span>
                </button>
              </div>
              <p v-if="errors.password?.[0]" class="text-xs text-red-500">{{ errors.password[0] }}</p>
            </div>

            <div v-if="!isEdit || form.password" class="space-y-1.5">
              <label class="block text-xs font-medium text-text-secondary tracking-wide">{{ $t('form.password_confirm') }}</label>
              <div class="relative">
                <input v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" :required="!isEdit && !!form.password"
                  class="block w-full bg-surface border border-border rounded-lg px-3 py-2 pr-10 text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20" />
                <button type="button" @click="showConfirm = !showConfirm" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-text-tertiary hover:text-text-secondary">
                  <span class="text-current"><EyeIcon v-if="!showConfirm" class="w-4 h-4" /><EyeSlashIcon v-else class="w-4 h-4" /></span>
                </button>
              </div>
            </div>

            <BaseSelect v-model="form.role" :label="$t('form.role')"
              :options="[{ value: '', label: $t('common.select_role') }, ...roles.map(r => ({ value: r.name, label: $t(roleLabel(r.name)) }))]"
              :error="errors.role?.[0] || ''" />
          </div>
        </BaseCard>

        <div class="flex items-center justify-end gap-3">
          <BaseButton variant="ghost" :to="{ name: 'Users' }">{{ $t('common.cancel') }}</BaseButton>
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
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import { ArrowLeftIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const route = useRoute();
const router = useRouter();
const { t: $t } = useI18n();
const showToast = inject('showToast');
const isEdit = computed(() => !!route.params.id);
const submitting = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);
const errors = reactive({});
const roles = ref([]);

const form = reactive({
  name: '', email: '', password: '', password_confirmation: '', role: '',
});

function roleLabel(role) {
  const labels = {
    admin: 'roles.admin', manager: 'roles.manager', accountant: 'roles.accountant',
    warehouse_manager: 'roles.warehouse_manager', sales: 'roles.sales', employee: 'roles.employee',
  };
  return labels[role] || role;
}

onMounted(async () => {
  try {
    const { data } = await axios.get('/roles');
    roles.value = data.data ?? data;
  } catch {
    showToast('Erreur lors du chargement des rôles', 'error');
  }
  if (!isEdit.value) return;
  try {
    const { data } = await axios.get(`/users/${route.params.id}`);
    const item = data.data ?? data;
    form.name = item.name ?? '';
    form.email = item.email ?? '';
    form.role = item.roles?.[0] || '';
  } catch {
    showToast($t('page.users.load_error'), 'error');
    router.push({ name: 'Users' });
  }
});

async function submit() {
  submitting.value = true;
  Object.assign(errors, {});
  try {
    if (isEdit.value) {
      const payload = {};
      if (form.name) payload.name = form.name;
      if (form.email) payload.email = form.email;
      if (form.password) {
        payload.password = form.password;
        payload.password_confirmation = form.password_confirmation;
      }
      if (form.role) payload.role = form.role;
      await axios.put(`/users/${route.params.id}`, payload);
    } else {
      await axios.post('/users', { ...form });
    }
    showToast(isEdit.value ? $t('page.users.updated') : $t('page.users.invited'), 'success');
    router.push({ name: 'Users' });
  } catch (err) {
    if (err.response?.status === 422) {
      Object.assign(errors, err.response.data.errors || {});
      const first = Object.values(errors).flat()[0];
      if (first) showToast(first, 'error');
    } else if (err.response?.status === 403) {
      showToast($t('common.unauthorized'), 'error');
    } else {
      showToast($t('common.save_error'), 'error');
    }
  } finally {
    submitting.value = false;
  }
}
</script>
