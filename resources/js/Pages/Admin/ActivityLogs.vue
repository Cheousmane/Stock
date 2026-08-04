<template>
  <SuperAdminLayout>
    <div class="space-y-6 animate-fade-in pb-12">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold text-text-primary">Journal des interférences (Global)</h2>
          <p class="text-sm text-text-tertiary mt-1">Surveillez toutes les actions effectuées par les utilisateurs sur l'ensemble des locataires.</p>
        </div>
      </div>

      <!-- Filtres -->
      <div class="flex flex-wrap gap-3">
        <input v-model="filters.search" placeholder="Rechercher (email, action, modèle)..." class="px-4 py-2.5 text-sm rounded-xl border border-border bg-surface text-text-primary placeholder:text-text-tertiary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-80 shadow-sm" @input="debouncedSearch" />
        <select v-model="filters.event" class="px-4 py-2.5 text-sm rounded-xl border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 shadow-sm" @change="fetchLogs">
          <option value="">Tous les événements</option>
          <option value="created">Créations</option>
          <option value="updated">Modifications</option>
          <option value="deleted">Suppressions</option>
        </select>
        <button @click="fetchLogs" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition-colors shadow-sm">
          Rafraîchir
        </button>
      </div>

      <!-- Table -->
      <div class="rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-12 flex flex-col items-center justify-center space-y-3">
          <div class="w-8 h-8 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
          <p class="text-sm font-medium text-text-tertiary">Chargement des interférences...</p>
        </div>
        <table v-else class="w-full">
          <thead>
            <tr class="border-b border-border/50 text-left text-[11px] font-bold text-text-tertiary uppercase tracking-wider bg-surface-secondary/30">
              <th class="px-5 py-4">Date</th>
              <th class="px-5 py-4">Événement</th>
              <th class="px-5 py-4">Utilisateur</th>
              <th class="px-5 py-4">Description (Sujet)</th>
              <th class="px-5 py-4">Détails techniques</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/30">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-surface-tertiary/40 transition-colors group">
              <td class="px-5 py-4 whitespace-nowrap">
                <p class="text-sm font-medium text-text-primary">{{ formatDate(log.created_at) }}</p>
                <p class="text-[11px] text-text-tertiary">{{ timeAgo(log.created_at) }}</p>
              </td>
              <td class="px-5 py-4">
                <span class="inline-flex px-2.5 py-1 text-xs font-bold rounded-lg uppercase tracking-wide border" :class="eventBadgeClass(log.event)">
                  {{ log.event || log.description }}
                </span>
              </td>
              <td class="px-5 py-4">
                <div v-if="log.causer">
                  <p class="text-sm font-bold text-text-primary">{{ log.causer.name }}</p>
                  <p class="text-[11px] text-text-tertiary">{{ log.causer.email }}</p>
                </div>
                <div v-else class="text-sm text-text-tertiary italic">Système</div>
              </td>
              <td class="px-5 py-4">
                <p class="text-sm text-text-secondary font-medium">{{ log.description }}</p>
                <p v-if="log.subject_type" class="text-[11px] text-text-tertiary font-mono mt-1">
                  {{ log.subject_type.split('\\').pop() }} #{{ log.subject_id }}
                </p>
              </td>
              <td class="px-5 py-4 text-xs font-mono text-text-tertiary">
                <button @click="showDetails(log)" class="text-emerald-500 hover:text-emerald-600 font-sans font-medium text-sm underline decoration-emerald-500/30 underline-offset-4">Voir payload</button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="!loading && logs.length === 0" class="p-12 flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 bg-surface-tertiary rounded-2xl flex items-center justify-center mb-4 text-2xl">🕵️‍♂️</div>
          <p class="text-base font-bold text-text-primary">Aucune interférence trouvée</p>
          <p class="text-sm text-text-tertiary mt-1">Personne n'a rien touché récemment.</p>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.total > 0" class="flex items-center justify-between px-5 py-4 border-t border-border/50 bg-surface-secondary/20">
          <p class="text-xs font-medium text-text-tertiary">Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }}</p>
          <div class="flex items-center gap-2">
            <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-border/50 hover:bg-surface-tertiary disabled:opacity-30 transition-colors">&larr; Précédent</button>
            <span class="px-2 text-xs font-bold text-text-secondary">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-border/50 hover:bg-surface-tertiary disabled:opacity-30 transition-colors">Suivant &rarr;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Détails -->
    <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in" @click.self="selectedLog = null">
      <div class="bg-surface w-full max-w-3xl rounded-2xl shadow-2xl border border-border overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-border">
          <h3 class="text-lg font-bold text-text-primary">Détails de l'interférence</h3>
          <button @click="selectedLog = null" class="text-text-tertiary hover:text-text-primary transition-colors">&times;</button>
        </div>
        <div class="p-5 overflow-y-auto flex-1 bg-surface-secondary/30">
          <pre class="text-xs font-mono text-text-secondary whitespace-pre-wrap break-words bg-surface p-4 rounded-xl border border-border/50">{{ JSON.stringify(selectedLog.properties, null, 2) }}</pre>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'

const logs = ref([])
const loading = ref(true)
const pagination = ref(null)
const filters = reactive({ search: '', event: '' })
const selectedLog = ref(null)

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchLogs, 400)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function timeAgo(date) {
  if (!date) return ''
  const diff = Date.now() - new Date(date).getTime()
  const mins = Math.floor(diff / 60000)
  if (mins < 1) return 'à l\'instant'
  if (mins < 60) return `${mins} min`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h`
  const days = Math.floor(hours / 24)
  return `${days}j`
}

function eventBadgeClass(event) {
  if (event === 'created') return 'bg-emerald-50 text-emerald-600 border-emerald-200'
  if (event === 'updated') return 'bg-blue-50 text-blue-600 border-blue-200'
  if (event === 'deleted') return 'bg-rose-50 text-rose-600 border-rose-200'
  return 'bg-gray-50 text-gray-600 border-gray-200'
}

function showDetails(log) {
  selectedLog.value = log
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 50, ...filters }
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
    const { data } = await axios.get('/admin/activity-logs', { params })
    logs.value = data.data || []
    pagination.value = {
      ...data,
      prev_page_url: data.prev_page_url,
      next_page_url: data.next_page_url,
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

function goToPage(page) {
  if (page < 1 || page > (pagination.value?.last_page || 1)) return
  fetchLogs(page)
}

onMounted(() => fetchLogs())
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
