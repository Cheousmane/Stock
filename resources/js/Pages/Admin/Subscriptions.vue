<template>
  <SuperAdminLayout>
    <div class="space-y-5">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
          <p class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-[0.16em] text-emerald-600 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-3 py-1">
            {{ $t('adminPages.subscriptions.eyebrow', { count: pagination?.total ?? '…' }) }}
          </p>
          <h2 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-text-primary">{{ $t('admin.subscriptions') }}</h2>
          <p class="text-sm text-text-tertiary mt-1 font-medium">
            {{ $t('adminPages.subscriptions.description') }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="refreshAll" :disabled="loading" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-extrabold rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 shadow hover:-translate-y-px active:scale-[0.98] disabled:opacity-50 transition">
            <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-4.992 4.992l2.121-2.121a9 9 0 11-3.464 8.193" />
            </svg>
            {{ $t('common.refresh') }}
          </button>
        </div>
      </div>

      <!-- Plan distribution -->
      <div v-if="plans.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div v-for="plan in plans" :key="plan.id" class="p-4 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-emerald-400/40 transition-all">
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-extrabold text-text-primary truncate">{{ plan.name }}</p>
            <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full" :class="plan.slug === 'free' ? 'bg-neutral-500/10 text-neutral-500 border border-neutral-500/20' : 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20'">{{ plan.slug }}</span>
          </div>
          <p class="mt-2 text-xl font-black tabular-nums text-text-primary">{{ plan.companies_count }} <span class="text-xs font-bold text-text-tertiary">{{ $t('adminPages.companies.company_plural') }}</span></p>
          <div class="mt-0.5 flex items-center justify-between gap-2">
            <p class="text-xs font-semibold text-text-tertiary">{{ plan.price_xof > 0 ? formatCurrency(plan.price_xof, plan.currency) + '/' + $t('adminPages.subscriptions.month') : $t('adminPages.subscriptions.free') }}</p>
            <button
              type="button"
              class="text-[11px] font-extrabold text-emerald-700 dark:text-emerald-400 hover:underline disabled:opacity-50"
              :disabled="savingPlanId === plan.id"
              @click="startEditingPlan(plan)"
            >{{ $t('common.edit') }}</button>
          </div>
          <form v-if="editingPlanId === plan.id" class="mt-3 space-y-2" @submit.prevent="savePlanPrice(plan)">
            <label class="block text-[11px] font-bold text-text-secondary" :for="`plan-price-${plan.id}`">{{ $t('adminPages.subscriptions.monthly_price') }}</label>
            <input
              :id="`plan-price-${plan.id}`"
              v-model.number="planPriceInput"
              type="number"
              min="0"
              max="1000000000000"
              step="1"
              required
              class="w-full px-3 py-2 text-sm font-bold rounded-xl border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/10 outline-none"
            />
            <p v-if="planError" class="text-xs font-semibold text-red-600">{{ planError }}</p>
            <div class="flex items-center gap-2">
              <button type="submit" :disabled="savingPlanId === plan.id" class="px-3 py-1.5 text-xs font-extrabold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50">
                {{ savingPlanId === plan.id ? $t('common.saving') : $t('common.save') }}
              </button>
              <button type="button" class="px-2 py-1.5 text-xs font-bold text-text-tertiary hover:text-text-primary" @click="cancelEditingPlan">{{ $t('common.cancel') }}</button>
            </div>
          </form>
          <div class="mt-2 flex flex-wrap gap-1 text-[10px] font-bold text-text-tertiary">
            <span class="px-1.5 py-0.5 rounded bg-surface-secondary border border-border/60">{{ plan.quotas?.max_users ?? '∞' }} {{ $t('adminPages.subscriptions.users_short') }}</span>
            <span class="px-1.5 py-0.5 rounded bg-surface-secondary border border-border/60">{{ plan.quotas?.max_products ?? '∞' }} {{ $t('adminPages.subscriptions.products_short') }}</span>
            <span class="px-1.5 py-0.5 rounded bg-surface-secondary border border-border/60">{{ plan.quotas?.max_invoices ?? '∞' }} {{ $t('adminPages.subscriptions.invoices_short') }}</span>
            <span class="px-1.5 py-0.5 rounded bg-surface-secondary border border-border/60">{{ plan.quotas?.max_warehouses ?? '∞' }} {{ $t('adminPages.subscriptions.warehouses_short') }}</span>
          </div>
        </div>
      </div>

      <!-- Over-quota free companies -->
      <div v-if="overQuota.length" class="rounded-[1.25rem] border border-amber-500/30 bg-amber-500/[0.06] overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 border-b border-amber-500/20">
          <div class="flex items-center gap-2.5">
            <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-500 text-white shadow-md shadow-amber-500/25">
              <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </span>
            <div>
              <p class="text-sm font-extrabold text-text-primary">{{ $t('adminPages.subscriptions.over_quota_title') }}</p>
              <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.over_quota_description') }}</p>
            </div>
          </div>
          <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold tabular-nums bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/25">{{ overQuota.length }}</span>
        </div>
        <div class="divide-y divide-amber-500/15">
          <button
            v-for="row in overQuota"
            :key="row.company.id"
            @click="openDetails(row.company.id)"
            class="w-full flex flex-wrap items-center gap-3 px-5 py-3 text-left hover:bg-amber-500/10 transition"
          >
            <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 font-black text-sm shrink-0">{{ (row.company.name || '?').charAt(0).toUpperCase() }}</span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-extrabold text-text-primary truncate">{{ row.company.name }}</p>
              <p class="text-xs font-mono text-text-tertiary truncate">{{ row.company.slug }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span v-for="item in row.quotas.resources.filter(r => r.over_quota)" :key="item.resource" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/25">
                {{ quotaLabel(item) }} {{ item.used }}/{{ item.limit }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-surface border border-border text-text-secondary">{{ $t('adminPages.companies.frozen_count', { count: row.quotas.frozen_total }) }}</span>
            </div>
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
          </button>
        </div>
      </div>

      <!-- Filters -->
      <div class="pro-filterbar">
        <label class="flex items-center gap-2.5 flex-1 min-w-0 px-3.5 py-2.5 rounded-2xl bg-surface-secondary/70 border border-border/60 focus-within:border-emerald-500/60 focus-within:ring-4 focus-within:ring-emerald-500/10 transition">
          <svg class="w-4 h-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
          <input v-model="filters.search" :placeholder="$t('adminPages.companies.search')" class="w-full bg-transparent text-sm font-semibold text-text-primary placeholder:text-text-tertiary placeholder:font-medium outline-none" @input="debouncedSearch" />
        </label>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="filters.subscription_status" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchCompanies(1)">
            <option value="">{{ $t('adminPages.subscriptions.all_subscriptions') }}</option>
            <option value="trial">{{ $t('adminPages.companies.trial_in_progress') }}</option>
            <option value="expiring">{{ $t('adminPages.subscriptions.expiring_soon') }}</option>
            <option value="frozen">{{ $t('adminPages.subscriptions.with_frozen_data') }}</option>
          </select>
          <select v-model="filters.plan_id" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchCompanies(1)">
            <option value="">{{ $t('adminPages.subscriptions.all_plans') }}</option>
            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }}</option>
          </select>
          <select v-model="filters.status" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchCompanies(1)">
            <option value="">{{ $t('adminPages.subscriptions.all_statuses') }}</option>
            <option value="active">{{ $t('adminPages.companies.statuses.active') }}</option>
            <option value="trial">{{ $t('adminPages.companies.statuses.trial') }}</option>
            <option value="suspended">{{ $t('adminPages.companies.statuses.suspended') }}</option>
            <option value="disabled">{{ $t('adminPages.companies.statuses.disabled') }}</option>
          </select>
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
          <table class="w-full min-w-[980px]">
            <thead>
              <tr class="border-b border-border/70 text-left text-[11px] font-extrabold text-text-tertiary uppercase tracking-[0.1em] bg-surface-secondary/50">
                <th class="px-5 py-4">{{ $t('adminPages.companies.company') }}</th>
                <th class="px-5 py-4">{{ $t('adminPages.companies.plan') }}</th>
                <th class="px-5 py-4">{{ $t('common.status') }}</th>
                <th class="px-5 py-4">{{ $t('adminPages.subscriptions.trial') }}</th>
                <th class="px-5 py-4">{{ $t('admin.subscriptions') }}</th>
                <th class="px-5 py-4">{{ $t('adminPages.companies.frozen') }}</th>
                <th class="px-5 py-4 text-right">{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border/50">
              <tr v-for="company in companies" :key="company.id" class="hover:bg-emerald-500/[0.04] transition-colors group">
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-sm font-black shrink-0 shadow-md shadow-emerald-500/20">{{ (company.name || '?').charAt(0).toUpperCase() }}</span>
                    <div class="min-w-0">
                      <p class="text-sm font-extrabold text-text-primary truncate">{{ company.name }}</p>
                      <p class="text-xs text-text-tertiary font-mono truncate">{{ company.slug }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <p class="text-[13px] font-extrabold text-text-primary">{{ company.plan?.name || '—' }}</p>
                  <p class="text-[11px] font-semibold text-text-tertiary">{{ company.plan?.price_xof > 0 ? formatCurrency(company.plan.price_xof, 'XOF') + '/' + $t('adminPages.subscriptions.month') : $t('adminPages.subscriptions.free') }}</p>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full" :class="statusClass(company.status)">{{ statusLabel(company.status) }}</span>
                </td>
                <td class="px-5 py-3.5">
                  <template v-if="company.trial_ends_at">
                    <p class="text-[13px] font-bold tabular-nums" :class="company.trial_active ? 'text-amber-600 dark:text-amber-400' : 'text-text-secondary'">
                      {{ company.trial_active ? $t('adminPages.subscriptions.days_remaining', { count: daysLeft(company.trial_ends_at) }) : $t('adminPages.subscriptions.ended') }}
                    </p>
                    <p class="text-[11px] font-medium text-text-tertiary tabular-nums">{{ dateShort(company.trial_ends_at) }}</p>
                  </template>
                  <span v-else class="text-xs font-semibold text-text-tertiary">—</span>
                </td>
                <td class="px-5 py-3.5">
                  <template v-if="company.subscription">
                    <p class="text-[13px] font-bold text-text-primary tabular-nums">{{ dateShort(company.subscription.ends_at) }}</p>
                    <span class="inline-flex mt-0.5 px-1.5 py-px rounded text-[10px] font-extrabold uppercase tracking-wider" :class="subscriptionClass(company.subscription.status)">{{ subscriptionLabel(company.subscription.status) }}</span>
                  </template>
                  <span v-else class="text-xs font-semibold text-text-tertiary">—</span>
                </td>
                <td class="px-5 py-3.5">
                  <span v-if="company.frozen_total > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                    {{ company.frozen_total }}
                  </span>
                  <span v-else class="text-xs font-semibold text-text-tertiary">0</span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                    <button @click="openDetails(company.id)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-blue-600 hover:text-white bg-blue-500/10 hover:bg-blue-600 border border-blue-500/20 rounded-xl transition">{{ $t('adminPages.companies.manage') }}</button>
                    <button v-if="company.status !== 'suspended'" @click="quickSuspend(company)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-rose-600 hover:text-white bg-rose-500/10 hover:bg-rose-600 border border-rose-500/20 rounded-xl transition">{{ $t('adminPages.subscriptions.suspend') }}</button>
                    <button v-else @click="quickReactivate(company)" class="px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 hover:text-white bg-emerald-500/10 hover:bg-emerald-600 border border-emerald-500/20 rounded-xl transition">{{ $t('adminPages.subscriptions.reactivate') }}</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="!loading && companies.length === 0" class="p-12 text-center">
          <div class="mx-auto w-14 h-14 rounded-[1.25rem] bg-surface-secondary border border-border flex items-center justify-center mb-3 text-text-tertiary">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
          </div>
          <p class="text-sm font-extrabold text-text-primary">{{ $t('adminPages.subscriptions.empty_title') }}</p>
          <p class="text-xs text-text-tertiary mt-1">{{ $t('adminPages.companies.empty_description') }}</p>
        </div>

        <div v-if="pagination" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-t border-border/70 bg-surface-secondary/40">
          <p class="text-xs font-bold text-text-tertiary tabular-nums">{{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} sur {{ pagination.total }}</p>
          <div class="flex items-center gap-1.5">
            <button :disabled="!pagination.prev_page_url" @click="fetchCompanies(pagination.current_page - 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">←</button>
            <span class="px-3 text-[13px] font-extrabold text-text-primary tabular-nums">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="fetchCompanies(pagination.current_page + 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">→</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail / management modal -->
    <Transition name="modal-fade">
      <div v-if="detail" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-neutral-950/60 backdrop-blur-md" @click.self="closeDetails">
        <Transition name="modal-pop">
          <div v-if="detail" class="bg-surface w-full max-w-5xl rounded-[28px] shadow-modal border border-border/60 overflow-hidden flex flex-col max-h-[92vh]">
            <!-- Hero -->
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 text-white shrink-0">
              <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-16 -right-12 h-52 w-52 rounded-full bg-white/15 blur-3xl" />
                <div class="absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-teal-950/25 blur-3xl" />
                <div class="absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 20px 20px;" />
              </div>
              <div class="relative flex items-start gap-4 p-5 sm:p-6">
                <span class="flex items-center justify-center w-14 h-14 rounded-[1.25rem] bg-white/20 border border-white/30 backdrop-blur text-2xl font-black shadow-lg shrink-0">{{ (detail.company.name || '?').charAt(0).toUpperCase() }}</span>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-xl sm:text-2xl font-black tracking-tight truncate">{{ detail.company.name }}</h3>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/15 border border-white/25 backdrop-blur">
                      <span class="w-1.5 h-1.5 rounded-full bg-current mr-1.5" />{{ statusLabel(detail.company.status) }}
                    </span>
                    <span v-if="detail.plan" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-neutral-950/25 border border-white/25 backdrop-blur">◆ {{ detail.plan.name }}</span>
                    <span v-if="detail.quotas.over_quota" class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-400 text-amber-950 border border-amber-300">{{ $t('adminPages.subscriptions.quotas_exceeded') }}</span>
                  </div>
                  <p class="mt-1 text-xs font-mono text-emerald-50/80 truncate">{{ detail.company.slug }} · #{{ detail.company.id }}</p>
                  <p class="text-[13px] text-emerald-50/90 mt-0.5 truncate">{{ detail.company.email || '—' }}</p>
                </div>
                <button @click="closeDetails" class="flex items-center justify-center w-9 h-9 rounded-2xl bg-white/15 border border-white/25 backdrop-blur hover:bg-white/30 active:scale-95 transition shrink-0" :title="$t('common.close')">
                  <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
              <div class="relative grid grid-cols-2 sm:grid-cols-4 border-t border-white/15 divide-x divide-white/15 bg-emerald-950/15 backdrop-blur text-center">
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.users') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ quotaOf('users')?.used ?? 0 }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('nav.products') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ quotaOf('products')?.used ?? 0 }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('nav.invoices') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ quotaOf('invoices')?.used ?? 0 }}</p>
                </div>
                <div class="px-3 py-3">
                  <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ $t('adminPages.companies.frozen') }}</p>
                  <p class="text-lg font-black tabular-nums">{{ detail.quotas.frozen_total }}</p>
                </div>
              </div>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 bg-surface-secondary/40 space-y-4 custom-scrollbar">
              <!-- Feedback -->
              <div v-if="notice.message" class="flex items-start gap-2.5 p-3.5 rounded-2xl border text-sm font-semibold" :class="notice.type === 'error' ? 'bg-rose-500/10 border-rose-500/25 text-rose-700 dark:text-rose-400' : 'bg-emerald-500/10 border-emerald-500/25 text-emerald-700 dark:text-emerald-400'">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ notice.message }}
              </div>

              <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
                <!-- Subscription state -->
                <section class="lg:col-span-3 p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm">
                  <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.subscriptions.subscription_state') }}</h4>
                  <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.state_description') }}</p>

                  <div class="mt-4 grid sm:grid-cols-2 gap-2.5 text-xs font-semibold">
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.subscriptions.current_plan') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold text-text-primary">{{ detail.plan?.name || $t('common.none') }}</p>
                    </div>
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.subscriptions.company_status') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold text-text-primary">{{ statusLabel(detail.company.status) }}</p>
                    </div>
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.subscriptions.trial_start') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold text-text-primary tabular-nums">{{ formatDateOrDash(detail.company.trial_started_at) }}</p>
                    </div>
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.companies.trial_end') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold tabular-nums" :class="detail.company.trial_active ? 'text-amber-600 dark:text-amber-400' : 'text-text-primary'">
                        {{ formatDateOrDash(detail.company.trial_ends_at) }}
                        <span v-if="detail.company.trial_active" class="ml-1 text-[10px] font-extrabold uppercase">({{ $t('adminPages.subscriptions.days_short', { count: daysLeft(detail.company.trial_ends_at) }) }})</span>
                      </p>
                    </div>
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.subscriptions.subscription_start') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold text-text-primary tabular-nums">{{ formatDateOrDash(detail.subscription?.starts_at) }}</p>
                    </div>
                    <div class="rounded-xl bg-surface-secondary/60 border border-border/50 px-3 py-2.5">
                      <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary">{{ $t('adminPages.companies.subscription_end') }}</p>
                      <p class="mt-0.5 text-[13px] font-bold text-text-primary tabular-nums">{{ formatDateOrDash(detail.subscription?.ends_at) }}</p>
                    </div>
                  </div>

                  <div v-if="detail.subscription?.notes" class="mt-3 rounded-xl bg-blue-500/[0.07] border border-blue-500/20 px-3 py-2 text-xs font-semibold text-text-secondary">
                    <span class="font-extrabold text-blue-600 dark:text-blue-400">{{ $t('common.note') }}:</span> {{ detail.subscription.notes }}
                  </div>

                  <!-- Quotas -->
                  <div class="mt-4 pt-4 border-t border-border">
                    <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-text-tertiary mb-2">{{ $t('adminPages.subscriptions.plan_quotas') }}</p>
                    <div class="space-y-2">
                      <div v-for="item in detail.quotas.resources" :key="item.resource" class="flex items-center gap-2.5">
                        <span class="w-24 shrink-0 text-xs font-bold text-text-secondary">{{ quotaLabel(item) }}</span>
                        <div class="flex-1 h-2 rounded-full bg-surface-secondary border border-border/50 overflow-hidden">
                          <div class="h-full rounded-full" :class="item.over_quota ? 'bg-amber-500' : 'bg-emerald-500'" :style="{ width: quotaBarWidth(item) }"></div>
                        </div>
                        <span class="w-24 shrink-0 text-right text-[11px] font-extrabold tabular-nums" :class="item.over_quota ? 'text-amber-600 dark:text-amber-400' : 'text-text-primary'">
                          {{ item.used }}/{{ item.limit < 0 ? '∞' : item.limit }}
                          <span v-if="item.frozen > 0" class="text-amber-600 dark:text-amber-400"> · {{ $t('adminPages.companies.frozen_count', { count: item.frozen }) }}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                </section>

                <!-- Actions -->
                <section class="lg:col-span-2 p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm">
                  <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('common.actions') }}</h4>
                  <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.manual_management') }}</p>

                  <div class="mt-4 space-y-4">
                    <!-- Assign -->
                    <div>
                      <label class="block text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ $t('adminPages.subscriptions.assign_plan') }}</label>
                      <select v-model="forms.assign.plan_id" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition cursor-pointer">
                        <option value="">{{ $t('adminPages.subscriptions.choose_plan') }}</option>
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name }} — {{ plan.price_xof > 0 ? formatCurrency(plan.price_xof, plan.currency) : $t('adminPages.subscriptions.free') }}</option>
                      </select>
                      <input v-model="forms.assign.ends_at" type="date" class="mt-1.5 w-full px-3 py-2 text-sm font-medium rounded-xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:border-emerald-500 transition" :title="$t('adminPages.subscriptions.optional_end_date')" />
                      <button @click="runAction('assign')" :disabled="actionLoading || !forms.assign.plan_id" class="mt-1.5 w-full px-3 py-2 text-xs font-extrabold text-white bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-md shadow-emerald-500/20 hover:brightness-110 active:scale-[0.98] disabled:opacity-40 transition">
                        {{ actionLoading === 'assign' ? $t('adminPages.subscriptions.applying') : $t('adminPages.subscriptions.apply_plan') }}
                      </button>
                    </div>

                    <!-- Renew -->
                    <div class="pt-3 border-t border-border/70">
                      <label class="block text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ $t('adminPages.subscriptions.renew') }}</label>
                      <div class="flex gap-1.5">
                        <select v-model.number="forms.renew.months" class="flex-1 min-w-0 px-3 py-2 text-sm font-semibold rounded-xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:border-emerald-500 transition cursor-pointer">
                          <option :value="1">{{ $t('adminPages.subscriptions.month_count', { count: 1 }) }}</option>
                          <option :value="3">{{ $t('adminPages.subscriptions.month_count', { count: 3 }) }}</option>
                          <option :value="6">{{ $t('adminPages.subscriptions.month_count', { count: 6 }) }}</option>
                          <option :value="12">{{ $t('adminPages.subscriptions.month_count', { count: 12 }) }}</option>
                          <option :value="24">{{ $t('adminPages.subscriptions.month_count', { count: 24 }) }}</option>
                        </select>
                        <button @click="runAction('renew')" :disabled="actionLoading || !detail.plan || detail.plan.slug === 'free'" class="px-3 py-2 text-xs font-extrabold text-white bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-md shadow-blue-500/20 hover:brightness-110 active:scale-[0.98] disabled:opacity-40 transition shrink-0">
                          {{ actionLoading === 'renew' ? '…' : $t('adminPages.subscriptions.renew') }}
                        </button>
                      </div>
                      <p v-if="!detail.plan || detail.plan.slug === 'free'" class="mt-1 text-[10px] font-semibold text-text-tertiary">{{ $t('adminPages.subscriptions.assign_paid_plan_first') }}</p>
                    </div>

                    <!-- Trial -->
                    <div class="pt-3 border-t border-border/70">
                      <label class="block text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ $t('adminPages.subscriptions.trial_period') }}</label>
                      <div class="flex gap-1.5">
                        <input v-model.number="forms.trial.days" type="number" min="1" max="365" class="w-20 px-2.5 py-2 text-sm font-semibold rounded-xl border border-border bg-surface-secondary/50 text-text-primary outline-none focus:border-emerald-500 transition" />
                        <button @click="runAction('extend')" :disabled="actionLoading || !forms.trial.days" class="flex-1 px-3 py-2 text-xs font-extrabold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/25 rounded-xl active:scale-[0.98] disabled:opacity-40 transition">
                          {{ actionLoading === 'extend' ? '…' : $t('adminPages.subscriptions.extend') }}
                        </button>
                        <button @click="runAction('reset')" :disabled="actionLoading" class="flex-1 px-3 py-2 text-xs font-extrabold text-amber-700 dark:text-amber-400 bg-amber-500/10 hover:bg-amber-500/15 border border-amber-500/25 rounded-xl active:scale-[0.98] disabled:opacity-40 transition">
                          {{ actionLoading === 'reset' ? '…' : $t('adminPages.subscriptions.reset') }}
                        </button>
                      </div>
                      <p class="mt-1 text-[10px] font-semibold text-text-tertiary">{{ $t('adminPages.subscriptions.reset_trial_description') }}</p>
                    </div>

                    <!-- Suspend / sync -->
                    <div class="pt-3 border-t border-border/70 flex flex-wrap gap-1.5">
                      <button v-if="detail.company.status !== 'suspended'" @click="runAction('suspend')" :disabled="actionLoading" class="flex-1 px-3 py-2 text-xs font-extrabold text-rose-600 bg-rose-500/10 hover:bg-rose-500 hover:text-white border border-rose-500/25 rounded-xl active:scale-[0.98] disabled:opacity-40 transition">
                        {{ actionLoading === 'suspend' ? '…' : $t('adminPages.subscriptions.suspend') }}
                      </button>
                      <button v-else @click="runAction('reactivate')" :disabled="actionLoading" class="flex-1 px-3 py-2 text-xs font-extrabold text-emerald-600 bg-emerald-500/10 hover:bg-emerald-500 hover:text-white border border-emerald-500/25 rounded-xl active:scale-[0.98] disabled:opacity-40 transition">
                        {{ actionLoading === 'reactivate' ? '…' : $t('adminPages.subscriptions.reactivate') }}
                      </button>
                      <button @click="runAction('sync')" :disabled="actionLoading" class="flex-1 px-3 py-2 text-xs font-extrabold text-text-secondary bg-surface-secondary hover:bg-surface-tertiary border border-border rounded-xl active:scale-[0.98] disabled:opacity-40 transition">
                        {{ actionLoading === 'sync' ? '…' : $t('adminPages.subscriptions.sync_frozen') }}
                      </button>
                    </div>
                  </div>
                </section>
              </div>

              <!-- Frozen records -->
              <section v-if="hasFrozenRecords" class="p-5 rounded-[1.25rem] bg-surface border border-amber-500/25 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                  <div>
                    <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.subscriptions.frozen_data') }}</h4>
                    <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.frozen_data_description') }}</p>
                  </div>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold tabular-nums bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20">{{ detail.quotas.frozen_total }}</span>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                  <div v-for="group in frozenGroups" :key="group.key" class="rounded-2xl bg-surface-secondary/60 border border-border/50 p-3">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-tertiary mb-1.5">{{ group.label }} ({{ group.items.length }})</p>
                    <ul v-if="group.items.length" class="space-y-1 max-h-36 overflow-y-auto pr-1 custom-scrollbar">
                      <li v-for="item in group.items" :key="item.id" class="flex items-center justify-between gap-2 text-[12px]">
                        <span class="font-semibold text-text-primary truncate">{{ item.label }}</span>
                        <span class="font-mono text-[10px] text-text-tertiary truncate">{{ item.reference }}</span>
                      </li>
                    </ul>
                    <p v-else class="text-[11px] font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.no_items') }}</p>
                  </div>
                </div>
              </section>

              <!-- History -->
              <section class="p-5 rounded-[1.25rem] bg-surface border border-border/70 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                  <div>
                    <h4 class="text-[15px] font-extrabold tracking-tight text-text-primary">{{ $t('adminPages.subscriptions.history') }}</h4>
                    <p class="text-xs font-medium text-text-tertiary">{{ $t('adminPages.subscriptions.history_description') }}</p>
                  </div>
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold tabular-nums bg-surface-secondary border border-border text-text-secondary">{{ detail.history?.length || 0 }}</span>
                </div>
                <div v-if="!detail.history || detail.history.length === 0" class="py-5 text-center text-sm font-bold text-text-tertiary">{{ $t('adminPages.subscriptions.no_history') }}</div>
                <ol v-else class="relative ml-2 space-y-1 max-h-56 overflow-y-auto pr-2 custom-scrollbar">
                  <li v-for="entry in detail.history" :key="entry.id" class="relative ml-5 pl-4 pb-4 border-l-2 border-border/70 last:pb-0">
                    <span class="absolute -left-[7px] top-1 w-3 h-3 rounded-full border-[2.5px] border-surface shadow" :class="eventDotClass(entry.event)"></span>
                    <p class="text-[13px] font-semibold text-text-primary leading-snug">{{ entry.description }}</p>
                    <p class="text-[11px] font-medium text-text-tertiary mt-1 flex items-center gap-1.5 flex-wrap">
                      {{ dateTime(entry.created_at) }} · {{ entry.user ? entry.user.name : $t('adminPages.activity.system') }}
                      <span v-if="entry.event" class="px-2 py-px rounded-full text-[10px] font-extrabold uppercase tracking-wider border bg-surface-secondary border-border text-text-tertiary">{{ entry.event }}</span>
                    </p>
                  </li>
                </ol>
              </section>
            </div>

            <div class="px-4 sm:px-6 py-4 border-t border-border/70 bg-surface/90 backdrop-blur flex justify-end gap-2.5 shrink-0">
              <button @click="closeDetails" class="px-5 py-2.5 text-sm font-bold text-text-secondary border border-border hover:bg-surface-tertiary rounded-2xl transition active:scale-[0.98]">{{ $t('common.close') }}</button>
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
import { formatPrice, formatDateShort, formatDateTime } from '../../utils/format'

