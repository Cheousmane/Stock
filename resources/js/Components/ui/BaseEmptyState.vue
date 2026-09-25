<template>
  <div class="py-12 px-6 text-center">
    <span class="pro-empty-icon">
      <component :is="icon" class="w-7 h-7" />
    </span>
    <h3 v-if="title" class="text-sm font-extrabold text-text-primary mb-1">{{ title }}</h3>
    <p v-if="description" class="text-[13px] font-medium text-text-tertiary mb-4 max-w-sm mx-auto">{{ description }}</p>
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
