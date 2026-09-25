<template>
  <div class="h-screen flex flex-col bg-surface-secondary text-text-primary overflow-hidden antialiased">
    <!-- Header -->
    <header class="relative bg-surface/85 backdrop-blur-xl border-b border-border/70 shrink-0 z-30">
      <div class="absolute inset-x-0 top-0 h-[3px] bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-500" />
      <div class="flex items-center gap-3 px-4 h-16 max-w-[1600px] mx-auto w-full">
        <router-link to="/dashboard" class="flex items-center justify-center w-10 h-10 rounded-2xl border border-border bg-surface text-text-secondary hover:text-emerald-600 hover:border-emerald-400/50 shadow-sm active:scale-95 transition" :title="$t('common.back_to_dashboard')">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" /></svg>
        </router-link>
        <AppLogo :size="38" />
        <div class="min-w-0 leading-tight hidden min-[400px]:block">
          <h1 class="text-[15px] font-black tracking-tight truncate">{{ $t('page.pos.title') }}</h1>
          <p class="text-[11px] font-semibold text-text-tertiary truncate">{{ companyName }}</p>
        </div>

        <div class="flex items-center gap-2 ml-auto">
          <span v-if="sessionOpen" class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-extrabold text-emerald-600">
            <span class="relative flex h-2 w-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60" />
              <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500" />
            </span>
            {{ $t('page.pos.open_label') }} · {{ openedAt }}
          </span>
          <span v-else class="hidden md:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-xs font-extrabold text-rose-600">
            <span class="w-2 h-2 rounded-full bg-rose-500" />
            {{ $t('page.pos.closed_label') }}
          </span>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-secondary border border-border text-xs font-black tabular-nums text-text-primary">
            <svg class="w-3.5 h-3.5 text-text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ clock }}
          </span>
          <router-link to="/pos/sessions" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-border bg-surface text-[13px] font-extrabold text-text-secondary hover:text-text-primary hover:border-emerald-400/50 shadow-sm active:scale-95 transition">
            {{ $t('page.pos_sessions.title') }}
          </router-link>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-hidden flex min-h-0">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import AppLogo from '../../Components/AppLogo.vue';

const props = defineProps({
  session: Object
});

const sessionOpen = computed(() => props.session && props.session.status === 'open');
const openedAt = computed(() => {
  const d = props.session?.opened_at || props.session?.created_at;
  if (!d) return '';
  return new Date(d).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
});
const companyName = computed(() => {
  try {
    const c = JSON.parse(localStorage.getItem('company') || '{}');
    return c.name || '';
  } catch { return ''; }
});

const clock = ref('');
let timer = null;
function tick() {
  clock.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
onMounted(() => { tick(); timer = setInterval(tick, 1000); });
onUnmounted(() => clearInterval(timer));
</script>
