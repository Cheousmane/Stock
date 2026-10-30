<template>
  <AdminLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <BasePageHeader eyebrow="Finance" :title="$t('capital.title')" />

      <div v-if="loading" class="space-y-4">
        <div v-for="n in 4" :key="n" class="h-24 pro-card shimmer"></div>
      </div>

      <BaseEmptyState v-else-if="error" :title="error" :description="$t('capital.load_error')">
        <BaseButton variant="primary" size="sm" :to="{ name: 'Dashboard' }" class="mt-2">
          {{ $t('common.back_to_dashboard') }}
        </BaseButton>
      </BaseEmptyState>

      <template v-else-if="data">
        <BaseCard class="!border-emerald-500/20 !bg-emerald-500/[0.06]">
          <p class="text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ $t('capital.calculated_label') }}</p>
          <p class="mt-1 text-4xl font-black text-emerald-700 dark:text-emerald-300 tabular-nums">{{ formatXOF(data.calculated) }}</p>
          <p v-if="data.is_manual" class="mt-1.5 text-xs font-medium text-emerald-600/80 dark:text-emerald-400/80">
            {{ $t('capital.last_updated') }}: {{ formatDate(data.capital_updated_at) }}
          </p>
        </BaseCard>

        <BaseCard :title="$t('capital.breakdown')">
          <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-surface-secondary/60 rounded-xl border border-border/60">
              <div>
                <p class="text-sm font-bold text-text-primary">{{ $t('capital.initial_capital') }}</p>
                <p class="text-xs font-medium text-text-tertiary">{{ $t('capital.initial_capital_desc') }}</p>
              </div>
              <div class="flex items-center gap-2">
                <input v-model.number="form.initial_capital" type="number" min="0" step="1"
                  class="pro-form-control !w-36 text-right font-mono"
                  @input="hasChanges = true" />
                <button @click="resetCapital" :disabled="!data.is_manual"
                  class="pro-icon-btn disabled:opacity-30 disabled:pointer-events-none"
                  :title="$t('common.cancel')">
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between gap-3 p-4 bg-blue-500/[0.07] rounded-xl border border-blue-500/15">
              <div>
                <p class="text-sm font-bold text-blue-700 dark:text-blue-300">{{ $t('capital.revenue') }}</p>
                <p class="text-xs font-medium text-blue-500/80 dark:text-blue-400/80">{{ $t('capital.revenue_desc') }}</p>
              </div>
              <p class="text-lg font-bold text-blue-700 dark:text-blue-300 tabular-nums">+ {{ formatXOF(data.revenue) }}</p>
            </div>

            <div class="flex items-center justify-between gap-3 p-4 bg-rose-500/[0.07] rounded-xl border border-rose-500/15">
              <div>
                <p class="text-sm font-bold text-rose-700 dark:text-rose-300">{{ $t('capital.outflows') }}</p>
                <p class="text-xs font-medium text-rose-500/80 dark:text-rose-400/80">{{ $t('capital.outflows_desc') }}</p>
              </div>
              <p class="text-lg font-bold text-rose-700 dark:text-rose-300 tabular-nums">- {{ formatXOF(data.outflows) }}</p>
            </div>

            <div class="flex items-center justify-between gap-3 p-4 bg-emerald-500/[0.08] border border-emerald-500/20 rounded-xl">
              <p class="text-sm font-extrabold text-emerald-700 dark:text-emerald-300">{{ $t('capital.calculated') }}</p>
              <p class="text-xl font-black text-emerald-700 dark:text-emerald-300 tabular-nums">{{ formatXOF(data.calculated) }}</p>
            </div>
          </div>
        </BaseCard>

        <div class="flex justify-end gap-3">
          <BaseButton v-if="hasChanges" variant="primary" @click="saveCapital" :disabled="saving">
            <ArrowPathIcon class="w-4 h-4" />{{ saving ? $t('common.saving') : $t('common.save') }}
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
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import { XMarkIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

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
