<template>
  <div class="auth-root">
    <!-- Left -->
    <div class="auth-left">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="grid-overlay"></div>
      <div class="auth-left-content">
        <router-link to="/" class="auth-logo">
          <AppLogo :size="40" />
          <div>
            <p class="auth-logo-name">SIDIBE CORPORATE</p>
            <p class="auth-logo-sub">Facturation &amp; Stock</p>
          </div>
        </router-link>

        <div class="left-icon-wrap">
          <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="24" cy="24" r="20" stroke="rgba(16,185,129,0.3)" stroke-width="1"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke="#10b981" d="M24 14v7m0 0l-3-3m3 3l3-3M16 24h4m8 0h4M24 31v3"/>
          </svg>
        </div>

        <h2 class="auth-left-title">Mot de passe<br><span>oublié ?</span></h2>
        <p class="auth-left-desc">Pas de panique ! Saisissez votre adresse email et nous vous envoyons un lien pour réinitialiser votre mot de passe.</p>

        <div class="how-works">
          <p class="how-title">Comment ça marche :</p>
          <ol class="how-steps">
            <li>Entrez votre adresse email</li>
            <li>Vérifiez votre boîte de réception</li>
            <li>Cliquez sur le lien reçu</li>
            <li>Définissez un nouveau mot de passe</li>
          </ol>
        </div>
      </div>
    </div>

    <!-- Right -->
    <div class="auth-right">
      <button @click="toggleTheme" class="auth-theme-btn" :title="isDark ? 'Mode clair' : 'Mode sombre'" aria-label="Basculer le thème">
        <svg v-if="isDark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
      </button>
      <div class="auth-mobile-logo">
        <router-link to="/" class="auth-logo">
          <AppLogo :size="40" />
          <span class="auth-logo-name">SIDIBE CORPORATE</span>
        </router-link>
      </div>

      <div class="auth-card">
        <!-- Success state -->
        <transition name="fade-scale" mode="out-in">
          <div v-if="success" key="success" class="success-state">
            <div class="success-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
              </svg>
            </div>
            <h2 class="success-title">Email envoyé !</h2>
            <p class="success-msg">{{ success }}</p>
            <p class="success-hint">Vérifiez aussi vos spams si vous ne voyez pas l'email.</p>
            <router-link :to="{ name: 'Login' }" class="auth-submit-btn" style="text-decoration:none;display:flex;margin-top:8px">
              <span class="btn-inner">Retour à la connexion</span>
            </router-link>
          </div>

          <!-- Form state -->
          <div v-else key="form">
            <div class="auth-card-header">
              <div class="forgot-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
              </div>
              <h1 class="auth-card-title">Réinitialiser</h1>
              <p class="auth-card-sub">Entrez votre email pour recevoir le lien</p>
            </div>

            <transition name="slide-down">
              <div v-if="error" class="auth-alert auth-alert-error">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                {{ error }}
              </div>
            </transition>

            <form @submit.prevent="submit" class="auth-form">
              <div class="field-group">
                <label class="field-label">Adresse email <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'email' }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z"/><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z"/></svg>
                  </div>
                  <input
                    v-model="email"
                    type="email" required autocomplete="email"
                    placeholder="vous@exemple.com"
                    class="field-input"
                    @focus="focused = 'email'"
                    @blur="focused = ''"
                  />
                </div>
              </div>

              <button type="submit" :disabled="loading || !email" class="auth-submit-btn">
                <span v-if="loading" class="btn-loader">
                  <svg class="spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" opacity="0.3"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="15" stroke-linecap="round"/></svg>
                  Envoi en cours...
                </span>
                <span v-else class="btn-inner">
                  <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 005.135 9.25h6.115a.75.75 0 010 1.5H5.135a1.5 1.5 0 00-1.442 1.086l-1.414 4.926a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z"/></svg>
                  Envoyer le lien
                </span>
              </button>
            </form>
          </div>
        </transition>

        <p class="auth-switch-text">
          <router-link :to="{ name: 'Login' }" class="auth-switch-link">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd"/></svg>
            Retour à la connexion
          </router-link>
        </p>
      </div>

      <div class="auth-footer">
        <span>© 2026 SIDIBE CORPORATE</span>
        <span class="auth-footer-sep">·</span>
        <a href="#">Aide</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useTheme } from '../../composables/useTheme';
