<template>
  <div class="auth-root">
    <!-- ── Left Panel ── -->
    <div class="auth-left">
      <!-- Mesh orbs -->
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
      <!-- Grid lines -->
      <div class="grid-overlay"></div>

      <div class="auth-left-content">
        <!-- Logo -->
        <router-link to="/" class="auth-logo">
          <AppLogo :size="40" />
          <div>
            <p class="auth-logo-name">SIDIBE CORPORATE</p>
            <p class="auth-logo-sub">Facturation &amp; Stock</p>
          </div>
        </router-link>

        <h2 class="auth-left-title">Bienvenue,<br><span>connectez-vous</span></h2>
        <p class="auth-left-desc">Reprenez là où vous vous êtes arrêté. Vos factures, votre stock, tout vous attend.</p>

        <!-- Feature list -->
        <ul class="auth-features">
          <li v-for="(f, i) in features" :key="i">
            <div class="feat-check">
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
            </div>
            <span>{{ f }}</span>
          </li>
        </ul>

        <!-- Floating notification cards -->
        <div class="notif-stack">
          <div class="notif-card notif-1">
            <div class="notif-icon notif-green">
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
            </div>
            <div>
              <p class="notif-title">Paiement reçu</p>
              <p class="notif-sub">FAC-2024-248 · 125 000 FCFA</p>
            </div>
            <span class="notif-time">À l'instant</span>
          </div>
          <div class="notif-card notif-2">
            <div class="notif-icon notif-blue">
              <svg viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41z" clip-rule="evenodd"/></svg>
            </div>
            <div>
              <p class="notif-title">+12 clients actifs</p>
              <p class="notif-sub">Ce mois-ci</p>
            </div>
            <span class="notif-time">Aujourd'hui</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right Panel ── -->
    <div class="auth-right">
      <button @click="toggleTheme" class="auth-theme-btn" :title="isDark ? 'Mode clair' : 'Mode sombre'" aria-label="Basculer le thème">
        <svg v-if="isDark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" /></svg>
      </button>
      <!-- Mobile logo -->
      <div class="auth-mobile-logo">
        <router-link to="/" class="auth-logo">
          <AppLogo :size="40" />
          <span class="auth-logo-name">SIDIBE CORPORATE</span>
        </router-link>
      </div>

      <!-- Form card -->
      <div class="auth-card">
        <div class="auth-card-header">
          <h1 class="auth-card-title">Se connecter</h1>
          <p class="auth-card-sub">Accédez à votre espace de gestion</p>
        </div>

        <!-- Error alert -->
        <transition name="slide-down">
          <div v-if="error" class="auth-alert auth-alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            {{ error }}
          </div>
        </transition>

        <form @submit.prevent="login" class="auth-form">
          <template v-if="googleConfigured">
            <GoogleSignInButton text="signin_with" @credential="onGoogleCredential" @error="onGoogleError" />
            <div class="auth-divider"><span>ou</span></div>
          </template>
          <!-- Email -->
          <div class="field-group">
            <label class="field-label">Email <span class="field-req">*</span></label>
            <div class="field-wrap" :class="{ 'field-focused': focused === 'email' }">
              <div class="field-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z"/><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z"/></svg>
              </div>
              <input
                v-model="form.email"
                type="email" required autocomplete="email"
                placeholder="vous@exemple.com"
                list="login-email-list"
                class="field-input"
                @focus="focused = 'email'"
                @blur="focused = ''"
              />
              <datalist id="login-email-list">
                <option v-for="em in emailSuggestions" :key="em" :value="em" />
              </datalist>
            </div>
          </div>

          <!-- Password -->
          <div class="field-group">
            <div class="field-label-row">
              <label class="field-label">Mot de passe <span class="field-req">*</span></label>
              <router-link :to="{ name: 'ForgotPassword' }" class="forgot-link">Mot de passe Oublié ?</router-link>
            </div>
            <div class="field-wrap" :class="{ 'field-focused': focused === 'pwd' }">
              <div class="field-icon">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
              </div>
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required autocomplete="current-password"
                placeholder="••••••••"
                class="field-input"
                @focus="focused = 'pwd'"
                @blur="focused = ''"
              />
              <button type="button" class="field-toggle" @click="showPassword = !showPassword">
                <svg v-if="!showPassword" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41z" clip-rule="evenodd"/></svg>
                <svg v-else viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd"/><path d="M10.748 13.93l2.523 2.524a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z"/></svg>
              </button>
            </div>
          </div>

          <!-- Remember -->
          <div class="field-remember">
            <label class="remember-label">
              <input type="checkbox" v-model="form.remember" class="remember-check" />
              <span class="remember-box"></span>
              <span class="remember-text">Se souvenir de moi</span>
            </label>
          </div>

          <!-- Submit -->
          <button type="submit" :disabled="loading" class="auth-submit-btn">
            <span v-if="loading" class="btn-loader">
              <svg class="spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" stroke-linecap="round" opacity="0.3"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="15" stroke-dashoffset="0" stroke-linecap="round"/></svg>
              Connexion en cours...
            </span>
            <span v-else class="btn-inner">
              Se connecter
              <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
            </span>
          </button>
        </form>

        <p class="auth-switch-text">
          Pas encore de compte ?
          <router-link :to="{ name: 'Register' }" class="auth-switch-link">Créer un compte</router-link>
        </p>
      </div>

      <!-- Bottom bar -->
      <div class="auth-footer">
        <span>© 2026 SIDIBE CORPORATE</span>
        <span class="auth-footer-sep">·</span>
        <a href="#">Confidentialité</a>
        <span class="auth-footer-sep">·</span>
        <a href="#">Aide</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { switchLocale } from '../../i18n';
