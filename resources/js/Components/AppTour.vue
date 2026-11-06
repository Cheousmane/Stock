<template>
  <Teleport to="body">
    <Transition name="tour-fade">
      <div v-if="open && currentStep" class="tour-root" role="presentation">
        <template v-if="targetRect">
          <div class="tour-shade" :style="panelStyle('top')" />
          <div class="tour-shade" :style="panelStyle('left')" />
          <div class="tour-shade" :style="panelStyle('right')" />
          <div class="tour-shade" :style="panelStyle('bottom')" />
          <div class="tour-highlight" :style="highlightStyle" aria-hidden="true" />
        </template>
        <div v-else class="tour-intro-backdrop" aria-hidden="true" />

        <section
          class="tour-card"
          :class="{ 'tour-card--intro': !targetRect }"
          :style="cardStyle"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="`tour-title-${stepIndex}`"
          :aria-describedby="`tour-description-${stepIndex}`"
        >
          <div class="tour-card-accent" aria-hidden="true" />

          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="tour-step-chip">
                  {{ $t('appTour.step', { current: stepIndex + 1, total: steps.length }) }}
                </span>
                <span v-if="profileLabel" class="tour-profile-chip">
                  <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0" />
                  </svg>
                  {{ $t('appTour.personalized_for', { role: $t(profileLabel) }) }}
                </span>
              </div>
              <h2 :id="`tour-title-${stepIndex}`" class="tour-title" aria-live="polite">
                {{ $t(currentStep.title) }}
              </h2>
            </div>
            <button type="button" class="tour-icon-button" :aria-label="$t('appTour.close')" @click="finish(true)">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
              </svg>
            </button>
          </div>

          <div
            class="tour-progress-track mt-4"
            role="progressbar"
            :aria-valuenow="stepIndex + 1"
            aria-valuemin="1"
            :aria-valuemax="steps.length"
          >
            <div class="tour-progress-bar" :style="{ width: `${((stepIndex + 1) / steps.length) * 100}%` }" />
          </div>

          <p :id="`tour-description-${stepIndex}`" class="tour-description">
            {{ $t(currentStep.description) }}
          </p>

          <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            <button type="button" class="tour-skip-button" @click="finish(true)">
              {{ $t('appTour.skip') }}
            </button>
            <div class="flex items-center gap-2">
              <button
                v-if="stepIndex > 0"
                type="button"
                class="tour-secondary-button"
                :disabled="busy"
                @click="previous"
              >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                {{ $t('appTour.previous') }}
              </button>
              <button type="button" class="tour-primary-button" :disabled="busy" @click="next">
                <span v-if="busy" class="tour-spinner" aria-hidden="true" />
                <template v-else>
                  {{ stepIndex === steps.length - 1 ? $t('appTour.finish') : $t('appTour.next') }}
                  <svg v-if="stepIndex < steps.length - 1" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                  </svg>
                </template>
              </button>
            </div>
          </div>
        </section>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { emitTourSidebar } from '../tour/events'

const props = defineProps({
  profileLabel: { type: String, default: '' },
})
const emit = defineEmits(['finished'])

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const open = ref(false)
const busy = ref(false)
const stepIndex = ref(0)
const steps = ref([])
const targetRect = ref(null)
const viewportTick = ref(0)
let runToken = 0

const currentStep = computed(() => steps.value[stepIndex.value])

const highlightStyle = computed(() => {
  if (!targetRect.value) return {}
  const { top, left, width, height } = targetRect.value
  return {
    top: `${top}px`,
    left: `${left}px`,
    width: `${width}px`,
    height: `${height}px`,
  }
})

const cardStyle = computed(() => {
  // Keep a reactive dependency on viewport changes.
  viewportTick.value

  if (!targetRect.value || typeof window === 'undefined' || window.innerWidth < 640) {
    return {}
  }

  const width = Math.min(460, window.innerWidth - 32)
  const estimatedHeight = 280
  const rect = targetRect.value
  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top
  const placeBelow = spaceBelow >= estimatedHeight + 24 || spaceBelow >= spaceAbove
  const top = placeBelow
    ? Math.min(rect.bottom + 16, Math.max(16, window.innerHeight - estimatedHeight - 16))
    : Math.max(16, rect.top - estimatedHeight - 16)
  const left = Math.max(
    16,
    Math.min(rect.left + rect.width / 2 - width / 2, window.innerWidth - width - 16),
  )

  return {
    top: `${top}px`,
    left: `${left}px`,
    width: `${width}px`,
    bottom: 'auto',
    transform: 'none',
  }
})

function panelStyle(panel) {
  const rect = targetRect.value
  if (!rect) return {}
  const { top, left, right, bottom } = rect
  const common = { position: 'fixed', background: 'rgb(2 6 23 / 0.74)' }
  if (panel === 'top') return { ...common, inset: '0 0 auto', height: `${top}px` }
  if (panel === 'bottom') return { ...common, top: `${bottom}px`, right: '0', bottom: '0', left: '0' }
  if (panel === 'left') return { ...common, top: `${top}px`, left: '0', width: `${left}px`, height: `${bottom - top}px` }
  return { ...common, top: `${top}px`, left: `${right}px`, right: '0', height: `${bottom - top}px` }
}

