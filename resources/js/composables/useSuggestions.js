import { ref } from 'vue';

// Clé de stockage localStorage pour toutes les suggestions
const STORAGE_KEY = 'form_suggestions';

// Composable réutilisable pour sauvegarder et proposer des suggestions
// à partir des valeurs déjà saisies par l'utilisateur
export function useSuggestions(fieldKey, maxItems = 10) {
  // Lit l'intégralité du stockage des suggestions
  function readAll() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    } catch {
      return {};
    }
  }

  // Récupère la liste des suggestions pour ce champ
  function readList() {
    return readAll()[fieldKey] || [];
  }

  // Liste réactive des suggestions affichée dans le datalist
  const list = ref(readList());

  // Sauvegarde une valeur saisie dans l'historique du champ
  function saveValue(value) {
    if (!value || typeof value !== 'string') return;
    const trimmed = value.trim();
    if (!trimmed) return;
    const all = readAll();
    all[fieldKey] = [trimmed, ...(all[fieldKey] || []).filter(v => v !== trimmed)].slice(0, maxItems);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(all));
    list.value = all[fieldKey];
  }

  return { saveValue, list };
}
