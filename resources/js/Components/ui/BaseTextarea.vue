<template>
  <div class="space-y-1.5 min-w-0">
    <label v-if="label" :for="textareaId" class="block text-[13px] font-bold text-text-primary">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <textarea
      :id="textareaId"
      :value="modelValue"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      @input="$emit('update:modelValue', $event.target.value)"
      v-bind="$attrs"
      :class="[
        'block w-full bg-surface border rounded-xl text-sm font-medium text-text-primary placeholder:text-text-tertiary placeholder:font-normal focus:outline-none transition-all duration-200 shadow-2xs hover:border-text-tertiary/60 resize-y',
        error ? 'border-red-400 focus:border-red-500 focus:ring-4 focus:ring-red-500/15' : 'border-border/80 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15',
        { 'opacity-50 bg-surface-tertiary cursor-not-allowed': disabled },
      ]"
    />
    <p v-if="error" class="text-xs font-medium text-red-500">{{ error }}</p>
    <p v-else-if="hint" class="text-xs font-medium text-text-tertiary">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, useId } from 'vue'

defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  rows: { type: [String, Number], default: 3 },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const uid = useId()
const textareaId = computed(() => `textarea-${uid}`)
</script>
