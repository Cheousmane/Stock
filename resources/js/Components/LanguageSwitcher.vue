<template>
  <div class="relative" v-click-outside="close">
    <button @click="toggle"
      class="flex items-center gap-1.5 px-2.5 py-2 text-sm font-bold text-text-secondary rounded-xl border border-transparent hover:border-border hover:bg-surface-tertiary hover:text-text-primary active:scale-95 transition-all"
      :title="current?.name">
      <span class="text-base leading-none">{{ current?.flag }}</span>
      <span class="hidden sm:inline uppercase text-[11px] font-extrabold tracking-wider">{{ current?.code }}</span>
      <ChevronDownIcon class="w-3.5 h-3.5 text-text-tertiary transition-transform duration-200" :class="open && 'rotate-180'" />
    </button>

    <Transition name="pop">
    <div v-if="open"
      class="absolute right-0 z-50 mt-2 w-68 bg-surface border border-border/70 rounded-2xl shadow-xl shadow-neutral-950/[0.08] overflow-hidden">
      <div class="p-2 border-b border-border/60">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-text-tertiary" />
          <input v-model="query" ref="searchRef" type="text" :placeholder="$t('common.search')"
            class="w-full pl-9 pr-3 py-2.5 text-sm font-semibold bg-surface-secondary/70 border border-border/60 rounded-xl focus:bg-surface focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500/60 outline-none transition placeholder:text-text-tertiary placeholder:font-normal" />
        </div>
      </div>
      <div class="max-h-60 overflow-y-auto overscroll-contain custom-scrollbar p-1.5">
        <template v-for="lang in filtered" :key="lang.code">
          <button @click="select(lang.code)"
            :class="['group flex items-center gap-3 w-full px-3 py-2.5 text-sm text-left rounded-xl transition-all', lang.code === current?.code ? 'bg-emerald-500/[0.08] text-emerald-700 dark:text-emerald-400 font-extrabold shadow-[inset_0_0_0_1px_rgb(16_185_129/0.2)]' : 'text-text-secondary hover:bg-surface-tertiary hover:text-text-primary font-semibold']">
            <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-surface-secondary border border-border/60 text-lg leading-none shrink-0 transition-transform group-hover:scale-105">{{ lang.flag }}</span>
            <span class="flex-1 truncate">{{ lang.name }}</span>
            <span class="text-[10px] font-extrabold text-text-tertiary uppercase tracking-wider">{{ lang.code }}</span>
            <CheckIcon v-if="lang.code === current?.code" class="w-4 h-4 text-emerald-600 shrink-0" />
          </button>
        </template>
        <div v-if="!filtered.length" class="px-3 py-8 text-sm font-semibold text-center text-text-tertiary">
          {{ $t('common.no_results') }}
        </div>
      </div>
    </div>
    </Transition>
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
  unmounted(el) { document.removeEventListener('click', el.__clickOutside) },
};
</script>

<style scoped>
.pop-enter-active { animation: modal-pop 0.18s ease-out; transform-origin: top right; }
.pop-leave-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-4px) scale(0.98); }
</style>