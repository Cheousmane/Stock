<template>
  <div class="flex h-screen bg-surface-secondary text-text-primary antialiased">
    <!-- Mobile backdrop -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="sidebarOpen = false" />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col bg-sidebar-surface border-r border-sidebar-border transition-[width,transform] duration-200 ease-out lg:translate-x-0 sidebar-transition"
      :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-[68px]' : 'lg:w-64', 'w-64']"
    >
      <!-- Logo -->
      <div class="flex items-center h-14 px-3 border-b border-sidebar-border shrink-0 sidebar-transition">
        <button @click="sidebarOpen = false" class="p-1.5 rounded-lg lg:hidden hover:bg-sidebar-surface-hover sidebar-transition">
          <span class="flex items-center justify-center w-4 h-4 text-sidebar-text-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </span>
        </button>
        <router-link to="/dashboard" class="flex items-center gap-2.5 min-w-0">
          <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-lg shadow-emerald-500/20 shrink-0">
            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
          </span>
          <div class="sidebar-label overflow-hidden transition-all duration-150 ease-out" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">
            <div class="min-w-0 whitespace-nowrap">
              <p class="text-sm font-semibold text-sidebar-text truncate sidebar-transition">{{ abbreviatedName }}</p>
              <p class="text-[10px] font-medium text-sidebar-text-secondary truncate tracking-wider sidebar-transition">{{ companyName }}</p>
            </div>
          </div>
        </router-link>
        <button
          @click="sidebarCollapsed = !sidebarCollapsed"
          class="hidden lg:flex items-center justify-center ml-auto w-6 h-6 rounded-md text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover sidebar-transition"
          :class="{ 'rotate-180': sidebarCollapsed }"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5" />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 py-3 overflow-y-auto overflow-x-hidden">
        <div class="space-y-0.5">
          <template v-for="item in navItems" :key="item.name || item.key">
            <div v-if="item.divider" class="sidebar-label overflow-hidden transition-all duration-150 ease-out" :class="sidebarCollapsed ? 'w-0 opacity-0 h-0 pt-0 pb-0' : 'w-auto opacity-100 px-3 pt-4 pb-1.5'">
              <span v-if="item.label" class="text-[10px] font-semibold tracking-widest text-sidebar-text-secondary uppercase whitespace-nowrap">{{ item.label || $t(item.key) }}</span>
            </div>
            <router-link
              v-if="!item.divider"
              :to="item.to"
              class="nav-item flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium sidebar-transition"
              :class="isActive(item.to) ? 'nav-item--active text-sidebar-text-active' : 'text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover'"
              @mouseenter="showTooltip($event, item)"
              @mouseleave="hideTooltip"
            >
              <span class="flex items-center justify-center w-5 h-5 shrink-0 sidebar-transition" :class="isActive(item.to) ? 'text-sidebar-text-active' : 'text-sidebar-text-secondary group-hover:text-sidebar-text'">
                <component :is="item.icon" class="w-5 h-5" />
              </span>
              <span class="sidebar-label truncate overflow-hidden transition-all duration-150 ease-out" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">{{ item.label || $t(item.key) }}</span>
              <span v-if="item.badge" class="sidebar-label flex-shrink-0 transition-all duration-150 ease-out" :class="sidebarCollapsed ? 'w-0 opacity-0 scale-0' : 'w-auto opacity-100 scale-100'">
                <span class="px-1.5 py-0.5 text-[10px] font-semibold rounded-full bg-emerald-500/20 text-emerald-400 whitespace-nowrap">{{ item.badge }}</span>
              </span>
            </router-link>
          </template>
        </div>
      </nav>

      <!-- Bottom -->
      <div class="p-2 border-t border-sidebar-border sidebar-transition">
        <button
          @click="logout"
          class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm font-medium text-sidebar-text-secondary hover:text-red-400 hover:bg-red-500/10 sidebar-transition"
          @mouseenter="showTooltip($event, { label: $t('common.logout') })"
          @mouseleave="hideTooltip"
        >
          <span class="flex items-center justify-center w-5 h-5 shrink-0 text-sidebar-text-secondary group-hover:text-red-400 sidebar-transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
          </span>
          <span class="sidebar-label truncate overflow-hidden transition-all duration-150 ease-out" :class="sidebarCollapsed ? 'w-0 opacity-0 -translate-x-2' : 'w-auto opacity-100 translate-x-0'">{{ $t('common.logout') }}</span>
        </button>
      </div>
    </aside>

    <!-- Tooltip -->
    <Teleport to="body">
      <div
        v-if="tooltip.text"
        :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px' }"
        class="fixed z-[99999] px-2.5 py-1.5 text-xs font-medium bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 rounded-lg shadow-lg whitespace-nowrap pointer-events-none border border-white/10 dark:border-gray-300 -translate-y-1/2 tooltip-in-enter-active"
      >
        {{ tooltip.text }}
      </div>
    </Teleport>

    <!-- Main area -->
    <div class="flex flex-col flex-1 min-w-0 transition-[margin] duration-200 ease-out" :class="sidebarCollapsed ? 'lg:ml-[68px]' : 'lg:ml-64'">
      <!-- Top navbar -->
      <header class="sticky top-0 z-30 flex items-center h-14 px-4 lg:px-6 bg-surface/70 backdrop-blur-xl border-b border-border">
        <button @click="sidebarOpen = true" class="flex items-center justify-center w-8 h-8 -ml-1.5 text-text-secondary rounded-lg lg:hidden hover:bg-surface-tertiary">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>

        <div class="flex items-center gap-2 ml-auto">
          <!-- Global search -->
          <button
            @click="searchOpen = true"
            class="flex items-center gap-2 px-3 py-1.5 text-xs text-text-tertiary bg-surface-tertiary rounded-lg hover:bg-surface-tertiary/80 hidden sm:flex"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            Rechercher...
            <kbd class="hidden lg:inline-flex items-center gap-0.5 px-1.5 py-0.5 text-[10px] text-text-tertiary bg-surface border border-border rounded ml-4">⌘K</kbd>
          </button>
          <button
            @click="searchOpen = true"
            class="flex items-center justify-center w-8 h-8 text-text-secondary rounded-lg sm:hidden hover:bg-surface-tertiary"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
          </button>

          <LanguageSwitcher />
          <ThemeToggle />

          <!-- User dropdown -->
          <div class="relative" v-click-outside="() => dropdownOpen = false">
            <button
              @click="dropdownOpen = !dropdownOpen"
              class="flex items-center gap-2 px-2 py-1.5 text-sm font-medium text-text-secondary rounded-lg hover:bg-surface-tertiary"
            >
              <span class="flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white text-xs font-semibold shrink-0">{{ userInitial }}</span>
              <span class="hidden sm:inline text-text-primary text-sm max-w-[100px] truncate">{{ userName }}</span>
              <svg class="w-3.5 h-3.5 text-text-tertiary" :class="{ 'rotate-180': dropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
            <div v-if="dropdownOpen" class="absolute right-0 z-50 mt-2 w-56 bg-surface border border-border rounded-xl shadow-dropdown py-1 overflow-hidden">
              <div class="px-4 py-3 border-b border-border">
                <p class="text-sm font-semibold text-text-primary truncate">{{ userName }}</p>
                <p class="text-xs text-text-tertiary truncate mt-0.5">{{ userEmail }}</p>
              </div>
              <router-link to="/settings" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-text-secondary hover:bg-surface-tertiary">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ $t('nav.settings') }}
                </router-link>
                <button @click="logout" class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-left text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                  </svg>
                  {{ $t('common.logout') }}
                </button>
              </div>
            </div>
          </div>
      </header>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto">
        <slot name="header" />
        <main class="p-4 lg:p-6">
          <slot />
        </main>
      </div>
    </div>

    <!-- Search modal -->
    <Teleport to="body">
      <div v-if="searchOpen" class="fixed inset-0 z-[200] flex items-start justify-center pt-[15vh]" @click.self="searchOpen = false">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm modal-fade-enter-active" />
        <div class="relative w-full max-w-xl bg-surface border border-border rounded-2xl shadow-modal overflow-hidden modal-pop-enter-active">
            <div class="flex items-center gap-3 px-4 border-b border-border">
              <svg class="w-5 h-5 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
              <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher une page, un client, une facture..."
                class="flex-1 py-3.5 text-sm bg-transparent border-0 outline-none text-text-primary placeholder:text-text-tertiary"
              />
              <kbd class="hidden sm:inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium text-text-tertiary bg-surface-tertiary rounded">ESC</kbd>
            </div>
            <div class="p-2 max-h-80 overflow-y-auto">
              <p v-if="searchQuery.length < 2" class="py-8 text-center text-sm text-text-tertiary">
                Tapez au moins 2 caractères pour lancer la recherche
              </p>
              <div v-else-if="searchResults.length === 0" class="py-8 text-center text-sm text-text-tertiary">
                Aucun résultat pour "{{ searchQuery }}"
              </div>
              <div v-else class="space-y-0.5">
                <div v-for="r in searchResults" :key="r.url" @click="navigateToSearch(r)" class="group flex items-center gap-3 px-3 py-2.5 rounded-lg cursor-pointer transition-all duration-150 hover:bg-surface-tertiary hover:translate-x-0.5">
                  <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-surface-tertiary shrink-0 transition-transform duration-300 group-hover:scale-110">
                    <component :is="r.icon" class="w-4 h-4 text-text-secondary" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-text-primary truncate">{{ r.title }}</p>
                    <p class="text-xs text-text-tertiary truncate">{{ r.subtitle }}</p>
                  </div>
                  <svg class="w-4 h-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                  </svg>
                </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import ThemeToggle from './ThemeToggle.vue'