import { useSuggestions } from '../../composables/useSuggestions';
import { useTheme } from '../../composables/useTheme';
import { isGoogleConfigured } from '../../utils/google';
import AppLogo from '../../Components/AppLogo.vue';
import GoogleSignInButton from '../../Components/GoogleSignInButton.vue';

const REMEMBER_EMAIL_KEY = 'remembered_email';
const { saveValue: saveEmailSuggestion } = useSuggestions('login_email');
const router = useRouter();
const { t } = useI18n();

const loading = ref(false);
const error = ref('');
const { setTheme } = useTheme();
const isDark = ref(false);
onMounted(() => { isDark.value = document.documentElement.classList.contains('dark'); });
function toggleTheme() {
  isDark.value = !isDark.value;
  setTheme(isDark.value ? 'dark' : 'light');
}
const showPassword = ref(false);
const focused = ref('');
const form = reactive({ email: '', password: '', remember: false });
const googleConfigured = isGoogleConfigured();

const features = [
  'Factures et devis professionnels en un clic',
  'Gestion de stock multi-entrepôts en temps réel',
  'Point de vente avec sessions de caisse',
  'Rapports financiers et tableau de bord analytique',
];

const emailSuggestions = computed(() => {
  const all = JSON.parse(localStorage.getItem('form_suggestions') || '{}');
  return all.login_email || [];
});

onMounted(() => {
  const saved = localStorage.getItem(REMEMBER_EMAIL_KEY);
  if (saved) { form.email = saved; form.remember = true; }
  const suspended = sessionStorage.getItem('account_suspended');
  if (suspended) {
    error.value = suspended;
    sessionStorage.removeItem('account_suspended');
  }
});

function onGoogleError() {
  error.value = t('auth.google_error');
}

async function onGoogleCredential(idToken) {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/auth/google', { id_token: idToken });
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    if (data.user?.locale) switchLocale(data.user.locale);
    router.push({ name: 'Dashboard' });
  } catch (err) {
    // Nouveau compte Google sans entreprise : bascule vers l'inscription pré-remplie.
    if (err.response?.status === 422 && err.response?.data?.errors?.company_name) {
      sessionStorage.setItem('google_id_token', idToken);
      router.push({ name: 'Register' });
      return;
    }
    error.value = err.response?.data?.message || t('auth.google_error');
  } finally {
    loading.value = false;
  }
}

