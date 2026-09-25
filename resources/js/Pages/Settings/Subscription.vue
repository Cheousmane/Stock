<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Facturation" :title="$t('subscription.title')" :subtitle="$t('settings.manage_subscription')">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchData()" :title="$t('common.refresh')">
            <span class="text-current"><ArrowPathIcon class="w-4 h-4" /></span>
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
            <span class="text-white"><ArrowPathIcon class="w-4 h-4" /></span>{{ $t('common.retry') }}
          </BaseButton>
        </BaseEmptyState>
      </template>

      <template v-else>
        <!-- Current Subscription -->
        <BaseCard v-if="currentSubscription" :title="$t('subscription.current_plan')" padding="lg">
          <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-surface-tertiary rounded-xl">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                  <CheckCircleIcon class="w-6 h-6 text-emerald-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-text-primary">{{ currentSubscription.plan_name || $t('subscription.active_badge') }}</h3>
                  <p class="text-sm text-text-tertiary">{{ formatStatus(currentSubscription.stripe_status) }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span v-if="currentSubscription.trial_ends_at" class="px-2.5 py-1 text-xs font-medium rounded-full border border-amber-200 bg-amber-50 text-amber-700">
                  {{ $t('subscription.trial_until', { date: formatDate(currentSubscription.trial_ends_at) }) }}
                </span>
                <span v-else-if="currentSubscription.ends_at" class="px-2.5 py-1 text-xs font-medium rounded-full border border-rose-200 bg-rose-50 text-rose-700">
                  {{ $t('subscription.ends_on', { date: formatDate(currentSubscription.ends_at) }) }}
                </span>
                <BaseBadge variant="success">{{ $t('subscription.active_badge') }}</BaseBadge>
              </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
              <div class="p-3 bg-surface-tertiary rounded-lg">
                <p class="text-text-tertiary">{{ $t('subscription.cycle') }}</p>
                <p class="font-medium text-text-primary">{{ currentSubscription.billing_cycle || $t('subscription.cycle_monthly') }}</p>
              </div>
              <div class="p-3 bg-surface-tertiary rounded-lg">
                <p class="text-text-tertiary">{{ $t('subscription.next_billing') }}</p>
                <p class="font-medium text-text-primary">{{ currentSubscription.next_billing_date ? formatDate(currentSubscription.next_billing_date) : '—' }}</p>
              </div>
              <div class="p-3 bg-surface-tertiary rounded-lg">
                <p class="text-text-tertiary">{{ $t('subscription.payment_method') }}</p>
                <p class="font-medium text-text-primary">{{ currentSubscription.payment_method_brand ? currentSubscription.payment_method_brand.toUpperCase() + ' •••• ' + currentSubscription.payment_method_last4 : '—' }}</p>
              </div>
              <div class="p-3 bg-surface-tertiary rounded-lg">
                <p class="text-text-tertiary">ID Stripe</p>
                <p class="font-medium text-text-primary font-mono text-xs">{{ currentSubscription.stripe_id || '—' }}</p>
              </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-4 border-t border-border">
              <BaseButton
                v-if="currentSubscription.stripe_id && (currentSubscription.stripe_status === 'active' || currentSubscription.stripe_status === 'trialing')"
                variant="secondary"
                size="sm"
                @click="openBillingPortal"
                :loading="portalLoading"
              >
                <CreditCardIcon class="w-4 h-4" /> {{ $t('subscription.stripe_portal') }}
              </BaseButton>
              <BaseButton
                v-if="currentSubscription.stripe_id && (currentSubscription.stripe_status === 'active' || currentSubscription.stripe_status === 'trialing')"
                variant="danger-ghost"
                size="sm"
                @click="showCancelModal = true"
              >
                <XCircleIcon class="w-4 h-4" /> {{ $t('subscription.cancel_sub') }}
              </BaseButton>
              <BaseButton
                v-else-if="currentSubscription.stripe_id && currentSubscription.stripe_status === 'canceled'"
                variant="primary"
                size="sm"
                @click="resumeSubscription"
                :loading="resumeLoading"
              >
                <ArrowPathIcon class="w-4 h-4" /> {{ $t('subscription.reactivate') }}
              </BaseButton>
              <BaseButton
                v-if="!currentSubscription.stripe_id"
                variant="secondary"
                size="sm"
                @click="scrollToPlans"
              >
                {{ $t('subscription.change_plan') }}
              </BaseButton>
            </div>
          </div>
        </BaseCard>

        <!-- No Subscription -->
        <BaseEmptyState
          v-else
          :title="$t('subscription.no_sub_title')"
          :description="$t('subscription.no_sub_desc')"
        >
          <BaseButton variant="primary" size="sm" @click="scrollToPlans">
            <SparklesIcon class="w-4 h-4" /> {{ $t('subscription.view_available_plans') }}
          </BaseButton>
        </BaseEmptyState>

        <!-- Available Plans -->
        <BaseCard :title="$t('subscription.available_plans')" :subtitle="$t('subscription.available_plans_sub')" padding="lg" id="plans">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
              v-for="plan in plans"
              :key="plan.id"
              class="relative p-4 rounded-xl border-2 transition-all duration-200"
              :class="[
                isCurrentPlan(plan) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 ring-2 ring-emerald-500/20' : 'border-border bg-surface hover:border-emerald-200 dark:hover:border-emerald-800'
              ]"
            >
              <div v-if="isCurrentPlan(plan)" class="absolute -top-3 -right-3 px-2 py-0.5 text-xs font-semibold bg-emerald-500 text-white rounded-full">
                {{ $t('subscription.current') }}
              </div>
              <div v-else-if="plan.slug === 'free' && !currentSubscription" class="absolute -top-3 -right-3 px-2 py-0.5 text-xs font-semibold bg-gray-500 text-white rounded-full">
                {{ $t('subscription.free_badge') }}
              </div>
              <div v-else-if="isUpgrade(plan)" class="absolute -top-3 -right-3 px-2 py-0.5 text-xs font-semibold bg-emerald-500 text-white rounded-full">
                Upgrade
              </div>
              <div v-else-if="isDowngrade(plan)" class="absolute -top-3 -right-3 px-2 py-0.5 text-xs font-semibold bg-amber-500 text-white rounded-full">
                Downgrade
              </div>

              <div class="text-center mb-4">
                <h3 class="font-semibold text-text-primary">{{ plan.name }}</h3>
                <p class="text-sm text-text-tertiary mt-1">{{ plan.description }}</p>
              </div>

              <div class="text-center mb-4">
                <span class="text-3xl font-bold text-text-primary">{{ plan.price_xof === 0 ? $t('subscription.free_badge') : formatPrice(plan.price_xof, plan.currency) }}</span>
                <span v-if="plan.price_xof > 0" class="text-sm text-text-tertiary">{{ $t('subscription.per_month_short') }}</span>
              </div>

              <ul class="space-y-2 mb-6 min-h-[120px]">
                <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2 text-sm text-text-secondary">
                  <CheckIcon class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                  {{ feature }}
                </li>
              </ul>

              <div class="space-y-2">
                <BaseButton
                  v-if="isCurrentPlan(plan)"
                  variant="secondary"
                  class="w-full"
                  disabled
                >
                  <CheckIcon class="w-4 h-4" /> {{ $t('subscription.current_plan_btn') }}
                </BaseButton>
                <BaseButton
                  v-else-if="canSubscribe(plan)"
                  variant="primary"
                  class="w-full"
                  @click="subscribeToPlan(plan)"
                  :loading="subscribingPlan === plan.id"
                >
                  {{ getButtonText(plan) }}
                </BaseButton>
                <BaseButton
                  v-else
                  variant="secondary"
                  class="w-full"
                  disabled
                >
                  {{ getDisabledReason(plan) }}
                </BaseButton>
              </div>
            </div>
          </div>
        </BaseCard>

        <!-- Cancel Confirmation Modal -->
        <BaseModal v-model="showCancelModal" :title="$t('subscription.cancel_title')" size="sm">
          <p class="text-sm text-text-secondary">
            {{ $t('subscription.cancel_text', { date: currentSubscription?.ends_at ? formatDate(currentSubscription.ends_at) : $t('subscription.unknown_date') }) }}
          </p>
          <template #footer>
            <BaseButton variant="ghost" @click="showCancelModal = false">{{ $t('common.cancel') }}</BaseButton>
            <BaseButton variant="danger" @click="cancelSubscription" :loading="cancelLoading">{{ $t('subscription.confirm_cancel') }}</BaseButton>
          </template>
        </BaseModal>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import AdminLayout from '../../Components/AdminLayout.vue'
