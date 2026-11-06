export const TOUR_RESTART_EVENT = 'app-tour:restart'
export const TOUR_USER_UPDATED_EVENT = 'app-tour:user-updated'
export const TOUR_SIDEBAR_EVENT = 'app-tour:sidebar'

export function requestTourRestart() {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent(TOUR_RESTART_EVENT))
}

export function notifyTourUserUpdated() {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent(TOUR_USER_UPDATED_EVENT))
}

export function emitTourSidebar(open) {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new CustomEvent(TOUR_SIDEBAR_EVENT, { detail: { open: !!open } }))
}
