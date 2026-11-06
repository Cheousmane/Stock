<template>
  <AppTour ref="tourRef" :profile-label="profileLabel" @finished="handleFinished" />
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import AppTour from './AppTour.vue'
import { fallbackTourSteps } from '../tour/fallback'
import { TOUR_RESTART_EVENT } from '../tour/events'
import {
  applyAccessSnapshot,
  readStoredUser,
  refreshCurrentUser,
  resetCurrentUserRefreshClock,
} from '../composables/useCurrentUser'

const tourRef = ref(null)
const payload = ref(null)
const route = useRoute()

let bootstrapped = false
let starting = false
let focusTimer = null
let accessTimer = null
let lastFocusRefresh = 0
let lastAutoStartHash = null

const profileLabel = computed(() => payload.value?.profile_label || '')

function hasToken() {
  return Boolean(localStorage.getItem('token'))
}

function isGuestRoute() {
  return Boolean(route.meta?.guest)
}

function isTourOpen() {
  return Boolean(tourRef.value?.isOpen?.())
}

function waitForLayout() {
  return new Promise((resolve) => {
    let attempts = 0
    const check = () => {
      const ready = document.querySelector('[data-tour="profile"], [data-tour="navigation"]')
      if (ready || attempts >= 50) {
        resolve()
        return
      }
      attempts += 1
      window.requestAnimationFrame(check)
    }
    window.requestAnimationFrame(check)
  })
}

async function loadTour() {
  const { data } = await axios.get('/auth/tour')
  payload.value = data

  // Keep the cached session data aligned with the server so menus and route
  // guards immediately reflect the permissions used to build this tour.
  if (Array.isArray(data.permissions)) {
    applyAccessSnapshot({
      roles: data.roles,
      permissions: data.permissions,
      is_super_admin: data.is_super_admin,
    })
  }

  return data
}

function usableSteps(data) {
  return Array.isArray(data?.steps) ? data.steps.filter((step) => step?.title) : []
}

async function startTour(data, { force = false } = {}) {
  if (!hasToken() || isGuestRoute()) return false
  if ((!force && isTourOpen()) || starting) return false

  const steps = usableSteps(data)
  if (!steps.length) return false

  starting = true
  try {
    await waitForLayout()
    await nextTick()
    tourRef.value?.start(steps)
    return true
  } finally {
    starting = false
  }
}

async function bootstrap() {
  if (bootstrapped || !hasToken() || isGuestRoute()) return
  bootstrapped = true

  try {
    const data = await loadTour()
    if (data.should_start) {
      lastAutoStartHash = data.permission_hash || null
      await startTour(data)
    }
  } catch {
    // Do not interrupt a first visit with a fallback tour when the server is
    // unavailable. A manual restart can still use the local catalogue.
    bootstrapped = false
  }
}

async function restartTour() {
  if (!hasToken() || isGuestRoute()) return

  bootstrapped = true
  resetCurrentUserRefreshClock()

  try {
    try {
      await axios.post('/auth/tour/restart')
    } catch {
      // Continue with the freshly fetched catalogue; completion will simply
      // be overwritten once the user finishes the tour again.
    }

    const data = await loadTour()
    lastAutoStartHash = data.permission_hash || null
    const started = await startTour(data, { force: true })
    if (!started) throw new Error('tour_unavailable')
  } catch {
    const started = await startTour(
      { steps: fallbackTourSteps(readStoredUser()), profile_label: '' },
      { force: true },
    )
    if (!started) {
      window.dispatchEvent(new CustomEvent('app-tour:unavailable'))
    }
  }
}

async function handleFinished() {
  if (!payload.value) return

  const completedHash = payload.value.permission_hash || null
  payload.value = {
    ...payload.value,
    completed: true,
    needs_refresh: false,
    should_start: false,
  }
  lastAutoStartHash = completedHash

  try {
    await axios.post('/auth/tour/complete')
  } catch {
    // The server remains the source of truth on the next request; a failed
    // acknowledgement must not block navigation.
  }
}

async function refreshAccess() {
  if (!hasToken() || isGuestRoute() || isTourOpen()) return

  try {
    const { changed } = await refreshCurrentUser({ force: true })
    const data = await loadTour()

    if (!changed && !data.needs_refresh) return

    const hash = data.permission_hash || null
    if (data.should_start && hash && hash !== lastAutoStartHash) {
      lastAutoStartHash = hash
      await startTour(data)
    }
  } catch {
    // Offline or transient API failures are ignored; the next focus retries.
  }
}

function scheduleAccessRefresh() {
  if (!hasToken() || isGuestRoute() || isTourOpen()) return
  if (Date.now() - lastFocusRefresh < 30000) return

  lastFocusRefresh = Date.now()
  window.clearTimeout(focusTimer)
  focusTimer = window.setTimeout(() => {
    refreshAccess()
  }, 500)
}

function handleRestartRequest() {
  restartTour()
}

function onStorage(event) {
  if (event.key === 'token' && !event.newValue) {
    tourRef.value?.stop?.()
    bootstrapped = false
    payload.value = null
    lastAutoStartHash = null
  }
}

function onVisibilityChange() {
  if (document.visibilityState === 'visible') {
    scheduleAccessRefresh()
  }
}

watch(
  () => route.fullPath,
  () => {
    if (isGuestRoute()) {
      tourRef.value?.stop?.()
      bootstrapped = false
      payload.value = null
      lastAutoStartHash = null
      return
    }

    if (!bootstrapped && hasToken()) {
      window.setTimeout(() => {
        bootstrap()
      }, 350)
    }
  },
)

onMounted(() => {
  window.addEventListener('focus', scheduleAccessRefresh)
  window.addEventListener(TOUR_RESTART_EVENT, handleRestartRequest)
  window.addEventListener('storage', onStorage)
  document.addEventListener('visibilitychange', onVisibilityChange)
  accessTimer = window.setInterval(scheduleAccessRefresh, 300000)
  bootstrap()
})

onBeforeUnmount(() => {
  window.clearTimeout(focusTimer)
  window.clearInterval(accessTimer)
  window.removeEventListener('focus', scheduleAccessRefresh)
  window.removeEventListener(TOUR_RESTART_EVENT, handleRestartRequest)
  window.removeEventListener('storage', onStorage)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>
