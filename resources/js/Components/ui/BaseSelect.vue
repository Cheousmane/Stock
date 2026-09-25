<template>
  <div class="space-y-1.5">
    <label v-if="label" :for="selectId" class="block text-sm font-medium text-text-primary mb-1.5">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <div class="relative">
      <select
        :id="selectId"
        :value="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
        :class="[
          'block w-full bg-surface border rounded-2xl text-sm font-semibold text-text-primary focus:outline-none appearance-none cursor-pointer transition-all duration-200 shadow-2xs hover:border-text-tertiary/60',
          sizeClasses[size],
          error ? 'border-red-300 focus:border-red-500 focus:ring-4 focus:ring-red-500/15' : 'border-border focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15',
          { 'opacity-50 bg-surface-tertiary cursor-not-allowed': disabled },
        ]"
      >
        <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options" :key="opt.value" :value="opt.value" :disabled="opt.disabled">
          {{ opt.label }}
        </option>
      </select>
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-4 h-4 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </div>
    </div>
    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  size: { type: String, default: 'md' },
})

defineEmits(['update:modelValue'])

const uid = useId()
const selectId = computed(() => `select-${uid}`)

const sizeClasses = {
  sm: 'px-2.5 py-1.5 text-xs pr-8',
  md: 'px-3 py-2 text-sm pr-9',
  lg: 'px-3.5 py-2.5 text-sm pr-9',
}
</script>