const { t, locale } = useI18n()

const companies = ref([])
const plans = ref([])
const overQuota = ref([])
const loading = ref(true)
const pagination = ref(null)
const detail = ref(null)
const actionLoading = ref('')
const notice = reactive({ type: 'success', message: '' })
const editingPlanId = ref(null)
const planPriceInput = ref(0)
const savingPlanId = ref(null)
const planError = ref('')

const filters = reactive({ search: '', status: '', plan_id: '', subscription_status: '' })

const forms = reactive({
  assign: { plan_id: '', ends_at: '' },
  renew: { months: 1 },
  trial: { days: 14 },
})

const STATUS_LABELS = {
  active: 'adminPages.companies.statuses.active',
  trial: 'adminPages.companies.statuses.trial',
  suspended: 'adminPages.companies.statuses.suspended',
  disabled: 'adminPages.companies.statuses.disabled',
}
const SUBSCRIPTION_LABELS = {
  active: 'adminPages.companies.subscription_statuses.active',
  suspended: 'adminPages.companies.subscription_statuses.suspended',
  expired: 'adminPages.companies.subscription_statuses.expired',
  canceled: 'adminPages.companies.subscription_statuses.canceled',
}

function statusLabel(status) {
  return STATUS_LABELS[status] ? t(STATUS_LABELS[status]) : status || '—'
}