import LanguageSwitcher from './LanguageSwitcher.vue'
import {
  HomeIcon, ComputerDesktopIcon, CubeIcon, UsersIcon, DocumentTextIcon,
  DocumentDuplicateIcon, TruckIcon, CreditCardIcon, ArchiveBoxIcon,
  BanknotesIcon, TagIcon, BuildingStorefrontIcon, ScaleIcon,
  CalendarDaysIcon, BriefcaseIcon, ShoppingCartIcon,
  ArrowUturnLeftIcon, Cog6ToothIcon, ClockIcon, CurrencyDollarIcon, BuildingOfficeIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') !== 'false')
const dropdownOpen = ref(false)
const tooltip = ref({ text: '', x: 0, y: 0 })
const searchOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref(null)
const searchResults = ref([])
const userName = ref('')
const userEmail = ref('')
const userPermissions = ref([])
const companyName = ref('')
const isSuperAdmin = ref(false)

watch(sidebarCollapsed, (v) => localStorage.setItem('sidebarCollapsed', v))
watch(searchOpen, (v) => { if (v) nextTick(() => searchInput.value?.focus()) })

document.addEventListener('keydown', (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); searchOpen.value = true }
  if (e.key === 'Escape') searchOpen.value = false
})

const userInitial = computed(() => (userName.value || 'U').charAt(0).toUpperCase())

