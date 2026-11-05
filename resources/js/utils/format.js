export function formatPrice(amount, currency = 'XOF', locale = 'fr-FR') {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

export function formatDate(dateString, locale = 'fr-FR') {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleDateString(locale, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

export function formatDateShort(dateString, locale = 'fr-FR') {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

export function formatDateTime(dateString, locale = 'fr-FR') {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString(locale, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export const industryOptions = [
  'Agriculture & Agroalimentaire',
  'Commerce & Distribution',
  'BTP & Immobilier',
  'Industrie & Manufacture',
  'Services & Consulting',
  'Transport & Logistique',
  'Santé & Pharmacie',
  'Éducation & Formation',
  'Technologie & Télécoms',
  'Finance & Assurance',
  'Hôtellerie & Restauration',
  'Énergie & Environnement',
  'Autre',
]
