<template>
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
    <div class="min-w-0">
      <p v-if="eyebrow" class="pro-eyebrow">{{ eyebrow }}</p>
      <h1 class="pro-title">{{ title }}</h1>
      <p v-if="subtitle" class="pro-subtitle">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
})
</script>
