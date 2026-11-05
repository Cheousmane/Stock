<template>
  <SuperAdminLayout>
    <div class="space-y-5">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <p class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-[0.16em] text-emerald-600 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-3 py-1">{{ $t('adminPages.companies.eyebrow', { count: companyStats?.total ?? '…' }) }}</p>
          <h2 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-text-primary">{{ $t('admin.companies') }}</h2>
          <p class="text-sm text-text-tertiary mt-1 font-medium">{{ $t('adminPages.companies.description') }}</p>
        </div>
        <button @click="exportCsv" :disabled="exporting" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-extrabold rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 shadow hover:-translate-y-px active:scale-[0.98] disabled:opacity-50 transition">
          <svg class="w-4 h-4" :class="{ 'animate-bounce': exporting }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
          {{ exporting ? $t('common.exporting') : $t('adminPages.activity.export_csv') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="pro-filterbar">
        <label class="flex items-center gap-2.5 flex-1 min-w-0 px-3.5 py-2.5 rounded-2xl bg-surface-secondary/70 border border-border/60 focus-within:border-emerald-500/60 focus-within:ring-4 focus-within:ring-emerald-500/10 transition">
          <svg class="w-4 h-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
          <input v-model="filters.search" :placeholder="$t('adminPages.companies.search')" class="w-full bg-transparent text-sm font-semibold text-text-primary placeholder:text-text-tertiary placeholder:font-medium outline-none" @input="debouncedSearch" />
          <kbd v-if="!filters.search" class="hidden sm:inline-flex px-1.5 py-0.5 rounded-md bg-surface border border-border text-[10px] font-bold text-text-tertiary">/</kbd>
        </label>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="filters.status" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchCompanies">
            <option value="">{{ $t('adminPages.companies.all_statuses') }}</option>
            <option value="active">{{ $t('adminPages.companies.statuses.active') }}</option>
            <option value="suspended">{{ $t('adminPages.companies.statuses.suspended') }}</option>
            <option value="trial">{{ $t('adminPages.companies.statuses.trial') }}</option>
            <option value="disabled">{{ $t('adminPages.companies.statuses.disabled') }}</option>
          </select>
          <select v-model="filters.size" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchCompanies">
            <option value="">{{ $t('adminPages.companies.all_sizes') }}</option>
            <option value="petite">{{ $t('auth.size_petite') }}</option>
            <option value="moyenne">{{ $t('auth.size_moyenne') }}</option>
            <option value="grande">{{ $t('auth.size_grande') }}</option>
          </select>
        </div>
      </div>

      <!-- Stats -->
      <div v-if="companyStats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="p-4 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-400/40 transition-all">
          <p class="text-xl font-black tabular-nums text-text-primary">{{ companyStats.total }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mt-0.5">{{ $t('common.total') }}</p>
        </div>
        <div class="p-4 rounded-[1.25rem] bg-emerald-500/[0.06] border border-emerald-500/20 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md transition-all">
          <p class="text-xl font-black tabular-nums text-emerald-600">{{ companyStats.active }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600/80 mt-0.5">{{ $t('adminPages.companies.statuses.active_plural') }}</p>
        </div>
        <div class="p-4 rounded-[1.25rem] bg-rose-500/[0.06] border border-rose-500/20 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md transition-all">
          <p class="text-xl font-black tabular-nums text-rose-500">{{ companyStats.suspended }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-rose-500/80 mt-0.5">{{ $t('adminPages.companies.statuses.suspended_plural') }}</p>
        </div>
        <div class="p-4 rounded-[1.25rem] bg-amber-500/[0.07] border border-amber-500/20 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md transition-all">
          <p class="text-xl font-black tabular-nums text-amber-500">{{ companyStats.trial }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-amber-600/80 mt-0.5">{{ $t('adminPages.companies.statuses.trial') }}</p>
        </div>
        <div class="p-4 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md transition-all">
          <p class="text-xl font-black tabular-nums text-text-primary">{{ companyStats.registrations_last_7_days }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mt-0.5">{{ $t('adminPages.companies.last_days', { count: 7 }) }}</p>
        </div>
        <div class="p-4 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm text-center hover:-translate-y-0.5 hover:shadow-md transition-all">
          <p class="text-xl font-black tabular-nums text-text-primary">{{ companyStats.registrations_last_30_days }}</p>
          <p class="text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mt-0.5">{{ $t('adminPages.companies.last_days', { count: 30 }) }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-[1.25rem] bg-surface border border-border/70 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 6" :key="i" class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl bg-surface-tertiary animate-pulse shrink-0" />
            <div class="flex-1 space-y-2"><div class="h-3.5 w-1/3 rounded bg-surface-tertiary animate-pulse" /><div class="h-3 w-1/4 rounded bg-surface-tertiary animate-pulse" /></div>
          </div>
        </div>
        <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[860px]">
          <thead>
            <tr class="border-b border-border/70 text-left text-[11px] font-extrabold text-text-tertiary uppercase tracking-[0.1em] bg-surface-secondary/50">
              <th class="px-5 py-4">{{ $t('adminPages.companies.company') }}</th>
              <th class="px-5 py-4">{{ $t('common.status') }}</th>
              <th class="px-5 py-4">{{ $t('adminPages.companies.size') }}</th>
              <th class="px-5 py-4">{{ $t('adminPages.companies.industry') }}</th>
              <th class="px-5 py-4">{{ $t('adminPages.companies.users') }}</th>
              <th class="px-5 py-4">{{ $t('adminPages.companies.registration_date') }}</th>
              <th class="px-5 py-4 text-right">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/50">
            <tr v-for="c in companies" :key="c.id" class="hover:bg-emerald-500/[0.04] transition-colors group">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-sm font-black shrink-0 shadow-md shadow-emerald-500/20">{{ (c.name || '?').charAt(0).toUpperCase() }}</span>
                  <div class="min-w-0"><p class="text-sm font-extrabold text-text-primary truncate">{{ c.name }}</p>
                  <p class="text-xs text-text-tertiary font-mono truncate">{{ c.slug }}</p></div>
                </div>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full" :class="statusClass(c.status)">{{ statusLabel(c.status) }}</span>
              </td>
              <td class="px-5 py-3.5 text-[13px] font-semibold text-text-secondary">{{ c.size ? sizeLabel(c.size) : '-' }}</td>
              <td class="px-5 py-3.5 text-[13px] font-semibold text-text-secondary max-w-[180px] truncate">{{ industryLabel(c.industry) }}</td>
              <td class="px-5 py-3.5"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold tabular-nums bg-surface-secondary border border-border text-text-primary">{{ c.users_count }}</span></td>
              <td class="px-5 py-3.5 text-[13px] font-semibold text-text-secondary tabular-nums whitespace-nowrap">{{ dateShort(c.created_at) }}</td>
              <td class="px-5 py-3.5">
                <div class="flex items-center justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                  <button @click="showCompanyDetails(c.id)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-blue-600 hover:text-white bg-blue-500/10 hover:bg-blue-600 border border-blue-500/20 rounded-xl transition">{{ $t('common.details') }}</button>
                  <button v-if="c.status !== 'suspended'" @click="suspend(c)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-rose-600 hover:text-white bg-rose-500/10 hover:bg-rose-600 border border-rose-500/20 rounded-xl transition">{{ $t('adminPages.companies.block') }}</button>
                  <button v-if="c.status === 'suspended'" @click="activate(c)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 hover:text-white bg-emerald-500/10 hover:bg-emerald-600 border border-emerald-500/20 rounded-xl transition">{{ $t('adminPages.companies.unblock') }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <div v-if="!loading && companies.length === 0" class="p-12 text-center">
          <div class="mx-auto w-14 h-14 rounded-[1.25rem] bg-surface-secondary border border-border flex items-center justify-center mb-3 text-text-tertiary">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.008v.008H6.75V6.75zm0 3h.008v.008H6.75v-.008zm0 3h.008v.008H6.75v-.008zm0 3h.008v.008H6.75v-.008zm0 3h.008v.008H6.75v-.008zm3.75-9h.008v.008h-.008V6.75zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm3.75-9h.008v.008h-.008V6.75zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
          </div>
          <p class="text-sm font-extrabold text-text-primary">{{ $t('adminPages.companies.empty_title') }}</p>
          <p class="text-xs text-text-tertiary mt-1">{{ $t('adminPages.companies.empty_description') }}</p>
        </div>

        <!-- Pagination -->
        <div v-if="pagination" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-t border-border/70 bg-surface-secondary/40">
          <p class="text-xs font-bold text-text-tertiary tabular-nums">{{ $t('common.pagination_summary', { from: pagination.from, to: pagination.to, total: pagination.total }) }}</p>
          <div class="flex items-center gap-1.5">
            <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">←</button>
            <span class="px-3 text-[13px] font-extrabold text-text-primary tabular-nums">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">→</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Company details modal -->
    <Transition name="modal-fade">
      <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-neutral-950/60 backdrop-blur-md" @click.self="selectedCompany = null">
        <Transition name="modal-pop">
          <div v-if="selectedCompany" class="bg-surface w-full max-w-4xl rounded-[28px] shadow-modal border border-border/60 overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Hero header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 text-white shrink-0">
              <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-16 -right-12 h-52 w-52 rounded-full bg-white/15 blur-3xl" />
                <div class="absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-teal-950/25 blur-3xl" />
                <div class="absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 20px 20px;" />
              </div>
              <div class="relative flex items-start gap-4 p-5 sm:p-6">
                <span class="flex items-center justify-center w-14 h-14 rounded-[1.25rem] bg-white/20 border border-white/30 backdrop-blur text-2xl font-black shadow-lg shrink-0">
                  {{ (selectedCompany.name || '?').charAt(0).toUpperCase() }}
                </span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-xl sm:text-2xl font-black tracking-tight truncate">{{ selectedCompany.name }}</h3>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/15 border border-white/25 backdrop-blur" :class="selectedCompany.status === 'active' ? 'text-white' : selectedCompany.status === 'suspended' ? '!bg-rose-500 !border-rose-400 text-white' : 'text-white'">
                      <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5" />{{ statusLabel(selectedCompany.status) }}
                    </span>
                    <span v-if="selectedCompany.plan?.name" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-neutral-950/25 border border-white/25 backdrop-blur">
                      ◆ {{ selectedCompany.plan.name }}
                    </span>
                  </div>
                  <p class="mt-1 text-xs font-mono text-emerald-50/80 truncate">ID · {{ selectedCompany.uuid || selectedCompany.id }}</p>
                  <p class="text-[13px] text-emerald-50/90 mt-0.5 truncate">{{ selectedCompany.email || '—' }} <span v-if="selectedCompany.phone">· {{ selectedCompany.phone }}</span></p>
                </div>
                <button @click="selectedCompany = null" class="flex items-center justify-center w-9 h-9 rounded-2xl bg-white/15 border border-white/25 backdrop-blur hover:bg-white/30 active:scale-95 transition shrink-0" :title="$t('common.close')">
                  <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
              <!-- KPI strip -->
              <div class="relative grid grid-cols-2 sm:grid-cols-4 border-t border-white/15 divide-x divide-white/15 bg-emerald-950/15 backdrop-blur text-center">
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.users') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ selectedCompany.users_count || (selectedCompany.users ? selectedCompany.users.length : 0) }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.customers') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ selectedCompany.customers_count || 0 }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.invoices') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ selectedCompany.invoices_count || 0 }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.volume_30d') }}</p>
                  <p class="text-lg font-black tabular-nums truncate">{{ formatXof(selectedCompany.invoiced_volume_30d) }}</p>
                </div>
              </div>
            </div>

            <div class="p-4 sm:p-6 overflow-y-auto flex-1 bg-surface-secondary/40 space-y-4 custom-scrollbar">
              <div v-if="selectedCompany.status === 'suspended'" class="flex items-center gap-3.5 p-4 rounded-[1.25rem] bg-rose-500/[0.07] border border-rose-500/25">
                <span class="flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/30 shrink-0">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </span>
                <div class="min-w-0">
                  <h4 class="text-sm font-extrabold text-rose-700 dark:text-rose-400">{{ $t('adminPages.companies.suspended_title') }}</h4>
                  <p class="text-xs font-medium text-rose-600/90 dark:text-rose-400/90 mt-0.5">{{ $t('adminPages.companies.suspended_description') }}</p>
                </div>
                <button @click="activate(selectedCompany); selectedCompany.status = 'active'" class="ml-auto px-4 py-2.5 rounded-2xl text-xs font-extrabold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-500/25 active:scale-95 transition shrink-0">{{ $t('adminPages.companies.unblock') }}</button>
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <!-- Informations -->
                <section class="lg:col-span-3 p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm">
                  <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                      <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.companies.information') }}</h4>
                      <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.companies.contact_lifecycle') }}</p>
                    </div>
                    <button v-if="!editing" @click="startEdit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-extrabold text-blue-600 bg-blue-500/10 hover:bg-blue-500/15 border border-blue-500/20 active:scale-95 transition">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                      {{ $t('common.edit') }}
                    </button>
                  </div>
                  <template v-if="editing">
                    <div class="grid sm:grid-cols-2 gap-3">
                      <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('common.email') }}</label>
                        <input v-model="editForm.email" type="email" class="w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition" />
                      </div>
                      <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('common.phone') }}</label>
                        <input v-model="editForm.phone" type="text" class="w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition" />
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('auth.industry') }}</label>
                        <select v-model="editForm.industry" class="w-full px-3.5 py-2.5 text-sm font-semibold rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition cursor-pointer">
                          <option value="">{{ $t('settings.not_defined') }}</option>
                          <option v-for="(s, index) in industryOptions" :key="s" :value="s">{{ $t(`auth.industry_options.${index}`) }}</option>
                        </select>
                        <input
                          v-if="editForm.industry === industryOptions[industryOptions.length - 1]"
                          v-model="editForm.otherIndustry"
                          type="text"
                          :placeholder="$t('settings.specify_industry')"
                          class="mt-2 w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary placeholder:text-text-tertiary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition"
                        />
                      </div>
                      <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('auth.company_size') }}</label>
                        <select v-model="editForm.size" class="w-full px-3.5 py-2.5 text-sm font-semibold rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition cursor-pointer">
                          <option value="">{{ $t('settings.not_defined_f') }}</option>
                          <option value="petite">{{ $t('auth.size_petite') }}</option>
                          <option value="moyenne">{{ $t('auth.size_moyenne') }}</option>
                          <option value="grande">{{ $t('auth.size_grande') }}</option>
                        </select>
                      </div>
                      <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('common.status') }}</label>
                        <select v-model="editForm.status" class="w-full px-3.5 py-2.5 text-sm font-semibold rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition cursor-pointer">
                          <option value="active">{{ $t('adminPages.companies.statuses.active') }}</option>
                          <option value="trial">{{ $t('adminPages.companies.statuses.trial') }}</option>
                          <option value="suspended">{{ $t('adminPages.companies.statuses.suspended') }}</option>
                          <option value="disabled">{{ $t('adminPages.companies.statuses.disabled') }}</option>
                        </select>
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('adminPages.companies.plan') }}</label>
                        <div class="flex gap-2">
                          <select v-model="editForm.plan_id" class="flex-1 min-w-0 px-3.5 py-2.5 text-sm font-semibold rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition cursor-pointer">
                            <option value="">{{ $t('adminPages.companies.no_plan') }}</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} — {{ formatXof(p.price_xof) }}</option>
                          </select>
                          <button
                            v-if="editForm.plan_id && editForm.plan_id !== selectedCompany.plan?.id"
                            @click="assignPlan(selectedCompany, editForm.plan_id)"
                            :disabled="assignPlanLoading"
                            class="px-4 py-2.5 text-xs font-extrabold text-white bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-md shadow-emerald-500/25 hover:shadow-lg active:scale-95 disabled:opacity-50 transition shrink-0"
                          >
                            {{ assignPlanLoading ? '...' : $t('adminPages.companies.apply') }}
                          </button>
                        </div>
                      </div>
                      <div>
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('adminPages.companies.trial_end') }}</label>
                        <input v-model="editForm.trial_ends_at" type="date" class="w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition" />
                      </div>
                      <div v-if="editForm.status === 'suspended'">
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('adminPages.companies.auto_reactivation') }} <span class="normal-case font-semibold">({{ $t('common.optional') }})</span></label>
                        <input v-model="editForm.suspended_until" type="date" class="w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition" />
                        <p class="text-[11px] font-medium text-text-tertiary mt-1">{{ $t('adminPages.companies.auto_reactivation_help') }}</p>
                      </div>
                      <div class="sm:col-span-2">
                        <label class="block text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1">{{ $t('settings.address') }}</label>
                        <textarea v-model="editForm.address" rows="2" class="w-full px-3.5 py-2.5 text-sm font-medium rounded-2xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:bg-surface focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition resize-none"></textarea>
                      </div>
                      <div class="sm:col-span-2 flex gap-2 pt-1">
                        <button @click="saveCompany" class="flex-1 px-4 py-2.5 text-sm font-extrabold text-white bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-md shadow-emerald-500/25 hover:shadow-lg active:scale-[0.98] transition">{{ $t('common.save') }}</button>
                        <button @click="cancelEdit" class="px-4 py-2.5 text-sm font-bold text-text-secondary hover:bg-surface-tertiary rounded-2xl transition">{{ $t('common.cancel') }}</button>
                      </div>
                    </div>
                  </template>
                  <dl v-else class="grid sm:grid-cols-2 gap-x-4 gap-y-3">
                    <div v-for="row in infoRows" :key="row.label" class="rounded-2xl bg-surface-secondary/60 border border-border/50 px-3.5 py-2.5">
                      <dt class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-text-tertiary">{{ row.label }}</dt>
                      <dd class="mt-0.5 text-[13px] font-bold text-text-primary truncate" :title="row.value">{{ row.value }}</dd>
                    </div>
                  </dl>
                </section>

                <!-- Plan & usage -->
                <section class="lg:col-span-2 p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm flex flex-col">
                  <div class="flex items-start justify-between gap-2">
                    <div>
                      <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.companies.plan_usage') }}</h4>
                      <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.companies.subscription_activity') }}</p>
                    </div>
                    <span v-if="selectedCompany.over_quota" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/25">{{ $t('adminPages.companies.over_quota') }}</span>
                  </div>
                  <div class="mt-4 flex items-center gap-3 p-3.5 rounded-2xl bg-violet-500/[0.07] border border-violet-500/20">
                    <span class="flex items-center justify-center w-11 h-11 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-md shadow-violet-500/25 shrink-0 text-lg font-black">◆</span>
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-extrabold text-text-primary truncate">{{ selectedCompany.plan?.name || $t('adminPages.companies.no_plan') }}</p>
                      <p v-if="selectedCompany.subscription" class="text-[11px] font-bold text-text-tertiary uppercase tracking-wider">{{ subscriptionStatusLabel(selectedCompany.subscription.status) }}</p>
                      <p v-else-if="selectedCompany.trial_active" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ $t('adminPages.companies.trial_in_progress') }}</p>
                      <p v-else class="text-[11px] font-bold text-text-tertiary uppercase tracking-wider">{{ $t('adminPages.companies.no_subscription') }}</p>
                    </div>
                  </div>
                  <div class="mt-2.5 space-y-1.5 text-xs font-semibold">
                    <div v-if="selectedCompany.trial_ends_at" class="flex items-center justify-between rounded-xl bg-surface-secondary/60 px-3 py-2">
                      <span class="text-text-tertiary">{{ $t('adminPages.companies.trial_end') }}</span>
                      <span class="text-text-primary tabular-nums">{{ dateShort(selectedCompany.trial_ends_at) }}</span>
                    </div>
                    <div v-if="selectedCompany.subscription?.ends_at" class="flex items-center justify-between rounded-xl bg-surface-secondary/60 px-3 py-2">
                      <span class="text-text-tertiary">{{ $t('adminPages.companies.subscription_end') }}</span>
                      <span class="text-text-primary tabular-nums">{{ dateShort(selectedCompany.subscription.ends_at) }}</span>
                    </div>
                  </div>

                  <!-- Quotas -->
                  <div v-if="selectedCompany.quota_snapshot?.resources?.length" class="mt-3 space-y-1.5">
                    <div v-for="q in selectedCompany.quota_snapshot.resources" :key="q.resource" class="flex items-center justify-between gap-2 rounded-xl bg-surface-secondary/60 px-3 py-2 text-xs font-semibold">
                      <span class="text-text-tertiary">{{ q.label }}</span>
                      <span class="tabular-nums" :class="q.over_quota ? 'text-amber-600 dark:text-amber-400 font-extrabold' : 'text-text-primary'">
                        {{ q.used }}/{{ q.limit < 0 ? '∞' : q.limit }}
                        <span v-if="q.frozen > 0" class="text-amber-600 dark:text-amber-400"> · {{ $t('adminPages.companies.frozen_count', { count: q.frozen }) }}</span>
                      </span>
                    </div>
                  </div>

                  <div class="mt-3 grid grid-cols-2 gap-2 text-center">
                    <div class="rounded-2xl bg-surface-secondary/60 border border-border/50 px-2 py-3">
                      <p class="text-base font-black tabular-nums text-text-primary">{{ selectedCompany.products_count || 0 }}</p>
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mt-0.5">{{ $t('nav.products') }}</p>
                    </div>
                    <div class="rounded-2xl bg-surface-secondary/60 border border-border/50 px-2 py-3">
                      <p class="text-base font-black tabular-nums text-text-primary">{{ selectedCompany.invoices_count_30d || 0 }}</p>
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mt-0.5">{{ $t('adminPages.companies.invoicing_30d') }}</p>
                    </div>
                  </div>
                  <div v-if="selectedCompany.frozen_total > 0" class="mt-2.5 flex items-center gap-2 rounded-xl bg-amber-500/[0.07] border border-amber-500/25 px-3 py-2">
                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    <p class="text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $t('adminPages.companies.frozen_data_reversible', { count: selectedCompany.frozen_total }) }}</p>
                  </div>
                  <div class="mt-3 rounded-2xl bg-surface-secondary/60 border border-border/50 px-3.5 py-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.12em] text-text-tertiary">{{ $t('adminPages.companies.owner') }}</p>
                    <p class="mt-0.5 text-[13px] font-bold text-text-primary truncate">{{ selectedCompany.owner?.name || '-' }}</p>
                  </div>
                  <div class="mt-auto pt-3 grid grid-cols-2 gap-2 text-xs font-semibold text-text-tertiary">
                    <div>{{ $t('adminPages.companies.created_at') }}<br><span class="text-text-primary font-bold tabular-nums">{{ dateShort(selectedCompany.created_at) }}</span></div>
                    <div>{{ $t('adminPages.companies.last_login') }}<br><span class="text-text-primary font-bold tabular-nums">{{ dateShort(selectedCompany.last_login) }}</span></div>
                  </div>
                </section>
              </div>

              <!-- History -->
              <section class="p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.companies.recent_history') }}</h4>
                    <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.companies.last_actions', { count: 30 }) }}</p>
                  </div>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold tabular-nums bg-surface-secondary border border-border text-text-secondary">{{ selectedCompany.timeline?.length || 0 }}</span>
                </div>
                <div v-if="!selectedCompany.timeline || selectedCompany.timeline.length === 0" class="py-6 text-center">
                  <p class="text-sm font-bold text-text-tertiary">{{ $t('adminPages.companies.no_activity') }}</p>
                </div>
                <ol v-else class="relative ml-2 space-y-1 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                  <li v-for="entry in selectedCompany.timeline" :key="entry.id" class="relative ml-5 pl-4 pb-4 border-l-2 border-border/70 last:pb-0">
                    <span class="absolute -left-[7px] top-1 w-3 h-3 rounded-full border-[2.5px] border-surface shadow" :class="timelineDotClass(entry.event)"></span>
                    <p class="text-[13px] font-semibold text-text-primary leading-snug">{{ entry.description }}</p>
                    <p class="text-[11px] font-medium text-text-tertiary mt-1 flex items-center gap-1.5 flex-wrap">
                      {{ dateTime(entry.created_at) }} · {{ entry.user ? entry.user.name : $t('adminPages.activity.system') }}
                      <span v-if="entry.event" class="px-2 py-px rounded-full text-[10px] font-extrabold uppercase tracking-wider border" :class="eventBadgeClass(entry.event)">{{ entry.event }}</span>
                    </p>
                  </li>
                </ol>
              </section>

              <!-- Danger zone -->
            <section v-if="!isOwnCompany" class="p-5 rounded-[1.25rem] border-2 border-dashed border-rose-500/40 bg-rose-500/[0.03]">
              <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <span class="flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-500 text-white shadow-lg shadow-rose-500/30 shrink-0">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </span>
                <div class="flex-1 min-w-0">
                  <h4 class="text-[15px] font-extrabold tracking-tight text-rose-700 dark:text-rose-400">{{ $t('adminPages.companies.danger_zone') }}</h4>
                  <p class="text-xs font-medium text-text-secondary mt-0.5">{{ $t('adminPages.companies.delete_warning') }}</p>
                </div>
                <button v-if="!showDeleteConfirm" @click="showDeleteConfirm = true" class="px-4 py-2.5 rounded-2xl text-xs font-extrabold text-rose-600 border border-rose-500/30 hover:bg-rose-500 hover:text-white hover:border-rose-500 active:scale-95 transition shrink-0">{{ $t('adminPages.companies.delete') }}…</button>
              </div>
              <div v-if="showDeleteConfirm" class="mt-4 rounded-2xl bg-surface border border-rose-500/25 p-4 space-y-3">
                <p class="text-[13px] font-semibold text-text-primary">{{ $t('adminPages.companies.confirm_delete_prompt') }} <strong class="font-mono bg-surface-tertiary border border-border px-1.5 py-0.5 rounded-lg">{{ selectedCompany.name }}</strong> :</p>
                <input v-model="deleteName" type="text" :placeholder="$t('auth.company_name')" autocomplete="off"
                  class="w-full px-3.5 py-2.5 text-sm font-semibold rounded-2xl border border-border bg-surface-secondary/50 text-text-primary placeholder:text-text-tertiary placeholder:font-normal outline-none focus:bg-surface focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition" />
                <p v-if="deleteError" class="text-xs font-bold text-rose-600">{{ deleteError }}</p>
                <div class="flex gap-2">
                  <button @click="showDeleteConfirm = false; deleteName = ''; deleteError = ''" class="px-4 py-2.5 text-[13px] font-bold text-text-secondary hover:bg-surface-tertiary rounded-2xl transition">{{ $t('common.cancel') }}</button>
                  <button @click="deleteCompany" :disabled="deleting || deleteName.trim() !== selectedCompany.name"
                    class="flex-1 px-4 py-2.5 text-[13px] font-extrabold text-white bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg shadow-rose-500/25 hover:brightness-110 active:scale-[0.98] disabled:opacity-40 disabled:cursor-not-allowed transition">
                    {{ deleting ? $t('common.deleting') : $t('adminPages.companies.delete_permanently') }}
                  </button>
                </div>
              </div>
            </section>
            </div>

            <div class="px-4 sm:px-6 py-4 border-t border-border/70 bg-surface/90 backdrop-blur flex flex-col sm:flex-row justify-end gap-2.5 shrink-0">
              <button @click="selectedCompany = null" class="px-5 py-2.5 text-sm font-bold text-text-secondary border border-border hover:bg-surface-tertiary rounded-2xl transition active:scale-[0.98]">{{ $t('common.close') }}</button>
              <button v-if="selectedCompany.status !== 'suspended'" @click="suspend(selectedCompany); selectedCompany.status = 'suspended'" class="px-5 py-2.5 text-sm font-extrabold text-white bg-gradient-to-br from-rose-500 to-red-600 hover:brightness-110 rounded-2xl shadow-lg shadow-rose-500/25 transition active:scale-[0.98]">{{ $t('adminPages.companies.block_company') }}</button>
              <button v-if="selectedCompany.status === 'suspended'" @click="activate(selectedCompany); selectedCompany.status = 'active'" class="px-5 py-2.5 text-sm font-extrabold text-white bg-gradient-to-br from-emerald-500 to-teal-600 hover:brightness-110 rounded-2xl shadow-lg shadow-emerald-500/25 transition active:scale-[0.98]">{{ $t('adminPages.companies.unblock_company') }}</button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'
