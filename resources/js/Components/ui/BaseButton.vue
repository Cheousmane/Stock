<template>
  <component
    :is="tag"
    :type="tag === 'button' ? type : undefined"
    :disabled="tag === 'button' ? (disabled || loading) : undefined"
    :aria-disabled="disabled || loading ? 'true' : undefined"
    :class="[
      'inline-flex items-center justify-center gap-2 font-semibold whitespace-nowrap focus:outline-none select-none cursor-pointer transition-all duration-200 active:scale-[0.98]',
      variantClasses[variant],
      sizeClasses[size],
      { 'opacity-50 pointer-events-none cursor-not-allowed': loading || disabled },
    ]"
    v-bind="linkProps"
  >
    <svg v-if="loading" class="w-4 h-4 animate-spin-slow shrink-0" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
    </svg>
    <slot v-if="$slots.icon" name="icon" />
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  to: { type: [String, Object], default: undefined },
  href: { type: String, default: undefined },
  type: { type: String, default: 'button' },
})

const tag = computed(() => {
  if (props.disabled || props.loading) return 'button'
  if (props.to) return 'router-link'
  if (props.href) return 'a'
  return 'button'
})

const linkProps = computed(() => {
  if (props.disabled || props.loading) return {}
  if (props.to) return { to: props.to }
  if (props.href) return { href: props.href }
  return {}
})

const variantClasses = {
  primary:
    'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-sm shadow-emerald-500/25 hover:shadow-md hover:shadow-emerald-500/30 hover:brightness-105 focus-visible:ring-4 focus-visible:ring-emerald-500/20 rounded-xl',
  secondary:
    'bg-surface border border-border/80 text-text-primary shadow-xs hover:border-emerald-500/40 hover:bg-surface-secondary active:bg-surface-tertiary focus-visible:ring-4 focus-visible:ring-emerald-500/15 rounded-xl',
  ghost:
    'text-text-secondary hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-500/10 active:bg-emerald-500/15 rounded-xl',
  danger:
    'bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-sm shadow-red-500/20 hover:shadow-md hover:shadow-red-500/25 hover:brightness-105 focus-visible:ring-4 focus-visible:ring-red-500/20 rounded-xl',
  'danger-ghost':
    'text-red-600 hover:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/15 active:bg-red-500/20 rounded-xl',
}

const sizeClasses = {
  xs: 'px-2.5 py-1.5 text-[11px] gap-1.5 font-bold',
  sm: 'px-3.5 py-2 text-xs gap-1.5 font-bold',
  md: 'px-4 py-2.5 text-[13px] gap-2',
  lg: 'px-5 py-2.5 text-sm gap-2',
}
</script>
