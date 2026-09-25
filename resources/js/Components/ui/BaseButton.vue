<template>
  <component
    :is="tag"
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium focus:outline-none select-none cursor-pointer transition-all duration-200 active:scale-[0.98]',
      variantClasses[variant],
      sizeClasses[size],
      { 'opacity-50 pointer-events-none cursor-not-allowed': loading || disabled },
    ]"
    :disabled="disabled || loading"
    v-bind="$attrs"
  >
    <svg v-if="loading" class="w-4 h-4 animate-spin-slow -ml-0.5" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot v-if="$slots.icon" name="icon" />
    <span v-if="$slots.default"><slot /></span>
  </component>
</template>

<script setup>
import { computed, useAttrs } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

const attrs = useAttrs()

const tag = computed(() => props.loading || props.disabled ? 'button' : (attrs.to ? 'router-link' : 'button'))

const variantClasses = {
  primary:
    'bg-gradient-to-b from-primary-500 to-primary-700 text-white shadow-sm shadow-primary-500/20 dark:from-primary-600 dark:to-primary-800 dark:text-gray-50 dark:shadow-primary-500/5 hover:brightness-110 hover:shadow-md hover:shadow-primary-500/25 dark:hover:brightness-110 active:shadow-inner focus-visible:ring-2 focus-visible:ring-primary-500/30 rounded-xl',
  secondary:
    'bg-white border border-neutral-200 text-neutral-700 shadow-sm dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 hover:border-neutral-300 dark:hover:bg-neutral-700 dark:hover:border-neutral-600 dark:hover:text-neutral-100 active:bg-neutral-100 dark:active:bg-neutral-600 focus-visible:ring-2 focus-visible:ring-neutral-400/30 rounded-xl',
  ghost:
    'text-neutral-600 hover:text-primary-700 hover:bg-primary-50 dark:text-neutral-400 dark:hover:text-primary-300 dark:hover:bg-primary-500/10 active:bg-primary-100 dark:active:bg-primary-500/20 rounded-xl',
  danger:
    'bg-gradient-to-b from-red-500 to-red-700 text-white shadow-sm shadow-red-500/20 dark:from-red-600 dark:to-red-800 dark:text-gray-50 dark:shadow-red-500/5 hover:brightness-110 hover:shadow-md hover:shadow-red-500/25 dark:hover:brightness-110 active:shadow-inner focus-visible:ring-2 focus-visible:ring-red-500/30 rounded-xl',
  'danger-ghost':
    'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/20 active:bg-red-100 dark:active:bg-red-500/30 rounded-xl',
}

const sizeClasses = {
  xs: 'px-2 py-1 text-xs gap-1.5',
  sm: 'px-2.5 py-1.5 text-sm gap-1.5',
  md: 'px-3.5 py-2 text-sm gap-2',
  lg: 'px-4 py-2.5 text-sm gap-2',
}
</script>
