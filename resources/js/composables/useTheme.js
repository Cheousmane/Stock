import { ref, computed, watch } from 'vue';

const STORAGE_KEY = 'theme';

const current = ref('system');

function getSystemTheme() {
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function resolve(theme) {
  if (theme === 'system') return getSystemTheme();
  return theme;
}

function apply(theme) {
  const t = theme ?? current.value;
  const resolved = resolve(t);
  document.documentElement.classList.toggle('dark', resolved === 'dark');
}

export function applyTheme() {
  const saved = localStorage.getItem(STORAGE_KEY);
  current.value = saved || 'system';
  apply(current.value);
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (current.value === 'system') apply('system');
  });
}

export function useTheme() {
  function setTheme(theme) {
    current.value = theme;
    localStorage.setItem(STORAGE_KEY, theme);
    apply(theme);
  }

  return { current, setTheme };
}