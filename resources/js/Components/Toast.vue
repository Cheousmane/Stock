<template>
  <Transition name="modal-pop">
    <div v-if="visible" class="fixed top-5 right-5 z-[9999] max-w-sm w-full pointer-events-auto">
      <div
        class="flex items-center gap-3 p-4 rounded-2xl border shadow-xl backdrop-blur-md transition-all duration-200"
        :class="toastClasses"
      >
        <div class="flex items-center justify-center w-8 h-8 rounded-xl shrink-0" :class="iconBgClass">
          <component :is="icon" class="w-4 h-4" />
        </div>
        <p class="text-sm font-semibold flex-1 leading-snug">{{ message }}</p>
        <button
          @click="dismiss"
          class="p-1 rounded-lg opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 active:scale-95 transition shrink-0"
          :aria-label="t('common.close')"
        >
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { CheckCircleIcon, ExclamationCircleIcon, XMarkIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: Boolean,
  message: String,
  type: { type: String, default: 'success' },
  duration: { type: Number, default: 4000 },
});

const emit = defineEmits(['close']);
const visible = ref(false);
const { t } = useI18n();

const icons = { success: CheckCircleIcon, error: ExclamationCircleIcon, info: InformationCircleIcon };
const icon = computed(() => icons[props.type] || InformationCircleIcon);

const toastClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-surface border-emerald-500/20 text-text-primary shadow-emerald-500/10';
    case 'error':
      return 'bg-surface border-red-500/20 text-text-primary shadow-red-500/10';
    case 'info':
      return 'bg-surface border-blue-500/20 text-text-primary shadow-blue-500/10';
    default:
      return 'bg-surface border-border text-text-primary';
  }
});

const iconBgClass = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400';
    case 'error':
      return 'bg-red-500/15 text-red-600 dark:text-red-400';
    case 'info':
      return 'bg-blue-500/15 text-blue-600 dark:text-blue-400';
    default:
      return 'bg-surface-tertiary text-text-secondary';
  }
});

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
