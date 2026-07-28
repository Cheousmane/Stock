<template>
  <div class="py-12 text-center">
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-surface-tertiary mb-3">
      <component :is="icon" class="w-6 h-6 text-text-tertiary" />
    </div>
    <h3 v-if="title" class="text-sm font-semibold text-text-primary mb-1">{{ title }}</h3>
    <p v-if="description" class="text-sm text-text-tertiary mb-4">{{ description }}</p>
    <slot />
  </div>
</template>

<script setup>
import { InboxArrowDownIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: { type: String, default: '' },
  description: { type: String, default: '' },
  icon: { type: Object, default: InboxArrowDownIcon },
})
</script>
