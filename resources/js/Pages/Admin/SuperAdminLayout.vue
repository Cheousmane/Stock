<template>
  <div class="min-h-screen bg-surface-secondary text-text-primary antialiased selection:bg-emerald-500/20">
    <!-- Ambient background -->
    <div class="pointer-events-none fixed inset-0 z-0" aria-hidden="true">
      <div class="absolute -top-32 left-1/4 h-72 w-72 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-500/10" />
      <div class="absolute top-1/3 -right-24 h-80 w-80 rounded-full bg-teal-500/10 blur-3xl" />
    </div>

    <!-- Mobile backdrop -->
    <Transition name="fade">
      <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-neutral-950/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col w-[272px] border-r border-sidebar-border bg-sidebar-surface transition-[width,transform] duration-100 ease-out shadow-[4px_0_16px_-12px_rgb(0_0_0/0.15)]"
      :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        sidebarCollapsed ? 'lg:w-[84px]' : 'lg:w-[272px]'
      ]"
    >
      <!-- Brand -->
      <div class="flex items-center gap-3 h-[68px] px-4 border-b border-sidebar-border shrink-0">
        <router-link to="/admin/dashboard" class="group flex items-center gap-3 min-w-0 flex-1">
          <AppLogo :size="40" />
          <span class="min-w-0 transition-opacity duration-100 overflow-hidden" :class="sidebarCollapsed ? 'lg:w-0 lg:opacity-0' : 'w-auto opacity-100'">
            <span class="block text-[15px] font-extrabold tracking-tight text-sidebar-text leading-tight whitespace-nowrap">Super Admin</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-sidebar-text-secondary whitespace-nowrap">
              Sidibe Corporate
              <span class="inline-flex items-center px-1.5 py-px rounded-md bg-emerald-500/10 text-emerald-600 text-[9px] border border-emerald-500/20">PRO</span>
            </span>
          </span>
        </router-link>
        <button
          @click="sidebarCollapsed = !sidebarCollapsed"
          class="hidden lg:flex items-center justify-center w-7 h-7 rounded-lg text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover border border-transparent hover:border-sidebar-border transition-all"
          :class="{ 'rotate-180': sidebarCollapsed }"
          :title="$t('header.collapse')"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5" />
          </svg>
        </button>
        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg hover:bg-sidebar-surface-hover text-sidebar-text-secondary">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>

      <!-- System status -->
      <div class="px-4 pt-4 transition-opacity duration-100 overflow-hidden" :class="sidebarCollapsed ? 'lg:px-2 lg:pt-3' : ''">
        <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-2xl bg-emerald-500/[0.07] border border-emerald-500/15" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
          <span class="relative flex h-2.5 w-2.5 shrink-0">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
          </span>
          <div class="min-w-0 leading-tight" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $t('admin.operational') }}</p>
            <p class="text-[11px] text-sidebar-text-secondary">{{ $t('admin.all_services') }} · {{ time }}</p>
          </div>
        </div>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <div v-for="section in navSections" :key="section.title">
          <p
            class="px-3 mb-2 text-[10px] font-extrabold uppercase tracking-[0.16em] text-sidebar-text-secondary/80 transition-all"
            :class="sidebarCollapsed ? 'lg:text-center lg:px-0' : ''"
          >{{ sidebarCollapsed ? '· · ·' : section.title }}</p>
          <div class="space-y-1">
            <router-link
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              :title="sidebarCollapsed ? item.label : ''"
              class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-100"
              :class="[
                isActive(item.to)
                  ? 'bg-gradient-to-r from-emerald-500/12 to-teal-500/[0.06] text-sidebar-text-active shadow-[inset_0_0_0_1px_rgb(16_185_129/0.18)]'
                  : 'text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover',
                sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''
              ]"
            >
              <span
                v-if="isActive(item.to)"
                class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-gradient-to-b from-emerald-400 to-teal-600"
              />
              <span
                class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0 transition-transform duration-100 group-active:scale-95"
                :class="isActive(item.to)
                  ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25'
                  : 'bg-sidebar-surface-hover/60 text-sidebar-text-secondary group-hover:text-sidebar-text group-hover:bg-sidebar-surface-hover border border-sidebar-border/60'"
              >
                <component :is="item.icon" class="w-[18px] h-[18px]" />
              </span>
              <span class="flex-1 min-w-0 truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ item.label }}</span>
              <span
                v-if="item.badge && !sidebarCollapsed"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold"
                :class="item.badgeTone"
              >{{ item.badge }}</span>
              <svg v-if="isActive(item.to) && !sidebarCollapsed" class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            </router-link>
          </div>
        </div>
      </nav>

      <!-- Bottom -->
      <div class="p-3 border-t border-sidebar-border space-y-1 bg-sidebar-surface/60">
        <router-link
          to="/dashboard"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover border border-dashed border-sidebar-border hover:border-solid transition-all"
          :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
          :title="$t('admin.back_to_app')"
        >
          <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/15 shrink-0">
            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
          </span>
          <span class="truncate" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $t('admin.back_app') }}</span>
        </router-link>
        <div
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-sidebar-surface-hover/50 border border-sidebar-border/70"
          :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
        >
          <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-white text-sm font-extrabold shrink-0 shadow-md shadow-emerald-500/20">{{ userInitial }}</span>
          <div class="min-w-0 flex-1 leading-tight" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <p class="text-[13px] font-bold text-sidebar-text truncate">{{ userName || 'Administrateur' }}</p>
            <p class="text-[11px] text-emerald-600 font-semibold flex items-center gap-1">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 1a4 4 0 00-4 4v2H5a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-1V5a4 4 0 00-4-4zm-2 6V5a2 2 0 114 0v2H8z" clip-rule="evenodd"/></svg>
              {{ $t('admin.super_admin') }}
            </p>
          </div>
          <button @click="logout" :title="$t('common.logout')" class="p-2 rounded-lg text-sidebar-text-secondary hover:text-rose-500 hover:bg-rose-500/10 transition-colors shrink-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main column -->
    <div class="relative z-10 flex flex-col min-h-screen min-w-0 transition-[margin] duration-100 ease-out" :class="sidebarCollapsed ? 'lg:ml-[84px]' : 'lg:ml-[272px]'">
      <!-- Topbar -->
      <header class="sticky top-0 z-30 border-b border-border bg-surface/75 backdrop-blur-xl">
        <div class="flex items-center gap-3 h-[68px] px-4 lg:px-8 max-w-[1440px] mx-auto w-full">
          <button @click="sidebarOpen = true" class="lg:hidden flex items-center justify-center w-10 h-10 rounded-xl border border-border bg-surface text-text-secondary hover:text-text-primary shadow-sm active:scale-95 transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
          </button>

          <!-- Breadcrumb -->
          <div class="min-w-0 hidden sm:block">
            <nav class="flex items-center gap-1.5 text-xs font-semibold text-text-tertiary">
              <span class="hover:text-text-secondary transition-colors">Administration</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
              <span class="text-text-primary truncate">{{ currentPage }}</span>
            </nav>
            <h1 class="text-lg font-extrabold tracking-tight text-text-primary leading-tight truncate">{{ currentPage }}</h1>
          </div>
          <h1 class="sm:hidden text-base font-extrabold text-text-primary truncate">{{ currentPage }}</h1>

          <div class="flex items-center gap-2 ml-auto">
            <!-- Search -->
            <button @click="focusSearch" class="hidden md:flex items-center gap-2.5 pl-3.5 pr-2 py-2 w-64 rounded-xl border border-border bg-surface-secondary/70 text-sm text-text-tertiary hover:border-emerald-400/50 hover:bg-surface transition-all shadow-sm group">
              <svg class="w-4 h-4 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
              <span class="flex-1 text-left truncate">{{ $t('admin.search') }}</span>
              <kbd class="hidden lg:inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface border border-border text-[10px] font-bold text-text-tertiary">⌘K</kbd>
            </button>

            <span class="hidden xl:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-extrabold text-emerald-700 dark:text-emerald-400">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />               {{ $t('admin.live') }}
            </span>

            <ThemeToggle />

            <!-- Notifications -->
            <button class="relative flex items-center justify-center w-10 h-10 rounded-xl border border-border bg-surface text-text-secondary hover:text-text-primary hover:border-emerald-400/40 shadow-sm active:scale-95 transition" :title="$t('admin.notifications')">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
              <span v-if="alertCount > 0" class="absolute -top-1 -right-1 min-w-[20px] h-5 px-1 rounded-full bg-rose-500 text-white text-[10px] font-extrabold flex items-center justify-center border-2 border-surface shadow">{{ alertCount > 9 ? '9+' : alertCount }}</span>
            </button>

            <!-- User menu -->
            <div class="relative" ref="menuRef">
              <button @click="menuOpen = !menuOpen" class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-xl border border-border bg-surface shadow-sm hover:border-emerald-400/40 hover:shadow-md active:scale-[0.98] transition-all">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-600 text-white text-sm font-extrabold shadow">{{ userInitial }}</span>
                <span class="hidden sm:block text-left leading-tight">
                  <span class="block text-[13px] font-bold text-text-primary max-w-[120px] truncate">{{ userName || 'Admin' }}</span>
                  <span class="block text-[10px] font-bold uppercase tracking-wider text-emerald-600">{{ $t('admin.super_admin') }}</span>
                </span>
                <svg class="w-4 h-4 text-text-tertiary transition-transform" :class="{ 'rotate-180': menuOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
              </button>
              <Transition name="pop">
                <div v-if="menuOpen" class="absolute right-0 mt-2 w-56 rounded-2xl border border-border bg-surface shadow-xl shadow-neutral-950/5 p-1.5 z-50">
                  <div class="px-3 py-2.5 border-b border-border/60 mb-1">
                    <p class="text-sm font-bold text-text-primary truncate">{{ userName }}</p>
                    <p class="text-xs text-text-tertiary truncate">{{ userEmail }}</p>
                  </div>
                  <router-link to="/dashboard" @click="menuOpen = false" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-semibold text-text-secondary hover:bg-surface-tertiary hover:text-text-primary transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                    {{ $t('admin.back_to_app') }}
                  </router-link>
                  <button @click="logout" class="flex items-center gap-2.5 w-full px-3 py-2 rounded-xl text-sm font-semibold text-rose-600 hover:bg-rose-500/10 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                    {{ $t('common.logout') }}
                  </button>
                </div>
              </Transition>
            </div>
          </div>
        </div>
      </header>

      <!-- Content -->
      <div class="flex-1 w-full">
        <main class="px-4 lg:px-8 py-6 lg:py-8 max-w-[1440px] mx-auto w-full">
          <Transition name="page" mode="out-in">
            <div :key="route.path">
              <slot />
            </div>
          </Transition>
          <footer class="mt-10 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] font-medium text-text-tertiary border-t border-border/60 pt-5">
            <p class="flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
              Sidibe Corporate · {{ $t('admin.restricted') }}
            </p>
            <p class="tabular-nums">{{ today }} · v2.0 Pro</p>
          </footer>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
  HomeIcon, BuildingOfficeIcon, ClockIcon, UsersIcon, ShieldExclamationIcon,
  ChartBarIcon, Cog6ToothIcon
} from '@heroicons/vue/24/outline'
import { initAnalytics, setSuperAdminUser } from '@/composables/useAnalytics'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import AppLogo from '@/Components/AppLogo.vue'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const userName = ref('')
const userEmail = ref('')
const user = ref(null)
const alertCount = ref(0)

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('superAdminSidebarCollapsed') === 'true')
const menuOpen = ref(false)
const menuRef = ref(null)
const time = ref('')
const today = ref(new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }))