function updateTarget() {
  if (!open.value) return
  const selector = currentStep.value?.selector
  const target = selector ? document.querySelector(selector) : null
  if (!target) {
    targetRect.value = null
    return
  }
  const rect = target.getBoundingClientRect()
  const padding = 7
  const top = Math.max(0, rect.top - padding)
  const left = Math.max(0, rect.left - padding)
  const right = Math.min(window.innerWidth, rect.right + padding)
  const bottom = Math.min(window.innerHeight, rect.bottom + padding)

  targetRect.value = {
    top,
    left,
    right,
    bottom,
    width: Math.max(0, right - left),
    height: Math.max(0, bottom - top),
  }
}

function sleep(ms) {
  return new Promise((resolve) => window.setTimeout(resolve, ms))
}

async function waitForElement(selector, timeout = 3500) {
  const startedAt = Date.now()
  while (Date.now() - startedAt < timeout) {
    const element = document.querySelector(selector)
    if (element) return element
    await sleep(80)
  }
  return null
}

async function activate(index, direction, token) {
  if (token !== runToken || !open.value) return

  if (index < 0 || index >= steps.value.length) {
    finish(false)
    return
  }

  const step = steps.value[index]
  if (!step) {
    finish(false)
    return
  }

  busy.value = true
  stepIndex.value = index
  targetRect.value = null

  if (step.route && route.path !== step.route) {
    try {
      await router.push(step.route)
    } catch {
      busy.value = false
      if (token === runToken && open.value) activate(index + direction, direction, token)
      return
    }
    await nextTick()
    await sleep(280)
  }

  if (token !== runToken || !open.value) return

  // Open/close the mobile sidebar only after the destination layout is
  // mounted, otherwise a route change immediately closes it again.
  emitTourSidebar(!!step.focus_sidebar)

  if (step.selector) {
    const target = await waitForElement(step.selector)
    if (token !== runToken || !open.value) return

    if (!target) {
      busy.value = false
      // The interface element is unavailable despite the permission: never
      // present it as accessible, move to the next candidate instead.
      activate(index + direction, direction, token)
      return
    }

    target.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    await sleep(180)
    if (token !== runToken || !open.value) return
    updateTarget()
  }

  busy.value = false
}

async function start(customSteps = []) {
  if (!Array.isArray(customSteps) || customSteps.length === 0) return

  runToken += 1
  const token = runToken
  steps.value = customSteps
  stepIndex.value = 0
  targetRect.value = null
  open.value = true
  await nextTick()
  await activate(0, 1, token)
}

function next() {
  if (busy.value) return
  if (stepIndex.value >= steps.value.length - 1) {
    finish(false)
    return
  }
  runToken += 1
  activate(stepIndex.value + 1, 1, runToken)
}

function previous() {
  if (busy.value || stepIndex.value <= 0) return
  runToken += 1
  activate(stepIndex.value - 1, -1, runToken)
}

function stop() {
  if (!open.value) return
  runToken += 1
  open.value = false
  targetRect.value = null
  busy.value = false
  emitTourSidebar(false)
}

function finish(skipped = false) {
  if (!open.value) return
  stop()
  emit('finished', { skipped })
}

function isOpen() {
  return open.value
}

function onKeydown(event) {
  if (!open.value) return
  if (event.key === 'Escape') {
    event.preventDefault()
    finish(true)
  }
  if (event.key === 'ArrowRight') {
    event.preventDefault()
    next()
  }
  if (event.key === 'ArrowLeft') {
    event.preventDefault()
    previous()
  }
}

function onResize() {
  viewportTick.value += 1
  updateTarget()
}

onMounted(() => {
  window.addEventListener('resize', onResize)
  window.addEventListener('scroll', updateTarget, true)
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('app-tour:started', onResize)
})

onBeforeUnmount(() => {
  runToken += 1
  window.removeEventListener('resize', onResize)
  window.removeEventListener('scroll', updateTarget, true)
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('app-tour:started', onResize)
})

defineExpose({ start, stop, isOpen })
</script>

<style scoped>
.tour-root {
  position: fixed;
  inset: 0;
  z-index: 100;
  pointer-events: none;
}

.tour-shade,
.tour-intro-backdrop {
  pointer-events: auto;
}

.tour-intro-backdrop {
  position: fixed;
  inset: 0;
  background:
    radial-gradient(circle at 50% 35%, rgb(16 185 129 / 0.12), transparent 45%),
    rgb(2 6 23 / 0.74);
}

.tour-highlight {
  position: fixed;
  z-index: 101;
  border: 2px solid rgb(52 211 153);
  border-radius: 16px;
  background: rgb(16 185 129 / 0.06);
  box-shadow:
    0 0 0 4px rgb(16 185 129 / 0.18),
    0 0 38px rgb(16 185 129 / 0.28);
  pointer-events: none;
  transition: top 180ms ease, left 180ms ease, width 180ms ease, height 180ms ease;
}