function statusClass(status) {
  const map = {
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300',
    trial: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    disabled: 'bg-gray-100 text-gray-500 dark:bg-neutral-800 dark:text-neutral-400',
  }
  return map[status] || 'bg-gray-100 text-gray-500 dark:bg-neutral-800 dark:text-neutral-400'
}

function subscriptionLabel(status) {
  return SUBSCRIPTION_LABELS[status] ? t(SUBSCRIPTION_LABELS[status]) : status || '—'
}

function subscriptionClass(status) {
  const map = {
    active: 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20',
    suspended: 'bg-rose-500/10 text-rose-600 border border-rose-500/20',
    expired: 'bg-neutral-500/10 text-neutral-500 border border-neutral-500/20',
    canceled: 'bg-neutral-500/10 text-neutral-500 border border-neutral-500/20',
  }
  return map[status] || 'bg-surface-secondary text-text-tertiary border border-border'
}

function daysLeft(value) {
  if (!value) return 0
  const diff = new Date(value).getTime() - Date.now()
  return Math.max(0, Math.ceil(diff / 86400000))
}

function formatDateOrDash(value) {
  return value ? formatDateShort(value, locale.value) : '—'
}

function dateShort(value) {
  return formatDateShort(value, locale.value)
}

function dateTime(value) {
  return formatDateTime(value, locale.value)
}

