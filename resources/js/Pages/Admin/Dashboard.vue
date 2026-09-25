<template>
  <SuperAdminLayout>
    <div class="space-y-6 pb-10">
      <!-- Hero header -->
      <div class="relative overflow-hidden rounded-3xl border border-emerald-500/15 bg-gradient-to-br from-emerald-600 via-emerald-500 to-teal-500 text-white shadow-xl shadow-emerald-600/20">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
          <div class="absolute -top-20 -right-16 h-64 w-64 rounded-full bg-white/15 blur-3xl" />
          <div class="absolute -bottom-24 left-1/4 h-64 w-64 rounded-full bg-teal-950/25 blur-3xl" />
          <div class="absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 22px 22px;" />
        </div>
        <div class="relative flex flex-col lg:flex-row lg:items-center gap-6 p-6 sm:p-8">
          <div class="flex-1 min-w-0">
            <p class="inline-flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-[0.18em] text-emerald-50/90 bg-white/15 border border-white/20 rounded-full px-3 py-1.5 backdrop-blur">
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse" /> Console super admin · {{ todayLabel }}
            </p>
            <h2 class="mt-3 text-2xl sm:text-[32px] font-black tracking-tight leading-tight">Pilotage global de la plateforme</h2>
            <p class="mt-1.5 text-sm text-emerald-50/90 max-w-xl">Supervision des locataires, revenus, sécurité et adoption — tout, en temps réel, au même endroit.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2.5">
              <div class="flex items-center gap-2 bg-white/15 border border-white/20 rounded-2xl px-3.5 py-2 backdrop-blur text-sm font-bold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                {{ formatXof(stats.invoiced_volume_30d) }} facturés · 30j
              </div>
              <div class="flex items-center gap-2 bg-white/15 border border-white/20 rounded-2xl px-3.5 py-2 backdrop-blur text-sm font-bold">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                {{ stats.active_companies }} actives / {{ stats.total_companies }} totales
              </div>
            </div>
          </div>
          <div class="flex flex-col sm:flex-row lg:flex-col gap-2.5 shrink-0">
            <button @click="reload" :disabled="loading" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-white text-emerald-700 text-sm font-extrabold shadow-lg hover:bg-emerald-50 active:scale-[0.98] disabled:opacity-60 transition">
              <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
              {{ loading ? 'Actualisation…' : 'Actualiser' }}
            </button>
            <router-link to="/admin/companies" @click="trackNavigationClick('/admin/companies', 'hero-gerer')" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-emerald-950/25 border border-white/25 text-white text-sm font-extrabold backdrop-blur hover:bg-emerald-950/40 active:scale-[0.98] transition">
              Gérer les entreprises
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" /></svg>
            </router-link>
          </div>
        </div>
        <div class="relative grid grid-cols-2 sm:grid-cols-4 border-t border-white/15 divide-x divide-white/15 bg-emerald-950/15 backdrop-blur text-center">
          <div v-for="m in heroMetrics" :key="m.label" class="px-4 py-3.5">
            <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-emerald-50/70">{{ m.label }}</p>
            <p class="text-base sm:text-lg font-black tabular-nums">{{ m.value }}</p>
          </div>
        </div>
      </div>

      <!-- KPI cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-4">
        <div v-if="loading" v-for="i in 6" :key="'sk-'+i" class="p-5 rounded-3xl bg-surface border border-border shadow-sm">
          <div class="h-11 w-11 rounded-2xl bg-surface-tertiary animate-pulse" />
          <div class="mt-4 h-7 w-2/3 rounded-lg bg-surface-tertiary animate-pulse" />
          <div class="mt-2 h-4 w-1/2 rounded bg-surface-tertiary animate-pulse" />
        </div>
        <article
          v-else
          v-for="card in statCards"
          :key="card.label"
          class="group relative overflow-hidden p-5 rounded-3xl bg-surface border border-border/70 shadow-[0_1px_3px_rgb(0_0_0/0.05)] hover:shadow-[0_16px_40px_-16px_rgb(16_185_129/0.35)] hover:-translate-y-1 hover:border-emerald-400/40 active:scale-[0.99] transition-all duration-300"
        >
          <div class="absolute -right-8 -top-8 w-28 h-28 rounded-full blur-3xl transition-transform duration-500 group-hover:scale-150" :class="card.glow" />
          <div class="relative flex items-start justify-between gap-3">
            <span class="flex items-center justify-center w-11 h-11 rounded-2xl text-white shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-3" :class="card.iconBg">
              <component :is="card.icon" class="w-5 h-5" />
            </span>
            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-extrabold border" :class="card.trendTone">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
              {{ card.trend }}
            </span>
          </div>
          <p class="relative mt-4 text-[11px] font-extrabold text-text-tertiary uppercase tracking-[0.12em]">{{ card.label }}</p>
          <p class="relative mt-1 text-[26px] font-black text-text-primary tracking-tight tabular-nums leading-none">{{ card.value }}</p>
          <p class="relative mt-2 text-xs font-semibold" :class="card.color">{{ card.sublabel }}</p>
          <div class="relative mt-3 h-1.5 rounded-full bg-surface-tertiary overflow-hidden">
            <div class="h-full rounded-full transition-all duration-700" :class="card.bar" :style="{ width: card.pct + '%' }" />
          </div>
        </article>
      </div>

      <!-- Alerts -->
      <div v-if="alerts.length > 0" class="grid gap-3">
        <div
          v-for="(alert, i) in alerts" :key="i"
          class="group flex items-center gap-4 p-4 rounded-3xl border shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-[0.995] transition-all"
          :class="alertTone(alert.severity).card"
        >
          <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-sm transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3" :class="alertTone(alert.severity).icon">
            <component :is="alertTone(alert.severity).iconCmp" class="w-5 h-5" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-extrabold text-text-primary flex items-center gap-2 flex-wrap">{{ alert.title }}
              <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded-full border" :class="alertTone(alert.severity).pill">{{ alert.severity }}</span>
            </p>
            <p class="text-[13px] text-text-secondary mt-0.5 truncate">{{ alert.message }}</p>
          </div>
          <router-link :to="alert.link" @click="trackAlertClick(alert.title, alert.severity)" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl text-[13px] font-extrabold text-white bg-neutral-900 hover:bg-neutral-700 dark:bg-white dark:text-neutral-900 dark:hover:bg-neutral-200 shrink-0 shadow active:scale-95 transition">
            Traiter
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M21 12H3" /></svg>
          </router-link>
        </div>
      </div>

      <!-- Plans + sans plan -->
      <div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
        <section class="xl:col-span-3 p-6 rounded-3xl bg-surface border border-border/70 shadow-sm">
          <div class="flex items-center justify-between gap-3 mb-1">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Distribution des plans</h3>
              <p class="text-xs text-text-tertiary font-medium">Répartition des {{ stats.total_companies }} entreprises par offre</p>
            </div>
            <router-link to="/admin/companies" class="inline-flex items-center gap-1 text-[13px] font-extrabold text-emerald-600 hover:text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/15 border border-emerald-500/20 px-3.5 py-2 rounded-xl transition">Gérer <span aria-hidden="true">→</span></router-link>
          </div>
          <div v-if="planDistribution.length === 0 && stats.companies_without_plan === 0" class="py-10 text-center">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-surface-tertiary flex items-center justify-center text-text-tertiary mb-3">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" /></svg>
            </div>
            <p class="text-sm font-semibold text-text-tertiary">Aucune donnée disponible</p>
          </div>
          <div v-else class="mt-4 space-y-2.5">
            <div v-for="plan in planDistribution" :key="plan.id" class="group flex items-center gap-3.5 p-3 rounded-2xl bg-surface-secondary/60 hover:bg-surface-secondary border border-transparent hover:border-border transition-all">
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 text-sm font-black shadow-sm" :class="planBadgeClass(plan.slug)">
                {{ plan.name.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="text-sm font-extrabold text-text-primary">{{ plan.name }}</p>
                  <span class="text-[11px] font-bold text-text-tertiary bg-surface border border-border px-2 py-0.5 rounded-full tabular-nums">{{ formatXof(plan.price_xof) }}/mois</span>
                </div>
                <div class="mt-2 h-2 rounded-full bg-surface-tertiary overflow-hidden">
                  <div class="h-full rounded-full transition-all duration-700" :class="planBarClass(plan.slug)" :style="{ width: `${planPct(plan.companies_count)}%` }" />
                </div>
              </div>
              <div class="text-right shrink-0">
                <p class="text-lg font-black tabular-nums text-text-primary leading-none">{{ plan.companies_count }}</p>
                <p class="text-[10px] font-bold uppercase tracking-wider text-text-tertiary mt-1">{{ planPct(plan.companies_count) }}%</p>
              </div>
            </div>
            <div v-if="stats.companies_without_plan > 0" class="flex items-center gap-3.5 p-3 rounded-2xl bg-rose-500/[0.06] hover:bg-rose-500/10 border border-rose-500/20 transition-all">
              <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 bg-rose-500 text-white font-black shadow-md shadow-rose-500/25">!</div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-extrabold text-rose-700 dark:text-rose-400">Sans plan · action requise</p>
                <div class="mt-2 h-2 rounded-full bg-rose-500/15 overflow-hidden">
                  <div class="h-full rounded-full bg-rose-500 transition-all duration-700" :style="{ width: `${planPct(stats.companies_without_plan)}%` }" />
                </div>
              </div>
              <span class="text-lg font-black tabular-nums text-rose-600 shrink-0">{{ stats.companies_without_plan }}</span>
            </div>
          </div>
        </section>

        <section class="xl:col-span-2 p-6 rounded-3xl bg-surface border border-border/70 shadow-sm flex flex-col min-h-[320px]">
          <div class="flex items-center justify-between gap-3 mb-1">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Entreprises sans plan</h3>
              <p class="text-xs text-text-tertiary font-medium">{{ companiesWithoutPlanList.length }} en attente d'assignation</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold border" :class="companiesWithoutPlanList.length ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'">
              {{ companiesWithoutPlanList.length ? 'À traiter' : 'OK' }}
            </span>
          </div>
          <div v-if="companiesWithoutPlanList.length === 0" class="flex-1 flex flex-col items-center justify-center text-center py-10">
            <span class="w-14 h-14 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 flex items-center justify-center mb-3">
              <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <p class="text-sm font-extrabold text-text-primary">Tout est en règle</p>
            <p class="text-xs text-text-tertiary mt-1">Toutes les entreprises ont un plan assigné.</p>
          </div>
          <div v-else class="mt-4 space-y-2 flex-1 overflow-y-auto max-h-[380px] pr-1 custom-scrollbar">
            <div v-for="c in companiesWithoutPlanList" :key="c.id" class="group flex items-center gap-3 p-3 rounded-2xl bg-surface-secondary/60 hover:bg-surface-secondary border border-transparent hover:border-rose-500/25 transition-all">
              <div class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-500 text-white text-sm font-black shrink-0 shadow-md shadow-rose-500/20">
                {{ c.name.charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-extrabold text-text-primary truncate">{{ c.name }}</p>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="inline-flex px-2 py-px rounded-full text-[10px] font-extrabold uppercase tracking-wider border" :class="c.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20'">{{ c.status }}</span>
                  <span class="text-[11px] font-semibold text-text-tertiary tabular-nums">{{ c.users_count || 0 }} users</span>
                </div>
              </div>
              <router-link :to="`/admin/companies`" class="text-[11px] font-extrabold text-white bg-neutral-900 dark:bg-white dark:text-neutral-900 px-3 py-2 rounded-xl opacity-0 group-hover:opacity-100 transition-all hover:-translate-y-px shrink-0">Assigner</router-link>
            </div>
          </div>
        </section>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <section class="p-6 rounded-3xl bg-surface border border-border/70 shadow-sm">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Nouvelles entreprises · 30 jours</h3>
              <p class="text-xs text-text-tertiary font-medium">Dynamique d'acquisition des locataires</p>
            </div>
            <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold text-emerald-600 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1.5 rounded-xl">
              <span class="w-2 h-2 rounded-full bg-emerald-500" /> Inscriptions
            </span>
          </div>
          <div class="h-64">
            <Line v-if="chartDataRegistrations" :data="chartDataRegistrations" :options="chartOptionsRegistrations" />
            <div v-else class="flex flex-col items-center justify-center h-full gap-2 text-text-tertiary text-sm">
              <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              Chargement du graphique…
            </div>
          </div>
        </section>
        <section class="p-6 rounded-3xl bg-surface border border-border/70 shadow-sm">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Connexions · 14 jours</h3>
              <p class="text-xs text-text-tertiary font-medium">{{ stats.recent_logins }} succès · <span class="text-rose-500 font-bold">{{ stats.failed_logins }} échecs</span></p>
            </div>
            <div class="flex items-center gap-1.5 text-[11px] font-bold">
              <span class="inline-flex items-center gap-1 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-2 py-1 rounded-lg"><span class="w-2 h-2 rounded-full bg-emerald-500" />Succès</span>
              <span class="inline-flex items-center gap-1 bg-rose-500/10 text-rose-600 border border-rose-500/20 px-2 py-1 rounded-lg"><span class="w-2 h-2 rounded-full bg-rose-500" />Échecs</span>
            </div>
          </div>
          <div class="h-64">
            <Bar v-if="chartDataLogins" :data="chartDataLogins" :options="chartOptionsLogins" />
            <div v-else class="flex flex-col items-center justify-center h-full gap-2 text-text-tertiary text-sm">
              <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              Chargement du graphique…
            </div>
          </div>
        </section>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Recent logins -->
        <section class="p-6 rounded-3xl bg-surface border border-border/70 shadow-sm flex flex-col">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Connexions récentes</h3>
              <p class="text-xs text-text-tertiary font-medium">Dernières tentatives tous locataires</p>
            </div>
            <router-link to="/admin/login-logs" @click="trackNavigationClick('/admin/login-logs', 'Voir toutes connexions')" class="text-[13px] font-extrabold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1">Tout voir <span aria-hidden="true">→</span></router-link>
          </div>
          <div v-if="logins.length === 0" class="flex-1 flex flex-col items-center justify-center py-10 text-center">
            <p class="text-sm font-bold text-text-tertiary">Aucune connexion récente</p>
          </div>
          <div v-else class="space-y-2 flex-1 overflow-y-auto max-h-[420px] pr-1 custom-scrollbar">
            <div v-for="log in logins" :key="log.id" class="group flex items-center gap-3.5 p-3 rounded-2xl bg-surface-secondary/50 hover:bg-surface-secondary border border-transparent hover:border-border transition-all">
              <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span v-if="log.success" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60" />
                <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="log.success ? 'bg-emerald-500' : 'bg-rose-500'" />
              </span>
              <div class="flex items-center justify-center w-10 h-10 rounded-2xl bg-surface border border-border text-sm font-black text-text-secondary shrink-0">{{ (log.email || '?').charAt(0).toUpperCase() }}</div>
              <div class="min-w-0 flex-1">
                <p class="text-[13px] font-extrabold text-text-primary truncate">{{ log.email }}</p>
                <p class="text-xs text-text-secondary truncate">{{ log.company?.name || 'Sans entreprise' }} · <span class="font-mono text-text-tertiary">{{ log.ip_address }}</span></p>
              </div>
              <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] font-extrabold border shrink-0" :class="log.success ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 border-rose-500/20'">{{ log.success ? 'OK' : 'ÉCHEC' }}</span>
              <span class="text-[11px] font-bold text-text-tertiary shrink-0 tabular-nums hidden sm:block">{{ timeAgo(log.created_at) }}</span>
            </div>
          </div>
        </section>

        <!-- Recent companies -->
        <section class="p-6 rounded-3xl bg-surface border border-border/70 shadow-sm flex flex-col">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <h3 class="text-base font-extrabold text-text-primary tracking-tight">Dernières inscriptions</h3>
              <p class="text-xs text-text-tertiary font-medium">Nouveaux locataires à accompagner</p>
            </div>
            <router-link to="/admin/companies" @click="trackNavigationClick('/admin/companies', 'Voir toutes entreprises')" class="text-[13px] font-extrabold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1">Tout voir <span aria-hidden="true">→</span></router-link>
          </div>
          <div v-if="recentCompanies.length === 0" class="flex-1 flex items-center justify-center py-10 text-sm font-bold text-text-tertiary">Aucune entreprise</div>
          <div v-else class="space-y-2 flex-1 overflow-y-auto max-h-[420px] pr-1 custom-scrollbar">
            <div v-for="c in recentCompanies" :key="c.id" class="group flex items-center gap-3.5 p-3 rounded-2xl bg-surface-secondary/50 hover:bg-surface-secondary border border-transparent hover:border-border transition-all">
              <div class="flex items-center justify-center w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white text-sm font-black shrink-0 shadow-md shadow-emerald-500/20 transition-transform duration-300 group-hover:scale-105">
                {{ c.name.charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-[13px] font-extrabold text-text-primary truncate">{{ c.name }}</p>
                <div class="flex items-center gap-1.5 mt-1">
                  <span class="inline-flex items-center px-2 py-px rounded-full text-[10px] font-extrabold uppercase tracking-wider border" :class="c.status === 'active' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20'">{{ c.status }}</span>
                  <span class="text-[11px] font-semibold text-text-tertiary tabular-nums">{{ c.users_count || 0 }} utilisateurs</span>
                </div>
              </div>
              <span class="text-[11px] font-bold text-text-tertiary shrink-0 tabular-nums">{{ timeAgo(c.created_at) }}</span>
            </div>
          </div>
        </section>
      </div>

      <!-- Top companies -->
      <section class="p-6 rounded-3xl bg-surface border border-border/70 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
          <div>
            <h3 class="text-base font-extrabold text-text-primary tracking-tight">Top entreprises · volume facturé (30 jours)</h3>
            <p class="text-xs text-text-tertiary font-medium">Les locataires qui génèrent le plus d'activité</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-text-tertiary tabular-nums">{{ topCompanies.length }} entreprises</span>
            <router-link to="/admin/companies" @click="trackNavigationClick('/admin/companies', 'Voir top entreprises')" class="text-[13px] font-extrabold text-emerald-600 hover:text-emerald-700 inline-flex items-center gap-1">Tout voir <span aria-hidden="true">→</span></router-link>
          </div>
        </div>
        <div v-if="topCompanies.length === 0" class="text-sm font-bold text-text-tertiary text-center py-10">Aucune facturation sur la période.</div>
        <div v-else class="grid gap-2.5 md:grid-cols-2">
          <div v-for="(c, i) in topCompanies" :key="c.id" class="group flex items-center gap-3.5 p-3.5 rounded-2xl bg-surface-secondary/50 hover:bg-surface-secondary border border-transparent hover:border-border transition-all">
            <span class="w-9 h-9 rounded-2xl flex items-center justify-center text-sm font-black shrink-0 shadow-sm" :class="i === 0 ? 'bg-gradient-to-br from-amber-400 to-orange-500 text-white shadow-amber-500/25' : i === 1 ? 'bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200' : i === 2 ? 'bg-orange-500/15 text-orange-600 border border-orange-500/25' : 'bg-surface-tertiary text-text-tertiary border border-border'">{{ i + 1 }}</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="text-[13px] font-extrabold text-text-primary truncate">{{ c.name }}</p>
              </div>
              <p class="text-[11px] font-semibold text-text-tertiary mt-0.5 tabular-nums">{{ c.invoices_count }} facture(s)</p>
              <div class="mt-2 h-1.5 rounded-full bg-surface-tertiary overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-700" :style="{ width: `${volumePct(c.volume)}%` }" />
              </div>
            </div>
            <span class="text-sm font-black tabular-nums text-text-primary shrink-0">{{ formatXof(c.volume) }}</span>
          </div>
        </div>
      </section>
    </div>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line, Bar } from 'vue-chartjs'
import { trackEvent } from '@/composables/useAnalytics'
import {
  BuildingOfficeIcon, BanknotesIcon, ReceiptPercentIcon, UsersIcon,
  ExclamationTriangleIcon, ClockIcon, ShieldExclamationIcon, InformationCircleIcon
} from '@heroicons/vue/24/outline'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

const loading = ref(true)
const logins = ref([])
const recentCompanies = ref([])
const topCompanies = ref([])
const alerts = ref([])
const planDistribution = ref([])
const companiesWithoutPlanList = ref([])
const todayLabel = ref(new Date().toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' }))
const stats = reactive({
  total_companies: 0, active_companies: 0, suspended_companies: 0,
  total_users: 0, active_users: 0, adoption_rate: 0,
  total_revenue: 0, paying_companies: 0, total_invoices: 0,
  trial_companies: 0, trial_expired_companies: 0,
  invoiced_volume_30d: 0, invoices_count_30d: 0,
  recent_logins: 0, failed_logins: 0,
  companies_without_plan: 0, companies_with_plan: 0,
})

function trackDashboardView() {
  trackEvent('super_admin_dashboard_view', {
    total_companies: stats.total_companies,
    active_companies: stats.active_companies,
    total_users: stats.total_users,
    total_revenue: stats.total_revenue,
  })
}

function trackNavigationClick(destination, label) {
  trackEvent('super_admin_navigation_click', { destination, label })
}

function trackAlertClick(alertTitle, alertSeverity) {
  trackEvent('super_admin_alert_click', { alert_title: alertTitle, alert_severity: alertSeverity })
}

const chartDataRegistrations = ref(null)
const chartDataLogins = ref(null)

const chartOptionsRegistrations = {
  responsive: true,
  maintainAspectRatio: false,
  resizeDelay: 100,
  plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#0f172a', padding: 12, cornerRadius: 12 } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } },
    y: { border: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, ticks: { precision: 0 } }
  },
  interaction: { mode: 'nearest', axis: 'x', intersect: false },
  elements: { line: { tension: 0.45, borderWidth: 3 }, point: { radius: 0, hoverRadius: 6 } }
}

const chartOptionsLogins = {
  responsive: true,
  maintainAspectRatio: false,
  resizeDelay: 100,
  plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false, backgroundColor: '#0f172a', padding: 12, cornerRadius: 12 } },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' } } },
    y: { stacked: true, border: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, ticks: { precision: 0 } }
  }
}

function formatXof(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v || 0)
}
function formatCompact(v) {
  return new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 1 }).format(v || 0)
}

