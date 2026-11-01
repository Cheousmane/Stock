<template>
  <div class="auth-root">
    <!-- ── Left Panel ── -->
    <div class="auth-left">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
      <div class="grid-overlay"></div>

      <div class="auth-left-content">
        <router-link to="/" class="auth-logo">
          <AppLogo :size="40" />
          <div>
            <p class="auth-logo-name">SIDIBE CORPORATE</p>
            <p class="auth-logo-sub">Facturation &amp; Stock</p>
          </div>
        </router-link>

        <h2 class="auth-left-title">Créez votre<br><span>entreprise</span></h2>
        <p class="auth-left-desc">Rejoignez +500 entreprises africaines qui gèrent leur business avec SIDIBE CORPORATE.</p>

        <!-- Steps progress -->
        <div class="left-steps">
          <div class="left-step active">
            <div class="ls-num">
              <span>1</span>
            </div>
            <div class="ls-text">
              <p class="ls-title">Votre compte</p>
              <p class="ls-sub">Nom, email, mot de passe</p>
            </div>
          </div>
        </div>

        <!-- Testimonial -->
        <div class="left-testimonial">
          <div class="test-avatar">M</div>
          <div>
            <p class="test-quote">"En 5 minutes, ma boutique était configurée et ma première facture envoyée."</p>
            <p class="test-author">Moussa D. — Commerce général, Dakar</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right Panel ── -->
    <div class="auth-right">
      <div class="auth-theme-toggle">
        <ThemeToggle button-class="auth-theme-btn" />
      </div>
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
          <h1 class="auth-card-title">Créer votre compte</h1>
          <p class="auth-card-sub">Quelques infos pour commencer — 14 jours d'essai gratuit</p>
        </div>

        <!-- Error alert -->
        <transition name="slide-down">
          <div v-if="error" class="auth-alert auth-alert-error">
            <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            {{ error }}
          </div>
        </transition>

        <form @submit.prevent="register">
          <div class="auth-form">
              <template v-if="googleConfigured && !googleIdToken">
                <GoogleSignInButton @credential="onGoogleCredential" @error="onGoogleError" />
                <div class="auth-divider"><span>ou</span></div>
              </template>
              <div v-else-if="googleIdToken" class="google-linked">
                <img v-if="googleAvatar" :src="googleAvatar" alt="" class="google-linked-avatar" referrerpolicy="no-referrer" />
                <span class="google-linked-text">Inscription avec {{ form.email }}</span>
                <button type="button" class="google-linked-cancel" @click="cancelGoogle" title="Annuler">✕</button>
              </div>
              <!-- Name -->
              <div class="field-group">
                <label class="field-label">Nom de l'entreprise <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'name', 'field-error': errors.name }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 16.5v-13h-.25a.75.75 0 010-1.5h12.5a.75.75 0 010 1.5H16v13h.25a.75.75 0 010 1.5h-3.5a.75.75 0 010-1.5H13v-2.25a.75.75 0 01.75-.75h1.5a.75.75 0 01.75.75v2.25h.25a.75.75 0 010 1.5H4.75a.75.75 0 010-1.5H5zm5.75-11h-1.5a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5zM9.5 8.5a.75.75 0 01.75-.75h.5a.75.75 0 010 1.5h-.5A.75.75 0 019.5 8.5zm.75 2.25a.75.75 0 000 1.5h.5a.75.75 0 000-1.5h-.5z" clip-rule="evenodd"/></svg>
                  </div>
                  <input v-model="form.name" type="text" required placeholder="SARL Exemple" class="field-input" @focus="focused='name'" @blur="focused=''" />
                </div>
                <p v-if="errors.name" class="field-error-msg">{{ errors.name[0] }}</p>
              </div>

              <!-- Company slug -->
              <div class="field-group">
                <label class="field-label">Domaine de l'entreprise <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'slug', 'field-error': errors.company_slug }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.493 2.853a.75.75 0 00-1.486-.205L7.545 6H4.198a.75.75 0 000 1.5h3.14l-.53 3.013a.75.75 0 001.486.205l.499-2.836A12.627 12.627 0 0112 8.25a12.627 12.627 0 013.207.411l.499 2.836a.75.75 0 001.486-.205l-.53-3.013h3.14a.75.75 0 000-1.5h-3.347l-.462-3.147a.75.75 0 00-1.486.205l.462 3.147H9.493l-.462-3.147zM12 15a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
                  </div>
                  <input v-model="form.company_slug" type="text" required placeholder="mon-entreprise" class="field-input" @input="slugTouched = true" @focus="focused='slug'" @blur="focused=''" />
                </div>
                <p v-if="errors.company_slug" class="field-error-msg">{{ errors.company_slug[0] }}</p>
              </div>

              <!-- Email -->
              <div class="field-group">
                <label class="field-label">Email <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'email', 'field-error': errors.email }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z"/><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z"/></svg>
                  </div>
                  <input v-model="form.email" type="email" required placeholder="vous@exemple.com" class="field-input" :disabled="!!googleIdToken" @focus="focused='email'" @blur="focused=''" />
                </div>
                <p v-if="errors.email" class="field-error-msg">{{ errors.email[0] }}</p>
              </div>

              <!-- Industry + Size row -->
              <div class="field-row">
                <div class="field-group">
                  <label class="field-label">Secteur</label>
                  <div class="field-wrap select-wrap" :class="{ 'field-focused': focused === 'industry' }">
                    <select v-model="form.industry" class="field-input field-select" @focus="focused='industry'" @blur="focused=''">
                      <option value="">Choisir...</option>
                      <option v-for="s in industryOptions" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                </div>
                <div class="field-group">
                  <label class="field-label">Taille</label>
                  <div class="field-wrap select-wrap" :class="{ 'field-focused': focused === 'size' }">
                    <select v-model="form.size" class="field-input field-select" @focus="focused='size'" @blur="focused=''">
                      <option value="">Taille...</option>
                      <option value="petite">Petite (1-9)</option>
                      <option value="moyenne">Moyenne (10-49)</option>
                      <option value="grande">Grande (50+)</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Password -->
              <div v-if="!googleIdToken" class="field-group">
                <label class="field-label">Mot de passe <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'pwd', 'field-error': errors.password }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                  </div>
                  <input v-model="form.password" :type="showPwd ? 'text' : 'password'" required placeholder="8 caractères minimum" class="field-input" @focus="focused='pwd'" @blur="focused=''" />
                  <button type="button" class="field-toggle" @click="showPwd = !showPwd">
                    <svg v-if="!showPwd" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41z" clip-rule="evenodd"/></svg>
                    <svg v-else viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd"/><path d="M10.748 13.93l2.523 2.524a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z"/></svg>
                  </button>
                </div>
                <!-- Password strength -->
                <div class="pwd-strength" v-if="form.password">
                  <div class="pwd-bars">
                    <div v-for="n in 4" :key="n" class="pwd-bar" :class="pwdStrength >= n ? `pwd-lvl-${pwdStrength}` : ''"></div>
                  </div>
                  <span class="pwd-label" :class="`pwd-lvl-${pwdStrength}-txt`">{{ pwdLabels[pwdStrength - 1] || '' }}</span>
                </div>
              </div>

              <!-- Confirm password -->
              <div v-if="!googleIdToken" class="field-group">
                <label class="field-label">Confirmer le mot de passe <span class="field-req">*</span></label>
                <div class="field-wrap" :class="{ 'field-focused': focused === 'confirm', 'field-error': form.password_confirmation && form.password !== form.password_confirmation }">
                  <div class="field-icon">
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd"/></svg>
                  </div>
                  <input v-model="form.password_confirmation" :type="showConfirm ? 'text' : 'password'" required placeholder="••••••••" class="field-input" @focus="focused='confirm'" @blur="focused=''" />
                  <button type="button" class="field-toggle" @click="showConfirm = !showConfirm">
                    <svg v-if="!showConfirm" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41z" clip-rule="evenodd"/></svg>
                    <svg v-else viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd"/><path d="M10.748 13.93l2.523 2.524a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z"/></svg>
                  </button>
                </div>
                <p v-if="form.password_confirmation && form.password !== form.password_confirmation" class="field-error-msg">Les mots de passe ne correspondent pas</p>
              </div>

              <button type="submit" :disabled="loading || !canGoNext" class="auth-submit-btn">
                <span v-if="loading" class="btn-loader">
                  <svg class="spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="40" opacity="0.3"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="15" stroke-linecap="round"/></svg>
                  Création...
                </span>
                <span v-else class="btn-inner">
                  Démarrer mon essai gratuit
                  <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd"/></svg>
                </span>
              </button>
          </div>
        </form>

        <p class="auth-switch-text">
          Déjà un compte ?
          <router-link :to="{ name: 'Login' }" class="auth-switch-link">Se connecter</router-link>
        </p>
      </div>

      <div class="auth-footer">
        <span>© 2026 SIDIBE CORPORATE</span>
        <span class="auth-footer-sep">·</span>
        <a href="#">Confidentialité</a>
        <span class="auth-footer-sep">·</span>
        <a href="#">CGU</a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { switchLocale } from '../../i18n';