const abbreviatedName = computed(() => {
  const words = companyName.value.trim().split(/\s+/)
  if (words.length <= 1) return companyName.value || 'FactureStock'
  return words.map(w => w.charAt(0).toUpperCase()).join('')
})

const allNavItems = [
  { label: 'Tableau de bord', to: '/dashboard', icon: HomeIcon, key: 'nav.dashboard', permission: 'view_dashboard' },
  { divider: true, key: 'nav.sales', label: 'Ventes' },
  { label: 'Caisse (POS)', to: '/pos', icon: ComputerDesktopIcon, key: 'nav.pos', permission: null },
  { label: 'Sessions caisse', to: '/pos/sessions', icon: ClockIcon, key: 'nav.pos_sessions', permission: null },
  { label: 'Produits', to: '/products', icon: CubeIcon, key: 'nav.products', permission: 'view_product' },
  { label: 'Clients', to: '/customers', icon: UsersIcon, key: 'nav.customers', permission: 'view_customer' },
  { label: 'Fournisseurs', to: '/suppliers', icon: BriefcaseIcon, key: 'nav.suppliers', permission: 'view_supplier' },
  { divider: true, key: 'nav.documentation', label: 'Documents' },
  { label: 'Factures', to: '/invoices', icon: DocumentTextIcon, key: 'nav.invoices', permission: 'view_invoice' },
  { label: 'Devis', to: '/quotes', icon: DocumentDuplicateIcon, key: 'nav.quotes', permission: 'view_quote' },
  { label: 'Avoirs', to: '/credit-notes', icon: ArrowUturnLeftIcon, key: 'nav.credit_notes', permission: 'view_credit_note' },
  { label: 'Bons commande', to: '/purchase-orders', icon: ShoppingCartIcon, key: 'nav.purchase_orders', permission: 'view_purchase_order' },
  { label: 'Bons livraison', to: '/delivery-notes', icon: TruckIcon, key: 'nav.delivery_notes', permission: 'view_delivery_note' },
  { divider: true, key: 'nav.finance', label: 'Finance' },
  { label: 'Paiements', to: '/payments', icon: CreditCardIcon, key: 'nav.payments', permission: 'view_payment' },
  { label: 'Dépenses', to: '/expenses', icon: BanknotesIcon, key: 'nav.expenses', permission: 'view_expenses' },
  { label: 'Capital', to: '/capital', icon: CurrencyDollarIcon, key: 'nav.capital', permission: null },
  { divider: true, key: 'nav.management', label: 'Gestion' },
  { label: 'Stock', to: '/stock', icon: ArchiveBoxIcon, key: 'nav.stock', permission: 'view_stock' },
  { label: 'Catégories', to: '/categories', icon: TagIcon, key: 'nav.categories', permission: 'view_category' },
  { label: 'Unités', to: '/units', icon: ScaleIcon, key: 'nav.units', permission: 'view_unit' },
  { label: 'Entrepôts', to: '/warehouses', icon: BuildingStorefrontIcon, key: 'nav.warehouses', permission: 'view_warehouse' },
  { label: 'Événements', to: '/events', icon: CalendarDaysIcon, key: 'nav.events', permission: 'view_events' },
  { divider: true, key: 'nav.system', label: 'Système' },
  { label: 'Panel Admin', to: '/admin/dashboard', icon: Cog6ToothIcon, key: 'nav.admin_panel', permission: null, superAdminOnly: true },
  { label: 'Utilisateurs', to: '/users', icon: UsersIcon, key: 'nav.users', permission: 'manage_users' },
  { label: 'Activité', to: '/activity-logs', icon: ClockIcon, key: 'nav.activity_logs', permission: 'view_activity_logs' },
  { label: 'Paramètres', to: '/settings', icon: Cog6ToothIcon, key: 'nav.settings', permission: 'manage_settings' },
]

