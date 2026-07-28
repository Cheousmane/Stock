# Facturation Stock

SaaS multi-tenant de gestion commerciale et de stock destiné au marché **Afrique de l'Ouest** (devise XOF).

Table des matières

- [Aperçu](#aperçu)
- [Stack technique](#stack-technique)
- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Base de données](#base-de-données)
- [API](#api)
- [Authentification](#authentification)
- [Multi-tenant](#multi-tenant)
- [Installation](#installation)
- [Développement](#développement)
- [Tests](#tests)
- [Structure du projet](#structure-du-projet)

---

## Aperçu

Application complète de gestion d'entreprise permettant la facturation, la gestion des stocks, des clients, fournisseurs, devis, bons de livraison, avoirs, achats, paiements, avec un système d'abonnements Stripe et un contrôle d'accès par rôles (RBAC).

**Public cible :** PME et TPE en zone XOF (Franc CFA Afrique de l'Ouest).

---

## Stack technique

### Backend

| Composant | Technologie |
|---|---|
| Langage | PHP ^8.2 |
| Framework | Laravel ^12.0 |
| Auth API | Laravel Sanctum ^4.0 (token-based) |
| RBAC | Spatie Laravel Permission ^6.25 (team-based) |
| Activity Log | Spatie Laravel Activitylog |
| PDF | barryvdh/laravel-dompdf ^3.1 |
| Excel | maatwebsite/excel ^3.1 |
| Paiement | Laravel Cashier ^16.5 (Stripe) |
| Queue / Cache / Session | Database driver |

### Frontend

| Composant | Technologie |
|---|---|
| Framework | Vue 3 (Composition API) |
| Build | Vite ^7.0.7 |
| CSS | Tailwind CSS ^4.0 |
| Router | vue-router ^4.6.4 |
| i18n | vue-i18n ^11.4.4 (16 langues) |
| Charts | Chart.js ^4.5.1 + vue-chartjs ^5.3.3 |
| Icons | @heroicons/vue ^2.2.0 |
| HTTP | axios ^1.11.0 |

---

## Fonctionnalités

### Gestion commerciale
- **Factures** — Création, édition, statuts (brouillon, envoyée, payée, en retard, annulée), PDF, paiements
- **Devis** — Création, conversion en facture, statuts (brouillon, envoyé, accepté, rejeté, expiré), PDF
- **Bons de livraison** — Expédition, livraison, retour, PDF
- **Avoirs** — Validation, PDF
- **Bons d'achat** — Réception, mise à jour du stock
- **Paiements** — Espèces, virement bancaire, Stripe, mobile money

### Gestion de stock
- Produits avec variantes (SKU, code-barres, prix multiples)
- Catégories hiérarchiques
- Taxes simples et composites
- Unités de mesure
- Multi-entrepôts
- Mouvements de stock (entrée, sortie, ajustement, transfert, retour)
- Transferts entre entrepôts
- Valorisation du stock (FIFO / coût moyen)
- Seuil d'alerte de stock minimum

### CRM
- Clients (code, limite de crédit, solde)
- Fournisseurs

### Abonnements & Facturation
- 4 plans : Free, Starter (15 000 XOF), Pro (35 000 XOF), Enterprise (100 000 XOF)
- Quotas : utilisateurs max, produits max, factures max, entrepôts max
- Paiement Stripe avec portail client
- Changement de plan (swap), annulation, reprise

### Sécurité & Multi-tenant
- Isolation complète des données par société (tenant)
- 6 rôles : admin, manager, accountant, warehouse_manager, sales, employee
- 51 permissions granulaires
- Middleware de quota par abonnement

### Autres
- 16 langues (fr, en, es, de, pt, it, nl, pl, ru, tr, vi, ar, zh, ja, ko, hi)
- Support RTL (arabe)
- Thème clair/sombre
- Journal d'activité
- Webhooks sortants
- Exports synchrones et asynchrones (CSV, XLSX)
- Analytics & indicateurs de profit
- Calendrier d'événements
- Gestion des dépenses

---

## Architecture

### Design Patterns

| Pattern | Utilisation |
|---|---|
| **Action** | Logique métier extraite dans des classes dédiées (ex: `CreateInvoiceAction`, `RegisterCompanyAction`) |
| **DTO** | Objets de transfert de données (ex: `InvoiceDTO`, `ProductDTO`) |
| **Service** | Services métier complexes (`AnalyticsService`, `ProfitService`, `StockValuationService`, PDF services) |
| **Repository** | `ProductRepositoryInterface`, `InvoiceRepositoryInterface` |
| **Observer** | `ProductObserver`, `InvoiceObserver`, `WarehouseStockObserver` |
| **Policy** | 20 policies pour l'autorisation par entité |
| **Resource** | 27 API Resources pour la transformation des réponses |
| **Form Request** | 25 classes de validation dédiées |
| **Global Scope** | `TenantScope` pour l'isolation automatique par `company_id` |

### Flux de création d'une facture

```
Requête → InvoiceRequest (validation)
        → InvoiceController@store
        → CreateInvoiceAction::execute(InvoiceDTO)
        → DB::transaction
            → generateInvoiceNumber()
            → Invoice::create()
            → InvoiceItem::create() (pour chaque item)
            → ProfitService::calculateItemProfit()
            → event(new InvoiceCreated)
        → DeductStockOnInvoiceCreated (listener)
            → StockMovement::create()
            → StockValuationService::recordStockOut()
```

---

## Base de données

### Tables principales (58 migrations)

**Core tenant**
- `companies` — Sociétés, lié à `plans`
- `users` — Utilisateurs, rattachés à `companies`

**Produits & Stock**
- `products`, `product_variants`, `categories`, `units`, `taxes`, `product_taxes`
- `warehouses`, `warehouse_stock`, `stock_movements`, `stock_transfers`, `stock_valuations`

**CRM**
- `customers`, `suppliers`

**Documents**
- `invoices` + `invoice_items` (soft deletes)
- `quotes` + `quote_items` (soft deletes)
- `delivery_notes` + `delivery_note_items` (soft deletes)
- `credit_notes` + `credit_note_items` (soft deletes)
- `purchase_orders` + `purchase_order_items` (soft deletes)

**Paiements & Abonnements**
- `payments`, `plans`, `subscriptions`, `subscription_items`

**Autres**
- `expenses`, `events`, `settings`, `currencies`, `exchange_rates`
- `webhook_endpoints`, `exports`
- `activity_log`, `notifications`, `personal_access_tokens`
- `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

### Relations clés

```
Company 1──* User
Company 1──* Product, Category, Unit, Tax, Warehouse, Customer, Supplier
Company 1──* Invoice, Quote, DeliveryNote, CreditNote, PurchaseOrder
Company 1──* Payment, Expense, Event, Setting, Export
Product  *──1 Category, Unit
Product  *──* Tax (via product_taxes)
Product  1──* ProductVariant, WarehouseStock, StockMovement
Invoice  1──* InvoiceItem, Payment
Invoice  *──1 Customer
Quote    1──* QuoteItem
DeliveryNote 1──* DeliveryNoteItem
PurchaseOrder 1──* PurchaseOrderItem
CreditNote 1──* CreditNoteItem
```

---

## API

**Base URL :** `/api/v1/`

### Endpoints publics

| Méthode | URI | Description | Throttle |
|---|---|---|---|
| POST | `/auth/register` | Inscription société + utilisateur | 5/60s |
| POST | `/auth/login` | Connexion | 10/60s |
| POST | `/auth/forgot-password` | Demande de réinitialisation de mot de passe | 5/60s |
| POST | `/auth/reset-password` | Réinitialisation du mot de passe | 5/60s |
| POST | `/stripe/webhook` | Webhook Stripe | - |

### Endpoints authentifiés

Tous nécessitent `Authorization: Bearer {token}` et optionnellement `X-Tenant-Slug: {slug}`.

**Profil**
- `GET /auth/me`, `POST /auth/ping`, `PUT /auth/locale`, `POST /auth/logout`

**CRUD complets**
- `products` (+ variantes), `categories`, `warehouses`, `customers`, `suppliers`
- `taxes`, `units`, `users`, `events`, `webhook-endpoints`

**Factures**
- CRUD, `PATCH /invoices/{id}/mark-as-sent`, `PATCH /invoices/{id}/mark-as-cancelled`
- `GET /invoices/{id}/pdf`, `GET /invoices/{id}/pdf/preview`, `POST /invoices/{id}/pdf/async`

**Devis**
- CRUD, mark-as-sent/accepted/rejected, convert-to-invoice, PDF

**Bons de livraison**
- CRUD, mark-as-shipped/delivered/returned, PDF

**Avoirs**
- CRUD, `POST /credit-notes/{id}/validate`

**Bons d'achat**
- CRUD, `POST /purchase-orders/{id}/receive`

**Paiements**
- `GET /payments`, `POST /payments`, `GET /payments/{id}`

**Stock**
- `GET/POST /stock-movements`
- `GET/POST /stock-transfers`
- `GET /stock-valuation/summary`, `/products/{id}`, `/products/{id}/average-cost`

**Dashboard, Analytics, Profits**
- `GET /dashboard`
- `GET /analytics/*` (overview, revenue-growth, top-products, top-customers, recent-invoices, expenses-by-category)
- `GET /profits/*` (summary, by-product, by-customer, by-invoice)
- `POST /profits/recalculate/{invoice}`

**Abonnements**
- `GET /subscriptions/plans`, `/current`
- `POST /subscribe`, `/cancel`, `/resume`, `/swap/{plan}`
- `GET /invoice-portal`

**Société**
- `GET/PUT /company`, `POST /company/logo`, `PUT /company/plan`

**Rôles & Permissions**
- `GET /roles`, `GET /permissions`

**Autres**
- `GET /activity-logs`
- `GET/POST /settings`, `POST /settings/test-mail`
- `GET/POST /exports/*`

---

## Authentification

### Mécanisme

- **Sanctum** : tokens API générés via `$user->createToken('auth_token')->plainTextToken`
- Le token est envoyé dans l'en-tête `Authorization: Bearer {token}`
- La déconnexion révoque le token courant

### Résolution du tenant

L'ordre de résolution du contexte tenant :

1. Utilisateur authentifié → sa société (`$user->company`)
2. En-tête HTTP `X-Tenant-Slug`
3. Sous-domaine
4. Paramètre de route `{company_slug}`

### Rôles disponibles

| Rôle | Accès |
|---|---|
| **admin** | Toutes les permissions (51) |
| **manager** | Opérationnel (sauf suppression facture, etc.) |
| **accountant** | Opérations financières uniquement |
| **warehouse_manager** | Stock et produits |
| **sales** | Clients, factures, devis |
| **employee** | Lecture seule |

### Plans & Quotas

| Plan | Prix | Quotas |
|---|---|---|
| Free | Gratuit | Limité |
| Starter | 15 000 XOF/mois | Standards |
| Pro | 35 000 XOF/mois | Élevés |
| Enterprise | 100 000 XOF/mois | Illimités |

Les quotas limitent : utilisateurs, produits, factures, entrepôts.

---

## Multi-tenant

L'isolation multi-tenant est assurée à plusieurs niveaux :

1. **BelongsToTenant** (trait) — Ajoute automatiquement `company_id` à la création et applique le `TenantScope`
2. **TenantScope** (global scope) — Filtre toutes les requêtes Eloquent par `company_id` via `TenantContext`
3. **TenantContext** (singleton) — Stocke la société active pour la durée de la requête
4. **TenantMiddleware** — Résout et injecte le contexte tenant
5. **CheckSubscriptionQuota** — Vérifie les limites du plan avant création

---

## Installation

### Prérequis

- PHP ^8.2
- Composer
- MySQL 8+
- Node.js 20+
- Stripe Account (optionnel pour les paiements)

### Étapes

```bash
# 1. Cloner le projet
git clone <url> facturation-stock
cd facturation-stock

# 2. Configuration
cp .env.example .env
# Éditer .env : DB_DATABASE, DB_USERNAME, DB_PASSWORD, STRIPE_KEY, STRIPE_SECRET

# 3. Dépendances PHP
composer install

# 4. Dépendances frontend
npm install
npm run build

# 5. Clé d'application
php artisan key:generate

# 6. Base de données
php artisan migrate --seed

# 7. Stockage
php artisan storage:link
```

### Lancement

```bash
# Terminal 1 : serveur Laravel
php artisan serve

# Terminal 2 : build frontend (watch)
npm run dev
```

### Variables d'environnement importantes

| Variable | Description |
|---|---|
| `APP_URL` | URL de l'application |
| `DB_CONNECTION` | `mysql` (production) / `sqlite` (tests) |
| `SANCTUM_TOKEN_PREFIX` | Préfixe des tokens |
| `SANCTUM_TOKEN_EXPIRATION` | Durée de validité (minutes) |
| `STRIPE_KEY` | Clé publique Stripe |
| `STRIPE_SECRET` | Clé secrète Stripe |
| `STRIPE_WEBHOOK_SECRET` | Secret du webhook Stripe |

---

## Développement

### Convention de code

- **PHP** : Laravel Pint (PSR-12)
- **JavaScript** : ESLint + Prettier
- **Tests** : Pest PHP
- **Strict types** : `declare(strict_types=1)` sur tous les fichiers PHP

### Commandes utiles

```bash
# Lint PHP
./vendor/bin/pint

# Lint JS
npm run lint

# Build frontend
npm run build

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Queue asynchrone
php artisan queue:work

# Créer un contrôleur
php artisan make:controller Api/v1/MonController

# Créer une migration
php artisan make:migration add_xx_to_yy_table
```

---

## Tests

### Lancement

```bash
# Tous les tests
php artisan test

# Avec Pest directement
vendor/bin/pest

# Format compact
vendor/bin/pest --compact

# Filtre par groupe
vendor/bin/pest --group=tenant
```

### Couverture

- **Tests Feature** (18 fichiers) : Auth, Tenant Isolation, Invoices, Products, Customers, Dashboard, Delivery Notes, Payments, Quotes, Subscriptions, Company Settings
- **Tests Unit** (4 fichiers) : Money, TenantContext, Models (Customer, Invoice, Product, User)

La base de données de test utilise SQLite en mémoire (`.env.testing` ou `phpunit.xml`).

---

## Structure du projet

```
facturation-stock/
├── app/
│   ├── Actions/                  # Logique métier
│   │   ├── Auth/                 # RegisterCompany, Login
│   │   ├── Invoice/              # CreateInvoice, UpdateInvoiceStatus
│   │   ├── Quote/                # CreateQuote, UpdateQuote, UpdateQuoteStatus, ConvertQuoteToInvoice
│   │   ├── Category/             # CreateCategory, UpdateCategory
│   │   ├── Customer/             # CreateCustomer, UpdateCustomer
│   │   ├── DeliveryNote/         # CreateDeliveryNote, UpdateDeliveryNoteStatus
│   │   ├── Payment/              # CreatePayment
│   │   ├── Product/              # CreateProduct, CreateVariant, UpdateVariant
│   │   ├── Purchases/            # CreatePurchaseOrder, MarkPurchaseOrderAsReceived
│   │   ├── Sales/                # CreateCreditNote, ValidateCreditNote
│   │   ├── Stock/                # CreateTransfer
│   │   ├── Warehouse/            # CreateWarehouse, UpdateWarehouse
│   │   └── Webhook/              # CreateWebhookEndpoint, UpdateWebhookEndpoint
│   ├── DTOs/                     # Data Transfer Objects (16)
│   ├── Enums/                    # InvoiceStatus, QuoteStatus, DeliveryNoteStatus, etc.
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   └── v1/           # 35 contrôleurs API
│   │   │   └── Controller.php
│   │   ├── Requests/             # 25 classes de validation
│   │   └── Resources/            # 27 API Resources
│   ├── Models/                   # 34 modèles Eloquent
│   ├── Services/                 # Analytics, Profit, StockValuation, PDF
│   ├── Support/                  # Money (value object), TenantContext (singleton)
│   └── Traits/                   # BelongsToTenant
├── bootstrap/
│   └── app.php                   # Configuration middleware
├── config/                       # Configuration Laravel
├── database/
│   ├── factories/                # Model factories
│   ├── migrations/               # 58 migrations
│   └── seeders/                  # PermissionSeeder, etc.
├── public/                       # Point d'entrée (index.php)
├── resources/
│   ├── js/                       # SPA Vue 3 + Router + i18n
│   │   ├── App.vue
│   │   ├── app.js
│   │   ├── router.js             # 56 routes frontend
│   │   ├── composables/          # useTheme, useToast
│   │   └── components/           # AdminLayout, LanguageSwitcher, Charts, etc.
│   └── views/                    # Blades (app, PDF templates)
├── routes/
│   ├── api.php                   # Routes API (/api/v1/)
│   ├── web.php                   # Catch-all SPA
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/                  # Tests fonctionnels
│   │   ├── Api/v1/               # Auth, Invoice, Product, TenantIsolation
│   │   └── Auth/                 # Login, Registration
│   ├── Unit/                     # Money, TenantContext, Models
│   └── Pest.php                  # Helpers globaux (actingAsUser, createProduct, createCustomer)
├── .env.example
├── composer.json
├── package.json
├── phpunit.xml
└── vite.config.js
```

---

## Licence

Projet privé — Tous droits réservés.
