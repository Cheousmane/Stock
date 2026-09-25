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
        <p class="pro-eyebrow">Légal · Traceurs</p>
        <h1 class="pro-title !text-3xl sm:!text-4xl">Politique cookies</h1>
        <p class="pro-subtitle">Dernière mise à jour : 21 septembre 2026 — Quels cookies et stockages locaux la plateforme utilise, et comment les gérer.</p>
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

          <section class="rounded-2xl border border-border/70 overflow-hidden">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-surface-secondary/60 text-left text-[11px] font-extrabold uppercase tracking-wider text-text-tertiary">
                  <th class="px-4 py-3">Nom</th>
                  <th class="px-4 py-3">Rôle</th>
                  <th class="px-4 py-3">Durée</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border/60 text-text-secondary">
                <tr><td class="px-4 py-3 font-bold text-text-primary">token / session</td><td class="px-4 py-3">Maintien de la connexion</td><td class="px-4 py-3">Session / révocation</td></tr>
                <tr><td class="px-4 py-3 font-bold text-text-primary">locale, theme</td><td class="px-4 py-3">Préférences langue et thème</td><td class="px-4 py-3">Persistant (navigateur)</td></tr>
                <tr><td class="px-4 py-3 font-bold text-text-primary">XSRF-TOKEN, session</td><td class="px-4 py-3">Sécurité anti-CSRF</td><td class="px-4 py-3">Session</td></tr>
              </tbody>
            </table>
          </section>

          <section id="contact" class="scroll-mt-24 rounded-2xl bg-emerald-500/[0.07] border border-emerald-500/20 p-5">
            <h2 class="text-lg font-extrabold tracking-tight text-text-primary">Contact</h2>
            <p class="mt-2 text-text-secondary">Questions sur les cookies : <strong class="text-text-primary">9k5osama@gmail.com</strong>.</p>
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
  { n: '1', id: 'definition', t: 'Qu\'est-ce qu\'un cookie ?', html: `<p>Un cookie est un petit fichier déposé sur votre terminal lors de la visite d'un site. La plateforme utilise également le <strong>stockage local du navigateur</strong> (localStorage) pour mémoriser vos préférences. Aucun cookie publicitaire tiers n'est utilisé.</p>` },
  { n: '2', id: 'necessaires', t: 'Strictement nécessaires', html: `<p>Ces traceurs sont indispensables au fonctionnement et ne nécessitent pas votre consentement : <strong>session et jeton d'authentification</strong> (connexion), <strong>protection anti-CSRF</strong>, <strong>préférences</strong> (langue, thème). Sans eux, la connexion et la sécurité ne peuvent pas fonctionner.</p>` },
  { n: '3', id: 'mesure', t: 'Mesure d\'audience', html: `<p>Nous pouvons utiliser une mesure d'audience <strong>respectueuse de la vie privée</strong> (données agrégées, sans suivi inter-sites) pour comprendre l'usage et améliorer le service. Vous pouvez vous y opposer à tout moment via les réglages de votre navigateur (Do Not Track respecté) ou en nous écrivant.</p>` },
  { n: '4', id: 'gerer', t: 'Gérer les cookies', html: `<p>Vous pouvez supprimer ou bloquer les cookies depuis les paramètres de votre navigateur (Chrome, Firefox, Safari, Edge). Le blocage des cookies strictement nécessaires empêchera la connexion. Le choix du thème et de la langue est stocké localement : effacer les données du site réinitialise ces préférences.</p>` },
]
</script>
