import { ref } from 'vue'

const isInitialized = ref(false)
const measurementId = ref(import.meta.env.VITE_GA_MEASUREMENT_ID || '')

function loadGtagScript() {
  return new Promise((resolve, reject) => {
    if (document.getElementById('ga-gtag-script')) {
      resolve()
      return
    }

    const script = document.createElement('script')
    script.id = 'ga-gtag-script'
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${measurementId.value}`
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

export function initAnalytics() {
  if (isInitialized.value || !measurementId.value) {
    return Promise.resolve()
  }

  return loadGtagScript().then(() => {
    window.dataLayer = window.dataLayer || []
    window.gtag = function gtag() {
      window.dataLayer.push(arguments)
    }
    window.gtag('js', new Date())
    window.gtag('config', measurementId.value, {
      send_page_view: false,
    })
    isInitialized.value = true
  }).catch((err) => {
    console.warn('Failed to load Google Analytics:', err)
  })
}

export function trackPageView(pagePath, pageTitle) {
  if (!isInitialized.value || !window.gtag) {
    return
  }

  window.gtag('config', measurementId.value, {
    page_path: pagePath,
    page_title: pageTitle,
  })
}

export function trackEvent(eventName, parameters = {}) {
  if (!isInitialized.value || !window.gtag) {
    return
  }

  window.gtag('event', eventName, parameters)
}

export function setUserId(userId) {
  if (!isInitialized.value || !window.gtag || !userId) {
    return
  }

  window.gtag('config', measurementId.value, {
    user_id: userId,
  })
}

export function setSuperAdminUser(user) {
  if (!user || !user.id) {
    return
  }

  setUserId(user.id.toString())

  if (isInitialized.value && window.gtag) {
    window.gtag('set', {
      user_properties: {
        user_type: 'super_admin',
        user_email: user.email || '',
        user_name: user.name || '',
      },
    })
  }
}

export function useAnalytics() {
  return {
    initAnalytics,
    trackPageView,
    trackEvent,
    setUserId,
    setSuperAdminUser,
    isInitialized,
    measurementId,
  }
}