watch(sidebarCollapsed, (v) => localStorage.setItem('superAdminSidebarCollapsed', v))

const navSections = computed(() => [
  {
    title: t('admin.section_pilotage'),
    items: [
      { label: t('admin.dashboard'), to: '/admin/dashboard', icon: HomeIcon },
      { label: t('admin.companies'), to: '/admin/companies', icon: BuildingOfficeIcon },
      { label: t('admin.users'), to: '/admin/users', icon: UsersIcon },
    ],
  },
  {
    title: t('admin.section_securite'),
    items: [
      { label: t('admin.logins'), to: '/admin/login-logs', icon: ClockIcon, badge: alertCount.value > 0 ? String(alertCount.value) : '', badgeTone: 'bg-rose-500/10 text-rose-600 border border-rose-500/20' },
      { label: t('admin.activity'), to: '/admin/activity-logs', icon: ShieldExclamationIcon },
    ],
  },
  {
    title: t('admin.section_systeme'),
    items: [
      { label: t('admin.metrics'), to: '/admin/dashboard', icon: ChartBarIcon },
      { label: t('admin.settings'), to: '/settings', icon: Cog6ToothIcon },
    ],
  },
])

const pageTitles = computed(() => ({
  '/admin/dashboard': t('admin.dashboard'),
  '/admin/companies': t('admin.companies'),
  '/admin/users': t('admin.users'),
  '/admin/login-logs': t('admin.logins'),
  '/admin/activity-logs': t('admin.activity'),
  '/settings': t('admin.settings'),
}))
const currentPage = computed(() => pageTitles.value[route.path] || route.meta.title || t('admin.dashboard'))