const maxTopVolume = computed(() => Math.max(1, ...topCompanies.value.map(c => Number(c.volume) || 0)))
function volumePct(volume) {
  return Math.round((Number(volume) / maxTopVolume.value) * 100)
}

const maxPlanCount = computed(() => Math.max(1, ...planDistribution.value.map(p => p.companies_count || 0), stats.companies_without_plan || 0))
function planPct(count) {
  return Math.round((Number(count) / maxPlanCount.value) * 100)
}

function planBadgeClass(slug) {
  const map = {
    free: 'bg-neutral-500/10 text-neutral-500 border border-neutral-500/20',
    starter: 'bg-blue-500/10 text-blue-600 border border-blue-500/20',
    pro: 'bg-violet-500/10 text-violet-600 border border-violet-500/20',
    enterprise: 'bg-amber-500/10 text-amber-600 border border-amber-500/20',
  }
  return map[slug] || 'bg-neutral-500/10 text-neutral-500 border border-neutral-500/20'
}

function planBarClass(slug) {
  const map = { free: 'bg-neutral-400', starter: 'bg-blue-500', pro: 'bg-violet-500', enterprise: 'bg-gradient-to-r from-amber-400 to-orange-500' }
  return map[slug] || 'bg-neutral-400'
}

function alertTone(severity) {
  const map = {
    critical: { card: 'bg-rose-500/[0.05] border-rose-500/25', icon: 'bg-rose-500 text-white shadow-rose-500/30 shadow-lg', pill: 'bg-rose-500/10 text-rose-600 border-rose-500/25', iconCmp: ShieldExclamationIcon },
    danger: { card: 'bg-rose-500/[0.05] border-rose-500/25', icon: 'bg-rose-500 text-white shadow-rose-500/30 shadow-lg', pill: 'bg-rose-500/10 text-rose-600 border-rose-500/25', iconCmp: ExclamationTriangleIcon },
    warning: { card: 'bg-amber-500/[0.06] border-amber-500/25', icon: 'bg-amber-500 text-white shadow-amber-500/30 shadow-lg', pill: 'bg-amber-500/10 text-amber-600 border-amber-500/25', iconCmp: ExclamationTriangleIcon },
    info: { card: 'bg-sky-500/[0.06] border-sky-500/25', icon: 'bg-sky-500 text-white shadow-sky-500/30 shadow-lg', pill: 'bg-sky-500/10 text-sky-600 border-sky-500/25', iconCmp: InformationCircleIcon },
  }
  return map[severity] || map.info
}

