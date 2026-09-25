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
        <p class="pro-eyebrow">Légal · Données personnelles</p>
        <h1 class="pro-title !text-3xl sm:!text-4xl">Politique de confidentialité</h1>
        <p class="pro-subtitle">Dernière mise à jour : 21 septembre 2026 — Cette politique explique quelles données SIDIBE CORPORATE collecte dans le cadre de la plateforme de facturation & stock, pourquoi, et quels sont vos droits.</p>
        <div class="mt-5 flex flex-wrap gap-2">
          <button @click="print" class="inline-flex items-center gap-2 px-4 py-2.5 text-[13px] font-extrabold rounded-2xl border border-border bg-surface hover:border-emerald-400/50 shadow-sm active:scale-[0.98] transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.111 48.111 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.25 2.25 0 013 12v-2.88" /></svg>
            Imprimer
          </button>
          <router-link to="/conditions" class="inline-flex items-center gap-2 px-4 py-2.5 text-[13px] font-extrabold rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 shadow active:scale-[0.98] transition">Conditions d'utilisation →</router-link>
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
            <h2 class="text-lg font-extrabold tracking-tight text-text-primary">Contact — données personnelles</h2>
            <p class="mt-2 text-text-secondary">Pour toute question ou demande relative à vos données : <strong class="text-text-primary">9k5osama@gmail.com</strong>. Nous répondons sous 30 jours maximum.</p>
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
  { n: '1', id: 'editeur', t: 'Éditeur du service', html: `<p>La plateforme <strong>Facturation & Stock</strong> est éditée par <strong>SIDIBE CORPORATE</strong>, société immatriculée [forme juridique, capital, immatriculation à compléter], dont le siège est situé [adresse à compléter]. Contact : <strong>9k5osama@gmail.com</strong>.</p>` },
  { n: '2', id: 'perimetre', t: 'Périmètre', html: `<p>Cette politique s'applique à l'utilisation de la plateforme (application web, caisse POS, API) par les entreprises clientes (« locataires ») et leurs utilisateurs. Les données des <strong>clients finaux des locataires</strong> (figurant sur factures, devis, bons de livraison) sont traitées par chaque locataire, qui en est responsable ; SIDIBE CORPORATE agit alors comme sous-traitant technique.</p>` },
  { n: '3', id: 'donnees', t: 'Données collectées', html: `<p><strong>Compte et entreprise :</strong> nom, e-mail, téléphone, mot de passe chiffré, nom et paramètres de l'entreprise (devise, langue, logo, mentions légales, coordonnées bancaires affichées sur les documents).</p><p><strong>Gestion commerciale :</strong> produits, clients, fournisseurs, factures, devis, avoirs, bons de commande et de livraison, paiements, dépenses, mouvements de stock, sessions de caisse.</p><p><strong>Technique :</strong> journaux de connexion (adresse IP, date, navigateur, succès/échec), journaux d'activité, préférences (langue, thème).</p><p><strong>Paiement d'abonnement :</strong> les paiements sont traités par notre prestataire (Stripe) ; nous ne stockons pas les numéros de carte bancaire.</p>` },
  { n: '4', id: 'finalites', t: 'Finalités', html: `<p>Vos données servent exclusivement à : fournir et sécuriser le service ; gérer les comptes, abonnements et la facturation ; établir vos documents commerciaux et PDF ; prévenir la fraude et les abus (détection des tentatives de connexion suspectes) ; améliorer la plateforme (statistiques agrégées et anonymisées) ; respecter nos obligations légales et comptables.</p>` },
  { n: '5', id: 'base', t: 'Fondement', html: `<p>Le traitement repose sur <strong>l'exécution du contrat</strong> (compte, abonnement, fonctionnalités), notre <strong>intérêt légitime</strong> (sécurité, prévention de la fraude, amélioration du service) et, le cas échéant, votre <strong>consentement</strong> (cookies de mesure, communications marketing).</p>` },
  { n: '6', id: 'durees', t: 'Durées de conservation', html: `<p><strong>Compte actif :</strong> données conservées pendant toute la relation contractuelle. <strong>Après clôture :</strong> suppression ou anonymisation sous 12 mois, sauf obligations légales (documents comptables et fiscaux conservés selon la durée exigée par la loi applicable, en général 10 ans). <strong>Journaux de sécurité :</strong> 12 mois maximum. <strong>Sauvegardes :</strong> rotation sur 30 jours.</p>` },
  { n: '7', id: 'securite', t: 'Sécurité', html: `<p>Mots de passe chiffrés (hachage robuste), chiffrement des échanges (TLS/SSL), isolation stricte des données par entreprise (RLS PostgreSQL), révocation immédiate des accès en cas de suspension, sauvegardes chiffrées, journalisation des accès sensibles. Aucun système n'est infaillible : en cas d'incident affectant vos données, vous serez notifié dans les meilleurs délais.</p>` },
  { n: '8', id: 'partage', t: 'Partage et sous-traitants', html: `<p>Vos données ne sont <strong>ni vendues, ni louées</strong>. Elles sont partagées uniquement avec : notre hébergeur ; notre prestataire de paiement (abonnements) ; notre prestataire d'e-mails transactionnels ; les autorités sur réquisition légale. Chaque sous-traitant est contractuellement tenu à la confidentialité et à la sécurité.</p>` },
  { n: '9', id: 'droits', t: 'Vos droits', html: `<p>Vous disposez des droits d'<strong>accès, rectification, suppression, limitation, opposition et portabilité</strong> de vos données, exerçables depuis l'application (Paramètres, export CSV) ou par e-mail à <strong>9k5osama@gmail.com</strong>. Vous pouvez également introduire une réclamation auprès de l'autorité de protection des données compétente.</p>` },
  { n: '10', id: 'mineurs', t: 'Mineurs', html: `<p>Le service s'adresse aux professionnels. Les comptes ouverts pour des mineurs doivent l'être sous la responsabilité d'un représentant légal.</p>` },
  { n: '11', id: 'modifs', t: 'Modifications', html: `<p>Cette politique peut évoluer (fonctionnalités, cadre légal). Toute modification substantielle sera notifiée dans l'application ou par e-mail au moins 15 jours avant son entrée en vigueur. La poursuite de l'utilisation vaut acceptation.</p>` },
]
</script>
