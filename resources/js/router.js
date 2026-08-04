import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', name: 'Landing', component: () => import('./Pages/Landing.vue'), meta: { guest: true, title: 'Accueil' } },
  { path: '/login', name: 'Login', component: () => import('./Pages/Auth/Login.vue'), meta: { guest: true, title: 'Connexion' } },
  { path: '/register', name: 'Register', component: () => import('./Pages/Auth/Register.vue'), meta: { guest: true, title: 'Inscription' } },
  { path: '/forgot-password', name: 'ForgotPassword', component: () => import('./Pages/Auth/ForgotPassword.vue'), meta: { guest: true, title: 'Mot de passe oublié' } },
  { path: '/reset-password', name: 'ResetPassword', component: () => import('./Pages/Auth/ResetPassword.vue'), meta: { guest: true, title: 'Réinitialisation' } },
  { path: '/dashboard', name: 'Dashboard', component: () => import('./Pages/Dashboard.vue'), meta: { requiresAuth: true, permission: 'view_dashboard', title: 'Tableau de bord' } },
  { path: '/products', name: 'Products', component: () => import('./Pages/Products/Index.vue'), meta: { requiresAuth: true, permission: 'view_product', title: 'Produits' } },
  { path: '/products/create', name: 'ProductCreate', component: () => import('./Pages/Products/Form.vue'), meta: { requiresAuth: true, permission: 'create_product', title: 'Nouveau produit' } },
  { path: '/products/:id', name: 'ProductShow', component: () => import('./Pages/Products/Show.vue'), meta: { requiresAuth: true, permission: 'view_product', title: 'Produit' } },
  { path: '/products/:id/edit', name: 'ProductEdit', component: () => import('./Pages/Products/Form.vue'), meta: { requiresAuth: true, permission: 'update_product', title: 'Modifier produit' } },
  { path: '/customers', name: 'Customers', component: () => import('./Pages/Customers/Index.vue'), meta: { requiresAuth: true, permission: 'view_customer', title: 'Clients' } },
  { path: '/customers/create', name: 'CustomerCreate', component: () => import('./Pages/Customers/Form.vue'), meta: { requiresAuth: true, permission: 'create_customer', title: 'Nouveau client' } },
  { path: '/customers/:id', name: 'CustomerShow', component: () => import('./Pages/Customers/Show.vue'), meta: { requiresAuth: true, permission: 'view_customer', title: 'Client' } },
  { path: '/customers/:id/edit', name: 'CustomerEdit', component: () => import('./Pages/Customers/Form.vue'), meta: { requiresAuth: true, permission: 'update_customer', title: 'Modifier client' } },
  { path: '/invoices', name: 'Invoices', component: () => import('./Pages/Invoices/Index.vue'), meta: { requiresAuth: true, permission: 'view_invoice', title: 'Factures' } },
  { path: '/invoices/create', name: 'InvoiceCreate', component: () => import('./Pages/Invoices/Form.vue'), meta: { requiresAuth: true, permission: 'create_invoice', title: 'Nouvelle facture' } },
  { path: '/invoices/:id', name: 'InvoiceShow', component: () => import('./Pages/Invoices/Show.vue'), meta: { requiresAuth: true, permission: 'view_invoice', title: 'Facture' } },
  { path: '/invoices/:id/edit', name: 'InvoiceEdit', component: () => import('./Pages/Invoices/Form.vue'), meta: { requiresAuth: true, permission: 'update_invoice', title: 'Modifier facture' } },
  { path: '/quotes', name: 'Quotes', component: () => import('./Pages/Quotes/Index.vue'), meta: { requiresAuth: true, permission: 'view_quote', title: 'Devis' } },
  { path: '/quotes/create', name: 'QuoteCreate', component: () => import('./Pages/Quotes/Form.vue'), meta: { requiresAuth: true, permission: 'create_quote', title: 'Nouveau devis' } },
  { path: '/quotes/:id', name: 'QuoteShow', component: () => import('./Pages/Quotes/Show.vue'), meta: { requiresAuth: true, permission: 'view_quote', title: 'Devis' } },
  { path: '/quotes/:id/edit', name: 'QuoteEdit', component: () => import('./Pages/Quotes/Form.vue'), meta: { requiresAuth: true, permission: 'update_quote', title: 'Modifier devis' } },
  { path: '/credit-notes', name: 'CreditNotes', component: () => import('./Pages/CreditNotes/Index.vue'), meta: { requiresAuth: true, permission: 'view_credit_note', title: 'Avoirs' } },
  { path: '/credit-notes/create', name: 'CreditNoteCreate', component: () => import('./Pages/CreditNotes/Create.vue'), meta: { requiresAuth: true, permission: 'create_credit_note', title: 'Nouvel avoir' } },
  { path: '/credit-notes/:id', name: 'CreditNoteShow', component: () => import('./Pages/CreditNotes/Show.vue'), meta: { requiresAuth: true, permission: 'view_credit_note', title: 'Avoir' } },
  { path: '/credit-notes/:id/edit', name: 'CreditNoteEdit', component: () => import('./Pages/CreditNotes/Create.vue'), meta: { requiresAuth: true, permission: 'update_credit_note', title: 'Modifier avoir' } },
  { path: '/delivery-notes', name: 'DeliveryNotes', component: () => import('./Pages/DeliveryNotes/Index.vue'), meta: { requiresAuth: true, permission: 'view_delivery_note', title: 'Bons de livraison' } },
  { path: '/delivery-notes/create', name: 'DeliveryNoteCreate', component: () => import('./Pages/DeliveryNotes/Form.vue'), meta: { requiresAuth: true, permission: 'create_delivery_note', title: 'Nouveau BL' } },
  { path: '/delivery-notes/:id', name: 'DeliveryNoteShow', component: () => import('./Pages/DeliveryNotes/Show.vue'), meta: { requiresAuth: true, permission: 'view_delivery_note', title: 'Bon de livraison' } },
  { path: '/payments', name: 'Payments', component: () => import('./Pages/Payments/Index.vue'), meta: { requiresAuth: true, permission: 'view_payment', title: 'Paiements' } },
  { path: '/stock', name: 'Stock', component: () => import('./Pages/Stock/Index.vue'), meta: { requiresAuth: true, permission: 'view_stock', title: 'Stock' } },
  { path: '/stock/transfer', name: 'StockTransfer', component: () => import('./Pages/Stock/Transfer.vue'), meta: { requiresAuth: true, permission: 'transfer_stock', title: 'Transfert de stock' } },
  { path: '/expenses', name: 'Expenses', component: () => import('./Pages/Expenses/Index.vue'), meta: { requiresAuth: true, permission: 'view_expenses', title: 'Dépenses' } },
  { path: '/capital', name: 'Capital', component: () => import('./Pages/Capital/Index.vue'), meta: { requiresAuth: true, title: 'Capital' } },
  { path: '/expenses/create', name: 'ExpenseCreate', component: () => import('./Pages/Expenses/Form.vue'), meta: { requiresAuth: true, permission: 'view_expenses', title: 'Nouvelle dépense' } },
  { path: '/expenses/:id/edit', name: 'ExpenseEdit', component: () => import('./Pages/Expenses/Form.vue'), meta: { requiresAuth: true, permission: 'update_expense', title: 'Modifier dépense' } },
  { path: '/suppliers', name: 'Suppliers', component: () => import('./Pages/Suppliers/Index.vue'), meta: { requiresAuth: true, permission: 'view_supplier', title: 'Fournisseurs' } },
  { path: '/suppliers/create', name: 'SupplierCreate', component: () => import('./Pages/Suppliers/Form.vue'), meta: { requiresAuth: true, permission: 'create_supplier', title: 'Nouveau fournisseur' } },
  { path: '/suppliers/:id/edit', name: 'SupplierEdit', component: () => import('./Pages/Suppliers/Form.vue'), meta: { requiresAuth: true, permission: 'update_supplier', title: 'Modifier fournisseur' } },
  { path: '/purchase-orders', name: 'PurchaseOrders', component: () => import('./Pages/PurchaseOrders/Index.vue'), meta: { requiresAuth: true, permission: 'view_purchase_order', title: 'Bons de commande' } },
  { path: '/purchase-orders/create', name: 'PurchaseOrderCreate', component: () => import('./Pages/PurchaseOrders/Form.vue'), meta: { requiresAuth: true, permission: 'create_purchase_order', title: 'Nouveau bon de commande' } },
  { path: '/purchase-orders/:id/edit', name: 'PurchaseOrderEdit', component: () => import('./Pages/PurchaseOrders/Form.vue'), meta: { requiresAuth: true, permission: 'update_purchase_order', title: 'Modifier bon de commande' } },
  { path: '/categories', name: 'Categories', component: () => import('./Pages/Categories/Index.vue'), meta: { requiresAuth: true, permission: 'view_category', title: 'Catégories' } },
  { path: '/categories/create', name: 'CategoryCreate', component: () => import('./Pages/Categories/Form.vue'), meta: { requiresAuth: true, permission: 'create_category', title: 'Nouvelle catégorie' } },
  { path: '/categories/:id/edit', name: 'CategoryEdit', component: () => import('./Pages/Categories/Form.vue'), meta: { requiresAuth: true, permission: 'update_category', title: 'Modifier catégorie' } },
  { path: '/warehouses', name: 'Warehouses', component: () => import('./Pages/Warehouses/Index.vue'), meta: { requiresAuth: true, permission: 'view_warehouse', title: 'Dépôts' } },
  { path: '/warehouses/create', name: 'WarehouseCreate', component: () => import('./Pages/Warehouses/Form.vue'), meta: { requiresAuth: true, permission: 'create_warehouse', title: 'Nouveau dépôt' } },
  { path: '/warehouses/:id/edit', name: 'WarehouseEdit', component: () => import('./Pages/Warehouses/Form.vue'), meta: { requiresAuth: true, permission: 'update_warehouse', title: 'Modifier dépôt' } },
  { path: '/units', name: 'Units', component: () => import('./Pages/Units/Index.vue'), meta: { requiresAuth: true, permission: 'view_unit', title: 'Unités' } },
  { path: '/units/create', name: 'UnitCreate', component: () => import('./Pages/Units/Form.vue'), meta: { requiresAuth: true, permission: 'create_unit', title: 'Nouvelle unité' } },
  { path: '/units/:id/edit', name: 'UnitEdit', component: () => import('./Pages/Units/Form.vue'), meta: { requiresAuth: true, permission: 'update_unit', title: 'Modifier unité' } },
  { path: '/events', name: 'Events', component: () => import('./Pages/Events/Index.vue'), meta: { requiresAuth: true, permission: 'view_events', title: 'Événements' } },
  { path: '/events/create', name: 'EventCreate', component: () => import('./Pages/Events/Form.vue'), meta: { requiresAuth: true, permission: 'view_events', title: 'Nouvel événement' } },
  { path: '/users', name: 'Users', component: () => import('./Pages/Users/Index.vue'), meta: { requiresAuth: true, permission: 'manage_users', title: 'Utilisateurs' } },
  { path: '/users/create', name: 'UserCreate', component: () => import('./Pages/Users/Form.vue'), meta: { requiresAuth: true, permission: 'manage_users', title: 'Nouvel utilisateur' } },
  { path: '/users/:id/edit', name: 'UserEdit', component: () => import('./Pages/Users/Form.vue'), meta: { requiresAuth: true, permission: 'manage_users', title: 'Modifier utilisateur' } },
  { path: '/activity-logs', name: 'ActivityLogs', component: () => import('./Pages/ActivityLogs/Index.vue'), meta: { requiresAuth: true, permission: 'view_activity_logs', title: 'Journal d\'activité' } },
  { path: '/settings', name: 'Settings', component: () => import('./Pages/Settings/Index.vue'), meta: { requiresAuth: true, permission: 'manage_settings', title: 'Paramètres' } },
  { path: '/settings/subscription', name: 'Subscription', component: () => import('./Pages/Settings/Subscription.vue'), meta: { requiresAuth: true, permission: 'manage_settings', title: 'Abonnement' } },
  { path: '/pos', name: 'PosIndex', component: () => import('./Pages/POS/Index.vue'), meta: { requiresAuth: true, title: 'Caisse (POS)' } },
  { path: '/pos/session', name: 'PosSession', component: () => import('./Pages/POS/Session.vue'), meta: { requiresAuth: true, title: 'Session caisse' } },
  { path: '/pos/sessions', name: 'PosSessions', component: () => import('./Pages/POS/Sessions.vue'), meta: { requiresAuth: true, title: 'Sessions caisse' } },

  // Super Admin routes
  { path: '/admin/dashboard', name: 'AdminDashboard', component: () => import('./Pages/Admin/Dashboard.vue'), meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Admin - Tableau de bord' } },
  { path: '/admin/companies', name: 'AdminCompanies', component: () => import('./Pages/Admin/Companies.vue'), meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Admin - Entreprises' } },
  { path: '/admin/login-logs', name: 'AdminLoginLogs', component: () => import('./Pages/Admin/LoginLogs.vue'), meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Admin - Connexions' } },
  { path: '/admin/activity-logs', name: 'AdminActivityLogs', component: () => import('./Pages/Admin/ActivityLogs.vue'), meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Admin - Interférences' } },
  { path: '/admin/users', name: 'AdminUsers', component: () => import('./Pages/Admin/Users.vue'), meta: { requiresAuth: true, requiresSuperAdmin: true, title: 'Admin - Utilisateurs' } },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

function getUser() {
  try {
    return JSON.parse(localStorage.getItem('user') || '{}');
  } catch {
    return {};
  }
}

function getUserPermissions() {
  try {
    const u = JSON.parse(localStorage.getItem('user') || '{}');
    return u.permissions || [];
  } catch {
    return [];
  }
}

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token');
  const user = getUser();

  // Set document title
  const companyName = (localStorage.getItem('companyName') || '').trim();
  const suffix = companyName ? ' - ' + companyName : '';
  if (to.meta.title) {
    document.title = to.meta.title + suffix;
  } else {
    document.title = companyName || 'Facturation Stock';
  }

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'Login' });
  }

  if (to.meta.guest && token && to.name !== 'Landing') {
    return next({ name: 'Dashboard' });
  }

  if (to.meta.requiresSuperAdmin && !user?.is_super_admin) {
    return next({ name: 'Dashboard' });
  }

  if (to.meta.permission && !user?.is_super_admin) {
    const permissions = getUserPermissions();
    if (permissions.length === 0) {
      // Évite la boucle infinie Login ↔ Dashboard :
      // si authentifié, on laisse passer (le serveur gère l'autorisation)
      if (token) return next();
      return next({ name: 'Login' });
    }
    if (!permissions.includes(to.meta.permission)) {
      return next({ name: 'Dashboard' });
    }
  }

  next();
});

export default router;