function can(perm) { return !perm || userPermissions.value.includes(perm) }

const navItems = computed(() => {
  const visible = allNavItems.filter(i => {
    if (i.divider) return true
    if (i.superAdminOnly) return isSuperAdmin.value
    return can(i.permission)
  })
  return visible.filter((i, idx, arr) => {
    if (!i.divider) return true
    return arr.slice(idx + 1).some(x => !x.divider)
  })
})

function isActive(to) {
  if (to === '/') return route.path === '/'
  return route.path.startsWith(to)
}

async function logout() {
  try {
    // Revocation du token cote serveur avant nettoyage local
    await axios.post('/auth/logout')
  } catch {} finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push({ name: 'Login' })
  }
}

function showTooltip(event, item) {
  if (!sidebarCollapsed.value) return
  const rect = event.currentTarget.getBoundingClientRect()
  tooltip.value = {
    text: item.label || (item.key ? t(item.key) : ''),
    x: rect.right + 8,
    y: rect.top + rect.height / 2,
  }
}

function hideTooltip() {
  tooltip.value = { text: '', x: 0, y: 0 }
}

const searchPages = [
  { title: 'Tableau de bord', subtitle: 'Vue d\'ensemble et indicateurs', url: '/dashboard', icon: HomeIcon },
  { title: 'Caisse (POS)', subtitle: 'Point de vente', url: '/pos', icon: ComputerDesktopIcon },
  { title: 'Sessions caisse', subtitle: 'Historique des sessions', url: '/pos/sessions', icon: ClockIcon },
  { title: 'Produits', subtitle: 'Gérer le catalogue', url: '/products', icon: CubeIcon },
  { title: 'Clients', subtitle: 'Gérer les clients', url: '/customers', icon: UsersIcon },
  { title: 'Fournisseurs', subtitle: 'Gérer les fournisseurs', url: '/suppliers', icon: BriefcaseIcon },
  { title: 'Factures', subtitle: 'Gérer les factures', url: '/invoices', icon: DocumentTextIcon },
  { title: 'Devis', subtitle: 'Gérer les devis', url: '/quotes', icon: DocumentDuplicateIcon },
  { title: 'Avoirs', subtitle: 'Gérer les avoirs', url: '/credit-notes', icon: ArrowUturnLeftIcon },
  { title: 'Bons de commande', subtitle: 'Gérer les bons de commande', url: '/purchase-orders', icon: ShoppingCartIcon },
  { title: 'Bons de livraison', subtitle: 'Gérer les bons de livraison', url: '/delivery-notes', icon: TruckIcon },
  { title: 'Paiements', subtitle: 'Historique des paiements', url: '/payments', icon: CreditCardIcon },
  { title: 'Dépenses', subtitle: 'Suivi des dépenses', url: '/expenses', icon: BanknotesIcon },
  { title: 'Capital', subtitle: 'Gestion du capital', url: '/capital', icon: CurrencyDollarIcon },
  { title: 'Stock', subtitle: 'Gestion des stocks', url: '/stock', icon: ArchiveBoxIcon },
  { title: 'Catégories', subtitle: 'Catégories de produits', url: '/categories', icon: TagIcon },
  { title: 'Unités', subtitle: 'Unités de mesure', url: '/units', icon: ScaleIcon },
  { title: 'Entrepôts', subtitle: 'Gestion des dépôts', url: '/warehouses', icon: BuildingStorefrontIcon },
  { title: 'Utilisateurs', subtitle: 'Gestion des utilisateurs', url: '/users', icon: Cog6ToothIcon },
  { title: 'Journal activité', subtitle: 'Logs système', url: '/activity-logs', icon: ClockIcon },
  { title: 'Paramètres', subtitle: 'Configuration', url: '/settings', icon: Cog6ToothIcon },
  { title: 'Panel Admin', subtitle: 'Administration globale', url: '/admin/dashboard', icon: Cog6ToothIcon },
  { title: 'Admin Entreprises', subtitle: 'Gérer les entreprises', url: '/admin/companies', icon: BuildingOfficeIcon },
  { title: 'Admin Connexions', subtitle: 'Journal des connexions', url: '/admin/login-logs', icon: ClockIcon },
]

