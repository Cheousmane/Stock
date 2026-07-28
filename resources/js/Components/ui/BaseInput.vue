<template>
  <div class="space-y-1.5">
    <label v-if="label" :for="inputId" class="block text-sm font-medium text-text-primary mb-1.5">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <div v-if="$slots.prefix" class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
        <slot name="prefix" />
      </div>
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :list="list ? `${inputId}-list` : undefined"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur', $event)"
        v-bind="$attrs"
        :class="[
          'block w-full bg-surface border rounded-lg text-sm text-text-primary placeholder:text-text-tertiary transition-all duration-150 focus:outline-none',
          sizeClasses[size],
          { 'pl-9': $slots.prefix, 'pr-9': $slots.suffix || clearable },
          error ? 'border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' : 'border-border focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20',
          { 'opacity-50 bg-surface-tertiary cursor-not-allowed': disabled },
        ]"
      />
      <datalist v-if="list?.length" :id="`${inputId}-list`">
        <option v-for="item in list" :key="item" :value="item" />
      </datalist>
      <div v-if="$slots.suffix && !clearable" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <slot name="suffix" />
      </div>
      <button
        v-if="clearable && modelValue"
        @click="$emit('update:modelValue', '')"
        type="button"
        class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-text-tertiary hover:text-text-secondary transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-text-tertiary">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  type: { type: String, default: 'text' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  clearable: { type: Boolean, default: false },
  size: { type: String, default: 'md' },
  list: { type: Array, default: null },
})

defineEmits(['update:modelValue', 'blur'])

const uid = useId()
const inputId = computed(() => `input-${uid}`)

const sizeClasses = {
  sm: 'px-2.5 py-1.5 text-xs',
  md: 'px-3 py-2 text-sm',
  lg: 'px-3.5 py-2.5 text-sm',
}
</script>
