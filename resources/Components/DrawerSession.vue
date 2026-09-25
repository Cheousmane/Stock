<template>
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl transform transition-transform scale-100" @click.self="close">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Ouverture Session Caisse</h2>
      
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Montant ouverture</label>
        <input ref="openingCash"
          type="number"
          min="0"
          step="100"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none dark:bg-gray-700 dark:text-white"
          v-model.number="openingAmount"
          placeholder="0" />
      </div>
      
      <div class="flex justify-between mb-4">
        <button @click="close"
          class="flex-1 px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
            Annuler
        </button>
        <button @click="openSession"
          class="px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium text-sm hover:bg-emerald-500 transition-colors">
            Ouvrir
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const openingAmount = ref(0)
const isProcessing = ref(false)

const close = () => {
  emitting('close')
}

const openSession = async () => {
  if (openingAmount.value <= 0) {
    // Toast d'erreur
    emitting('close')
    return
  }
  
  isProcessing.value = true
  try {
    const { data } = await axios.post('/api/v1/pos/session/open', {
      opening_cash_xof: openingAmount.value
    })
    emitting('session-opened', data.data)
  } catch (error) {
    console.error('Erreur ouverture session:', error)
  } finally {
    isProcessing.value = false
    emitting('close')
  }
}
</script>