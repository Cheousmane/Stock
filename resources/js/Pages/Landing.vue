<template>
  <div class="landing-root" :class="{ dark: isDark }">
    <!-- ── Animated Mesh Background ── -->
    <div class="mesh-bg" aria-hidden="true">
      <div class="mesh-orb orb-1"></div>
      <div class="mesh-orb orb-2"></div>
      <div class="mesh-orb orb-3"></div>
      <canvas ref="noiseCanvas" class="noise-canvas"></canvas>
    </div>

    <!-- ── Navigation ── -->
    <nav class="nav" :class="{ 'nav-scrolled': scrolled }">
      <div class="nav-inner">
        <!-- Logo -->
        <router-link to="/" class="nav-logo group">
          <AppLogo :size="36" />
          <span class="nav-logo-text">SIDIBE CORPORATE</span>
        </router-link>

        <!-- Desktop links -->
        <div class="nav-links">
          <a href="#features" class="nav-link" @click.prevent="scrollTo('features')">Fonctionnalités</a>
          <a href="#how" class="nav-link" @click.prevent="scrollTo('how')">Comment ça marche</a>
          <a href="#pricing" class="nav-link" @click.prevent="scrollTo('pricing')">Tarifs</a>
        </div>

        <!-- Actions -->
        <div class="nav-actions">
          <button class="theme-btn" @click="isDark = !isDark" :aria-label="isDark ? 'Mode clair' : 'Mode sombre'">
            <svg v-if="!isDark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
          </button>
          <router-link to="/login" class="nav-login">Connexion</router-link>
          <router-link to="/register" class="btn-primary nav-cta">
            Démarrer gratuitement
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
          </router-link>
        </div>

        <!-- Mobile hamburger -->
        <button class="nav-hamburger" @click="mobileOpen = !mobileOpen">
          <span></span><span></span><span></span>
        </button>
      </div>

      <!-- Mobile menu -->
      <div class="mobile-menu" :class="{ 'mobile-open': mobileOpen }">
        <a href="#features" class="mobile-link" @click="mobileOpen = false; scrollTo('features')">Fonctionnalités</a>
        <a href="#how" class="mobile-link" @click="mobileOpen = false; scrollTo('how')">Comment ça marche</a>
        <a href="#pricing" class="mobile-link" @click="mobileOpen = false; scrollTo('pricing')">Tarifs</a>
        <div class="mobile-actions">
          <router-link to="/login" class="btn-ghost w-full text-center">Connexion</router-link>
          <router-link to="/register" class="btn-primary w-full text-center">Démarrer gratuitement</router-link>
        </div>
      </div>
    </nav>

    <!-- ── HERO ── -->
    <section class="hero">
      <div class="hero-inner">
        <!-- Badge -->
        <!-- <div class="hero-badge" ref="heroBadge">
          <span class="badge-dot"></span>
        </div> -->

        <!-- Heading -->
        <h1 class="hero-h1" ref="heroH1">
          <span class="hero-line">Gérez votre</span>
          <span class="hero-line hero-gradient">entreprise</span>
          <span class="hero-line">avec précision</span>
        </h1>

        <p class="hero-sub" ref="heroSub">
          Facturation intelligente, gestion de stock en temps réel, CRM clients et tableau de bord analytique.
          Tout ce dont votre PME a besoin, dans une seule plateforme.
        </p>

        <!-- CTA Buttons -->
        <div class="hero-ctas" ref="heroCtas">
          <router-link to="/register" class="btn-primary btn-xl hero-cta-main">
            <svg viewBox="0 0 20 20" fill="currentColor" class="btn-icon"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/></svg>
            Créer votre entreprise
          </router-link>
          <router-link to="/login" class="btn-ghost btn-xl">
            <svg viewBox="0 0 20 20" fill="currentColor" class="btn-icon"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
            Voir une démo
          </router-link>
        </div>

        <!-- Trust signals -->
        <div class="hero-trust" ref="heroTrust">
          <div class="trust-avatars">
            <span class="trust-av" style="background: linear-gradient(135deg,#f59e0b,#ef4444)">M</span>
            <span class="trust-av" style="background: linear-gradient(135deg,#3b82f6,#8b5cf6)">A</span>
            <span class="trust-av" style="background: linear-gradient(135deg,#10b981,#059669)">S</span>
            <span class="trust-av" style="background: linear-gradient(135deg,#ec4899,#f43f5e)">D</span>
          </div>
          <span class="trust-text"><strong>+500 entreprises</strong> font confiance à SIDIBE CORPORATE</span>
        </div>

        <!-- ── Dashboard Mockup ── -->
        <div class="mockup-wrap" ref="mockupRef">
          <!-- glow behind -->
          <div class="mockup-glow"></div>

          <!-- Browser frame -->
          <div class="mockup-browser">
            <div class="browser-bar">
              <span class="dot dot-red"></span>
              <span class="dot dot-yellow"></span>
              <span class="dot dot-green"></span>
              <div class="browser-url">
                <svg viewBox="0 0 16 16" fill="currentColor" class="url-lock"><path fill-rule="evenodd" d="M8 1a3.5 3.5 0 00-3.5 3.5V7A1.5 1.5 0 003 8.5v4A1.5 1.5 0 004.5 14h7a1.5 1.5 0 001.5-1.5v-4A1.5 1.5 0 0011.5 7V4.5A3.5 3.5 0 008 1zm2 6V4.5a2 2 0 10-4 0V7h4z" clip-rule="evenodd"/></svg>
                <span>app.sidibecorporate.com/dashboard</span>
              </div>
            </div>

            <!-- Dashboard content -->
            <div class="dash-content">
              <!-- Sidebar mini -->
              <div class="dash-sidebar">
                <div class="ds-logo"></div>
                <div v-for="n in 6" :key="n" class="ds-nav-item" :class="{ active: n === 1 }"></div>
                <div class="ds-spacer"></div>
                <div class="ds-avatar"></div>
              </div>

              <!-- Main area -->
              <div class="dash-main">
                <!-- Topbar -->
                <div class="dash-topbar">
                  <div class="dt-title"></div>
                  <div class="dt-actions">
                    <div class="dt-btn"></div>
                    <div class="dt-avatar"></div>
                  </div>
                </div>

                <!-- KPI cards -->
                <div class="kpi-grid">
                  <div class="kpi-card kpi-green">
                    <div class="kpi-icon"></div>
                    <div class="kpi-data">
                      <div class="kpi-value">12.4M</div>
                      <div class="kpi-label">Chiffre d'affaires</div>
                      <div class="kpi-trend up">▲ +18%</div>
                    </div>
                  </div>
                  <div class="kpi-card kpi-blue">
                    <div class="kpi-icon"></div>
                    <div class="kpi-data">
                      <div class="kpi-value">248</div>
                      <div class="kpi-label">Factures</div>
                      <div class="kpi-trend up">▲ +7%</div>
                    </div>
                  </div>
                  <div class="kpi-card kpi-amber">
                    <div class="kpi-icon"></div>
                    <div class="kpi-data">
                      <div class="kpi-value">1 842</div>
                      <div class="kpi-label">Stock produits</div>
                      <div class="kpi-trend down">▼ -3%</div>
                    </div>
                  </div>
                  <div class="kpi-card kpi-violet">
                    <div class="kpi-icon"></div>
                    <div class="kpi-data">
                      <div class="kpi-value">96</div>
                      <div class="kpi-label">Clients actifs</div>
                      <div class="kpi-trend up">▲ +12%</div>
                    </div>
                  </div>
                </div>

                <!-- Chart area -->
                <div class="chart-area">
                  <div class="chart-header">
                    <div class="chart-title-bar"></div>
                    <div class="chart-filters">
                      <div class="cf-pill active">7j</div>
                      <div class="cf-pill">30j</div>
                      <div class="cf-pill">90j</div>
                    </div>
                  </div>
                  <div class="chart-bars">
                    <div v-for="(h, i) in bars" :key="i" class="bar-wrap">
                      <div class="bar" :style="{ height: h + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Floating cards -->
          <div class="float-card fc-invoice">
            <div class="fc-icon fc-icon-green">
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
            </div>
            <div class="fc-info">
              <p class="fc-title">Facture payée</p>
              <p class="fc-sub">FAC-2024-248 · 125 000 FCFA</p>
            </div>
          </div>

          <div class="float-card fc-alert">
            <div class="fc-icon fc-icon-amber">
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            </div>
            <div class="fc-info">
              <p class="fc-title">Stock faible</p>
              <p class="fc-sub">Papier A4 · 3 unités restantes</p>
            </div>
          </div>

          <div class="float-card fc-stats">
            <p class="fc-stats-label">Ventes aujourd'hui</p>
            <p class="fc-stats-value">850 000 <span>FCFA</span></p>
            <div class="fc-sparkline">
              <svg viewBox="0 0 80 30" fill="none" stroke="currentColor">
                <polyline points="0,25 10,18 20,22 30,10 40,15 50,6 60,12 70,4 80,8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Stats Bar ── -->
    <section class="stats-bar" ref="statsBarRef">
      <div class="stats-inner">
        <div v-for="stat in stats" :key="stat.label" class="stat-item">
          <span class="stat-num">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </div>
    </section>

    <!-- ── Features Bento ── -->
    <section class="section features-section" id="features" ref="featuresRef">
      <div class="section-inner">
        <div class="section-label">Fonctionnalités</div>
        <h2 class="section-title">Tout ce dont vous avez besoin,<br><em>rien de superflu</em></h2>
        <p class="section-sub">Une suite intégrée pensée pour les PME africaines. Rapide, fiable et intuitive.</p>

        <div class="bento-grid">
          <!-- Large card -->
          <div class="bento-card bento-large" ref="bento0">
            <div class="bento-icon-wrap green">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
            </div>
            <h3 class="bento-title">Facturation complète</h3>
            <p class="bento-desc">Devis → Bon de livraison → Facture → Paiement en quelques clics. Taxes multiples, remises, acomptes et PDF professionnel.</p>
            <div class="bento-preview invoice-preview">
              <div class="inv-row" v-for="n in 3" :key="n">
                <div class="inv-col inv-num"></div>
                <div class="inv-col inv-client"></div>
                <div class="inv-col inv-amount"></div>
                <div class="inv-col inv-badge" :class="['badge-green','badge-amber','badge-blue'][n-1]"></div>
              </div>
            </div>
          </div>

          <!-- Stock -->
          <div class="bento-card bento-medium" ref="bento1">
            <div class="bento-icon-wrap blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
            </div>
            <h3 class="bento-title">Gestion de stock</h3>
            <p class="bento-desc">Alertes de seuil, traçabilité des mouvements, multi-entrepôts. Ne manquez plus jamais d'un article.</p>
            <div class="stock-meter">
              <div v-for="(item, i) in stockItems" :key="i" class="stock-row">
                <span class="stock-name">{{ item.name }}</span>
                <div class="stock-bar-wrap"><div class="stock-bar-fill" :style="{ width: item.pct + '%', background: item.color }"></div></div>
                <span class="stock-qty">{{ item.qty }}</span>
              </div>
            </div>
          </div>

          <!-- Analytics -->
          <div class="bento-card bento-medium" ref="bento2">
            <div class="bento-icon-wrap violet">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            </div>
            <h3 class="bento-title">Analytics temps réel</h3>
            <p class="bento-desc">CA, marges, top produits, clients les plus rentables. Décidez avec des données, pas des intuitions.</p>
            <div class="mini-chart">
              <svg viewBox="0 0 120 50" fill="none" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.4"/>
                    <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <path d="M0 40 L10 32 L20 36 L30 20 L40 25 L50 12 L60 18 L70 8 L80 14 L90 5 L100 10 L110 4 L120 8 L120 50 L0 50 Z" fill="url(#chartGrad)"/>
                <path d="M0 40 L10 32 L20 36 L30 20 L40 25 L50 12 L60 18 L70 8 L80 14 L90 5 L100 10 L110 4 L120 8" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </div>

          <!-- CRM -->
          <div class="bento-card bento-small" ref="bento3">
            <div class="bento-icon-wrap rose">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <h3 class="bento-title">CRM Clients</h3>
            <p class="bento-desc">Historique complet, soldes, crédits et documents par client.</p>
          </div>

          <!-- POS -->
          <div class="bento-card bento-small" ref="bento4">
            <div class="bento-icon-wrap amber">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/></svg>
            </div>
            <h3 class="bento-title">Point de Vente</h3>
            <p class="bento-desc">Caisse rapide, paiement espèces / mobile money, tickets imprimables.</p>
          </div>

          <!-- Multi-tenant -->
          <div class="bento-card bento-small" ref="bento5">
            <div class="bento-icon-wrap teal">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            </div>
            <h3 class="bento-title">Multi-tenant sécurisé</h3>
            <p class="bento-desc">Isolation stricte des données par entreprise. RGPD-ready.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── How it Works ── -->
    <section class="section how-section" id="how" ref="howRef">
      <div class="section-inner">
        <div class="section-label">Démarrage rapide</div>
        <h2 class="section-title">Opérationnel en <em>moins de 5 minutes</em></h2>
        <p class="section-sub">Aucune installation, aucune configuration complexe. Juste créer votre compte et commencer.</p>

        <div class="steps-grid">
          <div class="step" v-for="(step, i) in steps" :key="i" ref="stepRefs">
            <div class="step-num">{{ String(i + 1).padStart(2, '0') }}</div>
            <div class="step-icon-wrap" :class="step.color">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="step.icon"></svg>
            </div>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
            <div class="step-connector" v-if="i < steps.length - 1"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Pricing / CTA ── -->
    <section class="section pricing-section" id="pricing" ref="pricingRef">
      <div class="pricing-bg-blob"></div>
      <div class="section-inner">
        <div class="section-label">Tarification simple</div>
        <h2 class="section-title">Un seul plan, <em>tout inclus</em></h2>
        <p class="section-sub">Pas de surprise, pas de frais cachés. Annulez à tout moment.</p>

        <div class="pricing-card-wrap">
          <div class="pricing-card">
            <div class="pricing-badge">Le plus populaire ✦</div>
            <div class="pricing-header">
              <h3 class="pricing-name">Plan Professionnel</h3>
              <div class="pricing-amount">
                <span class="pricing-currency">FCFA</span>
                <span class="pricing-price">15 000</span>
                <span class="pricing-period">/ mois</span>
              </div>
              <p class="pricing-sub-desc">Par entreprise · Utilisateurs illimités</p>
            </div>
            <ul class="pricing-features">
              <li v-for="f in pricingFeatures" :key="f">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                {{ f }}
              </li>
            </ul>
            <router-link to="/register" class="btn-primary btn-xl pricing-cta">
              Démarrer l'essai gratuit — 14 jours
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
            </router-link>
            <p class="pricing-note">Aucune carte bancaire requise</p>
          </div>

          <div class="pricing-features-extra">
            <div v-for="extra in pricingExtras" :key="extra.title" class="extra-item">
              <div class="extra-icon-wrap" :class="extra.color">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" v-html="extra.icon"></svg>
              </div>
              <div>
                <p class="extra-title">{{ extra.title }}</p>
                <p class="extra-desc">{{ extra.desc }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Final CTA ── -->
    <section class="final-cta-section" ref="finalCtaRef">
      <div class="final-cta-inner">
        <div class="final-cta-badge">Rejoignez la communauté</div>
        <h2 class="final-cta-title">Prêt à transformer<br><em>votre gestion ?</em></h2>
        <p class="final-cta-sub">Créez votre compte en 30 secondes. Aucune carte bancaire. Annulez quand vous voulez.</p>
        <div class="final-cta-buttons">
          <router-link to="/register" class="btn-primary btn-xl">
            Commencer gratuitement
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
          </router-link>
          <router-link to="/login" class="btn-ghost btn-xl">Me connecter</router-link>
        </div>
      </div>
    </section>

    <!-- ── Footer ── -->
    <footer class="landing-footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo group">
            <AppLogo :size="36" />
            <span class="nav-logo-text">SIDIBE CORPORATE</span>
          </div>
          <p class="footer-tagline">La plateforme de gestion d'entreprise conçue pour vous.</p>
        </div>
        <div class="footer-links">
          <div class="footer-col">
            <p class="footer-col-title">Produit</p>
            <a href="#features" @click.prevent="scrollTo('features')">Fonctionnalités</a>
            <a href="#pricing" @click.prevent="scrollTo('pricing')">Tarifs</a>
            <router-link to="/login">Connexion</router-link>
            <router-link to="/register">Inscription</router-link>
          </div>
          <div class="footer-col">
            <p class="footer-col-title">Légal</p>
            <router-link to="/confidentialite">Confidentialité</router-link>
            <router-link to="/conditions">Conditions d'utilisation</router-link>
            <router-link to="/cookies">Cookies</router-link>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 SIDIBE CORPORATE. Tous droits réservés.</p>
        <div class="footer-trust">
          <span>🔒 SSL</span>
          <span>🇸🇳 Made in Africa</span>
          <span>⚡ 99.9% uptime</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import AppLogo from '../Components/AppLogo.vue';

// ── Theme ──
const isDark = ref(false);

// ── Scroll state ──
const scrolled = ref(false);
const mobileOpen = ref(false);

// ── Refs ──
const heroBadge = ref(null);
const heroH1 = ref(null);
const heroSub = ref(null);
const heroCtas = ref(null);
const heroTrust = ref(null);
const mockupRef = ref(null);
const statsBarRef = ref(null);
const featuresRef = ref(null);
const howRef = ref(null);
const pricingRef = ref(null);
const finalCtaRef = ref(null);
const stepRefs = ref([]);
const noiseCanvas = ref(null);

// ── Chart bars ──
const bars = [45, 65, 40, 80, 55, 90, 70, 85, 60, 95, 75, 88];

// ── Stock items ──
const stockItems = [
  { name: 'Papier A4', pct: 15, qty: '3u', color: '#ef4444' },
  { name: 'Stylos', pct: 60, qty: '45u', color: '#10b981' },
  { name: 'Cartouches', pct: 40, qty: '12u', color: '#f59e0b' },
  { name: 'Classeurs', pct: 80, qty: '24u', color: '#3b82f6' },
];

// ── Stats ──
const stats = [
  { value: '+500', label: 'Entreprises actives' },
  { value: '250k+', label: 'Factures générées' },
  { value: '99.9%', label: 'Disponibilité SLA' },
  { value: '< 30s', label: 'Temps de démarrage' },
];

// ── Steps ──
const steps = [
  {
    title: 'Créez votre compte',
    desc: 'Inscrivez-vous en 30 secondes. Renseignez le nom de votre entreprise et c\'est parti.',
    color: 'step-green',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>',
  },
  {
    title: 'Configurez votre catalogue',
    desc: 'Ajoutez vos produits, services, clients et fournisseurs. Import CSV disponible.',
    color: 'step-blue',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>',
  },
  {
    title: 'Émettez vos premières factures',
    desc: 'Créez un devis, convertissez-le en facture et envoyez le PDF à votre client.',
    color: 'step-violet',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>',
  },
  {
    title: 'Analysez et optimisez',
    desc: 'Consultez vos tableaux de bord, identifiez vos meilleurs produits, suivez vos flux.',
    color: 'step-amber',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>',
  },
];

// ── Pricing ──
const pricingFeatures = [
  'Facturation & devis illimités',
  'Gestion stock multi-entrepôts',
  'Point de vente (POS)',
  'CRM clients & fournisseurs',
  'Tableau de bord analytique',
  'Notes de crédit & bons de livraison',
  'Multi-utilisateurs illimités',
  'Export PDF & rapports',
  'Support prioritaire',
];

const pricingExtras = [
  {
    title: 'Essai gratuit 14 jours',
    desc: 'Toutes fonctionnalités incluses, aucune carte requise.',
    color: 'step-green',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
  },
  {
    title: 'Support 7j/7',
    desc: 'Assistance par chat et email, réponse sous 4h.',
    color: 'step-blue',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>',
  },
  {
    title: 'Données sécurisées',
    desc: 'Chiffrement AES-256, sauvegardes quotidiennes.',
    color: 'step-violet',
    icon: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
  },
];

// ── Scroll helper ──
function scrollTo(id) {
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
}

// ── Intersection Observer ──
let observers = [];
function observe(el, delay = 0) {
  if (!el) return;
  el.style.opacity = '0';
  el.style.transform = 'translateY(32px)';
  el.style.transition = `opacity 0.7s cubic-bezier(0.16,1,0.3,1) ${delay}ms, transform 0.7s cubic-bezier(0.16,1,0.3,1) ${delay}ms`;
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.style.opacity = '1';
        e.target.style.transform = 'none';
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
  obs.observe(el);
  observers.push(obs);
}

onMounted(() => {
  // Scroll listener
  const onScroll = () => { scrolled.value = window.scrollY > 20; };
  window.addEventListener('scroll', onScroll, { passive: true });

  // Hero entrance animations (delay chain)
  [heroBadge.value, heroH1.value, heroSub.value, heroCtas.value, heroTrust.value, mockupRef.value].forEach((el, i) => {
    if (!el) return;
    el.style.opacity = '0';
    el.style.transform = 'translateY(28px)';
    el.style.transition = `opacity 0.8s cubic-bezier(0.16,1,0.3,1) ${200 + i * 120}ms, transform 0.8s cubic-bezier(0.16,1,0.3,1) ${200 + i * 120}ms`;
    requestAnimationFrame(() => requestAnimationFrame(() => {
      if (el) { el.style.opacity = '1'; el.style.transform = 'none'; }
    }));
  });

  // Scroll-triggered sections
  [statsBarRef.value, featuresRef.value, howRef.value, pricingRef.value, finalCtaRef.value].forEach((el, i) => observe(el, i * 60));

  // Noise canvas
  if (noiseCanvas.value) {
    const canvas = noiseCanvas.value;
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    const img = ctx.createImageData(canvas.width, canvas.height);
    for (let i = 0; i < img.data.length; i += 4) {
      const v = Math.random() * 255;
      img.data[i] = v; img.data[i+1] = v; img.data[i+2] = v;
      img.data[i+3] = 8;
    }
    ctx.putImageData(img, 0, 0);
  }

  // Store scroll handler for cleanup
  observers.push({ disconnect: () => window.removeEventListener('scroll', onScroll) });
});

onUnmounted(() => observers.forEach(o => o.disconnect()));
</script>

<style scoped>
/* ── Google Font ── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

/* ── Root / tokens ── */
.landing-root {
  --bg: #fafafa;
  --bg2: #ffffff;
  --border: rgba(0,0,0,0.07);
  --text: #0f0f0f;
  --text2: #4b5563;
  --text3: #9ca3af;
  --primary: #059669;
  --primary-h: #047857;
  --primary-glow: rgba(5,150,105,0.35);
  --primary-light: rgba(5,150,105,0.08);
  --card-bg: rgba(255,255,255,0.85);
  --card-border: rgba(0,0,0,0.07);
  --nav-bg: rgba(250,250,250,0.75);
  --shadow-card: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
  --shadow-lg: 0 20px 60px rgba(0,0,0,0.1), 0 4px 16px rgba(0,0,0,0.05);
  --shadow-glow: 0 0 80px rgba(5,150,105,0.2);
  font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
  overflow-x: hidden;
  min-height: 100vh;
}

.landing-root.dark {
  --bg: #0a0a0a;
  --bg2: #111111;
  --border: rgba(255,255,255,0.07);
  --text: #f0f0f0;
  --text2: #9ca3af;
  --text3: #4b5563;
  --primary: #10b981;
  --primary-h: #34d399;
  --primary-glow: rgba(16,185,129,0.4);
  --primary-light: rgba(16,185,129,0.1);
  --card-bg: rgba(17,17,17,0.9);
  --card-border: rgba(255,255,255,0.07);
  --nav-bg: rgba(10,10,10,0.8);
  --shadow-card: 0 1px 3px rgba(0,0,0,0.3), 0 4px 16px rgba(0,0,0,0.2);
  --shadow-lg: 0 20px 60px rgba(0,0,0,0.5), 0 4px 16px rgba(0,0,0,0.3);
  --shadow-glow: 0 0 100px rgba(16,185,129,0.25);
}

/* ── Mesh Background ── */
.mesh-bg {
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  overflow: hidden;
}
.mesh-orb {
  position: absolute; border-radius: 50%;
  filter: blur(80px); opacity: 0.55;
  animation: orbDrift 20s ease-in-out infinite alternate;
}
.orb-1 { width: 600px; height: 600px; top: -200px; left: -150px; background: radial-gradient(circle, rgba(5,150,105,0.25), transparent 70%); animation-delay: 0s; }
.orb-2 { width: 500px; height: 500px; top: 30%; right: -150px; background: radial-gradient(circle, rgba(16,185,129,0.18), transparent 70%); animation-delay: -7s; }
.orb-3 { width: 400px; height: 400px; bottom: -100px; left: 30%; background: radial-gradient(circle, rgba(52,211,153,0.15), transparent 70%); animation-delay: -14s; }
.dark .orb-1 { opacity: 0.7; }
.dark .orb-2 { opacity: 0.6; }
.dark .orb-3 { opacity: 0.5; }
@keyframes orbDrift {
  0% { transform: translate(0, 0) scale(1); }
  100% { transform: translate(40px, 40px) scale(1.08); }
}
.noise-canvas {
  position: absolute; inset: 0; width: 100%; height: 100%;
  opacity: 0.5; mix-blend-mode: overlay;
}

/* ── Nav ── */
.nav {
  position: fixed; top: 0; left: 0; right: 0; z-index: 100;
  background: transparent;
  transition: background 0.4s, backdrop-filter 0.4s, border-color 0.4s, box-shadow 0.4s;
  border-bottom: 1px solid transparent;
}
.nav-scrolled {
  background: var(--nav-bg);
  backdrop-filter: blur(20px) saturate(180%);
  border-bottom-color: var(--border);
  box-shadow: 0 1px 0 var(--border), 0 4px 16px rgba(0,0,0,0.04);
}
.nav-inner {
  max-width: 1200px; margin: 0 auto;
  padding: 0 24px; height: 64px;
  display: flex; align-items: center; gap: 32px;
}
.nav-logo {
  display: flex; align-items: center; gap: 10px;
  text-decoration: none; flex-shrink: 0;
}
.nav-logo-icon {
  width: 34px; height: 34px; border-radius: 9px;
  background: linear-gradient(135deg, #10b981, #059669);
  display: flex; align-items: center; justify-content: center;
  color: white; flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(5,150,105,0.35);
}
.nav-logo-icon svg { width: 18px; height: 18px; }
.nav-logo-text {
  font-size: 14px; font-weight: 800; letter-spacing: -0.02em;
  color: var(--text);
}
.nav-links {
  display: flex; gap: 4px; margin-left: auto;
}
.nav-link {
  padding: 6px 14px; border-radius: 8px; font-size: 14px;
  font-weight: 500; color: var(--text2); text-decoration: none;
  transition: color 0.2s, background 0.2s;
}
.nav-link:hover { color: var(--text); background: var(--primary-light); }
.nav-actions { display: flex; align-items: center; gap: 8px; }
.theme-btn {
  width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border);
  background: var(--card-bg); cursor: pointer; color: var(--text2);
  display: flex; align-items: center; justify-content: center;
  transition: all 0.2s;
}
.theme-btn svg { width: 16px; height: 16px; }
.theme-btn:hover { color: var(--text); border-color: var(--primary); }
.nav-login {
  padding: 7px 16px; font-size: 14px; font-weight: 500; border-radius: 8px;
  text-decoration: none; color: var(--text2); transition: color 0.2s;
}
.nav-login:hover { color: var(--text); }
.nav-hamburger {
  display: none; flex-direction: column; gap: 5px; padding: 6px;
  background: none; border: none; cursor: pointer; margin-left: auto;
}
.nav-hamburger span { display: block; width: 22px; height: 2px; border-radius: 2px; background: var(--text); transition: all 0.3s; }
.mobile-menu { display: none; }

/* ── Buttons ── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 600;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white; text-decoration: none; border: none; cursor: pointer;
  box-shadow: 0 4px 14px rgba(5,150,105,0.35);
  transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
  white-space: nowrap;
}
.btn-primary svg { width: 16px; height: 16px; flex-shrink: 0; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.5); background: linear-gradient(135deg, #34d399, #10b981); }
.btn-primary:active { transform: translateY(0); }
.btn-xl { padding: 14px 28px; font-size: 15px; border-radius: 12px; }
.btn-ghost {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px; border-radius: 10px; font-size: 14px; font-weight: 500;
  background: var(--card-bg); color: var(--text); text-decoration: none;
  border: 1px solid var(--border);
  transition: all 0.25s; white-space: nowrap;
}
.btn-ghost svg { width: 16px; height: 16px; }
.btn-ghost:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); transform: translateY(-1px); }
.btn-ghost.btn-xl { padding: 14px 28px; font-size: 15px; border-radius: 12px; }

/* ── Hero ── */
.hero {
  position: relative; z-index: 10;
  padding: 140px 24px 80px;
  text-align: center;
}
.hero-inner { max-width: 1000px; margin: 0 auto; }
.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 6px 14px 6px 10px; border-radius: 100px;
  background: var(--primary-light); border: 1px solid rgba(5,150,105,0.2);
  font-size: 13px; font-weight: 600; color: var(--primary);
  margin-bottom: 32px;
}
.badge-dot {
  width: 7px; height: 7px; border-radius: 50%;
  background: var(--primary); box-shadow: 0 0 8px var(--primary-glow);
  animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.6; transform: scale(1.3); }
}
.hero-h1 {
  font-size: clamp(42px, 7vw, 80px); font-weight: 900; line-height: 1.05;
  letter-spacing: -0.04em; margin: 0 0 24px; color: var(--text);
  display: flex; flex-direction: column;
}
.hero-line { display: block; }
.hero-gradient {
  background: linear-gradient(135deg, #10b981 20%, #059669 60%, #34d399 100%);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  background-clip: text;
}
.hero-sub {
  font-size: clamp(16px, 2vw, 19px); color: var(--text2); max-width: 540px;
  margin: 0 auto 36px; line-height: 1.65; font-weight: 400;
}
.hero-ctas { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 32px; }
.hero-cta-main { animation: ctaGlow 3s ease-in-out infinite; }
@keyframes ctaGlow {
  0%, 100% { box-shadow: 0 4px 14px rgba(5,150,105,0.35); }
  50% { box-shadow: 0 4px 40px rgba(5,150,105,0.6), 0 0 60px rgba(5,150,105,0.2); }
}

/* Trust */
.hero-trust { display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 64px; }
.trust-avatars { display: flex; }
.trust-av {
  width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--bg);
  display: flex; align-items: center; justify-content: center;
  font-size: 12px; font-weight: 700; color: white;
  margin-left: -8px;
}
.trust-av:first-child { margin-left: 0; }
.trust-text { font-size: 13px; color: var(--text2); }
.trust-text strong { color: var(--text); }

