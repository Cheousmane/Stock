import { onMounted, onBeforeUnmount } from 'vue'

// Composable qui rafraîchit automatiquement les données à intervalle régulier
// et au retour sur l'onglet (visibilitychange)
export function usePolling(fetchFn, interval = 30000) {
  let pollTimer = null // Identifiant du setInterval

  // Démarre le polling à intervalle régulier
  function startPolling() {
    stopPolling()
    pollTimer = setInterval(fetchFn, interval)
  }

  // Arrête le polling
  function stopPolling() {
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  }

  // Rafraîchit les données quand l'utilisateur revient sur l'onglet
  function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
      fetchFn()
    }
  }

  // Initialisation : démarre le polling + écoute le retour sur onglet
  onMounted(() => {
    startPolling()
    document.addEventListener('visibilitychange', onVisibilityChange)
  })

  // Nettoyage : arrête le polling et l'écouteur
  onBeforeUnmount(() => {
    stopPolling()
    document.removeEventListener('visibilitychange', onVisibilityChange)
  })

  return { startPolling, stopPolling }
}
