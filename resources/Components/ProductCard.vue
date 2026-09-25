<template>
  <div class="group cursor-pointer hover:opacity-70 transition-opacity border rounded-lg p-4 bg-white dark:bg-gray-800 group-hover:scale-105 transition-transform">
    <img v-if="product.image" :src="product.image" :alt="product.name" class="w-16 h-16 object-contain mx-auto mb-3" />
    <h3 class="text-sm font-medium line-clamp-1 text-center">{{ product.name }}</h3>
    <p class="text-primary font-medium text-lg text-center">{{ formatXof(product.price_xof) }}</p>
    <p class="text-xs text-gray-500 dark:text-gray-300 text-center mt-1">SKU: {{ product.sku }}</p>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

const formatXof = (amount) => {
  return amount.toLocaleString('fr-FR', { style: 'currency', currency: 'XOF' }).replace('XOF', ' FCFA')
}
</script>