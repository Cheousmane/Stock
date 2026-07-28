<template>
  <div v-if="visible" class="fixed top-4 right-4 z-50 transition-all duration-300" :class="[typeClass]">
    <div class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg" :class="bgClass">
      <component :is="icon" class="w-5 h-5" />
      <p class="text-sm font-medium">{{ message }}</p>
      <button @click="dismiss" class="ml-4 text-white/80 hover:text-white">
        <XMarkIcon class="w-4 h-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { CheckCircleIcon, ExclamationCircleIcon, XMarkIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: Boolean,
  message: String,
  type: { type: String, default: 'success' },
  duration: { type: Number, default: 4000 },
});

const emit = defineEmits(['close']);
const visible = ref(false);

const icons = { success: CheckCircleIcon, error: ExclamationCircleIcon, info: InformationCircleIcon };

const icon = computed(() => icons[props.type] || InformationCircleIcon);
const bgClass = computed(() => ({
  success: 'bg-green-600 text-white',
  error: 'bg-red-600 text-white',
  info: 'bg-blue-600 text-white',
}[props.type] || 'bg-gray-800 text-white'));

const typeClass = computed(() => props.show ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-2');

function dismiss() {
  visible.value = false;
  emit('close');
}

watch(() => props.show, (val) => {
  if (val) {
    visible.value = true;
    setTimeout(() => { visible.value = false; emit('close'); }, props.duration);
  }
});
</script>