import BaseButton from '../../Components/ui/BaseButton.vue'
import BaseCard from '../../Components/ui/BaseCard.vue'
import BaseBadge from '../../Components/ui/BaseBadge.vue'
import BasePageHeader from '../../Components/ui/BasePageHeader.vue'
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue'
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue'
import BaseModal from '../../Components/ui/BaseModal.vue'
import {
  ArrowLeftIcon, CheckCircleIcon, XCircleIcon,
  ArrowPathIcon, CreditCardIcon, SparklesIcon, CheckIcon,
} from '@heroicons/vue/24/outline'
import { inject } from 'vue'
import { formatPrice, formatDate } from '../../utils/format'

const router = useRouter()
const showToast = inject('showToast')
const { t } = useI18n()

const loading = ref(true)
const error = ref(null)
const plans = ref([])
const currentSubscription = ref(null)
const subscribingPlan = ref(null)
const cancelLoading = ref(false)
const resumeLoading = ref(false)
const portalLoading = ref(false)
const showCancelModal = ref(false)

function formatStatus(status) {
  const map = {
    active: t('subscription.status_active'),
    trialing: t('subscription.status_trialing'),
    past_due: t('subscription.status_past_due'),
    canceled: t('subscription.status_canceled'),
    incomplete: t('subscription.status_incomplete'),
    incomplete_expired: t('subscription.status_expired'),
    unpaid: t('subscription.status_unpaid'),
    paused: t('subscription.status_paused')
  }
  return map[status] || status
}