import AppLogo from '../../Components/AppLogo.vue';

const email = ref('');
const loading = ref(false);
const error = ref('');
const success = ref('');
const { setTheme } = useTheme();
const isDark = ref(false);
onMounted(() => { isDark.value = document.documentElement.classList.contains('dark'); });
function toggleTheme() {
  isDark.value = !isDark.value;
  setTheme(isDark.value ? 'dark' : 'light');
}
const focused = ref('');

async function submit() {
  loading.value = true;
  error.value = '';
  success.value = '';
  try {
    const { data } = await axios.post('/auth/forgot-password', { email: email.value });
    success.value = data.message || 'Un lien de réinitialisation a été envoyé à votre adresse email.';
  } catch (err) {
    error.value = err.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.auth-root { display: flex; min-height: 100vh; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; background: #0a0a0a; }

/* LEFT */
.auth-left { position: relative; width: 480px; flex-shrink: 0; overflow: hidden; display: flex; align-items: center; background: linear-gradient(160deg, #0d1117 0%, #0f1f18 50%, #0a1510 100%); }
@media (max-width: 1024px) { .auth-left { display: none; } }
.orb { position: absolute; border-radius: 50%; pointer-events: none; filter: blur(70px); animation: orbDrift 18s ease-in-out infinite alternate; }
.orb-1 { width: 450px; height: 450px; top: -150px; left: -150px; background: radial-gradient(circle, rgba(16,185,129,0.3), transparent 70%); }
.orb-2 { width: 350px; height: 350px; bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(5,150,105,0.2), transparent 70%); animation-delay: -8s; }
@keyframes orbDrift { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(20px,30px) scale(1.07)} }
.grid-overlay { position: absolute; inset: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black, transparent); }
.auth-left-content { position: relative; z-index: 10; padding: 48px; display: flex; flex-direction: column; height: 100%; justify-content: center; }
.auth-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; margin-bottom: 40px; }
.auth-logo-icon { width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 16px rgba(16,185,129,0.4); }
.auth-logo-icon svg { width: 20px; height: 20px; }
.auth-logo-name { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.auth-logo-sub { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 1px; }
.left-icon-wrap { width: 80px; height: 80px; border-radius: 20px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.15); display: flex; align-items: center; justify-content: center; margin-bottom: 24px; }
.left-icon-wrap svg { width: 48px; height: 48px; }
.auth-left-title { font-size: 40px; font-weight: 900; color: #fff; line-height: 1.1; letter-spacing: -0.04em; margin: 0 0 16px; }
.auth-left-title span { background: linear-gradient(135deg, #10b981, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.auth-left-desc { font-size: 14px; color: rgba(255,255,255,0.5); line-height: 1.65; margin-bottom: 32px; max-width: 340px; }
.how-works { padding: 20px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07); border-radius: 14px; }
.how-title { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.6); margin-bottom: 12px; }
.how-steps { padding-left: 16px; margin: 0; display: flex; flex-direction: column; gap: 8px; }
.how-steps li { font-size: 13px; color: rgba(255,255,255,0.5); }
.how-steps li::marker { color: #10b981; }

/* RIGHT */
.auth-right { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; background: #fafafa; position: relative; }
.auth-mobile-logo { display: none; margin-bottom: 32px; }
@media (max-width: 1024px) { .auth-mobile-logo { display: flex; } }
.auth-card { width: 100%; max-width: 420px; background: #fff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 20px 60px rgba(0,0,0,0.06); padding: 40px; }
.auth-card-header { text-align: center; margin-bottom: 28px; }
.forgot-icon { width: 56px; height: 56px; border-radius: 16px; background: rgba(16,185,129,0.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: #10b981; }
.forgot-icon svg { width: 28px; height: 28px; }
.auth-card-title { font-size: 24px; font-weight: 900; color: #0f0f0f; letter-spacing: -0.03em; margin: 0 0 6px; }
.auth-card-sub { font-size: 13px; color: #9ca3af; }

/* Success */
.success-state { text-align: center; }
.success-icon { width: 72px; height: 72px; border-radius: 20px; background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(16,185,129,0.06)); border: 1px solid rgba(16,185,129,0.2); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #10b981; }
.success-icon svg { width: 36px; height: 36px; }
.success-title { font-size: 22px; font-weight: 900; color: #0f0f0f; margin: 0 0 12px; letter-spacing: -0.03em; }
.success-msg { font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 8px; }
.success-hint { font-size: 12px; color: #9ca3af; margin-bottom: 24px; }

/* Transitions */
.fade-scale-enter-active, .fade-scale-leave-active { transition: all 0.4s cubic-bezier(0.16,1,0.3,1); }
.fade-scale-enter-from { opacity: 0; transform: scale(0.96); }
.fade-scale-leave-to { opacity: 0; transform: scale(1.02); }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s cubic-bezier(0.16,1,0.3,1); }
.slide-down-enter-from { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to { opacity: 0; }

.auth-alert { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; font-size: 13px; font-weight: 500; margin-bottom: 20px; }
.auth-alert svg { width: 16px; height: 16px; flex-shrink: 0; }
.auth-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

.auth-form { display: flex; flex-direction: column; gap: 18px; }
.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 13px; font-weight: 600; color: #374151; }
.field-req { color: #ef4444; margin-left: 2px; }
.field-wrap { display: flex; align-items: center; border: 1.5px solid #e5e7eb; border-radius: 12px; background: #f9fafb; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s, background 0.2s; }
.field-wrap.field-focused { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.12); background: #fff; }
.field-icon { width: 44px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex-shrink: 0; }
.field-icon svg { width: 16px; height: 16px; }
.field-focused .field-icon { color: #10b981; }
.field-input { flex: 1; min-width: 0; padding: 12px 12px 12px 0; font-size: 14px; color: #0f0f0f; background: transparent; border: none; outline: none; font-family: inherit; }
.field-input::placeholder { color: #d1d5db; }

.auth-submit-btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 14px; border-radius: 12px; border: none; cursor: pointer; font-size: 15px; font-weight: 700; color: white; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(5,150,105,0.35); transition: all 0.25s cubic-bezier(0.16,1,0.3,1); position: relative; overflow: hidden; }
.auth-submit-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #34d399, #10b981); opacity: 0; transition: opacity 0.3s; }
.auth-submit-btn:hover:not(:disabled)::before { opacity: 1; }
.auth-submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.45); }
.auth-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-inner, .btn-loader { display: flex; align-items: center; justify-content: center; gap: 8px; position: relative; z-index: 1; }
.btn-inner svg, .btn-loader svg { width: 18px; height: 18px; }
.spin { animation: spinAnim 0.8s linear infinite; }
@keyframes spinAnim { to { transform: rotate(360deg); } }

.auth-switch-text { text-align: center; font-size: 13px; color: #9ca3af; margin-top: 24px; }
.auth-switch-link { font-weight: 700; color: #059669; text-decoration: none; transition: color 0.2s; display: inline-flex; align-items: center; gap: 4px; }
.auth-switch-link svg { width: 14px; height: 14px; }
.auth-switch-link:hover { color: #047857; }

.auth-footer { position: absolute; bottom: 24px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px; align-items: center; font-size: 12px; color: #9ca3af; }
.auth-footer a { color: #9ca3af; text-decoration: none; transition: color 0.2s; }
.auth-footer a:hover { color: #374151; }
.auth-footer-sep { opacity: 0.4; }
</style>
