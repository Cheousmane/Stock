<template>
  <div class="flex min-h-screen">
    <!-- Left Panel - Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
      <div class="absolute inset-0 animate-[bgShift_20s_ease-in-out_infinite_alternate]"></div>
      <div ref="particlesRef" class="absolute inset-0"></div>
      <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
      <div class="absolute bottom-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl translate-x-1/3 translate-y-1/3" />
      <div class="relative flex flex-col justify-center px-16 max-w-lg mx-auto">
        <router-link to="/" class="flex items-center gap-3 mb-8 group opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.2s_forwards]">
          <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-shadow">
            <span class="text-white dark:text-gray-950"><DocumentTextIcon class="w-7 h-7" /></span>
          </div>
          <div>
            <p class="text-xl font-bold text-white dark:text-gray-950">SIDIBE CORPORATE</p>
            <p class="text-sm text-white/50 dark:text-gray-950/50">Facturation & Stock</p>
          </div>
        </router-link>
        <h2 class="text-3xl font-bold text-white dark:text-gray-950 leading-tight opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.4s_forwards]">Créez votre compte<br>gratuitement</h2>
        <p class="mt-3 text-white/60 dark:text-gray-950/60 leading-relaxed opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_0.6s_forwards]">
          Rejoignez des milliers d'entreprises qui gèrent efficacement leur facturation et leurs stocks.
        </p>
        <div class="mt-10 space-y-4">
          <div v-for="(item, i) in features" :key="i" class="flex items-start gap-3 opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_forwards]" :style="{ animationDelay: `${0.8 + i * 0.1}s` }">
            <div class="flex items-center justify-center w-7 h-7 rounded-lg bg-emerald-500/10 shrink-0 mt-0.5">
              <span class="text-emerald-400"><CheckIcon class="w-4 h-4" /></span>
            </div>
            <p class="text-sm text-white/70 dark:text-gray-950/70">{{ item }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10 bg-[var(--color-surface-secondary)]">
      <div class="w-full max-w-sm">
        <div class="text-center mb-8 lg:hidden">
          <router-link to="/" class="inline-flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600">
              <span class="text-white dark:text-gray-950"><DocumentTextIcon class="w-6 h-6" /></span>
            </div>
            <span class="text-lg font-bold text-[var(--color-text-primary)]">SIDIBE CORPORATE</span>
          </router-link>
        </div>

        <div class="p-8 rounded-xl bg-[var(--color-surface)] shadow-[var(--shadow-modal)] dark:border dark:border-[var(--color-border)]">
          <div class="text-center mb-7">
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">{{ $t('auth.register') }}</h1>
            <p class="text-sm text-[var(--color-text-tertiary)] mt-1.5">{{ $t('auth.register_title') }}</p>
          </div>

          <div v-if="error" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 rounded-xl border border-red-100 dark:border-red-900/70">
            <span class="text-red-500 shrink-0"><ExclamationTriangleIcon class="w-4 h-4" /></span>
            {{ error }}
          </div>

          <form @submit.prevent="register" class="space-y-5">
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.1s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.name') }} <span class="text-red-500">*</span></label>
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Le nom de votre entreprise"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              />
              <p v-if="errors.name" class="mt-1.5 text-xs text-red-500">{{ errors.name[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.15s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.email') }} <span class="text-red-500">*</span></label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="vous@exemple.com"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              />
              <p v-if="errors.email" class="mt-1.5 text-xs text-red-500">{{ errors.email[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.2s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.industry') }}</label>
              <select
                v-model="form.industry"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              >
                <option value="">{{ $t('auth.industry_placeholder') }}</option>
                <option v-for="sector in industryOptions" :key="sector" :value="sector">{{ sector }}</option>
              </select>
              <input
                v-if="form.industry === 'Autre'"
                v-model="form.otherIndustry"
                type="text"
                :placeholder="$t('auth.other_industry_placeholder')"
                class="mt-2 block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              />
              <p v-if="errors.industry" class="mt-1.5 text-xs text-red-500">{{ errors.industry[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.2s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.company_size') }}</label>
              <select
                v-model="form.size"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              >
                <option value="">{{ $t('auth.company_size_placeholder') }}</option>
                <option value="petite">{{ $t('auth.size_petite') }}</option>
                <option value="moyenne">{{ $t('auth.size_moyenne') }}</option>
                <option value="grande">{{ $t('auth.size_grande') }}</option>
              </select>
              <p v-if="errors.size" class="mt-1.5 text-xs text-red-500">{{ errors.size[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.25s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.password') }} <span class="text-red-500">*</span></label>
              <div class="relative">
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="block w-full px-3.5 py-2.5 pr-11 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 transition-colors"
                >
                  <span class="text-[var(--color-text-tertiary)] hover:text-[var(--color-text-secondary)]">
                    <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                    <EyeSlashIcon v-else class="w-4 h-4" />
                  </span>
                </button>
              </div>
              <p v-if="errors.password" class="mt-1.5 text-xs text-red-500">{{ errors.password[0] }}</p>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.25s_forwards]">
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">{{ $t('auth.confirm_password') }} <span class="text-red-500">*</span></label>
              <div class="relative">
                <input
                  v-model="form.password_confirmation"
                  :type="showConfirm ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="block w-full px-3.5 py-2.5 pr-11 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
                />
                <button
                  type="button"
                  @click="showConfirm = !showConfirm"
                  class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 transition-colors"
                >
                  <span class="text-[var(--color-text-tertiary)] hover:text-[var(--color-text-secondary)]">
                    <EyeIcon v-if="!showConfirm" class="w-4 h-4" />
                    <EyeSlashIcon v-else class="w-4 h-4" />
                  </span>
                </button>
              </div>
            </div>
            <div class="opacity-0 animate-[fadeUp_0.5s_cubic-bezier(0.16,1,0.3,1)_0.3s_forwards]">
              <button
                type="submit"
                :disabled="loading"
                class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-emerald-500/20"
              >
                <span v-if="loading" class="flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  {{ $t('auth.register_loading') }}
                </span>
                <span v-else>{{ $t('auth.sign_up') }}</span>
              </button>
            </div>
          </form>
        </div>

        <p class="mt-8 text-sm text-center text-[var(--color-text-tertiary)]">
          {{ $t('auth.has_account') }}
          <router-link :to="{ name: 'Login' }" class="font-semibold text-[var(--color-primary-500)] hover:text-emerald-700 transition-colors">{{ $t('auth.sign_in') }}</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { DocumentTextIcon, EyeIcon, EyeSlashIcon, ExclamationTriangleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { switchLocale } from '../../i18n';

// Référence pour les particules animées du panneau gauche
const particlesRef = ref(null);

const router = useRouter();
const { t } = useI18n();
const loading = ref(false);
const error = ref('');
const errors = reactive({});
const showPassword = ref(false);
const showConfirm = ref(false);
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', company_name: '', company_slug: '', industry: '', size: '', otherIndustry: '' });

onMounted(async () => {
  await nextTick();
  if (particlesRef.value) {
    const container = particlesRef.value;
    for (let i = 0; i < 30; i++) {
      const p = document.createElement('span');
      const size = 1.5 + Math.random() * 3;
      p.style.cssText = `position:absolute;width:${size}px;height:${size}px;left:${Math.random() * 100}%;top:${Math.random() * 100}%;border-radius:50%;background:rgba(5,150,105,${0.15 + Math.random() * 0.25});animation:particleFloat ${6 + Math.random() * 10}s ease-in-out infinite;animation-delay:${Math.random() * 5}s;pointer-events:none;`;
      container.appendChild(p);
    }
  }
});

const features = [
  'Factures et devis professionnels en un clic',
  'Gestion de stock multi-entrepôts en temps réel',
  'Point de vente avec sessions de caisse',
  'Rapports financiers et tableau de bord analytique',
];

const industryOptions = [
  'Agriculture & Agroalimentaire',
  'Commerce & Distribution',
  'BTP & Immobilier',
  'Industrie & Manufacture',
  'Services & Consulting',
  'Transport & Logistique',
  'Santé & Pharmacie',
  'Éducation & Formation',
  'Technologie & Télécoms',
  'Finance & Assurance',
  'Hôtellerie & Restauration',
  'Énergie & Environnement',
  'Autre',
];

async function register() {
  loading.value = true;
  error.value = '';
  Object.keys(errors).forEach(k => delete errors[k]);
  try {
    form.company_name = form.company_name || form.name;
    form.company_slug = form.company_slug || form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    if (form.industry === 'Autre') {
      form.industry = form.otherIndustry.trim() || 'Autre';
    }
    const { data } = await axios.post('/auth/register', form);
    localStorage.setItem('user', JSON.stringify(data.user));
    if (data.user?.locale) switchLocale(data.user.locale);
    router.push({ name: 'VerifyEmail', query: { email: form.email } });
  } catch (err) {
    if (err.response?.status === 422) Object.assign(errors, err.response.data.errors || {});
    error.value = err.response?.data?.message || t('auth.register_error');
  } finally {
    loading.value = false;
  }
}
</script>

<style>
/* Animation du fond : léger mouvement de zoom/rotation lent */
@keyframes bgShift {
  0% { transform: scale(1) rotate(0); }
  100% { transform: scale(1.1) rotate(2deg); }
}
/* Particules décoratives : apparaissent et flottent aléatoirement */
@keyframes particleFloat {
  0%, 100% { transform: translate(0, 0); opacity: 0; }
  10% { opacity: 1; }
  50% { transform: translate(var(--dx, 30px), var(--dy, -30px)); opacity: 0.6; }
  90% { opacity: 1; }
}
/* Entrée progressive par le bas */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