.tour-card {
  position: fixed;
  z-index: 102;
  left: 50%;
  bottom: max(1.25rem, env(safe-area-inset-bottom));
  width: min(28rem, calc(100vw - 2rem));
  padding: 1.25rem;
  transform: translateX(-50%);
  border: 1px solid var(--color-border);
  border-radius: 1.35rem;
  background: var(--color-surface);
  box-shadow:
    0 28px 90px rgb(0 0 0 / 0.32),
    0 0 0 1px rgb(255 255 255 / 0.04) inset;
  pointer-events: auto;
  overflow: hidden;
  transition: top 180ms ease, left 180ms ease, width 180ms ease;
}

.tour-card--intro {
  top: 50%;
  bottom: auto;
  transform: translate(-50%, -50%);
}

.tour-card-accent {
  position: absolute;
  inset: 0 0 auto;
  height: 3px;
  background: linear-gradient(90deg, #10b981, #2dd4bf, #34d399);
}

.tour-step-chip {
  display: inline-flex;
  align-items: center;
  border-radius: 9999px;
  background: rgb(16 185 129 / 0.1);
  border: 1px solid rgb(16 185 129 / 0.2);
  color: rgb(4 120 87);
  padding: 0.22rem 0.55rem;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

:global(.dark) .tour-step-chip {
  color: rgb(110 231 183);
}

.tour-profile-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  border-radius: 9999px;
  background: var(--color-surface-tertiary);
  color: var(--color-text-tertiary);
  padding: 0.22rem 0.55rem;
  font-size: 10px;
  font-weight: 700;
}

.tour-title {
  margin-top: 0.4rem;
  font-size: 1.06rem;
  line-height: 1.35;
  font-weight: 900;
  letter-spacing: -0.02em;
  color: var(--color-text-primary);
}

.tour-progress-track {
  height: 5px;
  overflow: hidden;
  border-radius: 9999px;
  background: var(--color-surface-tertiary);
}

.tour-progress-bar {
  height: 100%;
  border-radius: 9999px;
  background: linear-gradient(90deg, #10b981, #2dd4bf);
  transition: width 300ms ease;
}

.tour-description {
  margin-top: 0.8rem;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  line-height: 1.65;
}

.tour-icon-button {
  flex-shrink: 0;
  border-radius: 0.65rem;
  padding: 0.3rem;
  color: var(--color-text-tertiary);
  transition: background 150ms ease, color 150ms ease;
}

.tour-icon-button:hover {
  background: var(--color-surface-tertiary);
  color: var(--color-text-primary);
}

.tour-skip-button {
  color: var(--color-text-tertiary);
  font-size: 0.8rem;
  font-weight: 700;
  transition: color 150ms ease;
}

.tour-skip-button:hover {
  color: var(--color-text-primary);
}

.tour-secondary-button,
.tour-primary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  border-radius: 0.8rem;
  padding: 0.58rem 0.9rem;
  font-size: 0.8rem;
  font-weight: 800;
  transition: background 150ms ease, transform 150ms ease, box-shadow 150ms ease;
}

.tour-secondary-button {
  border: 1px solid var(--color-border);
  color: var(--color-text-secondary);
  background: var(--color-surface);
}

.tour-secondary-button:hover:not(:disabled) {
  background: var(--color-surface-tertiary);
}

.tour-primary-button {
  color: #fff;
  background: linear-gradient(135deg, #10b981, #0d9488);
  box-shadow: 0 8px 22px rgb(16 185 129 / 0.28);
}

.tour-primary-button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 28px rgb(16 185 129 / 0.35);
}

.tour-primary-button:disabled,
.tour-secondary-button:disabled {
  opacity: 0.65;
  cursor: wait;
}

.tour-spinner {
  width: 0.95rem;
  height: 0.95rem;
  border-radius: 9999px;
  border: 2px solid rgb(255 255 255 / 0.35);
  border-top-color: #fff;
  animation: tour-spin 0.7s linear infinite;
}

@keyframes tour-spin {
  to {
    transform: rotate(360deg);
  }
}

.tour-fade-enter-active,
.tour-fade-leave-active {
  transition: opacity 180ms ease;
}

.tour-fade-enter-from,
.tour-fade-leave-to {
  opacity: 0;
}

@media (max-width: 639px) {
  .tour-card {
    left: 1rem;
    right: 1rem;
    bottom: max(1rem, env(safe-area-inset-bottom));
    width: auto;
    transform: none;
    padding: 1.1rem;
  }

  .tour-card--intro {
    top: 50%;
    bottom: auto;
    transform: translateY(-50%);
  }

  .tour-description {
    font-size: 0.84rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .tour-highlight,
  .tour-card,
  .tour-progress-bar,
  .tour-fade-enter-active,
  .tour-fade-leave-active {
    transition: none;
  }
}
</style>
