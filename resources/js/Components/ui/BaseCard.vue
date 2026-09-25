<template>
  <div
    :class="[
      'pro-card',
      paddingClasses[padding],
      hoverable ? 'pro-card--hover card-sweep cursor-pointer' : '',
    ]"
  >
    <!-- Affiche l'en-tete si titre, sous-titre, slot header ou actions sont presents -->
    <div v-if="$slots.header || title || subtitle || $slots.actions" class="flex items-start justify-between gap-4 mb-4">
      <div v-if="title || subtitle" class="min-w-0">
        <h3 v-if="title" class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ title }}</h3>
        <p v-if="subtitle" class="mt-0.5 text-xs font-medium text-text-tertiary">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </div>
    <slot />
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  padding: { type: String, default: 'md' },
  hoverable: { type: Boolean, default: false },
})

const paddingClasses = {
  none: 'p-0',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-5',
}
</script>
