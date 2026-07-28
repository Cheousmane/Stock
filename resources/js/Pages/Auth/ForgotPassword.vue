<template>
  <!-- Page de demande de réinitialisation de mot de passe -->
  <div class="flex min-h-screen animate-fade-in">
    <!-- Panneau gauche - Marque et présentation -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
      <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
      <div class="absolute bottom-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl translate-x-1/3 translate-y-1/3" />
      <div class="relative flex flex-col justify-center px-16 max-w-lg mx-auto">
        <!-- Logo et nom de l'entreprise -->
        <router-link to="/" class="flex items-center gap-3 mb-8 group">
          <div class="flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-500/40 transition-shadow">
            <span class="text-white dark:text-gray-950"><DocumentTextIcon class="w-7 h-7" /></span>
          </div>
          <div>
            <p class="text-xl font-bold text-white dark:text-gray-950">SIDIBE CORPORATE</p>
            <p class="text-sm text-white/50 dark:text-gray-950/50">Facturation & Stock</p>
          </div>
        </router-link>
        <h2 class="text-3xl font-bold text-white dark:text-gray-950 leading-tight">Mot de passe oublié ?</h2>
        <p class="mt-3 text-white/60 dark:text-gray-950/60 leading-relaxed">
          Saisissez votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.
        </p>
      </div>
    </div>

    <!-- Panneau droit - Formulaire de demande -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 bg-[var(--color-surface-secondary)]">
      <div class="w-full max-w-sm animate-slide-up">
        <!-- Logo mobile (caché sur desktop) -->
        <div class="text-center mb-8 lg:hidden">
          <router-link to="/" class="inline-flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600">
              <span class="text-white dark:text-gray-950"><DocumentTextIcon class="w-6 h-6" /></span>
            </div>
            <span class="text-lg font-bold text-[var(--color-text-primary)]">SIDIBE CORPORATE</span>
          </router-link>
        </div>

        <!-- Carte formulaire -->
        <div class="p-8 rounded-xl bg-[var(--color-surface)] shadow-[var(--shadow-modal)] dark:border dark:border-[var(--color-border)]">
          <!-- En-tête -->
          <div class="text-center mb-7">
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Mot de passe oublié</h1>
            <p class="text-sm text-[var(--color-text-tertiary)] mt-1.5">Recevez un lien de réinitialisation par email</p>
          </div>

          <!-- Message de succès -->
          <div v-if="success" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-emerald-700 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-950/50 rounded-xl border border-emerald-100 dark:border-emerald-900/70">
            {{ success }}
          </div>

          <!-- Message d'erreur -->
          <div v-if="error" class="flex items-center gap-2.5 p-3.5 mb-6 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 rounded-xl border border-red-100 dark:border-red-900/70">
            <span class="text-red-500 shrink-0"><ExclamationTriangleIcon class="w-4 h-4" /></span>
            {{ error }}
          </div>

          <!-- Champ email -->
          <form @submit.prevent="submit" class="space-y-5">
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">Email <span class="text-red-500">*</span></label>
              <input
                v-model="email"
                type="email"
                required
                autocomplete="email"
                placeholder="vous@exemple.com"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] transition-all duration-200 focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              />
            </div>
            <!-- Bouton d'envoi -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-emerald-500/20 transition-all duration-200 active:scale-[0.98]"
            >
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                Envoi en cours...
              </span>
              <span v-else>Envoyer le lien</span>
            </button>
          </form>
        </div>

        <!-- Lien retour vers la connexion -->
        <p class="mt-8 text-sm text-center text-[var(--color-text-tertiary)]">
          <router-link :to="{ name: 'Login' }" class="font-semibold text-[var(--color-primary-500)] hover:text-emerald-700 transition-colors">Retour à la connexion</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { DocumentTextIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

// État du formulaire
const email = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');

// Envoie la demande de réinitialisation au backend
async function submit() {
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    const { data } = await axios.post('/auth/forgot-password', { email: email.value });
    success.value = data.message;
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* Animations d'entrée */
@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes slide-up {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in { animation: fade-in 0.4s ease-out; }
.animate-slide-up { animation: slide-up 0.4s ease-out; }
</style>
