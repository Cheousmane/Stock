import axios from 'axios'
import { notifyTourUserUpdated } from '../tour/events'

const USER_KEY = 'user'

export function readStoredUser() {
  try {
    return JSON.parse(localStorage.getItem(USER_KEY) || '{}') || {}
  } catch {
    return {}
  }
}

export function storeCurrentUser(user) {
  if (!user || typeof user !== 'object') return readStoredUser()
  localStorage.setItem(USER_KEY, JSON.stringify(user))
  return user
}

export function userAccessSignature(user = {}) {
  const roles = Array.isArray(user.roles) ? [...user.roles].sort() : []
  const permissions = Array.isArray(user.permissions) ? [...user.permissions].sort() : []
  return JSON.stringify({
    superAdmin: !!user.is_super_admin,
    roles,
    permissions,
  })
}

/**
 * Merge the access data returned by the tour endpoint into the cached user.
 */
export function applyAccessSnapshot(snapshot = {}) {
  const current = readStoredUser()
  if (!current || (!current.id && !current.email)) {
    return { user: current, changed: false }
  }

  const next = {
    ...current,
    roles: Array.isArray(snapshot.roles) ? snapshot.roles : current.roles || [],
    permissions: Array.isArray(snapshot.permissions) ? snapshot.permissions : current.permissions || [],
    is_super_admin: snapshot.is_super_admin ?? current.is_super_admin,
  }
  const changed = userAccessSignature(current) !== userAccessSignature(next)
  storeCurrentUser(next)
  if (changed) notifyTourUserUpdated()

  return { user: next, changed }
}

let lastRefreshAt = 0
let refreshPromise = null

/**
 * Refresh the authenticated user without firing a request for every page
 * navigation. Permissions are re-read from the server on a full reload and
 * on explicit focus refreshes.
 */
export async function refreshCurrentUser({ force = false, maxAge = 15000 } = {}) {
  const token = localStorage.getItem('token')
  if (!token) return { user: readStoredUser(), changed: false }

  if (!force && Date.now() - lastRefreshAt < maxAge) {
    return { user: readStoredUser(), changed: false }
  }

  if (refreshPromise) return refreshPromise

  refreshPromise = axios
    .get('/auth/me')
    .then(({ data }) => {
      const fresh = data.user ?? data.data ?? data
      const current = readStoredUser()
      const changed = userAccessSignature(current) !== userAccessSignature(fresh)
      storeCurrentUser(fresh)
      lastRefreshAt = Date.now()
      if (changed) notifyTourUserUpdated()
      return { user: fresh, changed }
    })
    .catch(() => ({ user: readStoredUser(), changed: false }))
    .finally(() => {
      refreshPromise = null
    })

  return refreshPromise
}

export function resetCurrentUserRefreshClock() {
  lastRefreshAt = 0
  refreshPromise = null
}
