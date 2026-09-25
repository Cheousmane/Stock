<template>
  <div class="flex min-h-screen bg-surface-secondary text-text-primary antialiased selection:bg-emerald-500/20">
    <div class="pointer-events-none fixed inset-0 z-0" aria-hidden="true">
      <div class="absolute -top-32 left-1/4 h-72 w-72 rounded-full bg-emerald-500/[0.07] blur-3xl" />
      <div class="absolute top-1/3 -right-24 h-80 w-80 rounded-full bg-teal-500/[0.07] blur-3xl" />
    </div>
    <!-- Mobile backdrop -->
    <Transition name="fade">
      <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-neutral-950/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-50 flex flex-col w-[272px] bg-sidebar-surface border-r border-sidebar-border shadow-[4px_0_16px_-12px_rgb(0_0_0/0.15)] transition-[width,transform] duration-100 ease-out"
      :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', sidebarCollapsed ? 'lg:w-[84px]' : 'lg:w-[272px]']"
    >
      <!-- Logo -->
      <div class="flex items-center gap-2.5 h-[68px] px-4 border-b border-sidebar-border shrink-0">
        <button @click="sidebarOpen = false" class="p-2 rounded-xl lg:hidden hover:bg-sidebar-surface-hover text-sidebar-text-secondary">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <router-link to="/dashboard" class="group flex items-center gap-3 min-w-0 flex-1">
          <AppLogo :size="40" />
          <div class="min-w-0 overflow-hidden transition-opacity duration-100" :class="sidebarCollapsed ? 'lg:w-0 lg:opacity-0' : 'w-auto opacity-100'">
            <div class="whitespace-nowrap">
              <p class="text-[15px] font-extrabold tracking-tight text-sidebar-text truncate">{{ abbreviatedName }}</p>
              <p class="text-[10px] font-bold text-sidebar-text-secondary truncate tracking-[0.12em] uppercase">{{ companyName }}</p>
              <p v-if="companyPlan" class="mt-1 inline-flex items-center gap-1 text-[10px] font-extrabold text-emerald-600 truncate tracking-wider bg-emerald-500/10 border border-emerald-500/20 rounded-full px-2 py-px">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ companyPlan.name }}
              </p>
            </div>
          </div>
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
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 overflow-y-auto overflow-x-hidden custom-scrollbar">
        <div class="space-y-5">
          <template v-for="item in navItems" :key="item.name || item.key">
            <p v-if="item.divider" class="px-3 text-[10px] font-extrabold tracking-[0.16em] text-sidebar-text-secondary/80 uppercase transition-all" :class="sidebarCollapsed ? 'lg:text-center lg:px-0' : ''">
              {{ sidebarCollapsed ? '· · ·' : (item.key ? $t(item.key) : item.label) }}
            </p>
            <router-link
              v-else
              :to="item.to"
              class="group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold transition-colors duration-100"
              :class="[
                isActive(item.to)
                  ? 'bg-gradient-to-r from-emerald-500/12 to-teal-500/[0.06] text-sidebar-text-active shadow-[inset_0_0_0_1px_rgb(16_185_129/0.18)]'
                  : 'text-sidebar-text-secondary hover:text-sidebar-text hover:bg-sidebar-surface-hover',
                sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''
              ]"
              @mouseenter="showTooltip($event, item)"
              @mouseleave="hideTooltip"
            >
              <span v-if="isActive(item.to)" class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-gradient-to-b from-emerald-400 to-teal-600" />
              <span
                class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0 transition-transform duration-100 group-active:scale-95"
                :class="isActive(item.to)
                  ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-md shadow-emerald-500/25'
                  : 'bg-sidebar-surface-hover/60 text-sidebar-text-secondary group-hover:text-sidebar-text group-hover:bg-sidebar-surface-hover border border-sidebar-border/60'"
              >
                <component :is="item.icon" class="w-[18px] h-[18px]" />
              </span>
              <span class="truncate transition-opacity duration-100 overflow-hidden" :class="sidebarCollapsed ? 'lg:w-0 lg:opacity-0' : 'flex-1 opacity-100'">{{ item.key ? $t(item.key) : item.label }}</span>
              <span v-if="item.badge" class="shrink-0 transition-all" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-500/15 text-emerald-600 border border-emerald-500/20 whitespace-nowrap">{{ item.badge }}</span>
              </span>
            </router-link>
          </template>
        </div>
      </nav>

      <!-- Bottom -->
      <div class="p-3 border-t border-sidebar-border bg-sidebar-surface/60">
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-sidebar-surface-hover/50 border border-sidebar-border/70" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
          <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-white text-sm font-extrabold shrink-0 shadow-md shadow-emerald-500/20">{{ userInitial }}</span>
          <div class="min-w-0 flex-1 leading-tight" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <p class="text-[13px] font-bold text-sidebar-text truncate">{{ userName || 'Utilisateur' }}</p>
            <p class="text-[11px] text-sidebar-text-secondary truncate">{{ companyName }}</p>
          </div>
        </div>
        <button
          @click="logout"
          class="mt-1.5 flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-semibold text-sidebar-text-secondary hover:text-rose-500 hover:bg-rose-500/10 transition-colors"
          :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
          @mouseenter="showTooltip($event, { label: $t('common.logout') })"
          @mouseleave="hideTooltip"
        >
          <svg class="w-[18px] h-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
          <span class="truncate transition-all" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $t('common.logout') }}</span>
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
    <div class="relative z-10 flex flex-col flex-1 min-w-0 min-h-screen transition-[margin] duration-100 ease-out" :class="sidebarCollapsed ? 'lg:ml-[84px]' : 'lg:ml-[272px]'">
      <!-- Top navbar -->
      <header class="sticky top-0 z-30 border-b border-border bg-surface/75 backdrop-blur-xl">
        <div class="flex items-center gap-2.5 h-[68px] px-4 lg:px-8 max-w-[1440px] mx-auto w-full">
        <button @click="sidebarOpen = true" class="flex lg:hidden items-center justify-center w-10 h-10 rounded-xl border border-border bg-surface text-text-secondary shadow-sm active:scale-95 transition">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
          </svg>
        </button>

        <div class="hidden md:flex items-center gap-2 min-w-0">
          <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-[11px] font-extrabold text-emerald-700 dark:text-emerald-400 whitespace-nowrap">{{ companyName || 'Mon entreprise' }}</span>
          <span v-if="companyPlan" class="hidden xl:inline-flex items-center px-2.5 py-1 rounded-lg bg-violet-500/10 border border-violet-500/20 text-[11px] font-extrabold text-violet-600 whitespace-nowrap">{{ companyPlan.name }}</span>
        </div>

        <div class="flex items-center gap-2 ml-auto">
          <!-- Quick Create Action -->
          <div class="relative" v-click-outside="() => quickCreateOpen = false">
            <button
              @click="quickCreateOpen = !quickCreateOpen"
              class="inline-flex items-center gap-1.5 px-4 py-2.5 text-[13px] font-extrabold text-white bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:-translate-y-px active:scale-[0.98] transition-all"
            >
              <PlusIcon class="w-4 h-4" />
              <span class="hidden sm:inline">{{ $t('header.new') }}</span>
            </button>
            <div
              v-if="quickCreateOpen"
              class="absolute right-0 z-50 mt-2 w-60 bg-surface border border-border/70 rounded-2xl shadow-xl shadow-neutral-950/5 p-1.5 dropdown-in-enter-active"
            >
              <div class="px-3 py-2 text-[10px] font-extrabold text-text-tertiary uppercase tracking-[0.14em]">
                {{ $t('header.quick_create') }}
              </div>
              <router-link
                to="/invoices/create"
                @click="quickCreateOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-text-primary hover:bg-emerald-500/[0.07] rounded-xl transition-colors"
              >
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600"><DocumentTextIcon class="w-4 h-4" /></span>
                {{ $t('header.new_invoice') }}
              </router-link>
              <router-link
                to="/pos"
                @click="quickCreateOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-text-primary hover:bg-emerald-500/[0.07] rounded-xl transition-colors"
              >
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-teal-500/10 text-teal-600"><ComputerDesktopIcon class="w-4 h-4" /></span>
                {{ $t('header.pos') }}
              </router-link>
              <router-link
                to="/products/create"
                @click="quickCreateOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-text-primary hover:bg-blue-500/[0.07] rounded-xl transition-colors"
              >
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600"><CubeIcon class="w-4 h-4" /></span>
                {{ $t('header.new_product') }}
              </router-link>
              <router-link
                to="/customers/create"
                @click="quickCreateOpen = false"
                class="flex items-center gap-3 px-3 py-2.5 text-[13px] font-bold text-text-primary hover:bg-violet-500/[0.07] rounded-xl transition-colors"
              >
                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-violet-500/10 text-violet-600"><UsersIcon class="w-4 h-4" /></span>
                {{ $t('header.new_customer') }}
              </router-link>
            </div>
          </div>

          <!-- Global search -->
          <button
            @click="searchOpen = true"
            class="hidden sm:flex items-center gap-2.5 pl-3.5 pr-2 py-2.5 w-52 xl:w-64 rounded-2xl border border-border bg-surface-secondary/70 text-sm text-text-tertiary hover:border-emerald-400/50 hover:bg-surface transition-all shadow-sm group"
          >
            <svg class="w-4 h-4 group-hover:text-emerald-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span class="flex-1 text-left truncate font-medium">{{ $t('header.search_placeholder') }}</span>
            <kbd class="hidden lg:inline-flex items-center px-1.5 py-0.5 rounded-md bg-surface border border-border text-[10px] font-bold text-text-tertiary">⌘K</kbd>
          </button>
          <button
            @click="searchOpen = true"
            class="flex sm:hidden items-center justify-center w-10 h-10 text-text-secondary rounded-xl border border-border bg-surface shadow-sm"
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
              class="flex items-center gap-2 pl-1.5 pr-2.5 py-1.5 rounded-2xl border border-border bg-surface shadow-sm hover:border-emerald-400/40 hover:shadow-md active:scale-[0.98] transition-all"
            >
              <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 text-white text-sm font-extrabold shadow shrink-0">{{ userInitial }}</span>
              <span class="hidden sm:inline text-text-primary text-[13px] font-bold max-w-[100px] truncate">{{ userName }}</span>
              <svg class="w-4 h-4 text-text-tertiary transition-transform" :class="{ 'rotate-180': dropdownOpen }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
            <div v-if="dropdownOpen" class="absolute right-0 z-50 mt-2 w-60 bg-surface border border-border/70 rounded-2xl shadow-xl shadow-neutral-950/5 p-1.5 dropdown-in-enter-active">
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
                <button @click="logout" class="flex items-center gap-2.5 w-full px-3 py-2.5 text-sm font-semibold text-left text-rose-600 hover:bg-rose-500/10 rounded-xl transition">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                  </svg>
                  {{ $t('common.logout') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Trial banner -->
      <div v-if="trialDaysLeft !== null" class="px-4 lg:px-8 pt-4 max-w-[1440px] mx-auto w-full">
        <div class="flex flex-col sm:flex-row sm:items-center gap-2.5 px-4 py-3 rounded-3xl border shadow-sm"
          :class="trialDaysLeft <= 3 ? 'bg-amber-500/[0.08] border-amber-500/25' : 'bg-emerald-500/[0.07] border-emerald-500/20'">
          <span class="flex items-center justify-center w-9 h-9 rounded-2xl shrink-0"
            :class="trialDaysLeft <= 3 ? 'bg-amber-500 text-white shadow-md shadow-amber-500/25' : 'bg-emerald-500 text-white shadow-md shadow-emerald-500/25'">
            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </span>
          <p class="text-[13px] font-bold flex-1" :class="trialDaysLeft <= 3 ? 'text-amber-700 dark:text-amber-400' : 'text-emerald-700 dark:text-emerald-400'">
            {{ $t('subscription.trial_banner', { n: trialDaysLeft }) }}
          </p>
          <router-link to="/settings/subscription" class="inline-flex items-center justify-center gap-1 px-4 py-2 rounded-2xl text-xs font-extrabold text-white bg-neutral-900 hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 active:scale-[0.98] transition shrink-0">
            {{ $t('subscription.change_plan') }} →
          </router-link>
        </div>
      </div>

      <!-- Content -->
      <div class="flex-1 w-full">
        <div class="px-4 lg:px-8 pt-5 lg:pt-7 max-w-[1440px] mx-auto w-full">
          <slot name="header" />
        </div>
        <main class="px-4 lg:px-8 pb-8 pt-4 max-w-[1440px] mx-auto w-full">
          <slot />
        </main>
      </div>
    </div>

    <!-- Search modal -->
    <Teleport to="body">
      <div v-if="searchOpen" class="fixed inset-0 z-[200] flex items-start justify-center pt-[15vh]" @click.self="searchOpen = false">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm modal-fade-enter-active" />
        <div class="relative w-full max-w-xl bg-surface border border-border/70 rounded-3xl shadow-modal overflow-hidden modal-pop-enter-active">
            <div class="flex items-center gap-3 px-5 border-b border-border/70">
              <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
              </svg>
              <input
                ref="searchInput"
                v-model="searchQuery"
                type="text"
                :placeholder="$t('header.search_placeholder')"
                class="flex-1 py-4 text-sm font-semibold bg-transparent border-0 outline-none text-text-primary placeholder:text-text-tertiary placeholder:font-normal"
              />
              <kbd class="hidden sm:inline-flex items-center px-2 py-1 text-[10px] font-extrabold text-text-tertiary bg-surface-tertiary border border-border rounded-lg">ESC</kbd>
            </div>
            <div class="p-2 max-h-80 overflow-y-auto">
              <p v-if="searchQuery.length < 2" class="py-8 text-center text-sm text-text-tertiary">
                {{ $t('header.search_hint') }}
              </p>
              <div v-else-if="searchResults.length === 0" class="py-8 text-center text-sm text-text-tertiary">
                {{ $t('header.no_results_for', { q: searchQuery }) }}
              </div>
              <div v-else class="space-y-1">
                <div v-for="r in searchResults" :key="r.url" @click="navigateToSearch(r)" class="group flex items-center gap-3 px-3 py-2.5 rounded-2xl cursor-pointer transition-all duration-150 hover:bg-emerald-500/[0.06] hover:translate-x-0.5">
                  <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 shrink-0 transition-transform duration-300 group-hover:scale-110">
                    <component :is="r.icon" class="w-4 h-4" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-extrabold text-text-primary truncate">{{ r.title }}</p>
                    <p class="text-xs font-medium text-text-tertiary truncate">{{ r.subtitle }}</p>
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
import AppLogo from './AppLogo.vue'
import {
  HomeIcon, ComputerDesktopIcon, CubeIcon, UsersIcon, DocumentTextIcon,
  DocumentDuplicateIcon, TruckIcon, CreditCardIcon, ArchiveBoxIcon,
  BanknotesIcon, TagIcon, BuildingStorefrontIcon, ScaleIcon,
  CalendarDaysIcon, BriefcaseIcon, ShoppingCartIcon,
  ArrowUturnLeftIcon, Cog6ToothIcon, ClockIcon, CurrencyDollarIcon, BuildingOfficeIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'

const router = useRouter()
const route = useRoute()
const { t } = useI18n()

const sidebarOpen = ref(false)
const sidebarCollapsed = ref(localStorage.getItem('sidebarCollapsed') !== 'false')
const dropdownOpen = ref(false)
const quickCreateOpen = ref(false)
const tooltip = ref({ text: '', x: 0, y: 0 })
const searchOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref(null)
const searchResults = ref([])
const userName = ref('')
const userEmail = ref('')
const userPermissions = ref([])
const companyName = ref('')
const companyPlan = ref(null)
const companyStatus = ref('')
const companyTrialEndsAt = ref(null)
const isSuperAdmin = ref(false)

const trialDaysLeft = computed(() => {
  if (companyStatus.value !== 'trial' || !companyTrialEndsAt.value) return null
  const diff = new Date(companyTrialEndsAt.value).getTime() - Date.now()
  if (diff <= 0) return null
  return Math.ceil(diff / 86400000)
})

async function refreshCompany() {
  const { data } = await axios.get('/company')
  const c = data.data ?? data
  const name = c.company_name || c.name || ''
  companyName.value = name
  if (c.plan) {
    companyPlan.value = c.plan
  }
  companyStatus.value = c.status || ''
  companyTrialEndsAt.value = c.trial_ends_at || null
  localStorage.setItem('company', JSON.stringify({
    name,
    plan: c.plan,
    status: c.status || '',
    trial_ends_at: c.trial_ends_at || null,
  }))
}

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
    text: item.key ? t(item.key) : (item.label || ''),
    x: rect.right + 8,
    y: rect.top + rect.height / 2,
  }
}

function hideTooltip() {
  tooltip.value = { text: '', x: 0, y: 0 }
}

const searchPages = computed(() => [
  { title: t('nav.dashboard'), subtitle: t('nav.system'), url: '/dashboard', icon: HomeIcon },
  { title: t('nav.pos'), subtitle: t('nav.sales'), url: '/pos', icon: ComputerDesktopIcon },
  { title: t('nav.pos_sessions'), subtitle: t('nav.sales'), url: '/pos/sessions', icon: ClockIcon },
  { title: t('nav.products'), subtitle: t('nav.sales'), url: '/products', icon: CubeIcon },
  { title: t('nav.customers'), subtitle: t('nav.sales'), url: '/customers', icon: UsersIcon },
  { title: t('nav.suppliers'), subtitle: t('nav.sales'), url: '/suppliers', icon: BriefcaseIcon },
  { title: t('nav.invoices'), subtitle: t('nav.documentation'), url: '/invoices', icon: DocumentTextIcon },
  { title: t('nav.quotes'), subtitle: t('nav.documentation'), url: '/quotes', icon: DocumentDuplicateIcon },
  { title: t('nav.credit_notes'), subtitle: t('nav.documentation'), url: '/credit-notes', icon: ArrowUturnLeftIcon },
  { title: t('nav.purchase_orders'), subtitle: t('nav.documentation'), url: '/purchase-orders', icon: ShoppingCartIcon },
  { title: t('nav.delivery_notes'), subtitle: t('nav.documentation'), url: '/delivery-notes', icon: TruckIcon },
  { title: t('nav.payments'), subtitle: t('nav.finance'), url: '/payments', icon: CreditCardIcon },
  { title: t('nav.expenses'), subtitle: t('nav.finance'), url: '/expenses', icon: BanknotesIcon },
  { title: t('nav.capital'), subtitle: t('nav.finance'), url: '/capital', icon: CurrencyDollarIcon },
  { title: t('nav.stock'), subtitle: t('nav.management'), url: '/stock', icon: ArchiveBoxIcon },
  { title: t('nav.categories'), subtitle: t('nav.management'), url: '/categories', icon: TagIcon },
  { title: t('nav.units'), subtitle: t('nav.management'), url: '/units', icon: ScaleIcon },
  { title: t('nav.warehouses'), subtitle: t('nav.management'), url: '/warehouses', icon: BuildingStorefrontIcon },
  { title: t('nav.users'), subtitle: t('nav.system'), url: '/users', icon: Cog6ToothIcon },
  { title: t('nav.activity_logs'), subtitle: t('nav.system'), url: '/activity-logs', icon: ClockIcon },
  { title: t('nav.settings'), subtitle: t('nav.system'), url: '/settings', icon: Cog6ToothIcon },
  { title: t('nav.admin_panel'), subtitle: t('nav.system'), url: '/admin/dashboard', icon: Cog6ToothIcon },
  { title: t('admin.companies'), subtitle: t('nav.system'), url: '/admin/companies', icon: BuildingOfficeIcon },
  { title: t('admin.logins'), subtitle: t('nav.system'), url: '/admin/login-logs', icon: ClockIcon },
])

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
      if (cachedCompany.plan) {
        companyPlan.value = cachedCompany.plan
      }
      companyStatus.value = cachedCompany.status || ''
      companyTrialEndsAt.value = cachedCompany.trial_ends_at || null
    }
  } catch {}
  // Rafraîchir en arrière-plan pour un bandeau d'essai toujours à jour.
  try { await refreshCompany() } catch {}
})
</script>