async function login() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.post('/auth/login', form);
    saveEmailSuggestion(form.email);
    if (form.remember) localStorage.setItem(REMEMBER_EMAIL_KEY, form.email);
    else localStorage.removeItem(REMEMBER_EMAIL_KEY);
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user));
    if (data.user?.locale) switchLocale(data.user.locale);
    router.push({ name: 'Dashboard' });
  } catch (err) {
    const errors = err.response?.data?.errors;
    if (errors?.email_verification) {
      await axios.post('/auth/resend-verification', { email: form.email }).catch(() => {});
      router.push({ name: 'VerifyEmail', query: { email: form.email } });
      return;
    }
    error.value = err.response?.data?.message || t('auth.invalid_credentials');
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

/* ── Root ── */
.auth-root {
  display: flex; min-height: 100vh;
  font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
  background: #0a0a0a;
}

/* ── LEFT PANEL ── */
.auth-left {
  position: relative; width: 480px; flex-shrink: 0;
  overflow: hidden; display: flex; align-items: center;
  background: linear-gradient(160deg, #0d1117 0%, #0f1f18 50%, #0a1510 100%);
}
@media (max-width: 1024px) { .auth-left { display: none; } }

/* Orbs */
.orb {
  position: absolute; border-radius: 50%; pointer-events: none;
  filter: blur(70px); animation: orbDrift 18s ease-in-out infinite alternate;
}
.orb-1 { width: 450px; height: 450px; top: -150px; left: -150px; background: radial-gradient(circle, rgba(16,185,129,0.3), transparent 70%); }
.orb-2 { width: 350px; height: 350px; bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(5,150,105,0.2), transparent 70%); animation-delay: -8s; }
.orb-3 { width: 250px; height: 250px; top: 50%; left: 50%; transform: translate(-50%,-50%); background: radial-gradient(circle, rgba(52,211,153,0.12), transparent 70%); animation-delay: -14s; }
@keyframes orbDrift { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(20px,30px) scale(1.07); } }
.orb-3 { animation: orbDrift3 18s ease-in-out infinite alternate -14s; }
@keyframes orbDrift3 { 0% { transform: translate(-50%,-50%) scale(1); } 100% { transform: translate(calc(-50% + 20px), calc(-50% + 30px)) scale(1.07); } }

/* Grid overlay */
.grid-overlay {
  position: absolute; inset: 0; pointer-events: none;
  background-image:
    linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
  background-size: 48px 48px;
  mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black, transparent);
}

.auth-left-content {
  position: relative; z-index: 10; padding: 48px 48px;
  display: flex; flex-direction: column; height: 100%;
  justify-content: center;
}

