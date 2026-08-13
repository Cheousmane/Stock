<template>
  <Teleport to="body">
    <div v-if="modelValue" class="fixed inset-0 z-[100] flex items-center justify-center p-4" @click.self="close">
      <Transition name="modal-fade">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" />
      </Transition>
      <Transition name="modal-pop">
        <div
          class="relative bg-surface border border-border rounded-2xl shadow-modal w-full"
          :class="sizeClasses[size]"
        >
        <div v-if="$slots.header || title" class="flex items-start justify-between gap-4 p-5 pb-0">
          <div class="min-w-0">
            <h3 v-if="title" class="text-base font-semibold text-text-primary">{{ title }}</h3>
            <p v-if="subtitle" class="mt-0.5 text-sm text-text-tertiary">{{ subtitle }}</p>
          </div>
          <button @click="close" class="p-1 -mr-1 text-text-tertiary hover:text-text-secondary rounded-lg hover:bg-surface-tertiary">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div :class="['p-5', { 'pt-4': $slots.header || title }]">
          <slot />
        </div>
        <div v-if="$slots.footer" class="flex items-center justify-end gap-3 px-5 pb-5">
          <slot name="footer" />
        </div>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup>
const props = defineProps({
  // Accepte un booleen ou un objet (pratique pour stocker l'element a supprimer)
  modelValue: { default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' },
})

const emit = defineEmits(['update:modelValue'])

function close() {
  emit('update:modelValue', false)
}

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  full: 'max-w-3xl',
}
</script>
