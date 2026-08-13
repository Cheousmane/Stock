<template>
  <div class="relative" v-click-outside="close">
    <button @click="toggle"
      class="flex items-center gap-1.5 px-2 py-1.5 text-sm font-medium text-gray-600 rounded-lg hover:bg-gray-100 hover:text-gray-900"
      :title="current?.name">
      <span class="text-base leading-none">{{ current?.flag }}</span>
      <span class="hidden sm:inline uppercase text-xs font-semibold">{{ current?.code }}</span>
      <ChevronDownIcon class="w-3.5 h-3.5 text-gray-400" :class="open && 'rotate-180'" />
    </button>

    <div v-if="open"
      class="absolute right-0 z-50 mt-1.5 w-64 bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
      <div class="p-2 border-b border-gray-100">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input v-model="query" ref="searchRef" type="text" :placeholder="$t('common.search')"
            class="w-full pl-8 pr-3 py-1.5 text-sm border border-gray-200 rounded-lg focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
        </div>
      </div>
      <div class="max-h-60 overflow-y-auto overscroll-contain">
        <button v-for="lang in filtered" :key="lang.code" @click="select(lang.code)"
          :class="['flex items-center gap-3 w-full px-3 py-2.5 text-sm text-left', lang.code === current?.code ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-gray-700 hover:bg-gray-50']">
          <span class="text-lg leading-none">{{ lang.flag }}</span>
          <span class="flex-1">{{ lang.name }}</span>
          <span class="text-xs text-gray-400 uppercase">{{ lang.code }}</span>
          <CheckIcon v-if="lang.code === current?.code" class="w-4 h-4 text-emerald-600" />
        </button>
        <div v-if="!filtered.length" class="px-3 py-8 text-sm text-center text-gray-400">
          {{ $t('common.no_results') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';
import { languages, switchLocale } from '../i18n';
import { ChevronDownIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';

const { locale } = useI18n();
const open = ref(false);
const query = ref('');
const searchRef = ref(null);

const current = computed(() => languages.find((l) => l.code === locale.value) || languages[0]);

const filtered = computed(() => {
  const q = query.value.toLowerCase().trim();
  if (!q) return languages;
  return languages.filter(
    (l) => l.name.toLowerCase().includes(q) || l.code.toLowerCase().includes(q)
  );
});

function toggle() {
  open.value = !open.value;
  if (open.value) {
    query.value = '';
    nextTick(() => searchRef.value?.focus());
  }
}

function close() {
  open.value = false;
}

async function select(code) {
  if (code === current.value.code) {
    close();
    return;
  }
  await switchLocale(code);
  close();
}

watch(open, (val) => {
  if (!val) query.value = '';
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