function formatCurrency(value, currency = 'XOF') {
  return formatPrice(value, currency, locale.value)
}

function quotaLabel(item) {
  const keys = {
    users: 'adminPages.companies.users',
    products: 'nav.products',
    invoices: 'nav.invoices',
    warehouses: 'nav.warehouses',
    customers: 'nav.customers',
  }
  return keys[item.resource] ? t(keys[item.resource]) : item.label
}

function quotaOf(resource) {
  return detail.value?.quotas?.resources?.find((item) => item.resource === resource)
}

function quotaBarWidth(item) {
  if (item.limit < 0) return '100%'
  if (item.limit === 0) return '100%'
  return `${Math.min(100, Math.round((item.used / item.limit) * 100))}%`
}

const frozenGroups = computed(() => {
  const records = detail.value?.frozen_records || {}
  return [
    { key: 'products', label: t('nav.products'), items: records.products || [] },
    { key: 'invoices', label: t('nav.invoices'), items: records.invoices || [] },
    { key: 'warehouses', label: t('nav.warehouses'), items: records.warehouses || [] },
    { key: 'users', label: t('adminPages.companies.users'), items: records.users || [] },
  ]
})

const hasFrozenRecords = computed(() => frozenGroups.value.some((group) => group.items.length > 0))

function eventDotClass(event) {
  if (event === 'subscription.assigned') return 'bg-emerald-500'
  if (event === 'subscription.renewed') return 'bg-blue-500'
  if (event === 'subscription.trial_started' || event === 'subscription.trial_extended' || event === 'subscription.trial_reset') return 'bg-amber-500'
  if (event === 'subscription.suspended') return 'bg-rose-500'
  if (event === 'subscription.reactivated') return 'bg-teal-500'
  if (event === 'subscription.expired' || event === 'subscription.trial_ended') return 'bg-neutral-400'
  return 'bg-gray-400'
}

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchCompanies(1), 300)
}

