<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 overflow-x-hidden">
    <!-- Header -->
    <header class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button @click="openSessionDrawer"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :class="sessionOpen ? 'text-emerald-600' : 'text-gray-400'"
            aria-label="Ouvrir session caisse">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="ml-2 text-sm font-medium">{{ sessionOpen ? 'Fermer session' : 'Ouvrir session' }}</span>
          </button>
          
          <!-- Total en cours -->
          <div v-if="sessionOpen" class="ml-4 bg-emerald-100 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-500 rounded-md px-3 py-1.5 text-sm text-emerald-800 dark:text-emerald-300">
            <span class="font-medium">CA en cours :</span>
            <span class="font-bold text-lg" :style="{ color: 'var(--math-rem) > 100000 ? #dc2626 : #16a34a' }">
              {{ formatXof(cashTotal) }}
            </span>
          </div>
        </div>

        <!-- Bouton drawer navigation mobile -->
        <button @click="drawerOpen = true"
          class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          aria-label="Navigation">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto p-4 md:p-6">
      
      <!-- Produits Catalogue - Grid Tactile -->
      <section class="mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Catalogue Produits</h2>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
          <product-card-v-for
            v-for="product in availableProducts"
            :key="product.id"
            :product="product"
            @add-to-sale="addProductToSale"
            :disabled="!product.is_active"
            class="group cursor-pointer hover:opacity-70 transition-opacity"
          >
            <template #default>
              <div class="group-hover:scale-105 transition-transform border rounded-lg p-3 bg-white dark:bg-gray-800">
                <img v-if="product.image" :src="product.image" :alt="product.name" class="w-12 h-12 object-contain mx-auto mb-2" />
                <p class="text-center text-xs font-medium line-clamp-1">{{ product.name }}</p>
                <p class="text-center text-primary font-medium">{{ formatXof(product.price_xof) }}</p>
              </div>
            </template>
            
            <template #icon>
              <svg class="w-5 h-5 text-emerald-500 group-hover:text-emerald-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
            </template>
          </product-card-v-for>
        </div>
      </section>

      <!-- Session en cours ou Bouton ouvrir -->
      <section v-if="sessionOpen" class="mt-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Session Caisse #{{ session?.receipt_number || 'N/A' }}</h2>
        
        <div class="grid grid-cols-2 gap-2 mb-3">
          <div v-for="payment in ['cash', 'card', 'mobile_money']" :key="payment">
            <div class="p-3 rounded-lg bg-white dark:bg-gray-800">
              <p class="text-xs text-gray-500 dark:text-gray-300">{{ payment }}</p>
              <p class="text-xl font-bold" :style="{ color: payment === 'cash' ? '#dc2626' : payment === 'card' ? '#2563eb' : '#059669' }">
                {{ formatXof(salesByPayment[payment] || 0) }}
              </p>
            </div>
          </div>
        </div>
        
        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
          <p class="text-sm text-gray-500 dark:text-gray-300">Total Caisse</p>
          <p class="text-2xl font-bold" :style="{ color: cashTotal > expectedClosing ? #dc2626 : #16a34a }">
            {{ formatXof(cashTotal) }} / {{ formatXof(expectedClosing) }}
          </p>
        </div>
      </section>

      <section v-else class="mt-4 p-4 rounded-lg bg-gray-50 dark:bg-gray-800 text-center">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Mise en route</h2>
        <p class="text-gray-600 dark:text-gray-300">Veuillez ouvrir une session caisse pour commencer à vendre</p>
        <button @click="openSessionDrawer"
          class="mt-3 px-6 py-2.5 bg-emerald-600 text-white font-medium rounded-lg hover:bg-emerald-500 transition-colors"
          aria-label="Ouvrir session caisse">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            Ouvrir session
        </button>
      </section>
    </div>

    <!-- Drawer Session Caisse (mobile) -->
    <drawer-session-v-if="drawerOpen"
      @close="drawerOpen = false"
      @session-opened="loadProducts"
      :company-id="companyId" />

    <!-- Modal Produits (mobile) -->
    <product-modal-v-if="showProductModal"
      @close="showProductModal = false"
      @select-product="addProductToSale"
      :products="availableProducts" />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { axios } from 'axios'

import ProductCard from '@/Components/ProductCard.vue'
import DrawerSession from '@/Components/DrawerSession.vue'
import ProductModal from '@/Components/ProductModal.vue'

import { useStore } from '@/store'
import { useTheme } from '@/composables/useTheme'

const store = useStore()
const { t } = useI18n()
const { current: theme } = useTheme()

// Route params
const companyId = store.state.user?.company_id || 1

// État de la session
const sessionOpen = ref(false)
const session = ref(null)
const drawerOpen = ref(false)
const showProductModal = ref(false)
const searchQuery = ref('')

// Données de la session
const cashTotal = ref(0)
const expectedClosing = ref(0)
const salesByPayment = {
  cash: ref(0),
  card: ref(0),
  mobile_money: ref(0)
}

// Produits disponibles (chargés depuis API)
const availableProducts = ref([])

// Chargement des produits
async function loadProducts() {
  try {
    const { data } = await axios.get('/api/v1/products?is_active=true')
    availableProducts.value = data.data || []
  } catch (error) {
    console.error('Erreur chargement produits:', error)
    availableProducts.value = []
  }
}

// Ajout produit à la vente
function addProductToSale(product) {
  // Vérifier stock
  const stock = availableProducts.value.find(p => p.id === product.id)?.stock
  if ((stock ?? 0) <= 0) {
    showToast('Stock insuffisant', 'error')
    return
  }
  
  // Ajouter au sale en cours
  // ... logique d'ajout
  showToast('Produit ajouté au panier', 'success')
}

// Ouverture session
function openSessionDrawer() {
  drawerOpen.value = true
}

// Format XOF
function formatXof(amount) {
  return amount.toLocaleString('fr-FR', { style: 'currency', currency: 'XOF' }).replace('XOF', ' FCFA')
}

// Toast
const { showToast } = useToast()
</script>

<style scoped>
/* Classes utilitaires pour le tactile */
.product-card-v-for {
  @apply border rounded-lg p-3 bg-white dark:bg-gray-800 transition-all duration-150;
}

/* Animation sur focus pour clavier/tactile */
:focus-visible {
  @apply outline-none ring-2 ring-emerald-500 ring-offset-white;
}
</style>