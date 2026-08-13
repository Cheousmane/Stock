<template>
  <PosLayout :session="null">
    <div class="flex-1 flex items-center justify-center p-6 bg-surface-secondary">
      <div class="bg-surface p-8 rounded-2xl shadow-card w-full max-w-md border border-border">
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <span class="text-emerald-600"><BanknotesIcon class="w-8 h-8" /></span>
          </div>
          <h2 class="text-2xl font-bold text-text-primary">Ouvrir la Caisse</h2>
          <p class="text-text-tertiary mt-2">Veuillez indiquer le fond de caisse initial pour démarrer la journée.</p>
        </div>

        <form @submit.prevent="openSession">
          <div class="mb-6">
            <label class="block text-sm font-medium text-text-secondary mb-2">Fond de Caisse Initial (FCFA)</label>
            <div class="relative">
              <input v-model="openingCash" type="number" min="0" required autofocus
                class="w-full pl-4 pr-16 py-3 border border-border rounded-xl focus:ring-emerald-500 focus:border-emerald-500 text-xl font-bold text-text-primary bg-surface-secondary outline-none transition-shadow focus:shadow-md">
              <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <span class="text-text-tertiary font-bold">XOF</span>
              </div>
            </div>
          </div>

          <BaseButton type="submit" :loading="loading" class="w-full justify-center py-4 text-lg">
            {{ loading ? 'Ouverture en cours...' : 'Ouvrir la session' }}
          </BaseButton>
        </form>
      </div>
    </div>
  </PosLayout>
</template>

<script setup>
import { ref, inject } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import PosLayout from './PosLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import { BanknotesIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const showToast = inject('showToast');
const openingCash = ref(0);
const loading = ref(false);

const openSession = async () => {
  loading.value = true;
  try {
    const res = await axios.post('/pos/session/open', {
      opening_cash_xof: openingCash.value
    });
    showToast('Caisse ouverte avec succès.');
    router.push('/pos');
  } catch (err) {
    showToast(err.response?.data?.message || 'Erreur lors de l\'ouverture de caisse', 'error');
  } finally {
    loading.value = false;
  }
};
</script>
