<template>
  <div v-if="lastPage > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4">
    <p class="text-sm text-text-tertiary">
      {{ from }}–{{ to }} sur {{ total }}
    </p>
    <div class="flex items-center gap-1">
      <button
        @click="$emit('change', 1)"
        :disabled="currentPage <= 1"
        class="p-2 text-text-tertiary hover:text-text-secondary hover:bg-surface-tertiary rounded-lg transition-all duration-150 active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5" />
        </svg>
      </button>
      <button
        @click="$emit('change', currentPage - 1)"
        :disabled="currentPage <= 1"
        class="p-2 text-text-tertiary hover:text-text-secondary hover:bg-surface-tertiary rounded-lg transition-all duration-150 active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
      </button>
      <template v-for="p in visiblePages" :key="p">
        <span v-if="p === '...'" class="px-2 text-xs text-text-tertiary">...</span>
        <button
          v-else
          @click="$emit('change', p)"
          class="min-w-[32px] h-8 text-xs font-medium rounded-lg transition-all duration-150 active:scale-90"
          :class="p === currentPage ? 'bg-primary-600 text-white shadow-sm' : 'text-text-secondary hover:bg-surface-tertiary'"
        >
          {{ p }}
        </button>
      </template>
      <button
        @click="$emit('change', currentPage + 1)"
        :disabled="currentPage >= lastPage"
        class="p-2 text-text-tertiary hover:text-text-secondary hover:bg-surface-tertiary rounded-lg transition-all duration-150 active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
      </button>
      <button
        @click="$emit('change', lastPage)"
        :disabled="currentPage >= lastPage"
        class="p-2 text-text-tertiary hover:text-text-secondary hover:bg-surface-tertiary rounded-lg transition-all duration-150 active:scale-90 disabled:opacity-30 disabled:cursor-not-allowed"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 4.5l7.5 7.5-7.5 7.5m6-15l7.5 7.5-7.5 7.5" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 25 },
})

defineEmits(['change'])

const from = computed(() => (props.currentPage - 1) * props.perPage + 1)
const to = computed(() => Math.min(props.currentPage * props.perPage, props.total))

const visiblePages = computed(() => {
  const { currentPage: cur, lastPage: last } = props
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  const pages = [1]
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur - 1); i <= Math.min(last - 1, cur + 1); i++) {
    pages.push(i)
  }
  if (cur < last - 2) pages.push('...')
  pages.push(last)
  return pages
})
</script>
