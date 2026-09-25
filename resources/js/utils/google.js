let loadPromise = null;

export function getGoogleClientId() {
  return import.meta.env.VITE_GOOGLE_CLIENT_ID || '';
}

export function isGoogleConfigured() {
  return getGoogleClientId() !== '';
}

export function loadGoogleScript() {
  if (window.google?.accounts?.id) return Promise.resolve();
  if (loadPromise) return loadPromise;
  loadPromise = new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = 'https://accounts.google.com/gsi/client';
    s.async = true;
    s.defer = true;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error('google_script_failed'));
    document.head.appendChild(s);
  });
  return loadPromise;
}

export function currentLocale() {
  return (localStorage.getItem('locale') || 'fr').split('-')[0];
}
