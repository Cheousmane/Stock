<template>
  <Teleport to="body">
    <div v-if="open && currentStep" class="fixed inset-0 z-[100]">
      <template v-if="targetRect">
        <div class="tour-shade" :style="panelStyle('top')" />
        <div class="tour-shade" :style="panelStyle('left')" />
        <div class="tour-shade" :style="panelStyle('right')" />
        <div class="tour-shade" :style="panelStyle('bottom')" />
        <div class="tour-highlight" :style="highlightStyle" aria-hidden="true" />
      </template>

      <section
        class="tour-card"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="`tour-title-${stepIndex}`"
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-600 dark:text-emerald-400">
              {{ $t('appTour.step', { current: stepIndex + 1, total: steps.length }) }}
            </p>
            <h2 :id="`tour-title-${stepIndex}`" class="mt-1 text-lg font-extrabold text-text-primary">
              {{ $t(currentStep.title) }}
            </h2>
          </div>
          <button
            type="button"
            class="rounded-lg p-1 text-text-tertiary hover:bg-surface-tertiary hover:text-text-primary"
            :aria-label="$t('appTour.close')"
            @click="finish"
          >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
            </svg>
          </button>
        </div>

        <p class="mt-2 text-sm leading-relaxed text-text-secondary">{{ $t(currentStep.description) }}</p>

        <div class="mt-5 flex items-center justify-between gap-3">
          <button
            type="button"
            class="text-sm font-semibold text-text-tertiary hover:text-text-primary"
            @click="finish"
          >
            {{ $t('appTour.skip') }}
          </button>
          <div class="flex items-center gap-2">
            <button
              v-if="stepIndex > 0"
              type="button"
              class="rounded-xl border border-border px-3.5 py-2 text-sm font-bold text-text-secondary hover:bg-surface-tertiary"
              @click="previous"
            >
              {{ $t('appTour.previous') }}
            </button>
            <button
              type="button"
              class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-700"
              @click="next"
            >
              {{ stepIndex === steps.length - 1 ? $t('appTour.finish') : $t('appTour.next') }}
            </button>
          </div>
        </div>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  identity: { type: String, default: '' },
})
const emit = defineEmits(['navigation-target'])

const { t } = useI18n()
const open = ref(false)
const stepIndex = ref(0)
const targetRect = ref(null)
const allSteps = [
  { selector: '[data-tour="dashboard"]', title: 'appTour.dashboard_title', description: 'appTour.dashboard_description' },
  { selector: '[data-tour="products"]', title: 'appTour.navigation_title', description: 'appTour.navigation_description' },
  { selector: '[data-tour="quick-create"]', title: 'appTour.quick_create_title', description: 'appTour.quick_create_description' },
  { selector: '[data-tour="search"]', title: 'appTour.search_title', description: 'appTour.search_description' },
  { selector: '[data-tour="settings"]', title: 'appTour.settings_title', description: 'appTour.settings_description' },
  { selector: '[data-tour="profile"]', title: 'appTour.profile_title', description: 'appTour.profile_description' },
]
const fallbackStep = {
  selector: null,
  title: 'appTour.dashboard_title',
  description: 'appTour.dashboard_description',
}
const steps = ref([])

const currentStep = computed(() => steps[stepIndex.value])
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

function panelStyle(panel) {
  const rect = targetRect.value
  if (!rect) return {}
  const { top, left, right, bottom } = rect
  const common = { position: 'fixed', background: 'rgb(2 6 23 / 0.72)' }
  if (panel === 'top') return { ...common, inset: `0 0 auto`, height: `${top}px` }
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
  const padding = 6
  targetRect.value = {
    top: Math.max(0, rect.top - padding),
    left: Math.max(0, rect.left - padding),
    right: Math.min(window.innerWidth, rect.right + padding),
    bottom: Math.min(window.innerHeight, rect.bottom + padding),
    width: Math.min(window.innerWidth, rect.right + padding) - Math.max(0, rect.left - padding),
    height: Math.min(window.innerHeight, rect.bottom + padding) - Math.max(0, rect.top - padding),
  }
}

function seenKey(identity) {
  return `app-tour-completed:${identity}`
}

async function show() {
  steps.value = allSteps.filter((step) => document.querySelector(step.selector))
  if (!steps.value.length) steps.value = [fallbackStep]
  stepIndex.value = 0
  const firstStep = steps.value[0]
  open.value = true
  emit('navigation-target', Boolean(firstStep.selector && /data-tour="(?:dashboard|products|settings)"/.test(firstStep.selector)))
  await nextTick()
  if (!open.value || currentStep.value !== firstStep) return
  if (firstStep.selector) {
    document.querySelector(firstStep.selector)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  }
  updateTarget()
}

async function start() {
  open.value = false
  targetRect.value = null
  await nextTick()
  await show()
}

async function moveTo(index) {
  if (index < 0 || index >= steps.value.length) return
  stepIndex.value = index
  targetRect.value = null
  const step = currentStep.value
  if (!step) return
  emit('navigation-target', Boolean(step.selector && /data-tour="(?:dashboard|products|settings)"/.test(step.selector)))
  await nextTick()
  if (!open.value || currentStep.value !== step) return
  const target = step.selector ? document.querySelector(step.selector) : null
  target?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  window.setTimeout(updateTarget, 220)
}

function next() {
  if (stepIndex.value >= steps.value.length - 1) {
    finish()
    return
  }
  moveTo(stepIndex.value + 1)
}

function previous() {
  if (stepIndex.value > 0) moveTo(stepIndex.value - 1)
}

function finish() {
  open.value = false
  targetRect.value = null
  emit('navigation-target', false)
  if (props.identity) localStorage.setItem(seenKey(props.identity), 'true')
}

function onKeydown(event) {
  if (!open.value) return
  if (event.key === 'Escape') finish()
  if (event.key === 'ArrowRight') next()
  if (event.key === 'ArrowLeft') previous()
}

watch(() => props.identity, (identity) => {
  if (!identity || localStorage.getItem(seenKey(identity))) return
  window.setTimeout(start, 900)
})

onMounted(() => {
  window.addEventListener('resize', updateTarget)
  window.addEventListener('scroll', updateTarget, true)
  window.addEventListener('keydown', onKeydown)
  if (props.identity && !localStorage.getItem(seenKey(props.identity))) {
    window.setTimeout(start, 900)
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateTarget)
  window.removeEventListener('scroll', updateTarget, true)
  window.removeEventListener('keydown', onKeydown)
})

defineExpose({ start })
</script>

<style scoped>
.tour-shade {
  position: fixed;
  z-index: 100;
  pointer-events: auto;
}

.tour-highlight {
  position: fixed;
  z-index: 101;
  border: 2px solid rgb(52 211 153);
  border-radius: 14px;
  box-shadow: 0 0 0 4px rgb(16 185 129 / 0.2);
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
  border-radius: 1.25rem;
  background: var(--color-surface);
  box-shadow: 0 24px 80px rgb(0 0 0 / 0.28);
}
</style>
