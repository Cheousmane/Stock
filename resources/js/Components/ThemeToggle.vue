<template>
  <div class="relative" v-click-outside="() => open = false">
    <button @click="open = !open"
      class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-400"
      :title="$t('component.theme.' + current)">
      <SunIcon v-if="resolved === 'light'" class="w-5 h-5" />
      <MoonIcon v-else-if="resolved === 'dark'" class="w-5 h-5" />
      <ComputerDesktopIcon v-else class="w-5 h-5" />
    </button>
    <div v-if="open"
      class="absolute right-0 z-50 mt-2 w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg">
      <button v-for="opt in options" :key="opt.value" @click="select(opt.value)"
        :class="[
          'flex items-center gap-2 w-full px-4 py-2.5 text-sm text-left transition-colors',
          current === opt.value ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-300',
          'hover:bg-gray-50 dark:hover:bg-gray-700'
        ]">
        <component :is="opt.icon" class="w-4 h-4" />
        {{ $t(opt.label) }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { SunIcon, MoonIcon, ComputerDesktopIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '../composables/useTheme';

const { t: $t } = useI18n();
const { current, set } = useTheme();
const open = ref(false);

const options = [
  { value: 'light', label: 'component.theme.light', icon: SunIcon },
  { value: 'dark', label: 'component.theme.dark', icon: MoonIcon },
  { value: 'system', label: 'component.theme.system', icon: ComputerDesktopIcon },
];

function select(value) {
  set(value);
  open.value = false;
}

const resolved = computed(() => {
  if (current.value === 'system') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }
  return current.value;
});

const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutside = (event) => {
      if (!el.contains(event.target)) binding.value();
    };
    document.addEventListener('click', el.__clickOutside);
  },
  unmounted(el) {
    document.removeEventListener('click', el.__clickOutside);
  },
};
</script>
