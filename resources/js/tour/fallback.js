/**
 * Minimal fallback tour used only when the personalisation endpoint is
 * temporarily unreachable. The server-side catalogue remains the source of
 * truth for every normal start or restart.
 */
export function fallbackTourSteps(user = {}) {
  const permissions = Array.isArray(user.permissions) ? user.permissions : []
  const home = permissions.includes('view_dashboard') ? '/dashboard' : null
  const steps = [
    {
      id: 'welcome',
      type: 'intro',
      route: null,
      selector: null,
      title: 'appTour.welcome_title',
      description: 'appTour.welcome_description',
      focus_sidebar: false,
    },
  ]

  if (home) {
    steps.push({
      id: 'navigation',
      type: 'target',
      route: home,
      selector: '[data-tour="navigation"]',
      title: 'appTour.steps.navigation.title',
      description: 'appTour.steps.navigation.description',
      focus_sidebar: true,
    })
  }

  steps.push({
    id: 'search',
    type: 'target',
    route: home,
    selector: '[data-tour="search"]',
    title: 'appTour.steps.search.title',
    description: 'appTour.steps.search.description',
    focus_sidebar: false,
  })

  if (['create_invoice', 'create_product', 'create_customer', 'access_pos'].some((permission) => permissions.includes(permission))) {
    steps.push({
      id: 'quick_create',
      type: 'target',
      route: home,
      selector: '[data-tour="quick-create"]',
      title: 'appTour.steps.quick_create.title',
      description: 'appTour.steps.quick_create.description',
      focus_sidebar: false,
    })
  }

  steps.push(
    {
      id: 'help',
      type: 'target',
      route: null,
      selector: '[data-tour="help"]',
      title: 'appTour.steps.help.title',
      description: 'appTour.steps.help.description',
      focus_sidebar: false,
    },
    {
      id: 'profile',
      type: 'target',
      route: null,
      selector: '[data-tour="profile"]',
      title: 'appTour.steps.profile.title',
      description: 'appTour.steps.profile.description',
      focus_sidebar: false,
    },
  )

  return steps
}
