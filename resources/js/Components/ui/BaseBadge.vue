<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 font-medium rounded-md',
      variantClasses[variant],
      sizeClasses[size],
    ]"
  >
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :class="dotColors[variant]" />
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'default' },
  size: { type: String, default: 'sm' },
  dot: { type: Boolean, default: false },
})

const variantClasses = {
  default: 'bg-surface-tertiary text-text-secondary',
  success: 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400',
  warning: 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
  danger: 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400',
  info: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
  purple: 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400',
}

const dotColors = {
  default: 'bg-text-tertiary',
  success: 'bg-green-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  info: 'bg-blue-500',
  purple: 'bg-purple-500',
}

const sizeClasses = {
  xs: 'px-1.5 py-0.5 text-[10px]',
  sm: 'px-2 py-0.5 text-[11px]',
  md: 'px-2.5 py-1 text-xs',
}
</script>