/* ── Dashboard Mockup ── */
.mockup-wrap {
  position: relative; max-width: 880px; margin: 0 auto;
}
.mockup-glow {
  position: absolute; inset: -60px;
  background: radial-gradient(ellipse 70% 50% at 50% 50%, var(--primary-glow), transparent 70%);
  z-index: 0; pointer-events: none;
  animation: glowPulse 4s ease-in-out infinite alternate;
}
@keyframes glowPulse {
  from { opacity: 0.6; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1.05); }
}
.mockup-browser {
  position: relative; z-index: 1; border-radius: 16px; overflow: hidden;
  border: 1px solid var(--card-border);
  box-shadow: var(--shadow-lg), 0 0 0 1px var(--border);
  background: var(--bg2);
  animation: mockupFloat 7s ease-in-out infinite;
}
@keyframes mockupFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  33% { transform: translateY(-8px) rotate(0.2deg); }
  66% { transform: translateY(-4px) rotate(-0.1deg); }
}

/* Browser bar */
.browser-bar {
  display: flex; align-items: center; gap: 12px; padding: 10px 16px;
  background: var(--bg); border-bottom: 1px solid var(--border);
}
.dot { width: 12px; height: 12px; border-radius: 50%; display: block; }
.dot-red { background: #ff5f57; }
.dot-yellow { background: #ffbd2e; }
.dot-green { background: #28ca41; }
.browser-url {
  flex: 1; max-width: 320px; height: 24px; border-radius: 6px;
  background: var(--card-bg); border: 1px solid var(--border);
  display: flex; align-items: center; gap: 6px; padding: 0 10px;
  margin: 0 auto;
}
.url-lock { width: 10px; height: 10px; color: var(--primary); }
.browser-url span { font-size: 11px; color: var(--text3); }

/* Dashboard layout */
.dash-content { display: flex; min-height: 340px; }
.dash-sidebar {
  width: 56px; background: var(--bg); border-right: 1px solid var(--border);
  padding: 12px 8px; display: flex; flex-direction: column; gap: 8px; align-items: center;
  flex-shrink: 0;
}
.ds-logo { width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg,#10b981,#059669); margin-bottom: 8px; }
.ds-nav-item { width: 32px; height: 7px; border-radius: 4px; background: var(--border); }
.ds-nav-item.active { background: var(--primary); }
.ds-spacer { flex: 1; }
.ds-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg,#6366f1,#8b5cf6); }

.dash-main { flex: 1; padding: 14px; display: flex; flex-direction: column; gap: 12px; overflow: hidden; }
.dash-topbar { display: flex; justify-content: space-between; align-items: center; }
.dt-title { width: 140px; height: 10px; border-radius: 5px; background: var(--border); }
.dt-actions { display: flex; gap: 8px; align-items: center; }
.dt-btn { width: 60px; height: 24px; border-radius: 6px; background: linear-gradient(135deg,#10b981,#059669); }
.dt-avatar { width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg,#f59e0b,#ef4444); }

/* KPI cards */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.kpi-card {
  border-radius: 10px; padding: 10px; border: 1px solid var(--border);
  display: flex; gap: 8px; align-items: flex-start;
  background: var(--card-bg);
}
.kpi-icon { width: 24px; height: 24px; border-radius: 6px; flex-shrink: 0; }
.kpi-green .kpi-icon { background: rgba(5,150,105,0.15); }
.kpi-blue .kpi-icon { background: rgba(59,130,246,0.15); }
.kpi-amber .kpi-icon { background: rgba(245,158,11,0.15); }
.kpi-violet .kpi-icon { background: rgba(139,92,246,0.15); }
.kpi-data { min-width: 0; }
.kpi-value { font-size: 13px; font-weight: 800; color: var(--text); letter-spacing: -0.02em; }
.kpi-label { font-size: 9px; color: var(--text3); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.kpi-trend { font-size: 9px; font-weight: 600; margin-top: 3px; }
.kpi-trend.up { color: #10b981; }
.kpi-trend.down { color: #ef4444; }

/* Chart */
.chart-area { flex: 1; border-radius: 10px; border: 1px solid var(--border); padding: 10px; background: var(--card-bg); }
.chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.chart-title-bar { width: 100px; height: 8px; border-radius: 4px; background: var(--border); }
.chart-filters { display: flex; gap: 4px; }
.cf-pill { padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: 600; color: var(--text3); background: var(--bg); border: 1px solid var(--border); }
.cf-pill.active { background: var(--primary); color: white; border-color: var(--primary); }
.chart-bars { display: flex; align-items: flex-end; gap: 4px; height: 80px; }
.bar-wrap { flex: 1; display: flex; align-items: flex-end; height: 100%; }
.bar {
  width: 100%; border-radius: 4px 4px 0 0;
  background: linear-gradient(to top, #059669, #34d399);
  transition: height 1s cubic-bezier(0.16,1,0.3,1);
  min-height: 4px;
}

/* Floating cards */
.float-card {
  position: absolute; border-radius: 14px; padding: 12px 16px;
  background: var(--card-bg); border: 1px solid var(--card-border);
  box-shadow: var(--shadow-card); backdrop-filter: blur(20px);
  display: flex; align-items: center; gap: 12px;
  z-index: 10;
}
.fc-invoice { bottom: -20px; left: -40px; animation: fc1Float 6s ease-in-out infinite; }
.fc-alert { top: 40px; right: -30px; animation: fc2Float 6s ease-in-out infinite -3s; }
.fc-stats { bottom: 60px; right: -30px; animation: fc3Float 7s ease-in-out infinite -1.5s; flex-direction: column; align-items: flex-start; }
@keyframes fc1Float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
@keyframes fc2Float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(10px)} }
@keyframes fc3Float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.fc-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fc-icon svg { width: 16px; height: 16px; }
.fc-icon-green { background: rgba(5,150,105,0.12); color: #059669; }
.fc-icon-amber { background: rgba(245,158,11,0.12); color: #d97706; }
.fc-title { font-size: 12px; font-weight: 700; color: var(--text); }
.fc-sub { font-size: 11px; color: var(--text3); margin-top: 1px; white-space: nowrap; }
.fc-stats-label { font-size: 10px; color: var(--text3); font-weight: 500; }
.fc-stats-value { font-size: 16px; font-weight: 800; color: var(--text); letter-spacing: -0.03em; }
.fc-stats-value span { font-size: 11px; font-weight: 600; color: var(--text3); }
.fc-sparkline { width: 80px; height: 30px; color: #10b981; margin-top: 4px; }
.fc-sparkline svg { width: 100%; height: 100%; }

/* ── Stats Bar ── */
.stats-bar { position: relative; z-index: 10; padding: 40px 24px; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); background: var(--card-bg); }
.stats-inner { max-width: 1000px; margin: 0 auto; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 24px; }
.stat-item { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.stat-num { font-size: clamp(28px,4vw,44px); font-weight: 900; letter-spacing: -0.04em; color: var(--primary); }
.stat-label { font-size: 13px; color: var(--text2); font-weight: 500; text-align: center; }

/* ── Section base ── */
.section { position: relative; z-index: 10; padding: 100px 24px; }
.section-inner { max-width: 1100px; margin: 0 auto; }
.section-label { display: inline-flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--primary); margin-bottom: 16px; }
.section-label::before { content:''; display: block; width: 20px; height: 2px; background: var(--primary); border-radius: 2px; }
.section-title { font-size: clamp(28px, 4vw, 44px); font-weight: 900; letter-spacing: -0.03em; color: var(--text); margin-bottom: 16px; line-height: 1.15; }
.section-title em { font-style: normal; background: linear-gradient(135deg, #10b981, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.section-sub { font-size: 17px; color: var(--text2); max-width: 520px; line-height: 1.65; margin-bottom: 64px; }

/* ── Bento Grid ── */
.bento-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  grid-template-rows: auto auto;
  gap: 16px;
}
.bento-card {
  background: var(--card-bg); border: 1px solid var(--card-border);
  border-radius: 20px; padding: 28px;
  transition: all 0.35s cubic-bezier(0.16,1,0.3,1);
  position: relative; overflow: hidden;
}
.bento-card::before {
  content: ''; position: absolute; inset: 0; opacity: 0;
  background: linear-gradient(135deg, var(--primary-light), transparent);
  transition: opacity 0.3s; border-radius: inherit;
}
.bento-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-card), 0 20px 40px rgba(5,150,105,0.08); border-color: rgba(5,150,105,0.2); }
.bento-card:hover::before { opacity: 1; }
.bento-large { grid-column: span 2; }
.bento-medium { grid-column: span 1; }
.bento-small { grid-column: span 1; }
.bento-icon-wrap {
  width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
  margin-bottom: 16px; transition: transform 0.3s;
}
.bento-card:hover .bento-icon-wrap { transform: scale(1.1) rotate(-3deg); }
.bento-icon-wrap svg { width: 22px; height: 22px; }
.green { background: rgba(5,150,105,0.1); color: #059669; }
.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
.violet { background: rgba(139,92,246,0.1); color: #8b5cf6; }
.rose { background: rgba(244,63,94,0.1); color: #f43f5e; }
.amber { background: rgba(245,158,11,0.1); color: #d97706; }
.teal { background: rgba(20,184,166,0.1); color: #0d9488; }
.bento-title { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.bento-desc { font-size: 14px; color: var(--text2); line-height: 1.6; }

/* Invoice preview in bento */
.invoice-preview { margin-top: 20px; border-radius: 10px; border: 1px solid var(--border); overflow: hidden; }
.inv-row { display: flex; gap: 8px; align-items: center; padding: 8px 12px; border-bottom: 1px solid var(--border); }
.inv-row:last-child { border-bottom: none; }
.inv-col { height: 8px; border-radius: 4px; background: var(--border); }
.inv-num { width: 60px; }
.inv-client { flex: 1; }
.inv-amount { width: 70px; }
.inv-badge { width: 52px; height: 18px; border-radius: 6px; }
.badge-green { background: rgba(5,150,105,0.15); }
.badge-amber { background: rgba(245,158,11,0.15); }
.badge-blue { background: rgba(59,130,246,0.15); }

/* Stock meter */
.stock-meter { margin-top: 20px; display: flex; flex-direction: column; gap: 10px; }
.stock-row { display: flex; align-items: center; gap: 8px; }
.stock-name { font-size: 11px; color: var(--text3); width: 68px; flex-shrink: 0; }
.stock-bar-wrap { flex: 1; height: 6px; border-radius: 99px; background: var(--border); overflow: hidden; }
.stock-bar-fill { height: 100%; border-radius: 99px; transition: width 1s cubic-bezier(0.16,1,0.3,1); }
.stock-qty { font-size: 11px; font-weight: 700; color: var(--text); width: 26px; text-align: right; }

/* Mini chart */
.mini-chart { margin-top: 20px; border-radius: 10px; overflow: hidden; }
.mini-chart svg { width: 100%; height: 80px; }

/* ── How it works ── */
.how-section { background: transparent; }
.steps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; position: relative; }
.step {
  position: relative; text-align: center; padding: 28px 20px;
  background: var(--card-bg); border: 1px solid var(--card-border);
  border-radius: 20px;
  transition: all 0.3s cubic-bezier(0.16,1,0.3,1);
}
.step:hover { transform: translateY(-4px); border-color: var(--primary); box-shadow: 0 20px 40px rgba(5,150,105,0.1); }
.step-num { font-size: 48px; font-weight: 900; color: var(--primary-light); letter-spacing: -0.05em; line-height: 1; margin-bottom: 16px; color: var(--border); }
.step-icon-wrap { width: 52px; height: 52px; border-radius: 14px; margin: 0 auto 16px; display: flex; align-items: center; justify-content: center; }
.step-icon-wrap svg { width: 24px; height: 24px; }
.step-green { background: rgba(5,150,105,0.1); color: #059669; }
.step-blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
.step-violet { background: rgba(139,92,246,0.1); color: #8b5cf6; }
.step-amber { background: rgba(245,158,11,0.1); color: #d97706; }
.step-title { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.step-desc { font-size: 13px; color: var(--text2); line-height: 1.6; }
.step-connector {
  display: none; position: absolute; top: 50%; right: -28px;
  width: 28px; height: 2px; background: var(--border);
  transform: translateY(-50%); z-index: 2;
}

/* ── Pricing ── */
.pricing-section { overflow: hidden; }
.pricing-bg-blob {
  position: absolute; width: 700px; height: 700px; top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  background: radial-gradient(circle, var(--primary-light), transparent 70%);
  pointer-events: none; z-index: 0;
}
.pricing-card-wrap {
  display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;
  position: relative; z-index: 1;
}
.pricing-card {
  background: var(--card-bg); border: 1.5px solid var(--primary);
  border-radius: 24px; padding: 36px;
  box-shadow: 0 0 0 4px var(--primary-light), var(--shadow-lg);
  position: relative; overflow: hidden;
}
.pricing-card::after {
  content: ''; position: absolute; inset: 0; z-index: 0;
  background: linear-gradient(135deg, var(--primary-light), transparent);
  border-radius: inherit; pointer-events: none;
}
.pricing-badge {
  display: inline-flex; font-size: 12px; font-weight: 700;
  background: linear-gradient(135deg,#10b981,#059669); color: white;
  padding: 4px 12px; border-radius: 99px; margin-bottom: 20px;
}
.pricing-header { margin-bottom: 28px; position: relative; z-index: 1; }
.pricing-name { font-size: 20px; font-weight: 800; color: var(--text); margin-bottom: 12px; }
.pricing-amount { display: flex; align-items: baseline; gap: 4px; margin-bottom: 4px; }
.pricing-currency { font-size: 18px; font-weight: 700; color: var(--text2); }
.pricing-price { font-size: 52px; font-weight: 900; letter-spacing: -0.05em; color: var(--text); }
.pricing-period { font-size: 16px; color: var(--text3); }
.pricing-sub-desc { font-size: 13px; color: var(--text3); }
.pricing-features { list-style: none; padding: 0; margin: 0 0 28px; display: flex; flex-direction: column; gap: 10px; position: relative; z-index: 1; }
.pricing-features li { display: flex; align-items: center; gap: 10px; font-size: 14px; color: var(--text); }
.pricing-features svg { width: 18px; height: 18px; color: var(--primary); flex-shrink: 0; }
.pricing-cta { width: 100%; justify-content: center; position: relative; z-index: 1; }
.pricing-note { font-size: 12px; color: var(--text3); text-align: center; margin-top: 12px; }
.pricing-features-extra { display: flex; flex-direction: column; gap: 24px; padding-top: 8px; }
.extra-item { display: flex; gap: 16px; align-items: flex-start; padding: 20px; border-radius: 16px; background: var(--card-bg); border: 1px solid var(--card-border); transition: all 0.3s; }
.extra-item:hover { border-color: var(--primary); transform: translateX(4px); }
.extra-icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.extra-icon-wrap svg { width: 20px; height: 20px; }
.extra-title { font-size: 14px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.extra-desc { font-size: 13px; color: var(--text2); line-height: 1.5; }

/* ── Final CTA ── */
.final-cta-section {
  position: relative; z-index: 10; padding: 100px 24px;
  background: linear-gradient(135deg, rgba(5,150,105,0.06), rgba(52,211,153,0.04));
  border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
  text-align: center;
}
.final-cta-inner { max-width: 700px; margin: 0 auto; }
.final-cta-badge {
  display: inline-block; font-size: 12px; font-weight: 700; letter-spacing: 0.08em;
  text-transform: uppercase; color: var(--primary); margin-bottom: 24px;
  background: var(--primary-light); padding: 6px 16px; border-radius: 99px;
  border: 1px solid rgba(5,150,105,0.2);
}
.final-cta-title { font-size: clamp(32px,5vw,56px); font-weight: 900; letter-spacing: -0.04em; color: var(--text); margin-bottom: 16px; line-height: 1.1; }
.final-cta-title em { font-style: normal; background: linear-gradient(135deg,#10b981,#059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.final-cta-sub { font-size: 17px; color: var(--text2); margin-bottom: 36px; line-height: 1.6; }
.final-cta-buttons { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* ── Footer ── */
.landing-footer { position: relative; z-index: 10; background: var(--bg2); border-top: 1px solid var(--border); padding: 56px 24px 0; }
.footer-inner { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: 1fr auto; gap: 48px; padding-bottom: 40px; }
.footer-brand { max-width: 320px; }
.footer-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
.footer-tagline { font-size: 14px; color: var(--text2); line-height: 1.6; }
.footer-links { display: flex; gap: 48px; }
.footer-col { display: flex; flex-direction: column; gap: 10px; }
.footer-col-title { font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.footer-col a { font-size: 14px; color: var(--text2); text-decoration: none; transition: color 0.2s; }
.footer-col a:hover { color: var(--primary); }
.footer-bottom {
  border-top: 1px solid var(--border); padding: 20px 0;
  max-width: 1100px; margin: 0 auto;
  display: flex; justify-content: space-between; align-items: center;
  font-size: 13px; color: var(--text3);
}
.footer-trust { display: flex; gap: 16px; }

/* ── Responsive ── */
@media (max-width: 1024px) {
  .bento-grid { grid-template-columns: repeat(2, 1fr); }
  .bento-large { grid-column: span 2; }
  .bento-small { grid-column: span 1; }
  .steps-grid { grid-template-columns: repeat(2, 1fr); }
  .pricing-card-wrap { grid-template-columns: 1fr; }
  .footer-inner { grid-template-columns: 1fr; gap: 32px; }
  .float-card { display: none; }
}

@media (max-width: 768px) {
  .nav-links { display: none; }
  .nav-actions { display: none; }
  .nav-hamburger { display: flex; }
  .mobile-menu {
    display: flex; flex-direction: column; gap: 4px;
    padding: 12px 16px; background: var(--nav-bg);
    backdrop-filter: blur(20px); border-bottom: 1px solid var(--border);
    max-height: 0; overflow: hidden; transition: max-height 0.4s cubic-bezier(0.16,1,0.3,1);
  }
  .mobile-menu.mobile-open { max-height: 400px; }
  .mobile-link { padding: 10px 12px; border-radius: 8px; font-size: 15px; font-weight: 500; color: var(--text2); text-decoration: none; }
  .mobile-link:hover { background: var(--primary-light); color: var(--primary); }
  .mobile-actions { display: flex; flex-direction: column; gap: 8px; padding-top: 12px; }
  .hero { padding-top: 100px; }
  .bento-grid { grid-template-columns: 1fr; }
  .bento-large { grid-column: span 1; }
  .steps-grid { grid-template-columns: 1fr 1fr; }
  .footer-links { flex-wrap: wrap; gap: 32px; }
  .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
  .steps-grid { grid-template-columns: 1fr; }
  .hero-ctas { flex-direction: column; align-items: center; }
  .final-cta-buttons { flex-direction: column; align-items: center; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