import { industryOptions } from '../../utils/format';
import { isGoogleConfigured } from '../../utils/google';
import AppLogo from '../../Components/AppLogo.vue';
import GoogleSignInButton from '../../Components/GoogleSignInButton.vue';
import ThemeToggle from '../../Components/ThemeToggle.vue';

const router = useRouter();
const { t } = useI18n();

const loading = ref(false);
const error = ref('');
const errors = reactive({});
const showPwd = ref(false);
const showConfirm = ref(false);
const focused = ref('');
const currentStep = ref(1);
// Google OAuth state: id_token vérifié côté backend, pas de mot de passe.
const googleConfigured = isGoogleConfigured();
const googleIdToken = ref('');
const googleAvatar = ref('');
const form = reactive({
  name: '', email: '', password: '', password_confirmation: '',
  company_name: '', company_slug: '', industry: '', size: '', otherIndustry: ''
});
const slugTouched = ref(false);

function slugify(value) {
  return (value || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
}

watch(() => form.name, (name) => {
  if (!slugTouched.value) form.company_slug = slugify(name);
});

// Password strength
const pwdStrength = computed(() => {
  const p = form.password;
  if (!p) return 0;
  let s = 0;
  if (p.length >= 8) s++;
  if (/[A-Z]/.test(p)) s++;
  if (/[0-9]/.test(p)) s++;
  if (/[^A-Za-z0-9]/.test(p)) s++;
  return Math.max(1, s);
});
const pwdLabels = ['Très faible', 'Faible', 'Moyen', 'Fort'];

const canGoNext = computed(() => {
  if (googleIdToken.value) return !!(form.name && form.email);
  return form.name && form.email && form.password.length >= 8 &&
    form.password === form.password_confirmation;
});

function cancelGoogle() {
  googleIdToken.value = '';
  googleAvatar.value = '';
  form.name = '';
  form.email = '';
}

function onGoogleError() {
  error.value = t('auth.google_error');
}

async function onGoogleCredential(idToken) {
  loading.value = true;
  error.value = '';
  try {
    const { data: profile } = await axios.post('/auth/google/profile', { id_token: idToken });
    if (!profile.is_new) {
      // Compte existant (ou e-mail déjà inscrit) : connexion directe.
      const { data } = await axios.post('/auth/google', { id_token: idToken });
      localStorage.setItem('token', data.access_token);
      localStorage.setItem('user', JSON.stringify(data.user));
      if (data.user?.locale) switchLocale(data.user.locale);
      router.push({ name: 'Dashboard' });
      return;
    }
    // Nouveau compte : pré-remplissage, l'utilisateur complète l'entreprise.
    form.name = profile.name || '';
    form.email = profile.email || '';
    googleIdToken.value = idToken;
    googleAvatar.value = profile.avatar || '';
  } catch (err) {
    error.value = err.response?.data?.message || t('auth.google_error');
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  // Retour depuis Login avec un compte Google nouveau : reprise du flux.
  const pending = sessionStorage.getItem('google_id_token');
  if (pending) {
    sessionStorage.removeItem('google_id_token');
    await onGoogleCredential(pending);
  }
});

async function register() {
  loading.value = true;
  error.value = '';
  Object.keys(errors).forEach(k => delete errors[k]);
  try {
    form.company_name = form.company_name || form.name;
    form.company_slug = form.company_slug || form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    if (form.industry === 'Autre') form.industry = form.otherIndustry.trim() || 'Autre';
    if (googleIdToken.value) {
      // Compte Google : e-mail déjà vérifié, connexion directe après création.
      const payload = {
        id_token: googleIdToken.value,
        company_name: form.company_name,
        company_slug: form.company_slug,
        industry: form.industry || undefined,
        size: form.size || undefined,
      };
      const { data } = await axios.post('/auth/google', payload);
      localStorage.setItem('token', data.access_token);
      localStorage.setItem('user', JSON.stringify(data.user));
      if (data.user?.locale) switchLocale(data.user.locale);
      router.push({ name: 'Dashboard' });
      return;
    }
    const payload = { ...form };
    const { data } = await axios.post('/auth/register', payload);
    localStorage.setItem('user', JSON.stringify(data.user));
    if (data.user?.locale) switchLocale(data.user.locale);
    router.push({ name: 'VerifyEmail', query: { email: form.email, ...(data.email_sent === false ? { mail_failed: '1' } : {}) } });
  } catch (err) {
    if (err.response?.status === 422) Object.assign(errors, err.response.data.errors || {});
    error.value = err.response?.data?.message || t('auth.register_error');
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

.auth-root {
  display: flex; min-height: 100vh; min-height: 100dvh;
  font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
  background: #0a0a0a;
}

/* LEFT */
.auth-left {
  position: relative; width: 480px; flex-shrink: 0;
  overflow: hidden; display: flex; align-items: center;
  background: linear-gradient(160deg, #0d1117 0%, #0f1f18 50%, #0a1510 100%);
}
@media (max-width: 1024px) { .auth-left { display: none; } }
.orb { position: absolute; border-radius: 50%; pointer-events: none; filter: blur(70px); animation: orbDrift 18s ease-in-out infinite alternate; }
.orb-1 { width: 450px; height: 450px; top: -150px; left: -150px; background: radial-gradient(circle, rgba(16,185,129,0.3), transparent 70%); }
.orb-2 { width: 350px; height: 350px; bottom: -100px; right: -100px; background: radial-gradient(circle, rgba(5,150,105,0.2), transparent 70%); animation-delay: -8s; }
.orb-3 { width: 250px; height: 250px; top: 50%; left: 50%; transform: translate(-50%,-50%); background: radial-gradient(circle, rgba(52,211,153,0.12), transparent 70%); animation: orbDrift3 18s ease-in-out infinite alternate -14s; }
@keyframes orbDrift { 0%{transform:translate(0,0) scale(1)} 100%{transform:translate(20px,30px) scale(1.07)} }
@keyframes orbDrift3 { 0%{transform:translate(-50%,-50%) scale(1)} 100%{transform:translate(calc(-50% + 20px),calc(-50% + 30px)) scale(1.07)} }
.grid-overlay { position: absolute; inset: 0; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px); background-size: 48px 48px; mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black, transparent); }

.auth-left-content { position: relative; z-index: 10; padding: 48px; display: flex; flex-direction: column; height: 100%; justify-content: center; }
.auth-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; margin-bottom: 40px; }
.auth-logo-icon { width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 16px rgba(16,185,129,0.4); }
.auth-logo-icon svg { width: 20px; height: 20px; }
.auth-logo-name { font-size: 15px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.auth-logo-sub { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 1px; }
.auth-left-title { font-size: 40px; font-weight: 900; color: #fff; line-height: 1.1; letter-spacing: -0.04em; margin: 0 0 16px; }
.auth-left-title span { background: linear-gradient(135deg, #10b981, #34d399); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.auth-left-desc { font-size: 14px; color: rgba(255,255,255,0.5); line-height: 1.65; margin-bottom: 40px; }

/* Left steps */
.left-steps { display: flex; flex-direction: column; gap: 0; margin-bottom: 40px; }
.left-step { display: flex; gap: 14px; align-items: center; padding: 12px 0; opacity: 0.4; transition: opacity 0.3s; }
.left-step.active { opacity: 1; }
.ls-num { width: 32px; height: 32px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.6); flex-shrink: 0; }
.left-step.active .ls-num { border-color: #10b981; color: #10b981; }
.left-step.done .ls-num { background: #10b981; border-color: #10b981; color: white; }
.ls-num svg { width: 14px; height: 14px; }
.ls-title { font-size: 14px; font-weight: 600; color: #fff; }
.ls-sub { font-size: 12px; color: rgba(255,255,255,0.4); }
.ls-connector { width: 1px; height: 20px; background: rgba(255,255,255,0.1); margin: 0 0 0 16px; transition: background 0.3s; }
.ls-connector.done { background: rgba(16,185,129,0.5); }

/* Testimonial */
.left-testimonial { display: flex; gap: 14px; align-items: flex-start; padding: 16px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; }
.test-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #ef4444); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: white; flex-shrink: 0; }
.test-quote { font-size: 13px; color: rgba(255,255,255,0.7); line-height: 1.5; font-style: italic; margin-bottom: 6px; }
.test-author { font-size: 11px; color: rgba(255,255,255,0.35); }

/* RIGHT */
.auth-right { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 24px; background: #fafafa; position: relative; overflow-y: auto; }
.auth-mobile-logo { display: none; margin-bottom: 32px; }
@media (max-width: 1024px) { .auth-mobile-logo { display: flex; } }

.auth-card { width: 100%; max-width: 480px; background: #fff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 20px 60px rgba(0,0,0,0.06); padding: 40px; }

/* Mobile steps */
.mobile-steps { display: flex; align-items: center; margin-bottom: 28px; }
.ms-step { display: flex; align-items: center; gap: 8px; }
.ms-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #e5e7eb; background: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #9ca3af; transition: all 0.3s; }
.ms-step.active .ms-dot { border-color: #10b981; color: #10b981; }
.ms-step.done .ms-dot { background: #10b981; border-color: #10b981; color: white; }
.ms-dot svg { width: 12px; height: 12px; }
.ms-label { font-size: 12px; font-weight: 600; color: #9ca3af; }
.ms-step.active .ms-label { color: #10b981; }
.ms-bar { flex: 1; height: 2px; background: #e5e7eb; margin: 0 12px; transition: background 0.4s; }
.ms-bar.done { background: #10b981; }

.auth-card-header { text-align: center; margin-bottom: 28px; }
.auth-card-title { font-size: 24px; font-weight: 900; color: #0f0f0f; letter-spacing: -0.03em; margin: 0 0 6px; }
.auth-card-sub { font-size: 13px; color: #9ca3af; }

.auth-alert { display: flex; align-items: center; gap: 10px; padding: 12px 14px; border-radius: 12px; font-size: 13px; font-weight: 500; margin-bottom: 20px; }
.auth-alert svg { width: 16px; height: 16px; flex-shrink: 0; }
.auth-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s cubic-bezier(0.16,1,0.3,1); }
.slide-down-enter-from { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to { opacity: 0; transform: translateY(-4px); }

/* Step transition */
.step-slide-enter-active, .step-slide-leave-active { transition: all 0.35s cubic-bezier(0.16,1,0.3,1); }
.step-slide-enter-from { opacity: 0; transform: translateX(24px); }
.step-slide-leave-to { opacity: 0; transform: translateX(-24px); }

.auth-form { display: flex; flex-direction: column; gap: 18px; }
.field-group { display: flex; flex-direction: column; gap: 6px; }
.field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.field-label { font-size: 13px; font-weight: 600; color: #374151; }
.field-req { color: #ef4444; margin-left: 2px; }
.field-wrap { display: flex; align-items: center; border: 1.5px solid #e5e7eb; border-radius: 12px; background: #f9fafb; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s, background 0.2s; }
.field-wrap.field-focused { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.12); background: #fff; }
.field-wrap.field-error { border-color: #ef4444; }
.field-icon { width: 44px; display: flex; align-items: center; justify-content: center; color: #9ca3af; flex-shrink: 0; }
.field-icon svg { width: 16px; height: 16px; }
.field-focused .field-icon { color: #10b981; }
.field-input { flex: 1; min-width: 0; padding: 12px 12px 12px 0; font-size: 14px; color: #0f0f0f; background: transparent; border: none; outline: none; font-family: inherit; }
.field-input::placeholder { color: #d1d5db; }
.field-select { padding: 12px 8px 12px 0; appearance: none; cursor: pointer; }
.select-wrap::after { content: '▾'; position: absolute; right: 14px; color: #9ca3af; pointer-events: none; font-size: 12px; }
.select-wrap { position: relative; }
.field-toggle { padding: 0 14px; color: #9ca3af; background: none; border: none; cursor: pointer; transition: color 0.2s; height: 100%; display: flex; align-items: center; }
.field-toggle:hover { color: #374151; }
.field-toggle svg { width: 18px; height: 18px; }
.field-error-msg { font-size: 12px; color: #ef4444; font-weight: 500; }

/* Password strength */
.pwd-strength { display: flex; align-items: center; gap: 8px; }
.pwd-bars { display: flex; gap: 4px; flex: 1; }
.pwd-bar { flex: 1; height: 3px; border-radius: 99px; background: #e5e7eb; transition: background 0.3s; }
.pwd-lvl-1 { background: #ef4444; }
.pwd-lvl-2 { background: #f59e0b; }
.pwd-lvl-3 { background: #3b82f6; }
.pwd-lvl-4 { background: #10b981; }
.pwd-label { font-size: 11px; font-weight: 600; }
.pwd-lvl-1-txt { color: #ef4444; }
.pwd-lvl-2-txt { color: #f59e0b; }
.pwd-lvl-3-txt { color: #3b82f6; }
.pwd-lvl-4-txt { color: #10b981; }

/* Trial summary */
.trial-card {
  position: relative; padding: 26px 22px 20px; border-radius: 18px;
  border: 1.5px solid rgba(16,185,129,0.35);
  background: linear-gradient(160deg, #f0fdf4 0%, #ffffff 65%);
  text-align: center;
}
.trial-badge {
  position: absolute; top: -1px; left: 50%; transform: translateX(-50%);
  font-size: 11px; font-weight: 800; letter-spacing: 0.04em;
  padding: 4px 12px; border-radius: 0 0 9px 9px;
  background: linear-gradient(135deg, #10b981, #059669); color: #fff;
  white-space: nowrap;
}
.trial-icon {
  width: 52px; height: 52px; margin: 8px auto 12px; border-radius: 16px;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid rgba(16,185,129,0.25);
  color: #059669; box-shadow: 0 8px 20px rgba(16,185,129,0.12);
}
.trial-icon svg { width: 26px; height: 26px; }
.trial-title { font-size: 17px; font-weight: 800; color: #0f0f0f; letter-spacing: -0.02em; margin-bottom: 6px; }
.trial-desc { font-size: 12.5px; color: #6b7280; line-height: 1.6; margin-bottom: 16px; }
.trial-feats { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; text-align: left; }
.trial-feats li { display: flex; align-items: flex-start; gap: 8px; font-size: 12.5px; color: #374151; line-height: 1.45; }
.trial-feats svg { width: 15px; height: 15px; color: #10b981; flex-shrink: 0; margin-top: 1px; }

.trial-recap {
  margin-top: 14px; border: 1px solid #e5e7eb; border-radius: 14px;
  background: #f9fafb; overflow: hidden;
}
.trial-recap-row {
  display: flex; align-items: center; justify-content: space-between; gap: 16px;
  padding: 10px 14px; font-size: 12.5px;
}
.trial-recap-row + .trial-recap-row { border-top: 1px solid #eceef1; }
.trial-recap-row span { color: #9ca3af; font-weight: 500; flex-shrink: 0; }
.trial-recap-row strong { color: #0f0f0f; font-weight: 700; text-align: right; }

.trial-note { margin-top: 12px; font-size: 11.5px; color: #9ca3af; line-height: 1.5; text-align: center; }

@keyframes spin { to { transform: rotate(360deg); } }

.step2-actions { display: flex; gap: 10px; align-items: center; }
.btn-back { display: flex; align-items: center; gap: 6px; padding: 14px 18px; border-radius: 12px; border: 1.5px solid #e5e7eb; background: #fff; color: #374151; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
.btn-back svg { width: 16px; height: 16px; }
.btn-back:hover { border-color: #10b981; color: #10b981; }

/* Submit */
.auth-submit-btn { display: flex; align-items: center; justify-content: center; width: 100%; padding: 14px; border-radius: 12px; border: none; cursor: pointer; font-size: 15px; font-weight: 700; color: white; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(5,150,105,0.35); transition: all 0.25s cubic-bezier(0.16,1,0.3,1); position: relative; overflow: hidden; }
.auth-submit-btn::before { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, #34d399, #10b981); opacity: 0; transition: opacity 0.3s; }
.auth-submit-btn:hover:not(:disabled)::before { opacity: 1; }
.auth-submit-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(5,150,105,0.45); }
.auth-submit-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-inner, .btn-loader { display: flex; align-items: center; justify-content: center; gap: 8px; position: relative; z-index: 1; }
.btn-inner svg, .btn-loader svg { width: 18px; height: 18px; }
.spin { animation: spinAnim 0.8s linear infinite; }
@keyframes spinAnim { to { transform: rotate(360deg); } }
.flex-1 { flex: 1; }

.auth-switch-text { text-align: center; font-size: 13px; color: #9ca3af;
margin-top: 24px; }
.auth-divider { display: flex; align-items: center; gap: 12px; margin: 18px 0; color: #9ca3af; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
.google-linked { display: flex; align-items: center; gap: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 10px 12px; margin-bottom: 18px; font-size: 13px; color: #166534; }
.google-linked-avatar { width: 28px; height: 28px; border-radius: 50%; }
.google-linked-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.google-linked-cancel { border: none; background: transparent; cursor: pointer; color: #166534; font-size: 14px; line-height: 1; }
.auth-switch-link { font-weight: 700; color: #059669; text-decoration: none; transition: color 0.2s; }
.auth-switch-link:hover { color: #047857; }

.auth-footer { position: absolute; bottom: 24px; left: 0; right: 0; display: flex; justify-content: center; gap: 8px; align-items: center; font-size: 12px; color: #9ca3af; }
.auth-footer a { color: #9ca3af; text-decoration: none; transition: color 0.2s; }
.auth-footer a:hover { color: #374151; }
.auth-footer-sep { opacity: 0.4; }

@media (max-width: 640px) {
  .auth-right {
    justify-content: flex-start;
    padding: 28px 16px calc(28px + env(safe-area-inset-bottom));
  }
  .auth-mobile-logo { margin-bottom: 20px; }
  .auth-card { padding: 24px 20px; border-radius: 20px; }
  .auth-card-header { margin-bottom: 24px; }
  .auth-card-title { font-size: 21px; }
  .auth-form { gap: 16px; }
  .field-row { grid-template-columns: 1fr; }
  .field-input { font-size: 16px; }
  .trial-card { padding: 24px 18px 18px; }
  .trial-recap-row { flex-direction: column; align-items: flex-start; gap: 2px; }
  .trial-recap-row strong { text-align: left; }
  .step2-actions { flex-direction: column-reverse; align-items: stretch; }
  .btn-back { justify-content: center; }
  .auth-footer {
    position: static; margin-top: 24px; padding-bottom: env(safe-area-inset-bottom);
    flex-wrap: wrap; text-align: center;
  }
}

@media (max-width: 380px) {
  .auth-right { padding-left: 12px; padding-right: 12px; }
  .auth-card { padding: 20px 16px; }
  .auth-card-title { font-size: 19px; }
}
</style>
