export function formatPrice(amount, currency = 'XOF') {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

export function formatDate(dateString) {
  if (!dateString) return '—'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

export function formatDateShort(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

export function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('fr-FR', {
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
