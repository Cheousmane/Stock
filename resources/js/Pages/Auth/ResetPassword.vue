<template>
  <!-- Page de réinitialisation du mot de passe avec token -->
  <div class="flex min-h-screen">
    <!-- Panneau gauche - Marque et présentation -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
      <div class="absolute top-0 left-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2" />
      <div class="absolute bottom-0 right-0 w-80 h-80 bg-emerald-500/5 rounded-full blur-3xl translate-x-1/3 translate-y-1/3" />
      <div class="relative flex flex-col justify-center px-16 max-w-lg mx-auto">
        <!-- Logo et nom de l'entreprise -->
        <router-link to="/" class="flex items-center gap-3 mb-8 group">
          <AppLogo :size="48" />
          <div>
            <p class="text-xl font-bold text-white">SIDIBE CORPORATE</p>
            <p class="text-sm text-white/50">Facturation & Stock</p>
          </div>
        </router-link>
        <h2 class="text-3xl font-bold text-white leading-tight">Nouveau mot de passe</h2>
        <p class="mt-3 text-white/60 leading-relaxed">
          Choisissez un mot de passe sécurisé pour votre compte.
        </p>
      </div>
    </div>

    <!-- Panneau droit - Formulaire de réinitialisation -->
    <div class="flex-1 flex items-center justify-center px-4 sm:px-6 bg-[var(--color-surface-secondary)]">
      <div class="w-full max-w-sm">
        <!-- Logo mobile (caché sur desktop) -->
        <div class="text-center mb-8 lg:hidden">
          <router-link to="/" class="inline-flex items-center justify-center gap-3 mb-4 hover:opacity-80 transition-opacity">
            <AppLogo :size="40" />
            <span class="text-lg font-bold text-[var(--color-text-primary)]">SIDIBE CORPORATE</span>
          </router-link>
        </div>

        <!-- Carte formulaire -->
        <div class="p-8 rounded-xl bg-[var(--color-surface)] shadow-[var(--shadow-modal)] dark:border dark:border-[var(--color-border)]">
          <!-- En-tête -->
          <div class="text-center mb-7">
            <h1 class="text-2xl font-bold text-[var(--color-text-primary)]">Réinitialiser le mot de passe</h1>
            <p class="text-sm text-[var(--color-text-tertiary)] mt-1.5">Choisissez un nouveau mot de passe</p>
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

          <form @submit.prevent="submit" class="space-y-5">
            <!-- Nouveau mot de passe avec toggle visibilité -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">Nouveau mot de passe <span class="text-red-500">*</span></label>
              <div class="relative">
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  autocomplete="new-password"
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
            </div>
            <!-- Confirmation du mot de passe -->
            <div>
              <label class="block text-sm font-medium text-[var(--color-text-primary)] mb-1.5">Confirmer le mot de passe <span class="text-red-500">*</span></label>
              <input
                v-model="passwordConfirmation"
                type="password"
                required
                autocomplete="new-password"
                placeholder="••••••••"
                class="block w-full px-3.5 py-2.5 text-sm rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-primary)] placeholder:text-[var(--color-text-tertiary)] focus:border-[var(--color-primary-500)] focus:ring-2 focus:ring-[var(--color-primary-500)]/20 hover:border-gray-300"
              />
            </div>
            <!-- Bouton de soumission -->
            <button
              type="submit"
              :disabled="loading"
              class="w-full px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm shadow-emerald-500/20"
            >
              <span v-if="loading" class="flex items-center justify-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                Réinitialisation en cours...
              </span>
              <span v-else>Réinitialiser le mot de passe</span>
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
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { EyeIcon, EyeSlashIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import AppLogo from '../../Components/AppLogo.vue';

// Récupération du token et email depuis l'URL (query params)
const route = useRoute();
const router = useRouter();

// État du formulaire
const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const loading = ref(false);
const error = ref('');
const success = ref('');

// Envoie le nouveau mot de passe au backend avec le token
async function submit() {
  // Validation côté client : vérifie que les deux mots de passe correspondent
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Les mots de passe ne correspondent pas.';
    return;
  }

  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    const { data } = await axios.post('/auth/reset-password', {
      email: route.query.email,
      token: route.query.token,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    success.value = data.message;
    // Redirige vers la page de connexion après 2 secondes
    setTimeout(() => router.push({ name: 'Login' }), 2000);
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
}
</script>
