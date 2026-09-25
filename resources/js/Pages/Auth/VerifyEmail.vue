<template>
  <div class="flex min-h-screen">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
      <div class="absolute inset-0 animate-[bgShift_20s_ease-in-out_infinite_alternate]"></div>
      <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
      <div class="absolute bottom-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl translate-x-1/3 translate-y-1/3" />
      <div class="relative flex flex-col justify-center px-16 max-w-lg mx-auto">
        <router-link to="/" class="flex items-center gap-3 mb-8 group opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.2s_forwards]">
          <AppLogo :size="48" />
          <div>
            <p class="text-xl font-bold text-white">SIDIBE CORPORATE</p>
            <p class="text-sm text-white/50">Facturation & Stock</p>
          </div>
        </router-link>
        <h2 class="text-3xl font-bold text-white leading-tight opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.4s_forwards]">Vérifiez votre adresse<br>e-mail</h2>
        <p class="mt-3 text-white/60 leading-relaxed opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.6s_forwards]">
          Nous venons de vous envoyer un code de confirmation pour sécuriser la création de votre compte.
        </p>
      </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10 bg-[var(--color-surface-secondary)]">
      <div class="w-full max-w-sm">
        <div class="text-center mb-8 lg:hidden">
          <router-link to="/" class="inline-flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
            <AppLogo :size="40" />
            <span class="text-lg font-bold text-[var(--color-text-primary)]">SIDIBE CORPORATE</span>
          </router-link>
        </div>

        <div class="p-8 rounded-xl bg-[var(--color-surface)] shadow-[var(--shadow-modal)] dark:border dark:border-[var(--color-border)]">
          <div class="text-center mb-7">
            <div class="mx-auto mb-4 flex items-center justify-center w-12 h-12 rounded-2xl bg-emerald-500/10">
              <span class="text-emerald-500"><EnvelopeIcon class="w-6 h-6" /></span>
            </div>
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.verify_email') }}</h1>
            <p class="text-sm text-[var(--color-text-tertiary)] mt-1.5">{{ $t('auth.verify_email_title') }}</p>
          </div>

          <div v-if="success" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 rounded-xl border border-emerald-100 dark:border-emerald-900/70">
            <span class="text-emerald-500 shrink-0"><CheckIcon class="w-4 h-4" /></span>
            {{ success }}
          </div>

          <div v-if="error" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 rounded-xl border border-red-100 dark:border-red-900/70">
            <span class="text-red-500 shrink-0"><ExclamationTriangleIcon class="w-4 h-4" /></span>
            {{ error }}
          </div>

          <div v-if="route.query.mail_failed" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 rounded-xl border border-amber-200 dark:border-amber-900/70">
            <span class="text-amber-500 shrink-0"><ExclamationTriangleIcon class="w-4 h-4" /></span>
            {{ $t('auth.email_not_sent') }}
          </div>

          <form @submit.prevent="verify" class="space-y-5">
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.1s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.email') }} <span class="text-red-500">*</span></label>
              <input
                v-model="email"
                type="email"
                required
                :disabled="!!prefilledEmail"
                placeholder="vous@exemple.com"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300 disabled:opacity-60"
              />
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.2s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.verification_code') }} <span class="text-red-500">*</span></label>
              <input
                v-model="code"
                type="text"
                required
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                pattern="\d{6}"
                placeholder="••••••"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300 tracking-[0.3em] text-center"
              />
              <p v-if="errors.code" class="mt-1.5 text-xs text-red-500">{{ errors.code[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.3s_forwards]">
              <button
                type="submit"
                :disabled="loading"
                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-emerald-500/20"
              >
                <span v-if="loading" class="flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  {{ $t('auth.verifying') }}
                </span>
                <span v-else>{{ $t('auth.confirm_email') }}</span>
              </button>
            </div>
          </form>

          <div class="mt-6 text-center opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.4s_forwards]">
            <p class="text-sm text-[var(--color-text-tertiary)]">
              {{ $t('auth.no_code_received') }}
            </p>
            <button
              type="button"
              :disabled="resending || resendCooldown > 0"
              @click="resend"
              class="mt-1.5 text-sm font-semibold text-[var(--color-primary-500)] hover:text-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <template v-if="resending">{{ $t('auth.sending_code') }}</template>
              <template v-else-if="resendCooldown > 0">{{ $t('auth.resend_in', { seconds: resendCooldown }) }}</template>
              <template v-else>{{ $t('auth.resend_code') }}</template>
            </button>
          </div>

          <p class="mt-6 text-sm text-center text-[var(--color-text-tertiary)]">
            {{ $t('auth.has_account') }}
            <router-link :to="{ name: 'Login' }" class="font-semibold text-[var(--color-primary-500)] hover:text-emerald-700 transition-colors">{{ $t('auth.sign_in') }}</router-link>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { EnvelopeIcon, ExclamationTriangleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import AppLogo from '../../Components/AppLogo.vue';
import { switchLocale } from '../../i18n';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const email = ref('');
const prefilledEmail = computed(() => route.query.email || '');
const code = ref('');
const loading = ref(false);
const resending = ref(false);
const error = ref('');
const success = ref('');
const errors = reactive({});
const resendCooldown = ref(0);
let cooldownTimer = null;

onMounted(() => {
  if (prefilledEmail.value) {
    email.value = prefilledEmail.value;
  } else {
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (user.email) email.value = user.email;
  }
});

onBeforeUnmount(() => {
  if (cooldownTimer) clearInterval(cooldownTimer);
});

async function verify() {
  loading.value = true;
  error.value = '';
  success.value = '';
  Object.keys(errors).forEach(k => delete errors[k]);
  try {
    const { data } = await axios.post('/auth/verify-email', { email: email.value, code: code.value });
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    if (data.user?.locale) switchLocale(data.user.locale);
    router.push({ name: 'Dashboard' });
  } catch (err) {
    if (err.response?.status === 422) Object.assign(errors, err.response.data.errors || {});
    error.value = err.response?.data?.message || t('auth.verification_error');
  } finally {
    loading.value = false;
  }
}

async function resend() {
  resending.value = true;
  error.value = '';
  success.value = '';
  try {
    await axios.post('/auth/resend-verification', { email: email.value });
    success.value = t('auth.code_resent');
    startCooldown(60);
  } catch (err) {
    if (err.response?.data?.errors?.email) {
      error.value = err.response.data.errors.email[0];
    } else {
      error.value = err.response?.data?.message || t('auth.resend_error');
    }
  } finally {
    resending.value = false;
  }
}

function startCooldown(seconds) {
  resendCooldown.value = seconds;
  if (cooldownTimer) clearInterval(cooldownTimer);
  cooldownTimer = setInterval(() => {
    resendCooldown.value -= 1;
    if (resendCooldown.value <= 0) {
      clearInterval(cooldownTimer);
      cooldownTimer = null;
    }
  }, 1000);
}
</script>

<style>
@keyframes bgShift {
  0% { transform: scale(1) rotate(0); }
  100% { transform: scale(1.1) rotate(2deg); }
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