import { formatPrice, formatDateShort, formatDateTime, industryOptions } from '../../utils/format'

const { t, locale } = useI18n()

const companies = ref([])
const companyStats = ref(null)
const plans = ref([])
const loading = ref(true)
const pagination = ref(null)
const selectedCompany = ref(null)
const editing = ref(false)
const exporting = ref(false)
const editForm = reactive({ email: '', phone: '', industry: '', size: '', address: '', otherIndustry: '', status: 'active', plan_id: '', trial_ends_at: '', suspended_until: '' })
const filters = reactive({ search: '', status: '', size: '' })
const assignPlanLoading = ref(false)

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchCompanies, 300)
}

function statusClass(status) {
  const map = { active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300', suspended: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', trial: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300', disabled: 'bg-gray-100 text-gray-500 dark:bg-neutral-800 dark:text-neutral-400' }
  return map[status] || 'bg-gray-100 text-gray-500 dark:bg-neutral-800 dark:text-neutral-400'
}

function sizeLabel(size) {
  const map = { petite: 'auth.size_petite', moyenne: 'auth.size_moyenne', grande: 'auth.size_grande' }
  return map[size] ? t(map[size]) : size
}

function statusLabel(status) {
  const key = `adminPages.companies.statuses.${status}`
  return t(key)
}

function industryLabel(industry) {
  if (!industry) return '-'
  const index = industryOptions.indexOf(industry)
  return index < 0 ? industry : t(`auth.industry_options.${index}`)
}

function dateShort(value) {
  return formatDateShort(value, locale.value)
}

function dateTime(value) {
  return formatDateTime(value, locale.value)
}

function subscriptionStatusLabel(status) {
  const map = { active: 'adminPages.companies.subscription_statuses.active', suspended: 'adminPages.companies.subscription_statuses.suspended', expired: 'adminPages.companies.subscription_statuses.expired', canceled: 'adminPages.companies.subscription_statuses.canceled' }
  return map[status] ? t(map[status]) : status || t('adminPages.companies.no_subscription')
}

const infoRows = computed(() => {
  const c = selectedCompany.value || {}
  return [
    { label: t('common.email'), value: c.email || '-' },
    { label: t('common.phone'), value: c.phone || '-' },
    { label: t('adminPages.companies.industry'), value: industryLabel(c.industry) },
    { label: t('adminPages.companies.size'), value: c.size ? sizeLabel(c.size) : '-' },
    { label: t('settings.address'), value: c.address || '-' },
    { label: t('settings.currency'), value: c.currency || c.currency_code || '-' },
    { label: t('adminPages.companies.trial_end'), value: dateShort(c.trial_ends_at) },
    { label: t('adminPages.companies.created_at'), value: dateShort(c.created_at) },
    { label: t('adminPages.companies.last_login'), value: dateShort(c.last_login) },
  ]
})

function formatXof(v) {
  return formatPrice(v || 0, 'XOF', locale.value)
}

function timelineDotClass(event) {
  if (event === 'created') return 'bg-emerald-500'
  if (event === 'updated') return 'bg-blue-500'
  if (event === 'deleted') return 'bg-rose-500'
  return 'bg-gray-400'
}

function eventBadgeClass(event) {
  if (event === 'created') return 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300'
  if (event === 'updated') return 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300'
  if (event === 'deleted') return 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300'
  return 'bg-gray-50 text-gray-600 dark:bg-neutral-800 dark:text-neutral-300'
}

async function fetchCompanies(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20, ...filters }
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
    const { data } = await axios.get('/admin/companies', { params })
    companies.value = data.data || []
    pagination.value = {
      ...data.meta,
      prev_page_url: data.links?.prev,
      next_page_url: data.links?.next,
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > (pagination.value?.last_page || 1)) return
  fetchCompanies(page)
}

