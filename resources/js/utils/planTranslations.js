const featureKeys = {
  'Facturation de base': 'subscription.features.basic_billing',
  'Gestion des produits': 'subscription.features.product_management',
  'Gestion des clients': 'subscription.features.customer_management',
  'Tout le plan Free': 'subscription.features.includes_free',
  'Tout le plan Gratuit': 'subscription.features.includes_free',
  'Everything in the Free plan': 'subscription.features.includes_free',
  'Tout le plan Starter': 'subscription.features.includes_simple',
  'Tout le plan Simple': 'subscription.features.includes_simple',
  'Everything in the Starter plan': 'subscription.features.includes_simple',
  'Everything in the Simple plan': 'subscription.features.includes_simple',
  'Tout le plan Pro': 'subscription.features.includes_pro',
  'Everything in the Pro plan': 'subscription.features.includes_pro',
  'Transferts de stock': 'subscription.features.stock_transfers',
  'Stock transfers': 'subscription.features.stock_transfers',
  'Bons de livraison': 'subscription.features.delivery_notes',
  'Delivery notes': 'subscription.features.delivery_notes',
  'Devis': 'subscription.features.quotes',
  'Quotes': 'subscription.features.quotes',
  'Produits illimités': 'subscription.features.unlimited_products',
  'Unlimited products': 'subscription.features.unlimited_products',
  'Factures illimitées': 'subscription.features.unlimited_invoices',
  'Unlimited invoices': 'subscription.features.unlimited_invoices',
  'Utilisateurs illimités': 'subscription.features.unlimited_users',
  'Unlimited users': 'subscription.features.unlimited_users',
  'Produits et factures illimités': 'subscription.features.unlimited_products_invoices',
  'Unlimited products and invoices': 'subscription.features.unlimited_products_invoices',
  'Entrepôts illimités': 'subscription.features.unlimited_warehouses',
  'Unlimited warehouses': 'subscription.features.unlimited_warehouses',
  'Support prioritaire': 'subscription.features.priority_support',
  'Priority support': 'subscription.features.priority_support',
  'Multi-entrepôts': 'subscription.features.multiple_warehouses',
  'Multiple dépôts': 'subscription.features.multiple_warehouses',
  'Entrepôts multiples': 'subscription.features.multiple_warehouses',
  'Multiple warehouses': 'subscription.features.multiple_warehouses',
}

const descriptionKeys = {
  'Pour les petites entreprises qui démarrent.': 'subscription.plan_descriptions.free',
  'Pour les entreprises en croissance avec plus de besoins.': 'subscription.plan_descriptions.starter',
  'Pour les entreprises en croissance avec des besoins accrus.': 'subscription.plan_descriptions.starter',
  'Pour les entreprises en croissance.': 'subscription.plan_descriptions.starter',
  'Pour les entreprises établies à fort volume.': 'subscription.plan_descriptions.pro',
  'Pour les grandes organisations avec des besoins spécifiques.': 'subscription.plan_descriptions.enterprise',
  'Pour les grandes organisations.': 'subscription.plan_descriptions.enterprise',
}

export function translatePlanFeature(feature, t) {
  const key = featureKeys[feature?.trim()]
  if (!key) {
    const userCount = feature?.match(/^(?:Jusqu['’]à|Up to)\s+(\d+)\s+(?:utilisateurs?|users?)$/i)
    if (userCount) return t('subscription.features.up_to_users', { count: userCount[1] })
    return feature
  }
  return t(key)
}

export function translatePlanDescription(description, t) {
  const key = descriptionKeys[description?.trim()]
  return key ? t(key) : description
}
