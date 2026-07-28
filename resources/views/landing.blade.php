<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIDIBE CORPORATE — La facturation & le stock, simplifiés.</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:#fafafa;color:#0a0a0a;line-height:1.6}

/* ---- containers ---- */
.w{max-width:1200px;margin:0 auto;padding:0 24px}
@media(min-width:768px){.w{padding:0 32px}}
@media(min-width:1024px){.w{padding:0 48px}}

/* ---- navbar ---- */
.nav{position:fixed;top:0;left:0;right:0;z-index:50;background:rgba(250,250,250,.85);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-bottom:1px solid rgba(0,0,0,.06)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:64px}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.nav-logo-icon{width:32px;height:32px;background:#059669;border-radius:7px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0}
.nav-logo-text{font-weight:700;font-size:18px;color:#0a0a0a;letter-spacing:-.3px}
.nav-right{display:flex;align-items:center;gap:20px}
.nav-link{font-size:14px;font-weight:500;color:#555;text-decoration:none;transition:color .2s}
.nav-link:hover{color:#0a0a0a}
.btn{display:inline-flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;border-radius:10px;transition:all .25s;cursor:pointer;text-decoration:none;border:none;font-family:inherit;line-height:1}
.btn-sm{padding:9px 20px}
.btn-lg{padding:16px 32px;font-size:16px;border-radius:12px}
.btn-primary{background:#059669;color:#fff}
.btn-primary:hover{background:#047857;transform:translateY(-1px);box-shadow:0 8px 24px rgba(5,150,105,.3)}
.btn-outline{background:transparent;color:#0a0a0a;border:1.5px solid #e5e7eb}
.btn-outline:hover{background:#f3f4f6;border-color:#d1d5db}

/* ---- hero ---- */
.hero{padding:140px 0 80px;text-align:center;position:relative;overflow:hidden}
.hero-bg{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.hero-bg-circle{position:absolute;width:640px;height:640px;border-radius:50%;background:radial-gradient(circle,rgba(5,150,105,.06) 0%,transparent 70%);top:-160px;left:50%;transform:translateX(-50%)}
.hero-bg-circle2{position:absolute;width:480px;height:480px;border-radius:50%;background:radial-gradient(circle,rgba(5,150,105,.04) 0%,transparent 70%);bottom:-200px;right:-120px}
.badge{display:inline-flex;align-items:center;gap:6px;background:#f0fdf4;color:#059669;font-size:13px;font-weight:600;padding:6px 16px;border-radius:20px;border:1px solid rgba(5,150,105,.15);margin-bottom:32px;letter-spacing:-.2px}
.badge-dot{width:6px;height:6px;border-radius:50%;background:#059669;flex-shrink:0}
.hero-title{font-size:clamp(36px,7vw,68px);font-weight:800;line-height:1.1;letter-spacing:-2px;margin-bottom:24px;color:#0a0a0a}
.hero-title em{font-style:normal;color:#059669}
.hero-sub{font-size:clamp(16px,2vw,20px);color:#6b7280;max-width:620px;margin:0 auto 40px;line-height:1.65;font-weight:400}
.hero-cta{display:flex;flex-wrap:wrap;gap:14px;justify-content:center}
.hero-cta .btn{font-weight:600}

/* ---- features ---- */
.features{padding:100px 0 120px}
.sec-label{font-size:13px;font-weight:600;color:#059669;text-transform:uppercase;letter-spacing:1.2px;margin-bottom:12px;text-align:center}
.sec-title{font-size:clamp(28px,4vw,40px);font-weight:800;text-align:center;letter-spacing:-1.2px;margin-bottom:12px;color:#0a0a0a}
.sec-sub{text-align:center;color:#6b7280;font-size:17px;max-width:540px;margin:0 auto 64px;font-weight:400}
.grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:640px){.grid{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.grid{grid-template-columns:repeat(3,1fr)}}
.card{background:#fff;border:1px solid #f0f0f0;border-radius:16px;padding:28px 26px;transition:all .3s;cursor:default}
.card:hover{border-color:#e5e7eb;box-shadow:0 8px 32px rgba(0,0,0,.04);transform:translateY(-2px)}
.card-icon{width:40px;height:40px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin-bottom:18px;flex-shrink:0}
.card-icon svg{width:20px;height:20px;color:#059669}
.card h3{font-size:16px;font-weight:700;margin-bottom:8px;color:#0a0a0a;letter-spacing:-.2px}
.card p{font-size:14px;color:#6b7280;line-height:1.65}

/* ---- footer ---- */
.footer{border-top:1px solid #f0f0f0;padding:32px 0;text-align:center;font-size:13px;color:#9ca3af}

/* ---- mobile ---- */
@media(max-width:639px){
.hero{padding:120px 0 60px}
.hero-title{letter-spacing:-1.2px}
.hero-cta{gap:12px}
.hero-cta .btn{width:100%;max-width:320px}
.nav-inner{height:56px}
.nav-right .nav-link{display:none}
.card{padding:24px 20px}
}
</style>
</head>
<body>

<nav class="nav">
<div class="w">
<div class="nav-inner">
<a href="#" class="nav-logo">
<div class="nav-logo-icon">SC</div>
<span class="nav-logo-text">SIDIBE CORPORATE</span>
</a>
<div class="nav-right">
<a href="#" class="nav-link">Se connecter</a>
<a href="#" class="btn btn-primary btn-sm">Commencer</a>
</div>
</div>
</div>
</nav>

<section class="hero">
<div class="hero-bg">
<div class="hero-bg-circle"></div>
<div class="hero-bg-circle2"></div>
</div>
<div class="w">
<h1 class="hero-title">La facturation & le stock,<br><em>simplifiés.</em></h1>
<p class="hero-sub">
Gérez vos factures, votre stock, vos clients et vos paiements depuis une seule plateforme. Multi-entreprises, sécurisée, et conçue pour passer à l'échelle.
</p>
<div class="hero-cta">
<a href="#" class="btn btn-primary btn-lg">Créer mon entreprise</a>
<a href="#" class="btn btn-outline btn-lg">J'ai déjà un compte</a>
</div>
</div>
</section>

<section class="features">
<div class="w">
<div class="sec-label">Plateforme</div>
<h2 class="sec-title">Tout ce dont vous avez besoin</h2>
<p class="sec-sub">Une solution complète pour gérer votre activité, de la facture à l'analyse, en passant par le stock et les clients.</p>
<div class="grid">
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
</div>
<h3>Facturation</h3>
<p>Devis, factures, paiements partiels, taxes multiples. PDF prêt à envoyer.</p>
</div>
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V3M10.5 3h3"/></svg>
</div>
<h3>Stock</h3>
<p>Produits, SKU, alertes stock faible, mouvements tracés.</p>
</div>
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
</div>
<h3>CRM Clients</h3>
<p>Historique d'achats, balance, crédit client, documents.</p>
</div>
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
</div>
<h3>Analytics</h3>
<p>Tableau de bord temps réel : CA, ventes, top produits.</p>
</div>
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
</div>
<h3>Multi-tenant sécurisé</h3>
<p>Isolation stricte des données par entreprise, RLS PostgreSQL.</p>
</div>
<div class="card">
<div class="card-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>
</div>
<h3>API-first</h3>
<p>Architecture moderne, prête pour mobile et intégrations.</p>
</div>
</div>
</div>
</section>

<footer class="footer">
<div class="w">
© 2026 OM-SID — Tous droits réservés.
</div>
</footer>

</body>
</html>