.auth-logo {
  display: flex; align-items: center; gap: 12px; text-decoration: none; margin-bottom: 48px;
}
.auth-logo-icon {
  width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
  background: linear-gradient(135deg, #10b981, #059669);
  display: flex; align-items: center; justify-content: center; color: white;
  box-shadow: 0 4px 16px rgba(16,185,129,0.4);
}
.auth-logo-icon svg { width: 20px; height: 20px; }
.auth-logo-name { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.auth-logo-sub { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 1px; }

.auth-left-title {
  font-size: 40px; font-weight: 900; color: #fff; line-height: 1.1;
  letter-spacing: -0.04em; margin: 0 0 16px;
}
.auth-left-title span {
  background: linear-gradient(135deg, #10b981, #34d399);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.auth-left-desc { font-size: 15px; color: rgba(255,255,255,0.5); line-height: 1.65; margin-bottom: 40px; max-width: 340px; }

.auth-features { list-style: none; padding: 0; margin: 0 0 auto; display: flex; flex-direction: column; gap: 14px; }
.auth-features li { display: flex; align-items: center; gap: 12px; font-size: 14px; color: rgba(255,255,255,0.65); }
.feat-check { width: 22px; height: 22px; border-radius: 6px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.25); flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #10b981; }
.feat-check svg { width: 12px; height: 12px; }

/* Notification cards */
.notif-stack { margin-top: 40px; display: flex; flex-direction: column; gap: 10px; }
.notif-card {
  display: flex; align-items: center; gap: 12px; padding: 12px 14px;
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
  border-radius: 14px; backdrop-filter: blur(16px);
}
.notif-1 { animation: notifFloat 5s ease-in-out infinite; }
.notif-2 { animation: notifFloat 5s ease-in-out infinite -2.5s; }
@keyframes notifFloat { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
.notif-icon { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.notif-icon svg { width: 16px; height: 16px; }
.notif-green { background: rgba(16,185,129,0.15); color: #34d399; }
.notif-blue { background: rgba(59,130,246,0.15); color: #60a5fa; }
.notif-title { font-size: 13px; font-weight: 600; color: #fff; }
.notif-sub { font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 1px; }
.notif-time { margin-left: auto; font-size: 11px; color: rgba(255,255,255,0.3); white-space: nowrap; }

/* ── RIGHT PANEL ── */
.auth-right {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  justify-content: center; padding: 40px 24px;
  background: #fafafa;
  position: relative;
}

.auth-mobile-logo {
  display: none; margin-bottom: 32px;
}
.auth-mobile-logo .auth-logo-name { color: #0f0f0f; }
.auth-mobile-logo .auth-logo-sub { color: #9ca3af; }
@media (max-width: 1024px) { .auth-mobile-logo { display: flex; } }

/* Card */
.auth-card {
  width: 100%; max-width: 420px;
  background: #fff; border-radius: 24px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 20px 60px rgba(0,0,0,0.06);
  padding: 40px;
}

.auth-card-header { text-align: center; margin-bottom: 32px; }
.auth-card-title { font-size: 26px; font-weight: 900; color: #0f0f0f; letter-spacing: -0.03em; margin: 0 0 6px; }
.auth-card-sub { font-size: 14px; color: #9ca3af; }

/* Alert */
.auth-alert {
  display: flex; align-items: center; gap: 10px; padding: 12px 14px;
  border-radius: 12px; font-size: 13px; font-weight: 500; margin-bottom: 20px;
}
.auth-alert svg { width: 16px; height: 16px; flex-shrink: 0; }
.auth-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* Transition */
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s cubic-bezier(0.16,1,0.3,1); }
.slide-down-enter-from { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to { opacity: 0; transform: translateY(-4px); }

/* Form */
.auth-form { display: flex; flex-direction: column; gap: 20px; }

.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 13px; font-weight: 600; color: #374151; }
.field-label-row { display: flex; justify-content: space-between; align-items: center; }
.field-req { color: #ef4444; margin-left: 2px; }
.forgot-link { font-size: 12px; font-weight: 600; color: #059669; text-decoration: none; transition: color 0.2s; }
.forgot-link:hover { color: #047857; }

.field-wrap {
  display: flex; align-items: center; gap: 0;
  border: 1.5px solid #e5e7eb; border-radius: 12px;
  background: #f9fafb; overflow: hidden;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
}
.field-wrap.field-focused { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.12); background: #fff; }
.field-icon { width: 44px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex-shrink: 0; }
.field-icon svg { width: 16px; height: 16px; }
.field-focused .field-icon { color: #10b981; }
.field-input {
  flex: 1; min-width: 0; padding: 12px 12px 12px 0; font-size: 14px;
  color: #0f0f0f; background: transparent; border: none; outline: none;
  font-family: inherit;
}
.field-input::placeholder { color: #d1d5db; }
.field-toggle {
  padding: 0 14px; color: #9ca3af; background: none; border: none;
  cursor: pointer; transition: color 0.2s; height: 100%; display: flex; align-items: center;
}
.field-toggle:hover { color: #374151; }
.field-toggle svg { width: 18px; height: 18px; }

/* Remember */
.field-remember { display: flex; align-items: center; }
.remember-label { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.remember-check { position: absolute; opacity: 0; width: 0; height: 0; }
.remember-box {
  width: 18px; height: 18px; border-radius: 5px; flex-shrink: 0;
  border: 1.5px solid #d1d5db; background: #fff; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center;
}
.remember-check:checked + .remember-box { background: #10b981; border-color: #10b981; }
.remember-check:checked + .remember-box::after {
  content: ''; display: block;
  width: 10px; height: 6px;
  border-left: 2px solid white; border-bottom: 2px solid white;
  transform: rotate(-45deg) translate(1px, -1px);
}
.remember-text { font-size: 13px; color: #6b7280; }

/* Submit button */
.auth-submit-btn {
  width: 100%; padding: 14px; border-radius: 12px; border: none; cursor: pointer;
  font-size: 15px; font-weight: 700; color: white;
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 4px 14px rgba(5,150,105,0.35);
  transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
  position: relative; overflow: hidden;
}
.auth-submit-btn::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(135deg, #34d399, #10b981);
  opacity: 0; transition: opacity 0.3s;
}
.auth-submit-btn:hover:not(:disabled)::before { opacity: 1; }
.auth-submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.45); }
.auth-submit-btn:active:not(:disabled) { transform: translateY(0); }
.auth-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-inner, .btn-loader { display: flex; align-items: center; justify-content: center; gap: 8px; position: relative; z-index: 1; }
.btn-inner svg, .btn-loader svg { width: 18px; height: 18px; }
.spin { animation: spinAnim 0.8s linear infinite; }
@keyframes spinAnim { to { transform: rotate(360deg); } }

.auth-switch-text { text-align: center; font-size: 13px; color: #9ca3af; margin-top: 24px; }
.auth-divider { display: flex; align-items: center; gap: 12px; margin: 18px 0; color: #9ca3af; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
.auth-switch-link { font-weight: 700; color: #059669; text-decoration: none; transition: color 0.2s; }
.auth-switch-link:hover { color: #047857; }

/* Footer */
.auth-footer {
  position: absolute; bottom: 24px; left: 0; right: 0;
  display: flex; justify-content: center; gap: 8px; align-items: center;
  font-size: 12px; color: #9ca3af;
}
.auth-footer a { color: #9ca3af; text-decoration: none; transition: color 0.2s; }
.auth-footer a:hover { color: #374151; }
.auth-footer-sep { opacity: 0.4; }

/* Dark mode is driven by the app theme (html.dark) — see app.css */
</style>
