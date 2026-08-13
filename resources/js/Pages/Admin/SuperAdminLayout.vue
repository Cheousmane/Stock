<template>
  <div class="flex h-screen bg-surface-secondary text-text-primary antialiased">
    <!-- Mobile backdrop -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="sidebarOpen = false" />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-sidebar-surface border-r border-sidebar-border transition-[width,transform] duration-200 ease-out lg:translate-x-0"
      :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-[68px]' : 'lg:w-64', 'w-64']"
    >
      <div class="flex items-center h-14 px-3 border-b border-sidebar-border shrink-0">
        <button @click="sidebarOpen = false" class="p-1.5 rounded-lg lg:hidden hover:bg-sidebar-surface-hover">
          <span class="flex items-center justify-center w-4 h-4 text-sidebar-text-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </span>
        </button>
        <router-link to="/admin/dashboard" class="flex items-center gap-2.5 min-w-0">
          <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-500/20 shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
          </span>
          <div class="overflow-hidden ease-[cubic-bezier(0.25,0.1,0.25,1)]" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">
            <div class="min-w-0 whitespace-nowrap">
              <p class="text-sm font-semibold text-sidebar-text truncate">Super Admin</p>
              <p class="text-[10px] font-medium text-sidebar-text-secondary truncate tracking-wider">SIDIBE CORPORATE</p>
            </div>
          </div>
        </router-link>
        <button
          @click="sidebarCollapsed = !sidebarCollapsed"
          class="hidden lg:flex items-center justify-center ml-auto w-6 h-6 rounded-md text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover"
          :class="{ 'rotate-180': sidebarCollapsed }"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5" />
          </svg>
        </button>
      </div>

      <nav class="flex-1 px-2 py-3 overflow-y-auto overflow-x-hidden">
        <div class="space-y-0.5">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="nav-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium"
            :class="isActive(item.to) ? 'nav-item--active text-sidebar-text-active' : 'text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover'"
          >
            <span class="flex items-center justify-center w-5 h-5 shrink-0">
              <component :is="item.icon" class="w-5 h-5" />
            </span>
            <span class="truncate overflow-hidden ease-[cubic-bezier(0.25,0.1,0.25,1)]" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">{{ item.label }}</span>
          </router-link>
        </div>
      </nav>

      <div class="p-2 border-t border-sidebar-border">
        <router-link to="/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover transition-colors">
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
          </svg>
          <span class="truncate overflow-hidden ease-[cubic-bezier(0.25,0.1,0.25,1)]" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">Retour app</span>
        </router-link>
        <button @click="logout" class="flex items-center gap-3 w-full px-3 py-2 mt-1 rounded-lg text-sm font-medium text-sidebar-text-secondary hover:text-red-400 hover:bg-red-500/10 transition-colors">
          <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
          <span class="truncate overflow-hidden ease-[cubic-bezier(0.25,0.1,0.25,1)]" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">Déconnexion</span>
        </button>
      </div>
    </aside>

    <div class="flex flex-col flex-1 min-w-0 transition-[margin] duration-300 ease-[cubic-bezier(0.25,0.1,0.25,1)]" :class="sidebarCollapsed ? 'lg:ml-[68px]' : 'lg:ml-64'">
      <header class="sticky top-0 z-30 flex items-center h-14 px-4 lg:px-6 bg-surface/70 backdrop-blur-xl border-b border-border">
        <button @click="sidebarOpen = true" class="flex items-center justify-center w-8 h-8 -ml-1.5 text-text-secondary rounded-lg lg:hidden hover:bg-surface-tertiary">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>
        <h1 class="text-lg font-semibold text-text-primary lg:hidden">Administration</h1>
        <div class="flex items-center gap-2 ml-auto">
          <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-semibold">{{ userInitial }}</span>
          <span class="text-sm text-text-primary">{{ userName }}</span>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto">
        <main class="p-6">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import {
  HomeIcon, BuildingOfficeIcon, ClockIcon, UsersIcon, Cog6ToothIcon, ShieldExclamationIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const userName = ref('')

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('superAdminSidebarCollapsed') !== 'false')

watch(sidebarCollapsed, (v) => localStorage.setItem('superAdminSidebarCollapsed', v))

const navItems = [
  { label: 'Tableau de bord', to: '/admin/dashboard', icon: HomeIcon },
  { label: 'Entreprises', to: '/admin/companies', icon: BuildingOfficeIcon },
  { label: 'Connexions', to: '/admin/login-logs', icon: ClockIcon },
  { label: 'Interférences', to: '/admin/activity-logs', icon: ShieldExclamationIcon },
  { label: 'Utilisateurs', to: '/admin/users', icon: UsersIcon },
]

const userInitial = computed(() => (userName.value || 'A').charAt(0).toUpperCase())

function isActive(to) {
  return route.path === to || route.path.startsWith(to + '/')
}

async function logout() {
  try { await axios.post('/auth/logout') } catch {}
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push({ name: 'Login' })
}

onMounted(() => {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    if (u.name) userName.value = u.name
  } catch {}
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>