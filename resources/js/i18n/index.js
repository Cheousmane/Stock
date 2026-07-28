import { createI18n } from 'vue-i18n';

import axios from 'axios';

const savedLocale = localStorage.getItem('locale') || 'fr';

const i18n = createI18n({
  legacy: false,
  locale: savedLocale,
  fallbackLocale: 'en',
  fallbackWarn: false,
  missingWarn: false,
  globalInjection: true,
  messages: {},
});

function loadLocaleMessages(locale) {
  return import(`./locales/${locale}.json`).then((msgs) => {
    i18n.global.setLocaleMessage(locale, msgs.default);
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
  });
}

const loadedLocales = new Set();

export async function switchLocale(locale) {
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
  } catch {
    if (locale !== 'en') {
      await loadLocaleMessages('en');
      loadedLocales.add('en');
      persistLocale('en');
    }
  }
}

function persistLocale(locale) {
  const token = localStorage.getItem('token');
  if (token) {
    axios.put('/auth/locale', { locale }).catch(() => {});
  }
}

export async function initI18n() {
  await loadLocaleMessages('fr');
  loadedLocales.add('fr');
  if (savedLocale !== 'fr') {
    await loadLocaleMessages(savedLocale);
    loadedLocales.add(savedLocale);
  }
}

export const languages = [
  { code: 'fr', name: 'Français', flag: '🇫🇷', dir: 'ltr' },
  { code: 'en', name: 'English', flag: '🇬🇧', dir: 'ltr' },
];

export default i18n;