function isCurrentPlan(plan) {
  if (!currentSubscription.value) return false
  const currentPlanId = currentSubscription.value.plan_id
  const currentPlanSlug = currentSubscription.value.plan_slug
  return (currentPlanId && currentPlanId === plan.id) ||
         (currentPlanSlug && currentPlanSlug === plan.slug)
}

function getCurrentPlan() {
  if (!currentSubscription.value) return null
  const currentPlanId = currentSubscription.value.plan_id
  const currentPlanSlug = currentSubscription.value.plan_slug
  if (currentPlanId) return plans.value.find(p => p.id === currentPlanId)
  if (currentPlanSlug) return plans.value.find(p => p.slug === currentPlanSlug)
  return plans.value.find(p => p.stripe_price_id === currentSubscription.value.stripe_price)
}

function isUpgrade(plan) {
  if (!currentSubscription.value) return plan.price_xof > 0
  const currentPlan = getCurrentPlan()
  if (!currentPlan) return plan.price_xof > 0
  return plan.price_xof > currentPlan.price_xof
}

function isDowngrade(plan) {
  if (!currentSubscription.value) {
    return plan.slug === 'free'
  }
  const currentPlan = getCurrentPlan()
  if (!currentPlan) return false
  return plan.price_xof < currentPlan.price_xof
}

function canSubscribe(plan) {
  if (isCurrentPlan(plan)) return false
  if (!currentSubscription.value) return true
  return isUpgrade(plan) || isDowngrade(plan)
}

function getButtonText(plan) {
  if (!currentSubscription.value) return t('subscription.subscribe_to', { plan: plan.name })
  if (isUpgrade(plan)) return t('subscription.upgrade_to', { plan: plan.name })
  if (isDowngrade(plan)) return t('subscription.downgrade_to', { plan: plan.name })
  return t('subscription.subscribe_to', { plan: plan.name })
}

function getDisabledReason(plan) {
  if (isCurrentPlan(plan)) return t('subscription.current_plan_btn')
  if (!currentSubscription.value) return t('subscription.unavailable')
  return t('subscription.not_available')
}

async function fetchData() {
  loading.value = true
  error.value = null
  try {
    const [plansRes, subRes] = await Promise.all([
      axios.get('/subscriptions/plans'),
      axios.get('/subscriptions/current')
    ])
    plans.value = Array.isArray(plansRes.data) ? plansRes.data : (plansRes.data.data || [])
    currentSubscription.value = subRes.data.data ?? null
  } catch (err) {
    console.error('Erreur chargement abonnement:', err)
    error.value = t('subscription.load_error_info')
    showToast(t('subscription.load_toast_error'), 'error')
  } finally {
    loading.value = false
  }
}

async function subscribeToPlan(plan) {
  subscribingPlan.value = plan.id
  try {
    const paymentMethodId = prompt(t('subscription.stripe_prompt'))
    if (!paymentMethodId) return

    await axios.post('/subscriptions/subscribe', {
      plan_id: plan.id,
      payment_method_id: paymentMethodId
    })

    showToast(t('subscription.created_ok'), 'success')
    await fetchData()
  } catch (err) {
    console.error('Erreur souscription:', err)
    const msg = err.response?.data?.message || t('subscription.subscribe_error')
    showToast(msg, 'error')
  } finally {
    subscribingPlan.value = null
  }
}

async function cancelSubscription() {
  cancelLoading.value = true
  try {
    await axios.post('/subscriptions/cancel')
    showToast(t('subscription.canceled_ok'), 'success')
    showCancelModal.value = false
    await fetchData()
  } catch (err) {
    console.error('Erreur annulation:', err)
    const msg = err.response?.data?.message || t('subscription.cancel_error')
    showToast(msg, 'error')
  } finally {
    cancelLoading.value = false
  }
}

async function resumeSubscription() {
  resumeLoading.value = true
  try {
    await axios.post('/subscriptions/resume')
    showToast(t('subscription.reactivated_ok'), 'success')
    await fetchData()
  } catch (err) {
    console.error('Erreur réactivation:', err)
    const msg = err.response?.data?.message || t('subscription.reactivate_error')
    showToast(msg, 'error')
  } finally {
    resumeLoading.value = false
  }
}

async function openBillingPortal() {
  portalLoading.value = true
  try {
    const { data } = await axios.get('/subscriptions/invoice-portal')
    window.open(data.url, '_blank')
  } catch (err) {
    console.error('Erreur portail:', err)
    showToast(t('subscription.portal_error'), 'error')
  } finally {
    portalLoading.value = false
  }
}

function scrollToPlans() {
  const el = document.getElementById('plans')
  if (el) el.scrollIntoView({ behavior: 'smooth' })
}

onMounted(fetchData)
</script>
