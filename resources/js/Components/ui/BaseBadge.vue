<template>
  <span
    :class="[
      'inline-flex items-center gap-1.5 font-extrabold rounded-full border shadow-xs tracking-wide uppercase',
      variantClasses[variant],
      sizeClasses[size],
    ]"
  >
    <span
      v-if="dot"
      class="w-1.5 h-1.5 rounded-full shrink-0"
      :class="[dotColors[variant], { 'animate-pulse': pulse || variant === 'danger' }]"
    />
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'default' },
  size: { type: String, default: 'sm' },
  dot: { type: Boolean, default: false },
  pulse: { type: Boolean, default: false },
})

const variantClasses = {
  default: 'bg-surface-tertiary text-text-secondary border-border',
  success: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
  warning: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
  danger: 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20',
  info: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
  purple: 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
}

const dotColors = {
  default: 'bg-text-tertiary',
  success: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  info: 'bg-blue-500',
  purple: 'bg-purple-500',
}

const sizeClasses = {
  xs: 'px-2 py-0.5 text-[10px]',
  sm: 'px-2.5 py-0.5 text-[11px]',
  md: 'px-3 py-1 text-xs',
}
</script>