function exportCsv() {
  exporting.value = true
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.status) params.status = filters.status
  if (filters.size) params.size = filters.size
  axios.get('/admin/companies/export', { params, responseType: 'blob' })
    .then(res => {
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }))
      const a = document.createElement('a')
      a.href = url
      a.download = `${t('adminPages.companies.export_filename')}-${new Date().toISOString().slice(0, 10)}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    })
    .catch(err => console.error(err))
    .finally(() => { exporting.value = false })
}

async function showCompanyDetails(id) {
  try {
    const { data } = await axios.get(`/admin/companies/${id}`)
    selectedCompany.value = data.data || data // Handles Resource wrapper
    editing.value = false
    showDeleteConfirm.value = false
    deleteName.value = ''
    deleteError.value = ''
  } catch (err) {
    console.error(t('adminPages.companies.details_load_error'), err)
  }
}

const showDeleteConfirm = ref(false)
const deleteName = ref('')
const deleting = ref(false)
const deleteError = ref('')

function currentUserCompanyId() {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}')
    return Number(u.company_id ?? u.company?.id ?? 0)
  } catch { return 0 }
}

const isOwnCompany = computed(() => {
  if (!selectedCompany.value) return false
  return Number(selectedCompany.value.id) === currentUserCompanyId()
})

async function deleteCompany() {
  if (!selectedCompany.value || deleteName.value.trim() !== selectedCompany.value.name) return
  deleting.value = true
  deleteError.value = ''
  try {
    const { data } = await axios.delete(`/admin/companies/${selectedCompany.value.id}`)
    selectedCompany.value = null
    showDeleteConfirm.value = false
    deleteName.value = ''
    fetchCompanies()
    console.info('Entreprise supprimée', data.stats)
  } catch (err) {
    deleteError.value = err.response?.data?.message || t('adminPages.companies.delete_error')
  } finally {
    deleting.value = false
  }
}

function startEdit() {
  const currentIndustry = selectedCompany.value.industry || ''
  const customIndustry = !!currentIndustry && !industryOptions.includes(currentIndustry)
  Object.assign(editForm, {
    email: selectedCompany.value.email || '',
    phone: selectedCompany.value.phone || '',
    industry: customIndustry ? 'Autre' : currentIndustry,
    size: selectedCompany.value.size || '',
    address: selectedCompany.value.address || '',
    otherIndustry: customIndustry ? currentIndustry : '',
    status: selectedCompany.value.status || 'active',
    plan_id: selectedCompany.value.plan?.id || '',
    trial_ends_at: selectedCompany.value.trial_ends_at ? String(selectedCompany.value.trial_ends_at).slice(0, 10) : '',
    suspended_until: selectedCompany.value.suspended_until ? String(selectedCompany.value.suspended_until).slice(0, 10) : '',
  })
  editing.value = true
}

function cancelEdit() {
  editing.value = false
}

async function saveCompany() {
  const payload = { ...editForm }
  if (payload.industry === industryOptions[industryOptions.length - 1]) {
    payload.industry = payload.otherIndustry.trim() || industryOptions[industryOptions.length - 1]
  }
  delete payload.otherIndustry
  if (!payload.plan_id) payload.plan_id = null
  if (!payload.trial_ends_at) payload.trial_ends_at = null
  if (!payload.suspended_until) payload.suspended_until = null
  try {
    await axios.put(`/admin/companies/${selectedCompany.value.id}`, payload)
    await showCompanyDetails(selectedCompany.value.id)
    fetchCompanies()
    editing.value = false
  } catch (err) { console.error(err) }
}

async function suspend(company) {
  if (!confirm(t('adminPages.companies.confirm_block', { name: company.name }))) return
  try {
    await axios.post(`/admin/companies/${company.id}/suspend`)
    company.status = 'suspended'
    fetchCompanies()
  } catch (err) { console.error(err) }
}

async function activate(company) {
  try {
    await axios.post(`/admin/companies/${company.id}/activate`)
    company.status = 'active'
    fetchCompanies()
  } catch (err) { console.error(err) }
}

async function assignPlan(company, planId) {
  if (!planId) return
  assignPlanLoading.value = true
  try {
    await axios.post(`/admin/companies/${company.id}/assign-plan`, { plan_id: planId })
    await showCompanyDetails(company.id)
    fetchCompanies()
  } catch (err) {
    console.error(t('adminPages.companies.assign_plan_error'), err)
  } finally {
    assignPlanLoading.value = false
  }
}

onMounted(async () => {
  await fetchCompanies()
  try {
    const { data } = await axios.get('/admin/companies/stats')
    companyStats.value = data
    plans.value = data.plans || []
  } catch {}
})
</script>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.22s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-pop-enter-active { animation: modal-pop 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
.modal-pop-leave-active { transition: opacity 0.16s ease, transform 0.16s ease; }
.modal-pop-enter-from { opacity: 0; transform: scale(0.96) translateY(14px); }
.modal-pop-leave-to { opacity: 0; transform: scale(0.97) translateY(8px); }
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgb(16 185 129 / 0.3); border-radius: 99px; }
</style>