async function fetchCompanies(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20, ...filters }
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k] })
    const { data } = await axios.get('/admin/subscriptions', { params })
    companies.value = data.data || []
    pagination.value = {
      current_page: data.current_page ?? data.meta?.current_page ?? 1,
      last_page: data.last_page ?? data.meta?.last_page ?? 1,
      total: data.total ?? data.meta?.total ?? 0,
      from: data.from ?? data.meta?.from ?? 0,
      to: data.to ?? data.meta?.to ?? 0,
      prev_page_url: data.prev_page_url ?? data.links?.prev ?? null,
      next_page_url: data.next_page_url ?? data.links?.next ?? null,
    }
  } catch (err) {
    console.error('Erreur chargement abonnements', err)
  } finally {
    loading.value = false
  }
}

async function fetchPlans() {
  try {
    const { data } = await axios.get('/admin/subscriptions/plans')
    plans.value = data.data || []
  } catch (err) {
    console.error('Erreur chargement plans', err)
  }
}

function startEditingPlan(plan) {
  editingPlanId.value = plan.id
  planPriceInput.value = plan.price_xof
  planError.value = ''
}

function cancelEditingPlan() {
  editingPlanId.value = null
  planError.value = ''
}

async function savePlanPrice(plan) {
  savingPlanId.value = plan.id
  planError.value = ''

  try {
    const { data } = await axios.put(`/admin/subscriptions/plans/${plan.id}`, {
      price_xof: planPriceInput.value,
    })
    Object.assign(plan, data.data)
    editingPlanId.value = null
  } catch (err) {
    console.error('Erreur mise à jour du tarif', err)
    planError.value = Object.values(err.response?.data?.errors || {}).flat()?.[0]
      || err.response?.data?.message
      || t('adminPages.subscriptions.price_update_error')
  } finally {
    savingPlanId.value = null
  }
}

