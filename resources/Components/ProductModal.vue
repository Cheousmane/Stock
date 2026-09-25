<template>
  <div class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-full max-h-[90vh] overflow-y-auto transform transition-transform scale-100">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        Produits
      </h2>
      
      <div class="mb-3">
        <input ref="searchInput"
          type="text"
          v-model="searchQuery"
          placeholder="Rechercher un produit..."
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none dark:bg-gray-700 dark:text-white"
          @keyup.enter="focusFirstResult"
          @input="filterProducts" />
      </div>
      
      <div class="space-y-1 max-h-[60vh] overflow-y-auto">
        <product-filtered-item-v-for
          v-for="(product, index) in filteredProducts"
          :key="product.id + index"
          :product="product"
          @add-to-sale="closeModal"
          :disabled="product.stock <= 0"
          class="py-2 px-3 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          :class="{ 'opacity-50 cursor-not-allowed': product.stock <= 0 }" />
      </div>
      
      <div class="mt-4 p-t-3 border-t border-gray-200 dark:border-gray-700">
        <p class="text-sm text-gray-500 dark:text-gray-300">Stock total disponible : {{ totalStock }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { productCardProps } from '@/Components/ProductCard'

import { useStore } from '@/store'
import { useI18n } from 'vue-i18n'
import { useAxios } from '@/composables/useAxios'

const store = useStore()
const { t } = useI18n()
const { get, post } = useAxios()

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  }
})

const searchQuery = ref('')
const products = ref([])
const filteredProducts = ref([])

// Chargement des produits
watch(show, async (newShow) => {
  if (newShow) {
    await loadProducts()
  }
})

async function loadProducts() {
  try {
    const { data } = await get('/api/v1/products?is_active=true')
    products.value = data.data || []
    filteredProducts.value = products.value
    searchQuery.value = ''
  } catch (error) {
    console.error('Erreur chargement produits:', error)
  }
})

const totalStock = computed(() => {
  return products.value.reduce((sum, p) => sum + (p.stock ?? 0), 0)
})

const filterProducts = () => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) {
    filteredProducts.value = products.value
    return
  }
  filteredProducts.value = products.value.filter(p =>
    p.name.toLowerCase().includes(q) ||
    p.sku.toLowerCase().includes(q) ||
    product.code.toLowerCase().includes(q)
  )
}

const focused = ref(false)

const focusFirstResult = () => {
  if (filteredProducts.value.length > 0) {
    // Scroll vers le premier résultat
    const firstItem = document.querySelector('.product-item')
    if (firstItem) firstItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
  }
}

// Méthode exposée au parent
const addToSale = (product) => {
  emitting('select-product', product)
  closeModal()
}

// Méthode close
const closeModal = () => {
  emitting('close')
}
</script>