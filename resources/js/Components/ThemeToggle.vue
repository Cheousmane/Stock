<template>
  <div ref="root" class="relative">
    <button
      ref="buttonRef"
      type="button"
      @click="open = !open"
      :class="buttonClasses"
      :title="label"
      :aria-label="label"
      aria-haspopup="menu"
      :aria-expanded="open"
    >
      <component :is="currentIcon" class="w-5 h-5" />
    </button>

    <Transition name="dropdown-in">
      <div v-if="open" :class="menuClasses" role="menu">
        <p class="px-3 pt-2 pb-1.5 text-[10px] font-extrabold uppercase tracking-[0.14em] text-text-tertiary">
          {{ $t('component.theme.label') }}
        </p>
        <button
          v-for="opt in options"
          :key="opt.value"
          type="button"
          role="menuitemradio"
          :aria-checked="current === opt.value"
          @click="select(opt.value)"
          :class="[
            'flex items-center gap-2.5 w-full px-3 py-2.5 text-[13px] font-semibold text-left rounded-xl transition-colors',
            current === opt.value
              ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
              : 'text-text-secondary hover:bg-surface-tertiary hover:text-text-primary'
          ]"
        >
          <component :is="opt.icon" class="w-4 h-4 shrink-0" />
          <span class="flex-1">{{ $t(opt.label) }}</span>
          <CheckIcon v-if="current === opt.value" class="w-4 h-4 shrink-0" />
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  CheckIcon,
  ComputerDesktopIcon,
  MoonIcon,
  SunIcon,
} from '@heroicons/vue/24/outline';
import { useTheme } from '../composables/useTheme';

const props = defineProps({
  buttonClass: { type: String, default: '' },
  menuClass: { type: String, default: '' },
});

const { t } = useI18n();
const { current, setTheme } = useTheme();

const root = ref(null);
const buttonRef = ref(null);
const open = ref(false);

const options = [
  { value: 'system', label: 'component.theme.auto', icon: ComputerDesktopIcon },
  { value: 'light', label: 'component.theme.light', icon: SunIcon },
  { value: 'dark', label: 'component.theme.dark', icon: MoonIcon },
];

const currentIcon = computed(() => {
  if (current.value === 'dark') return MoonIcon;
  if (current.value === 'light') return SunIcon;
  return ComputerDesktopIcon;
});

const label = computed(() => {
  if (current.value === 'system') return t('component.theme.auto');
  return t(`component.theme.${current.value}`);
});

const buttonClasses = computed(() => props.buttonClass || [
  'flex items-center justify-center w-9 h-9 rounded-xl',
  'text-text-secondary hover:text-text-primary',
  'bg-transparent hover:bg-surface-tertiary border border-transparent hover:border-border',
  'active:scale-95 transition-all',
].join(' '));

const menuClasses = computed(() => props.menuClass || [
  'absolute right-0 z-50 mt-2 w-44 p-1.5',
  'bg-surface border border-border/70 rounded-2xl',
  'shadow-xl shadow-neutral-950/[0.08]',
].join(' '));

function select(value) {
  setTheme(value);
  open.value = false;
  buttonRef.value?.focus();
}

function onDocumentClick(event) {
  if (!open.value) return;
  if (root.value && !root.value.contains(event.target)) open.value = false;
}

function onKeydown(event) {
  if (event.key === 'Escape' && open.value) {
    open.value = false;
    buttonRef.value?.focus();
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
  document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  document.removeEventListener('keydown', onKeydown);
});
</script>
