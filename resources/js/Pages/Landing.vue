<template>
  <div class="min-h-screen bg-surface text-text-primary font-sans overflow-x-hidden">
    <!-- Animated background -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
      <div class="absolute inset-0 bg-gradient-layer animate-[bgShift_20s_ease-in-out_infinite_alternate]"></div>
      <div ref="particlesRef" class="absolute inset-0"></div>
    </div>

    <!-- Nav -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-surface/80 backdrop-blur-xl border-b border-border animate-[navSlide_0.7s_cubic-bezier(0.16,1,0.3,1)]">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 lg:px-12 flex items-center justify-between h-16">
        <router-link to="/" class="flex items-center gap-2.5">
          <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center text-white font-extrabold text-sm">SC</div>
          <span class="font-bold text-lg text-text-primary tracking-tight">SIDIBE CORPORATE</span>
        </router-link>
        <div class="flex items-center gap-5">
          <router-link to="/login" class="text-sm font-medium text-text-tertiary hover:text-text-primary transition-colors">Connexion</router-link>
          <router-link to="/register" class="inline-flex items-center justify-center font-semibold rounded-xl px-4 py-2 text-sm bg-emerald-600 text-white hover:bg-emerald-700 transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-emerald-500/25">Commencer</router-link>
        </div>
      </div>
    </nav>

    <!-- Hero -->
    <section class="relative z-10 pt-40 pb-24 text-center">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 lg:px-12">
        <h1 class="text-[clamp(36px,7vw,68px)] font-extrabold leading-[1.1] tracking-tight text-text-primary mb-6">
          <span v-for="(word, i) in heroWords1" :key="'a'+i"
            class="inline-block opacity-0 translate-y-8 animate-[wordIn_0.6s_cubic-bezier(0.16,1,0.3,1)_forwards] mr-[0.2em]"
            :style="{ animationDelay: `${0.3 + i * 0.06}s` }">{{ word }}</span>
          <br>
          <span v-for="(word, i) in heroWords2" :key="'b'+i"
            class="inline-block text-emerald-600 opacity-0 translate-y-8 animate-[wordIn_0.6s_cubic-bezier(0.16,1,0.3,1)_forwards] mr-[0.2em] relative"
            :style="{ animationDelay: `${0.8 + i * 0.08}s` }">{{ word }}</span>
        </h1>

        <p class="text-lg sm:text-xl text-text-tertiary max-w-xl mx-auto mb-10 opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_1s_forwards]">
          Gérez votre facturation, stock, clients et caisse en un seul endroit. Solution multi-entreprise complète.
        </p>

        <!-- Floating mockup -->
        <div class="relative w-[320px] sm:w-[400px] h-[200px] sm:h-[240px] mx-auto mt-16 perspective-[800px]">
          <div class="absolute w-[180px] sm:w-[220px] h-[120px] sm:h-[140px] bg-surface border border-border/60 rounded-xl top-[10px] left-0 p-3 sm:p-4 shadow-card animate-[mockupFloat_6s_ease-in-out_infinite] z-[2]">
            <div class="flex gap-1"><span class="w-[6px] h-[6px] rounded-full bg-red-500 inline-block"></span><span class="w-[6px] h-[6px] rounded-full bg-amber-500 inline-block"></span><span class="w-[6px] h-[6px] rounded-full bg-emerald-500 inline-block"></span></div>
            <div class="h-1 bg-emerald-50 dark:bg-emerald-500/15 rounded mt-2"></div>
            <div class="h-[3px] w-[60%] bg-surface-tertiary rounded mt-2"></div>
            <div class="h-[3px] w-[40%] bg-surface-tertiary rounded mt-2"></div>
          </div>
          <div class="absolute w-[160px] sm:w-[200px] h-[140px] sm:h-[160px] bg-surface border border-border/60 rounded-xl top-[50px] right-0 p-3 sm:p-4 shadow-card animate-[mockupFloat_6s_ease-in-out_infinite_-2s] z-[1]">
            <div class="w-10 h-10 rounded-full border-[3px] border-emerald-100 dark:border-emerald-700 border-t-emerald-600 animate-spin mx-auto mb-3"></div>
            <div class="flex gap-1 justify-center"><span class="w-2 h-[30px] bg-emerald-100 dark:bg-emerald-500 rounded inline-block"></span><span class="w-2 h-5 bg-emerald-200 dark:bg-emerald-400 rounded inline-block"></span><span class="w-2 h-6 bg-emerald-300 dark:bg-emerald-500 rounded inline-block"></span></div>
          </div>
        </div>

        <div class="flex flex-wrap gap-3 justify-center opacity-0 animate-[fadeUp_0.6s_cubic-bezier(0.16,1,0.3,1)_1.2s_forwards]">
          <router-link to="/register" class="inline-flex items-center justify-center font-semibold rounded-xl px-8 py-4 text-base bg-emerald-600 text-white hover:bg-emerald-700 transition-all hover:-translate-y-0.5 shadow-lg hover:shadow-xl hover:shadow-emerald-500/30 animate-ctaPulse">
            <span>Créer votre entreprise</span>
          </router-link>
          <router-link to="/login" class="inline-flex items-center justify-center font-semibold rounded-xl px-8 py-4 text-base bg-surface border border-border text-text-primary hover:bg-surface-tertiary transition-all hover:-translate-y-0.5">
            <span>J'ai déjà un compte</span>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="relative z-10 py-24 lg:py-32">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 lg:px-12">
        <p ref="secLabelRef" class="text-xs font-semibold text-emerald-600 uppercase tracking-widest text-center mb-3 opacity-0 translate-y-5 transition-all duration-600 [&.in-view]:opacity-100 [&.in-view]:translate-y-0">Fonctionnalités</p>
        <h2 ref="secTitleRef" class="text-[clamp(28px,4vw,40px)] font-extrabold text-center tracking-tight mb-3 text-text-primary opacity-0 translate-y-5 transition-all duration-600 delay-100 [&.in-view]:opacity-100 [&.in-view]:translate-y-0">Tout ce dont vous avez besoin</h2>
        <p ref="secSubRef" class="text-center text-text-tertiary text-base sm:text-lg max-w-lg mx-auto mb-16 opacity-0 translate-y-5 transition-all duration-600 delay-200 [&.in-view]:opacity-100 [&.in-view]:translate-y-0">Une suite complète pour gérer votre entreprise au quotidien.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div v-for="(card, i) in cards" :key="i"
            class="bg-surface border border-border/60 rounded-2xl p-7 cursor-default relative overflow-hidden opacity-0 translate-y-8 animate-[cardIn_0.6s_cubic-bezier(0.16,1,0.3,1)_forwards] hover:-translate-y-1.5 hover:shadow-lg hover:border-border transition-all duration-300 group"
            :style="{ animationDelay: `${i * 0.12}s` }">
            <div class="absolute top-0 left-0 right-0 h-[3px] bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-400 scale-x-0 origin-left transition-transform duration-300 group-hover:scale-x-100"></div>
            <div class="card-icon w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/10 flex items-center justify-center mb-4 transition-all duration-300 group-hover:scale-110 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-500/20 group-hover:shadow-md" v-html="card.icon"></div>
            <h3 class="text-base font-bold text-text-primary mb-2">{{ card.title }}</h3>
            <p class="text-sm text-text-tertiary leading-relaxed">{{ card.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section ref="ctaFinalRef" class="relative z-10 py-24 text-center opacity-0 translate-y-8 transition-all duration-700 [&.in-view]:opacity-100 [&.in-view]:translate-y-0">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 lg:px-12">
        <h2 class="text-[clamp(28px,4vw,40px)] font-extrabold tracking-tight mb-3 text-text-primary">Prêt à démarrer ?</h2>
        <p class="text-text-tertiary text-base sm:text-lg mb-8">Créez votre compte gratuitement et commencez à gérer votre entreprise.</p>
        <router-link to="/register" class="inline-flex items-center justify-center font-semibold rounded-xl px-8 py-4 text-base bg-emerald-600 text-white hover:bg-emerald-700 transition-all hover:-translate-y-0.5 shadow-lg hover:shadow-xl hover:shadow-emerald-500/30 animate-ctaPulse">
          <span>Créer votre entreprise</span>
        </router-link>
      </div>
    </section>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-border py-8 text-center text-xs text-text-tertiary">
      <div class="max-w-6xl mx-auto px-4 sm:px-8 lg:px-12">
        &copy; 2026 SIDIBE CORPORATE. Tous droits réservés.
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const heroWords1 = computed(() => t('landing.hero.title.words1').split(' '));
const heroWords2 = computed(() => [t('landing.hero.title.words2')]);

const cards = [
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>', title: 'Facturation', desc: 'Devis, factures, paiements partiels, taxes multiples. PDF prêt à envoyer.' },
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V3M10.5 3h3"/></svg>', title: 'Stock', desc: 'Produits, SKU, alertes stock faible, mouvements tracés.' },
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>', title: 'CRM Clients', desc: "Historique d'achats, balance, crédit client, documents." },
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>', title: 'Analytics', desc: 'Tableau de bord temps réel : CA, ventes, top produits.' },
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>', title: 'Multi-tenant sécurisé', desc: 'Isolation stricte des données par entreprise.' },
  { icon: '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>', title: 'API-first', desc: 'Architecture moderne, prête pour mobile et intégrations.' },
];

const secLabelRef = ref(null);
const secTitleRef = ref(null);
const secSubRef = ref(null);
const ctaFinalRef = ref(null);
const particlesRef = ref(null);

let observers = [];

onMounted(async () => {
  await nextTick();

  if (particlesRef.value) {
    const container = particlesRef.value;
    const isDark = document.documentElement.classList.contains('dark');
    const minO = isDark ? 0.25 : 0.1;
    const maxO = isDark ? 0.5 : 0.3;
    for (let i = 0; i < 40; i++) {
      const p = document.createElement('span');
      const size = 2 + Math.random() * 4;
      p.style.cssText = `position:absolute;width:${size}px;height:${size}px;left:${Math.random() * 100}%;top:${Math.random() * 100}%;border-radius:50%;background:rgba(5,150,105,${minO + Math.random() * (maxO - minO)});animation:particleFloat ${6 + Math.random() * 10}s ease-in-out infinite;animation-delay:${Math.random() * 5}s;pointer-events:none;`;
      container.appendChild(p);
    }
  }

  const observerOptions = { threshold: 0.15, rootMargin: '0px 0px -40px 0px' };
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, observerOptions);

  const els = [secLabelRef.value, secTitleRef.value, secSubRef.value, ctaFinalRef.value].filter(Boolean);
  els.forEach(el => observer.observe(el));

  els.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight) {
      el.classList.add('in-view');
      observer.unobserve(el);
    }
  });

  observers.push(observer);
});