async function fetchOverQuota() {
  try {
    const { data } = await axios.get('/admin/subscriptions/over-quota')
    overQuota.value = data.data || []
  } catch (err) {
    console.error('Erreur chargement sur-quota', err)
  }
}

async function refreshAll() {
  await Promise.all([fetchCompanies(pagination.value?.current_page || 1), fetchPlans(), fetchOverQuota()])
}

async function openDetails(id) {
  notice.message = ''
  try {
    const { data } = await axios.get(`/admin/subscriptions/${id}`)
    detail.value = data.data || data
    forms.assign.plan_id = detail.value.plan?.id || ''
    forms.assign.ends_at = ''
    forms.renew.months = 1
    forms.trial.days = detail.value.company?.trial_active ? Math.max(1, daysLeft(detail.value.company.trial_ends_at)) : 14
  } catch (err) {
    console.error('Erreur chargement abonnement', err)
  }
}

async function reloadDetails() {
  if (!detail.value) return
  await openDetails(detail.value.company.id)
}

function closeDetails() {
  detail.value = null
  notice.message = ''
  actionLoading.value = ''
}

async function runAction(action) {
  if (!detail.value) return
  const id = detail.value.company.id
  actionLoading.value = action
  notice.message = ''

  try {
    let response
    if (action === 'assign') {
      response = await axios.post(`/admin/subscriptions/${id}/assign`, {
        plan_id: forms.assign.plan_id,
        ends_at: forms.assign.ends_at || null,
      })
    } else if (action === 'renew') {
      response = await axios.post(`/admin/subscriptions/${id}/renew`, { months: forms.renew.months })
    } else if (action === 'extend') {
      response = await axios.post(`/admin/subscriptions/${id}/trial/extend`, { days: forms.trial.days })
    } else if (action === 'reset') {
      response = await axios.post(`/admin/subscriptions/${id}/trial/reset`, { days: forms.trial.days })
    } else if (action === 'suspend') {
      response = await axios.post(`/admin/subscriptions/${id}/suspend`)
    } else if (action === 'reactivate') {
      response = await axios.post(`/admin/subscriptions/${id}/reactivate`)
    } else if (action === 'sync') {
      response = await axios.post(`/admin/subscriptions/${id}/sync-freeze`)
    }

    notice.type = 'success'
    notice.message = response?.data?.message || t('adminPages.subscriptions.action_done')
    await reloadDetails()
    await Promise.all([fetchCompanies(pagination.value?.current_page || 1), fetchOverQuota(), fetchPlans()])
  } catch (err) {
    notice.type = 'error'
    notice.message = err.response?.data?.message
      || Object.values(err.response?.data?.errors || {}).flat()?.[0]
      || t('adminPages.subscriptions.action_error')
  } finally {
    actionLoading.value = ''
  }
}

async function quickSuspend(company) {
  if (!confirm(t('adminPages.subscriptions.confirm_suspend', { name: company.name }))) return
  try {
    await axios.post(`/admin/subscriptions/${company.id}/suspend`)
    await refreshAll()
  } catch (err) {
    console.error('Erreur suspension', err)
  }
}

async function quickReactivate(company) {
  try {
    await axios.post(`/admin/subscriptions/${company.id}/reactivate`)
    await refreshAll()
  } catch (err) {
    console.error('Erreur réactivation', err)
  }
}

onMounted(async () => {
  await Promise.all([fetchCompanies(), fetchPlans(), fetchOverQuota()])
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
