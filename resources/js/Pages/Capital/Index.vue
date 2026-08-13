<template>
  <AdminLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <BasePageHeader :title="$t('capital.title')" />

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 4" :key="n" class="h-24 bg-surface-secondary rounded-xl shimmer"></div>
      </div>

      <div v-else-if="error" class="p-10 text-center bg-surface border border-border rounded-xl shadow-card">
        <div class="w-14 h-14 mx-auto mb-4 bg-danger-100 rounded-full flex items-center justify-center">
          <span class="text-danger-500"><ExclamationTriangleIcon class="w-7 h-7" /></span>
        </div>
        <p class="text-text-secondary">{{ error }}</p>
        <BaseButton variant="primary" size="sm" :to="{ name: 'Dashboard' }" class="mt-4">
          {{ $t('common.back_to_dashboard') }}
        </BaseButton>
      </div>

      <template v-else-if="data">
        <BaseCard class="bg-green-50 border-green-200">
          <p class="text-sm font-medium text-green-700">{{ $t('capital.calculated_label') }}</p>
          <p class="mt-1 text-4xl font-black text-green-700">{{ formatXOF(data.calculated) }}</p>
          <p v-if="data.is_manual" class="mt-1 text-xs text-green-500">
            {{ $t('capital.last_updated') }}: {{ formatDate(data.capital_updated_at) }}
          </p>
        </BaseCard>

        <BaseCard :title="$t('capital.breakdown')">
          <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-surface-secondary rounded-lg">
              <div>
                <p class="text-sm font-medium text-text-primary">{{ $t('capital.initial_capital') }}</p>
                <p class="text-xs text-text-tertiary">{{ $t('capital.initial_capital_desc') }}</p>
              </div>
              <div class="flex items-center gap-2">
                <input v-model.number="form.initial_capital" type="number" min="0" step="1"
                  class="w-36 px-3 py-2 text-right font-semibold text-text-primary border border-border rounded-lg focus:ring-primary-500 focus:border-primary-500 bg-surface"
                  @input="hasChanges = true" />
                <button @click="resetCapital" :disabled="!data.is_manual"
                  class="p-2 text-text-tertiary hover:text-text-primary disabled:opacity-30"
                  :title="$t('common.cancel')">
                  <span class="text-current"><XMarkIcon class="w-4 h-4" /></span>
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
              <div>
                <p class="text-sm font-medium text-blue-700">{{ $t('capital.revenue') }}</p>
                <p class="text-xs text-blue-400">{{ $t('capital.revenue_desc') }}</p>
              </div>
              <p class="text-lg font-bold text-blue-700">+ {{ formatXOF(data.revenue) }}</p>
            </div>

            <div class="flex items-center justify-between p-4 bg-rose-50 rounded-lg">
              <div>
                <p class="text-sm font-medium text-rose-700">{{ $t('capital.outflows') }}</p>
                <p class="text-xs text-rose-400">{{ $t('capital.outflows_desc') }}</p>
              </div>
              <p class="text-lg font-bold text-rose-700">- {{ formatXOF(data.outflows) }}</p>
            </div>

            <div class="flex items-center justify-between p-4 bg-green-50 border border-green-100 rounded-lg">
              <p class="text-sm font-bold text-green-700">{{ $t('capital.calculated') }}</p>
              <p class="text-xl font-black text-green-700">{{ formatXOF(data.calculated) }}</p>
            </div>
          </div>
        </BaseCard>

        <div class="flex justify-end gap-3">
          <BaseButton v-if="hasChanges" variant="primary" @click="saveCapital" :disabled="saving">
            <span class="text-white"><ArrowPathIcon class="w-4 h-4" /></span>{{ saving ? $t('common.saving') : $t('common.save') }}
          </BaseButton>
        </div>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, inject } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AdminLayout from '../../Components/AdminLayout.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import { XMarkIcon, ExclamationTriangleIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const { t: $t } = useI18n();
const showToast = inject('showToast');

const loading = ref(true);
const saving = ref(false);
const data = ref(null);
const error = ref('');
const hasChanges = ref(false);
const form = reactive({ initial_capital: 0 });
let pollTimer = null;

function formatXOF(amount) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(amount || 0);
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(iso));
}

async function fetchCapital(silent = false) {
  if (!silent) loading.value = true;
  try {
    const res = await axios.get('/capital');
    data.value = res.data.data;
    if (!silent) form.initial_capital = res.data.data.initial_capital;
    hasChanges.value = false;
    error.value = '';
  } catch (e) {
    if (!silent) {
      if (e.response?.status === 403) { error.value = $t('capital.unauthorized'); }
      else { error.value = e.response?.data?.message || $t('capital.load_error'); }
    }
  } finally { if (!silent) loading.value = false; }
}

function resetCapital() {
  form.initial_capital = 0;
  hasChanges.value = true;
}

async function saveCapital() {
  saving.value = true;
  try {
    const res = await axios.put('/capital', { initial_capital: form.initial_capital });
    data.value = res.data.data;
    hasChanges.value = false;
    showToast($t('capital.save_success'), 'success');
  } catch (e) {
    if (e.response?.status === 403) { error.value = $t('capital.unauthorized'); }
    else { showToast(e.response?.data?.message || $t('capital.save_error'), 'error'); }
  } finally { saving.value = false; }
}

onMounted(() => {
  fetchCapital();
  pollTimer = setInterval(() => fetchCapital(true), 10000);
});

onBeforeUnmount(() => { if (pollTimer) clearInterval(pollTimer); });
</script>
