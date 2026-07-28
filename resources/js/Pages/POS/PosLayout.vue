<template>
  <div class="h-screen flex flex-col bg-gray-100 overflow-hidden font-sans">
    <!-- Header -->
    <header class="bg-white border-b h-14 flex items-center justify-between px-4 shrink-0 shadow-sm">
      <div class="flex items-center gap-3">
        <router-link to="/dashboard" class="text-gray-500 hover:text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </router-link>
        <h1 class="text-lg font-bold text-gray-800">Caisse</h1>
      </div>
      <div class="flex items-center gap-4 text-sm text-gray-600">
        <span v-if="sessionOpen" class="flex items-center gap-2 text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full font-medium border border-emerald-100">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          Caisse Ouverte
        </span>
        <span v-else class="flex items-center gap-2 text-rose-600 bg-rose-50 px-3 py-1 rounded-full font-medium border border-rose-100">
          <span class="w-2 h-2 rounded-full bg-rose-500"></span>
          Caisse Fermée
        </span>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-hidden flex">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  session: Object
});

const sessionOpen = computed(() => props.session && props.session.status === 'open');
</script>