watch(searchQuery, async (q) => {
  if (q.length < 2) { searchResults.value = []; return }
  const ql = q.toLowerCase()
  searchResults.value = searchPages.filter(p =>
    p.title.toLowerCase().includes(ql) || p.subtitle.toLowerCase().includes(ql)
  )
})

function navigateToSearch(r) {
  searchOpen.value = false
  searchQuery.value = ''
  router.push(r.url)
}

const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutside = (event) => { if (!el.contains(event.target)) binding.value() }
    document.addEventListener('click', el.__clickOutside)
  },
  unmounted(el) { document.removeEventListener('click', el.__clickOutside) },
}

onMounted(async () => {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    if (u.name) userName.value = u.name
    if (u.email) userEmail.value = u.email
    if (u.is_super_admin) isSuperAdmin.value = u.is_super_admin
    if (u.permissions?.length) userPermissions.value = u.permissions
    else {
      const { data } = await axios.get('/auth/me')
      const fresh = data.user ?? data.data ?? data
      localStorage.setItem('user', JSON.stringify(fresh))
      if (fresh.permissions) userPermissions.value = fresh.permissions
      if (fresh.name) userName.value = fresh.name
      if (fresh.email) userEmail.value = fresh.email
      if (fresh.is_super_admin) isSuperAdmin.value = fresh.is_super_admin
    }
  } catch {}
  try {
    const cachedCompany = JSON.parse(localStorage.getItem('company') || '{}')
    if (cachedCompany.name) {
      companyName.value = cachedCompany.name
    } else {
      const { data } = await axios.get('/company')
      const c = data.data ?? data
      const name = c.company_name || c.name || ''
      companyName.value = name
      localStorage.setItem('company', JSON.stringify({ name }))
    }
  } catch {}
})
</script>