const userInitial = computed(() => (userName.value || 'A').charAt(0).toUpperCase())

function isActive(to) {
  if (to === '/admin/dashboard' && route.path !== '/admin/dashboard') return false
  return route.path === to || route.path.startsWith(to + '/')
}

function focusSearch() {
  router.push('/admin/companies')
}

function tick() {
  time.value = new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function onClickOutside(e) {
  if (menuRef.value && !menuRef.value.contains(e.target)) menuOpen.value = false
}

function onKey(e) {
  if (e.key === 'Escape') { menuOpen.value = false; sidebarOpen.value = false }
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); focusSearch() }
}

async function logout() {
  menuOpen.value = false
  try { await axios.post('/auth/logout') } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push({ name: 'Login' })
}

let timer = null
onMounted(() => {
  tick()
  timer = setInterval(tick, 30000)
  document.addEventListener('click', onClickOutside)
  document.addEventListener('keydown', onKey)
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    if (u.name) userName.value = u.name
    if (u.email) userEmail.value = u.email
    if (u.id) {
      user.value = u
      initAnalytics().then(() => setSuperAdminUser(u))
    }
  } catch {}
  axios.get('/admin/dashboard').then(({ data }) => {
    const d = data.data ?? data
    const fails = Number(d?.failed_logins ?? 0)
    const noPlan = Number(d?.stats?.companies_without_plan ?? d?.companies_without_plan ?? 0)
    alertCount.value = Math.min(99, fails + (noPlan > 0 ? 1 : 0))
  }).catch(() => {})
})

onUnmounted(() => {
  clearInterval(timer)
  document.removeEventListener('click', onClickOutside)
  document.removeEventListener('keydown', onKey)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.pop-enter-active { animation: modal-pop 0.18s ease-out; }
.pop-leave-active { transition: opacity 0.12s ease; }
.pop-leave-to { opacity: 0; }
.page-enter-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.page-leave-active { transition: opacity 0.15s ease; }
.page-enter-from { opacity: 0; transform: translateY(10px); }
.page-leave-to { opacity: 0; }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgb(16 185 129 / 0.25); border-radius: 99px; }
</style>
