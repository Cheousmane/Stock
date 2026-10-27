import { computed, ref } from 'vue';

const STORAGE_KEY = 'theme';
const VALID_THEMES = ['light', 'dark', 'system'];

const current = ref('system');
const systemDark = ref(false);

let mediaQuery = null;
let initialized = false;

function getStoredTheme() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    return VALID_THEMES.includes(saved) ? saved : 'system';
  } catch {
    return 'system';
  }
}

function getSystemDark() {
  if (typeof window === 'undefined' || !window.matchMedia) return false;
  return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

const resolved = computed(() => {
  if (current.value === 'system') return systemDark.value ? 'dark' : 'light';
  return current.value;
});

const isDark = computed(() => resolved.value === 'dark');

function applyResolvedTheme() {
  if (typeof document === 'undefined') return;

  const dark = resolved.value === 'dark';
  const root = document.documentElement;

  root.classList.toggle('dark', dark);
  root.dataset.theme = current.value;
  root.style.colorScheme = dark ? 'dark' : 'light';

  const meta = document.querySelector('meta[name="theme-color"]');
  if (meta) meta.setAttribute('content', dark ? '#0a0a0a' : '#ffffff');
}

function setTheme(theme) {
  const next = VALID_THEMES.includes(theme) ? theme : 'system';
  current.value = next;

  try {
    localStorage.setItem(STORAGE_KEY, next);
  } catch {
    // Mode privé / stockage indisponible : le thème reste actif pour la session.
  }

  applyResolvedTheme();
}

function handleSystemChange(event) {
  systemDark.value = event.matches;
  if (current.value === 'system') applyResolvedTheme();
}

function handleStorageChange(event) {
  if (event.key !== STORAGE_KEY) return;

  current.value = VALID_THEMES.includes(event.newValue) ? event.newValue : 'system';
  applyResolvedTheme();
}

export function applyTheme() {
  if (typeof window === 'undefined' || initialized) return;

  initialized = true;
  systemDark.value = getSystemDark();
  current.value = getStoredTheme();

  if (window.matchMedia) {
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    systemDark.value = mediaQuery.matches;

    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', handleSystemChange);
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(handleSystemChange);
    }
  }

  window.addEventListener('storage', handleStorageChange);
  applyResolvedTheme();
}

export function useTheme() {
  return {
    current,
    resolved,
    isDark,
    setTheme,
  };
}