const heroMetrics = computed(() => [
  { label: 'MRR', value: formatCompact(stats.total_revenue) + ' F' },
  { label: 'Entreprises', value: stats.total_companies },
  { label: 'Utilisateurs', value: stats.total_users },
  { label: 'Adoption', value: (stats.adoption_rate || 0) + '%' },
])

const statCards = computed(() => {
  const total = Math.max(1, stats.total_companies)
  return [
    { label: 'Entreprises', value: stats.total_companies, sublabel: `${stats.active_companies} actives · ${stats.suspended_companies} suspendues`, color: 'text-emerald-600', icon: BuildingOfficeIcon, iconBg: 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/30', glow: 'bg-emerald-500/10', bar: 'bg-gradient-to-r from-emerald-500 to-teal-400', pct: Math.round((stats.active_companies / total) * 100), trend: 'LIVE', trendTone: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
    { label: 'Revenu mensuel (MRR)', value: formatXof(stats.total_revenue), sublabel: `${stats.paying_companies} entreprise(s) payante(s)`, color: 'text-emerald-600', icon: BanknotesIcon, iconBg: 'bg-gradient-to-br from-emerald-400 to-green-600 shadow-emerald-500/30', glow: 'bg-emerald-500/10', bar: 'bg-gradient-to-r from-emerald-500 to-green-400', pct: Math.min(100, Math.round((stats.paying_companies / total) * 100)), trend: 'MRR', trendTone: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
    { label: 'Facturation 30j', value: formatXof(stats.invoiced_volume_30d), sublabel: `${stats.invoices_count_30d} facture(s) émises`, color: 'text-blue-600', icon: ReceiptPercentIcon, iconBg: 'bg-gradient-to-br from-blue-500 to-indigo-600 shadow-blue-500/30', glow: 'bg-blue-500/10', bar: 'bg-gradient-to-r from-blue-500 to-indigo-400', pct: Math.min(100, Math.round((stats.invoices_count_30d / 50) * 100)), trend: '30J', trendTone: 'bg-blue-500/10 text-blue-600 border-blue-500/20' },
    { label: 'Utilisateurs', value: stats.total_users, sublabel: `${stats.adoption_rate}% actifs · ${stats.active_users} comptes`, color: 'text-blue-600', icon: UsersIcon, iconBg: 'bg-gradient-to-br from-violet-500 to-purple-600 shadow-violet-500/30', glow: 'bg-violet-500/10', bar: 'bg-gradient-to-r from-violet-500 to-purple-400', pct: Math.min(100, Number(stats.adoption_rate) || 0), trend: `${stats.adoption_rate}%`, trendTone: 'bg-violet-500/10 text-violet-600 border-violet-500/20' },
    { label: 'Sans plan', value: stats.companies_without_plan, sublabel: `${stats.companies_with_plan} avec plan assigné`, color: stats.companies_without_plan > 0 ? 'text-rose-600' : 'text-emerald-600', icon: ExclamationTriangleIcon, iconBg: stats.companies_without_plan > 0 ? 'bg-gradient-to-br from-rose-500 to-orange-500 shadow-rose-500/30' : 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/30', glow: stats.companies_without_plan > 0 ? 'bg-rose-500/10' : 'bg-emerald-500/10', bar: stats.companies_without_plan > 0 ? 'bg-rose-500' : 'bg-emerald-500', pct: Math.round((stats.companies_without_plan / total) * 100), trend: stats.companies_without_plan > 0 ? 'ACTION' : 'OK', trendTone: stats.companies_without_plan > 0 ? 'bg-rose-500/10 text-rose-600 border-rose-500/20' : 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' },
    { label: 'Essais', value: stats.trial_companies, sublabel: stats.trial_expired_companies > 0 ? `${stats.trial_expired_companies} expiré(s) !` : 'Aucun essai expiré', color: stats.trial_expired_companies > 0 ? 'text-rose-600' : 'text-emerald-600', icon: ClockIcon, iconBg: 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30', glow: 'bg-amber-500/10', bar: 'bg-gradient-to-r from-amber-400 to-orange-500', pct: Math.min(100, Math.round((stats.trial_companies / total) * 100)), trend: 'TRIAL', trendTone: 'bg-amber-500/10 text-amber-600 border-amber-500/20' },
  ]
})

function timeAgo(date) {
  if (!date) return ''
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return "à l'instant"
  if (mins < 60) return `${mins} min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h`
  const days = Math.floor(hours / 24)
  return `${days}j`
}

async function load() {
  loading.value = true
  try {
    const { data } = await axios.get('/admin/dashboard')
    const d = data.data ?? data
    Object.assign(stats, d)
    logins.value = d.logins_today || []
    recentCompanies.value = d.recent_companies || []
    topCompanies.value = d.top_companies || []
    alerts.value = d.alerts || []
    planDistribution.value = d.plan_distribution || []
    companiesWithoutPlanList.value = d.companies_without_plan_list || []

    trackDashboardView()

    if (d.chart_registrations) {
      chartDataRegistrations.value = {
        labels: d.chart_registrations.map(i => new Date(i.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [{
          label: 'Nouvelles entreprises',
          data: d.chart_registrations.map(i => i.count),
          borderColor: '#10b981',
          backgroundColor: (ctx) => {
            const { chart } = ctx
            const { ctx: c, chartArea } = chart
            if (!chartArea) return 'rgba(16,185,129,0.12)'
            const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
            g.addColorStop(0, 'rgba(16,185,129,0.30)')
            g.addColorStop(1, 'rgba(16,185,129,0.02)')
            return g
          },
          fill: true,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#10b981',
          pointBorderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 6,
          borderWidth: 3,
          tension: 0.45,
        }]
      }
    }

    if (d.chart_logins) {
      chartDataLogins.value = {
        labels: d.chart_logins.map(i => new Date(i.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [
          { label: 'Succès', data: d.chart_logins.map(i => i.success), backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false, maxBarThickness: 18 },
          { label: 'Échecs', data: d.chart_logins.map(i => i.failed), backgroundColor: '#f43f5e', borderRadius: 6, borderSkipped: false, maxBarThickness: 18 },
        ]
      }
    }
  } catch (err) {
    console.error('Erreur chargement dashboard admin:', err)
  } finally {
    loading.value = false
  }
}

function reload() { load() }

onMounted(() => { load() })
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgb(16 185 129 / 0.3); border-radius: 99px; }
</style>
