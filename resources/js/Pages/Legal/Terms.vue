<template>
  <div class="min-h-screen bg-surface-secondary text-text-primary antialiased">
    <header class="sticky top-0 z-30 border-b border-border bg-surface/80 backdrop-blur-xl">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 h-16 flex items-center gap-3">
        <router-link to="/" class="flex items-center gap-2.5 group">
          <AppLogo :size="34" />
          <span class="text-sm font-extrabold tracking-tight">SIDIBE CORPORATE</span>
        </router-link>
        <router-link to="/" class="ml-auto inline-flex items-center gap-1.5 text-sm font-bold text-text-secondary hover:text-emerald-600 transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12l7.5-7.5M3 12h18" /></svg>
          Accueil
        </router-link>
      </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-10 lg:py-14">
      <div class="max-w-3xl">
        <p class="pro-eyebrow">Légal · Contrat</p>
        <h1 class="pro-title !text-3xl sm:!text-4xl">Conditions d'utilisation</h1>
        <p class="pro-subtitle">Dernière mise à jour : 21 septembre 2026 — En créant un compte ou en utilisant la plateforme Facturation & Stock, vous acceptez les présentes conditions.</p>
        <div class="mt-5 flex flex-wrap gap-2">
          <button @click="print" class="inline-flex items-center gap-2 px-4 py-2.5 text-[13px] font-extrabold rounded-2xl border border-border bg-surface hover:border-emerald-400/50 shadow-sm active:scale-[0.98] transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.111 48.111 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.25 2.25 0 013 12v-2.88" /></svg>
            Imprimer
          </button>
          <router-link to="/confidentialite" class="inline-flex items-center gap-2 px-4 py-2.5 text-[13px] font-extrabold rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 shadow active:scale-[0.98] transition">Politique de confidentialité →</router-link>
        </div>
      </div>

      <div class="mt-8 grid lg:grid-cols-[220px_1fr] gap-6 items-start">
        <nav class="hidden lg:block sticky top-24 p-4 rounded-3xl bg-surface border border-border/70 shadow-sm text-[13px] font-semibold space-y-1">
          <a v-for="s in sections" :key="s.id" :href="'#' + s.id" class="block px-3 py-2 rounded-xl text-text-secondary hover:text-emerald-600 hover:bg-emerald-500/[0.07] transition">{{ s.n }}. {{ s.t }}</a>
        </nav>

        <article class="pro-card !rounded-3xl p-6 sm:p-10 space-y-8 text-[15px] leading-relaxed">
          <section v-for="s in sections" :key="s.id" :id="s.id" class="scroll-mt-24">
            <h2 class="text-lg font-extrabold tracking-tight"><span class="text-emerald-600">{{ s.n }}.</span> {{ s.t }}</h2>
            <div class="mt-2.5 text-text-secondary space-y-2.5" v-html="s.html"></div>
          </section>

          <section id="contact" class="scroll-mt-24 rounded-2xl bg-emerald-500/[0.07] border border-emerald-500/20 p-5">
            <h2 class="text-lg font-extrabold tracking-tight text-text-primary">Contact</h2>
            <p class="mt-2 text-text-secondary">Questions sur ces conditions : <strong class="text-text-primary">9k5osama@gmail.com</strong>.</p>
          </section>
        </article>
      </div>

      <p class="mt-8 text-center text-xs text-text-tertiary">© 2026 SIDIBE CORPORATE — Tous droits réservés.</p>
    </main>
  </div>
</template>

<script setup>
import AppLogo from '../../Components/AppLogo.vue'

function print() { window.print() }