onUnmounted(() => {
  observers.forEach(o => o.disconnect());
});
</script>

<style>
@keyframes bgShift {
  0% { transform: scale(1) rotate(0); }
  100% { transform: scale(1.1) rotate(2deg); }
}
@keyframes particleFloat {
  0%, 100% { transform: translate(0, 0); opacity: 0; }
  10% { opacity: 1; }
  50% { transform: translate(var(--dx, 30px), var(--dy, -30px)); opacity: 0.8; }
  90% { opacity: 1; }
}
@keyframes navSlide {
  from { opacity: 0; transform: translateY(-100%); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes wordIn {
  to { opacity: 1; transform: translateY(0) rotateX(0); }
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(24px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes ctaPulseLight {
  0%, 100% { box-shadow: 0 0 20px rgba(5,150,105,0.2); }
  50% { box-shadow: 0 0 50px rgba(5,150,105,0.45); }
}
@keyframes ctaPulseDark {
  0%, 100% { box-shadow: 0 0 20px rgba(52,211,153,0.25); }
  50% { box-shadow: 0 0 50px rgba(52,211,153,0.55); }
}
@keyframes mockupFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-12px); }
}
@keyframes cardIn {
  to { opacity: 1; transform: translateY(0); }
}

.bg-gradient-layer {
  background:
    radial-gradient(ellipse 80% 60% at 50% -20%, rgba(5,150,105,0.08), transparent),
    radial-gradient(ellipse 50% 40% at 80% 80%, rgba(16,185,129,0.06), transparent),
    radial-gradient(ellipse 40% 50% at 20% 60%, rgba(52,211,153,0.05), transparent);
}
.dark .bg-gradient-layer {
  background:
    radial-gradient(ellipse 80% 60% at 50% -20%, rgba(5,150,105,0.22), transparent),
    radial-gradient(ellipse 50% 40% at 80% 80%, rgba(16,185,129,0.16), transparent),
    radial-gradient(ellipse 40% 50% at 20% 60%, rgba(52,211,153,0.12), transparent);
}

.animate-ctaPulse {
  animation: ctaPulseLight 3s ease-in-out infinite;
}
.dark .animate-ctaPulse {
  animation-name: ctaPulseDark;
}

.particles span { --dx: 30px; --dy: -30px; }
.particles span:nth-child(odd) { --dx: -20px; --dy: 25px; }
.particles span:nth-child(3n) { --dx: 40px; --dy: -15px; }
.particles span:nth-child(5n+2) { --dx: -35px; --dy: -20px; }
</style>
