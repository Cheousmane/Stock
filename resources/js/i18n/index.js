import { createI18n } from 'vue-i18n';
import axios from 'axios';

export const SUPPORTED_LOCALES = ['fr', 'en'];

function normalizeLocale(locale) {
  return SUPPORTED_LOCALES.includes(locale) ? locale : 'fr';
}

const savedLocale = normalizeLocale(localStorage.getItem('locale') || 'fr');

// Create i18n instance
const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  fallbackWarn: false,
  missingWarn: false,
  globalInjection: true,
  messages: {},
});

// Load locale messages dynamically
function loadLocaleMessages(locale) {
  return import(`./locales/${locale}.json`).then((msgs) => {
    i18n.global.setLocaleMessage(locale, msgs.default);
    return msgs.default;
  });
}

const loadedLocales = new Set();

// Switch locale
async function switchLocale(locale) {
  locale = normalizeLocale(locale);
  if (loadedLocales.has(locale)) {
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
    persistLocale(locale);
    return;
  }
  try {
    await loadLocaleMessages(locale);
    loadedLocales.add(locale);
    persistLocale(locale);
  } catch (e) {
    if (locale !== 'en') {
      await loadLocaleMessages('en');
      loadedLocales.add('en');
      persistLocale('en');
    }
  }
}

// Persist locale to server
function persistLocale(locale) {
  const token = localStorage.getItem('token');
  if (token) {
    axios.put('/auth/locale', { locale }).catch(() => {});
  }
}

// Initialize i18n
async function initI18n() {
  await loadLocaleMessages('fr');
  loadedLocales.add('fr');
  if (savedLocale !== 'fr') {
    await loadLocaleMessages(savedLocale);
    loadedLocales.add(savedLocale);
  }
}

// Export languages
export const languages = [
  { code: 'fr', name: 'Français', flag: '🇫🇷', dir: 'ltr' },
  { code: 'en', name: 'English', flag: '🇬🇧', dir: 'ltr' },
];

// Export i18n instance as default (for app.js: import i18n from './i18n')
export default i18n;

// Export functions (for app.js: import { initI18n } from './i18n')
export { switchLocale, initI18n, persistLocale };