const sections = [
  { n: '1', id: 'objet', t: 'Objet', html: `<p>SIDIBE CORPORATE propose en mode SaaS une plateforme de <strong>facturation, devis, avoirs, bons de commande et de livraison, stock multi-entrepôts, caisse POS, paiements, dépenses et pilotage</strong>, accessible via application web. Les présentes conditions encadrent l'accès et l'utilisation du service par les entreprises clientes et leurs utilisateurs.</p>` },
  { n: '2', id: 'compte', t: 'Compte et accès', html: `<p>L'inscription nécessite des informations exactes (identité, entreprise, e-mail). Un code de vérification est envoyé par e-mail avant activation. Vous êtes responsable de la <strong>confidentialité de vos identifiants</strong> et des actions effectuées depuis vos comptes utilisateurs (administrateur, gestionnaire, comptable, commercial...). Toute utilisation suspecte doit nous être signalée sans délai.</p>` },
  { n: '3', id: 'plans', t: 'Plans et abonnements', html: `<p>Le service est proposé selon plusieurs <strong>plans</strong> (dont une offre gratuite limitée), détaillés sur la page Tarifs et dans l'espace Abonnement. Les quotas (utilisateurs, documents, entreprises) dépendent du plan. Le changement de plan prend effet selon les modalités affichées ; tout downgrade peut restreindre l'accès aux fonctionnalités ou données excédentaires.</p>` },
  { n: '4', id: 'paiement', t: 'Paiement et facturation', html: `<p>Les abonnements payants sont facturés d'avance par cycle (mensuel ou selon l'offre), via notre prestataire de paiement sécurisé. <strong>Tout cycle entamé est dû.</strong> En cas d'impayé, l'accès peut être restreint puis suspendu après notification. Les prix sont affichés hors taxes sauf mention contraire ; les taxes applicables (ex. TVA) sont ajoutées le cas échéant.</p>` },
  { n: '5', id: 'essai', t: "Période d'essai", html: `<p>Certains plans incluent une période d'essai. À son expiration sans souscription, l'entreprise bascule sur l'offre gratuite ou voit son accès restreint, avec conservation temporaire des données pour permettre une reprise (voir Politique de confidentialité).</p>` },
  { n: '6', id: 'usage', t: 'Usage autorisé et interdictions', html: `<p>Vous vous engagez à utiliser le service <strong>conformément à la loi</strong> (notamment fiscale et comptable : mentions obligatoires, numérotation, conservation). Sont interdits : l'accès non autorisé, l'extraction massive, la revente du service sans accord, l'atteinte à la sécurité, les contenus illicites, et tout usage portant atteinte aux droits de tiers. En cas de manquement grave, nous pouvons <strong>suspendre ou résilier</strong> l'accès après mise en demeure restée sans effet, ou immédiatement en cas de risque pour la plateforme.</p>` },
  { n: '7', id: 'donnees-client', t: 'Données des clients', html: `<p>Vous restez <strong>propriétaire et responsable</strong> des données que vous saisissez (clients, documents commerciaux, stocks). Vous garantissez disposer des droits nécessaires, notamment pour les données personnelles de vos propres clients. SIDIBE CORPORATE n'accède à vos données que pour fournir le service, assurer le support (avec votre accord) ou respecter une obligation légale. Voir la Politique de confidentialité pour les durées et la sécurité.</p>` },
  { n: '8', id: 'dispo', t: 'Disponibilité et support', html: `<p>Nous visons une disponibilité de <strong>99,9 %</strong> hors maintenances planifiées (notifiées à l'avance). Le support est assuré via l'application et par e-mail ; les délais de réponse varient selon le plan. Aucune garantie de résultat commercial n'est consentie.</p>` },
  { n: '9', id: 'propriete', t: 'Propriété intellectuelle', html: `<p>La plateforme (code, design, marque SIDIBE CORPORATE, logo) reste notre propriété exclusive. Vous bénéficiez d'un <strong>droit d'utilisation personnel, non exclusif et non transférable</strong> pendant l'abonnement. Vos documents générés (factures PDF, etc.) vous appartiennent.</p>` },
  { n: '10', id: 'responsabilite', t: 'Responsabilité', html: `<p>Le service est fourni « en l'état ». Dans la limite autorisée par la loi, notre responsabilité est limitée aux <strong>dommages directs</strong> et plafonnée aux montants versés au cours des 12 derniers mois. Nous ne saurions être tenus des dommages indirects (perte de chiffre d'affaires, redressement fiscal lié à un paramétrage, etc.). Vous êtes responsable du paramétrage fiscal et comptable de vos documents.</p>` },
  { n: '11', id: 'resiliation', t: 'Durée et résiliation', html: `<p>Le contrat prend effet à l'inscription et se poursuit par reconduction selon le cycle choisi. Vous pouvez <strong>résilier à tout moment</strong> depuis l'espace Abonnement ; l'accès est maintenu jusqu'à la fin de la période payée, sans remboursement prorata. Nous pouvons résilier avec un préavis de 30 jours, ou immédiatement en cas de manquement grave ou d'impayé persistant. À la résiliation, vos données restent exportables (CSV) pendant 90 jours, puis supprimées conformément à la Politique de confidentialité.</p>` },
  { n: '12', id: 'modifs', t: 'Modifications des conditions', html: `<p>Nous pouvons faire évoluer ces conditions (fonctionnalités, cadre légal). Les modifications substantielles sont notifiées <strong>15 jours à l'avance</strong> ; la poursuite de l'utilisation vaut acceptation. En cas de désaccord, vous pouvez résilier avant l'entrée en vigueur.</p>` },
  { n: '13', id: 'droit', t: 'Droit applicable et litiges', html: `<p>Les présentes conditions sont régies par le <strong>droit OHADA</strong> et, à titre supplétif, le droit national applicable au siège de SIDIBE CORPORATE. En cas de litige, les parties privilégient le règlement amiable ; à défaut, les tribunaux compétents du siège seront saisis.</p>` },
]
</script>
