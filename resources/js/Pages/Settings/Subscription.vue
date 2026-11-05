<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Facturation" :title="$t('subscription.title')" :subtitle="$t('settings.manage_subscription')">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchData()" :title="$t('common.refresh')">
            <ArrowPathIcon class="w-4 h-4" />
          </BaseButton>
          <BaseButton variant="secondary" size="sm" :to="{ name: 'Settings' }">
            <ArrowLeftIcon class="w-4 h-4" /> {{ $t('common.back') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <template v-if="loading">
        <BaseSkeleton :count="4" />
      </template>

      <template v-else-if="error">
        <BaseEmptyState :title="$t('common.error')" :description="error">
          <BaseButton variant="primary" size="sm" @click="fetchData()">
            <ArrowPathIcon class="w-4 h-4" />{{ $t('common.retry') }}
          </BaseButton>
        </BaseEmptyState>
      </template>

      <template v-else>
        <!-- Trial banner -->
        <div
          v-if="snapshot?.trial?.active"
          class="relative overflow-hidden rounded-2xl border border-emerald-500/30 bg-gradient-to-r from-emerald-500/15 via-emerald-500/10 to-transparent p-5"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/25 flex items-center justify-center shrink-0">
                <SparklesIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div>
                <p class="font-extrabold text-text-primary">{{ $t('subscription.trial_active') }}</p>
                <p class="text-sm text-text-secondary mt-0.5">
                  {{ $t('subscription.trial_banner', { n: snapshot.trial.days_remaining }) }}
                </p>
              </div>
            </div>
            <BaseBadge variant="success" dot>
              {{ $t('subscription.trial_days_left', { n: snapshot.trial.days_remaining }) }}
            </BaseBadge>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <!-- Current plan -->
          <BaseCard class="lg:col-span-2" :title="$t('subscription.current_plan')" padding="lg">
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-surface-tertiary rounded-xl">
              <div class="flex items-center gap-4 min-w-0">
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                  <ShieldCheckIcon class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-extrabold text-lg text-text-primary">
                      {{ snapshot?.plan?.name || $t('subscription.no_plan') }}
                    </h3>
                    <BaseBadge :variant="statusVariant" dot>{{ statusLabel }}</BaseBadge>
                  </div>
                  <p class="text-sm text-text-tertiary mt-0.5">
                    {{ planPriceLabel }}
                  </p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 text-sm">
              <div class="p-3.5 bg-surface-tertiary rounded-xl">
                <p class="text-xs font-semibold text-text-tertiary">{{ $t('subscription.trial_started') }}</p>
                <p class="mt-0.5 font-bold text-text-primary">{{ formatDateOrDash(snapshot?.trial?.started_at) }}</p>
              </div>
              <div class="p-3.5 bg-surface-tertiary rounded-xl">
                <p class="text-xs font-semibold text-text-tertiary">{{ $t('subscription.trial_ends') }}</p>
                <p class="mt-0.5 font-bold text-text-primary">{{ formatDateOrDash(snapshot?.trial?.ends_at) }}</p>
              </div>
              <div class="p-3.5 bg-surface-tertiary rounded-xl">
                <p class="text-xs font-semibold text-text-tertiary">{{ $t('subscription.subscription_ends') }}</p>
                <p class="mt-0.5 font-bold text-text-primary">{{ formatDateOrDash(snapshot?.subscription?.ends_at) }}</p>
              </div>
            </div>

            <!-- Plan features -->
            <div v-if="snapshot?.plan?.features?.length" class="mt-4 pt-4 border-t border-border">
              <p class="text-xs font-bold uppercase tracking-wide text-text-tertiary mb-2">{{ $t('subscription.plan_features') }}</p>
              <ul class="grid sm:grid-cols-2 gap-2">
                <li v-for="feature in snapshot.plan.features" :key="feature" class="flex items-start gap-2 text-sm text-text-secondary">
                  <CheckIcon class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  {{ planFeatureLabel(feature) }}
                </li>
              </ul>
            </div>
          </BaseCard>

          <!-- Frozen data / admin managed -->
          <div class="space-y-4">
            <BaseCard :title="$t('subscription.frozen_title')" padding="lg">
              <div class="flex items-start gap-3">
                <div
                  class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border"
                  :class="totalFrozen > 0
                    ? 'bg-amber-500/10 border-amber-500/20'
                    : 'bg-surface-tertiary border-border'"
                >
                  <LockClosedIcon class="w-5 h-5" :class="totalFrozen > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-text-tertiary'" />
                </div>
                <div>
                  <p class="text-2xl font-black tabular-nums text-text-primary">{{ totalFrozen }}</p>
                  <p class="text-xs font-medium text-text-tertiary mt-0.5">{{ $t('subscription.frozen_items', { n: totalFrozen }) }}</p>
                </div>
              </div>
              <p class="text-xs text-text-secondary leading-relaxed mt-3">
                {{ $t('subscription.frozen_desc') }}
              </p>
            </BaseCard>

            <BaseCard padding="lg">
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center shrink-0">
                  <InformationCircleIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <p class="font-extrabold text-text-primary">{{ $t('subscription.admin_managed_title') }}</p>
                  <p class="text-xs text-text-secondary leading-relaxed mt-1">{{ $t('subscription.admin_managed_desc') }}</p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <a href="https://wa.me/22394147375?text=Bonjour%2C%20je%20souhaite%20faire%20%C3%A9voluer%20mon%20abonnement%20SIDIBE%20CORPORATE." target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/25 transition">
                      Chat WhatsApp
                    </a>
                    <a href="mailto:supportsidibecorporate@gmail.com?subject=Demande%20de%20changement%20de%20plan" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-blue-700 dark:text-blue-400 bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/25 transition">
                      Envoyer un email
                    </a>
                  </div>
                </div>
              </div>
            </BaseCard>
          </div>
        </div>

        <!-- Over quota alert -->
        <div
          v-if="snapshot?.quotas?.over_quota"
          class="flex flex-wrap items-start gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-4"
        >
          <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
          <div class="flex-1 min-w-0">
            <p class="font-extrabold text-text-primary">{{ $t('subscription.over_quota_title') }}</p>
            <p class="text-sm text-text-secondary mt-0.5">{{ $t('subscription.over_quota_desc') }}</p>
          </div>
        </div>

        <!-- Quotas -->
        <BaseCard :title="$t('subscription.quota_usage')" :subtitle="$t('subscription.quota_usage_sub')" padding="lg">
          <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div
              v-for="item in quotas"
              :key="item.resource"
              class="p-4 rounded-2xl border bg-surface-tertiary/60"
              :class="item.over_quota ? 'border-amber-500/40' : 'border-border/70'"
            >
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-extrabold text-text-primary">{{ $t(`subscription.resource_${item.resource}`) }}</p>
                <BaseBadge v-if="item.over_quota" variant="warning" size="xs">{{ $t('subscription.over_quota_short') }}</BaseBadge>
              </div>

              <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-black tabular-nums" :class="item.over_quota ? 'text-amber-600 dark:text-amber-400' : 'text-text-primary'">
                  {{ item.used }}
                </span>
                <span class="text-sm font-semibold text-text-tertiary">
                  / {{ item.limit < 0 ? $t('subscription.unlimited_label') : item.limit }}
                </span>
              </div>

              <div class="mt-3 h-1.5 rounded-full bg-border/70 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all"
                  :class="item.over_quota ? 'bg-amber-500' : 'bg-emerald-500'"
                  :style="{ width: barWidth(item) }"
                ></div>
              </div>

              <div class="mt-2 flex items-center justify-between gap-2 text-[11px] font-semibold">
                <span class="text-text-tertiary">{{ $t('subscription.remaining', { n: item.remaining ?? '∞' }) }}</span>
                <span v-if="item.frozen > 0" class="text-amber-600 dark:text-amber-400">
                  {{ $t('subscription.frozen_count', { n: item.frozen }) }}
                </span>
              </div>
            </div>
          </div>
        </BaseCard>

        <!-- Available plans (read only) -->
        <BaseCard
          v-if="plans.length"
          :title="$t('subscription.available_plans')"
          :subtitle="$t('subscription.available_plans_readonly')"
          padding="lg"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div
              v-for="plan in plans"
              :key="plan.id"
              class="relative p-5 rounded-2xl border-2 transition-all duration-200"
              :class="[
                isCurrentPlan(plan)
                  ? 'border-emerald-500 bg-emerald-500/[0.06] ring-4 ring-emerald-500/10'
                  : 'border-border/70 bg-surface'
              ]"
            >
              <BaseBadge v-if="isCurrentPlan(plan)" variant="success" class="absolute -top-3 right-4 shadow-sm">
                {{ $t('subscription.current') }}
              </BaseBadge>

              <div class="text-center mb-4">
                <h3 class="text-base font-extrabold text-text-primary">{{ plan.name }}</h3>
                <p class="text-xs font-medium text-text-tertiary mt-1 min-h-8">{{ planDescriptionLabel(plan.description) }}</p>
              </div>

              <div class="text-center mb-4">
                <span class="text-2xl font-black text-text-primary tabular-nums">
                  {{ plan.price_xof === 0 ? $t('subscription.free_badge') : formatPrice(plan.price_xof, plan.currency) }}
                </span>
                <span v-if="plan.price_xof > 0" class="text-xs font-medium text-text-tertiary">{{ $t('subscription.per_month_short') }}</span>
              </div>

              <ul class="space-y-2 mb-4 min-h-[96px]">
                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-text-secondary">
                  <CheckIcon class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  {{ planFeatureLabel(feature) }}
                </li>
              </ul>

              <div class="pt-3 border-t border-border/70 space-y-1.5">
                <div v-for="quota in planQuotaRows(plan)" :key="quota.key" class="flex items-center justify-between text-xs">
                  <span class="text-text-tertiary font-medium">{{ $t(`subscription.resource_${quota.resource}`) }}</span>
                  <span class="font-bold text-text-primary tabular-nums">
                    {{ quota.limit < 0 ? $t('subscription.unlimited_label') : quota.limit }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </BaseCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AdminLayout from '../../Components/AdminLayout.vue'
import BaseButton from '../../Components/ui/BaseButton.vue'
import BaseCard from '../../Components/ui/BaseCard.vue'
import BaseBadge from '../../Components/ui/BaseBadge.vue'
import BasePageHeader from '../../Components/ui/BasePageHeader.vue'
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue'
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue'
import {
  ArrowLeftIcon, ArrowPathIcon, CheckIcon, ExclamationTriangleIcon,
  InformationCircleIcon, LockClosedIcon, ShieldCheckIcon, SparklesIcon,
} from '@heroicons/vue/24/outline'
import { formatPrice, formatDate } from '../../utils/format'
import { translatePlanDescription, translatePlanFeature } from '../../utils/planTranslations'

const { t } = useI18n()

const planFeatureLabel = (feature) => translatePlanFeature(feature, t)
const planDescriptionLabel = (description) => translatePlanDescription(description, t)

const loading = ref(true)
const error = ref(null)
const snapshot = ref(null)
const plans = ref([])

const quotas = computed(() => snapshot.value?.quotas?.resources ?? [])
const totalFrozen = computed(() => snapshot.value?.total_frozen ?? snapshot.value?.quotas?.frozen_total ?? 0)

const COMPANY_STATUS = {
  trial: { label: 'subscription.status_trialing', variant: 'info' },
  active: { label: 'subscription.status_active', variant: 'success' },
  suspended: { label: 'subscription.status_suspended', variant: 'danger' },
  disabled: { label: 'subscription.status_disabled', variant: 'default' },
}

const statusLabel = computed(() => {
  const status = snapshot.value?.company?.status
  return COMPANY_STATUS[status] ? t(COMPANY_STATUS[status].label) : (status || '—')
})

const statusVariant = computed(() => COMPANY_STATUS[snapshot.value?.company?.status]?.variant ?? 'default')

const planPriceLabel = computed(() => {
  const plan = snapshot.value?.plan
  if (!plan) return t('subscription.no_plan')
  return plan.price_xof === 0
    ? t('subscription.free_badge')
    : `${formatPrice(plan.price_xof, plan.currency)} ${t('subscription.per_month_short')}`
})

function formatDateOrDash(value) {
  return value ? formatDate(value) : '—'
}

function barWidth(item) {
  if (item.limit < 0) return '100%'
  if (item.limit === 0) return '100%'
  return `${Math.min(100, Math.round((item.used / item.limit) * 100))}%`
}

function isCurrentPlan(plan) {
  return snapshot.value?.plan?.id === plan.id
}

function planQuotaRows(plan) {
  return quotas.value.map((item) => ({
    key: item.resource,
    resource: item.resource,
    limit: plan.quotas?.[`max_${item.resource}`] ?? -1,
  }))
}

async function fetchData() {
  loading.value = true
  error.value = null
  try {
    const [currentRes, plansRes] = await Promise.all([
      axios.get('/subscriptions/current'),
      axios.get('/subscriptions/plans'),
    ])
    snapshot.value = currentRes.data.data ?? null
    plans.value = Array.isArray(plansRes.data) ? plansRes.data : (plansRes.data.data || [])
  } catch (err) {
    console.error('Erreur chargement abonnement:', err)
    error.value = t('subscription.load_error_info')
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
