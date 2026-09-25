<template>
  <SuperAdminLayout>
    <div class="space-y-5 pb-12">
      <!-- Header -->
      <div>
        <p class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-[0.16em] text-amber-600 bg-amber-500/10 border border-amber-500/20 rounded-full px-3 py-1">Audit · traçabilité globale</p>
        <h2 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-text-primary">Journal d'activité</h2>
        <p class="text-sm text-text-tertiary mt-1 font-medium">Toutes les actions utilisateurs sur l'ensemble des locataires, en temps réel.</p>
      </div>

      <!-- Filtres -->
      <div class="flex flex-col lg:flex-row lg:items-center gap-3 p-3 rounded-3xl bg-surface border border-border/70 shadow-sm">
        <label class="flex items-center gap-2.5 flex-1 min-w-0 px-3.5 py-2.5 rounded-2xl bg-surface-secondary/70 border border-border/60 focus-within:border-emerald-500/60 focus-within:ring-4 focus-within:ring-emerald-500/10 transition">
          <svg class="w-4 h-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
          <input v-model="filters.search" placeholder="Rechercher (email, action, modèle)…" class="w-full bg-transparent text-sm font-semibold text-text-primary placeholder:text-text-tertiary outline-none" @input="debouncedSearch" />
        </label>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="filters.event" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer" @change="fetchLogs">
            <option value="">Tous les événements</option>
            <option value="created">Créations</option>
            <option value="updated">Modifications</option>
            <option value="deleted">Suppressions</option>
          </select>
          <select v-model="filters.company_id" class="px-3.5 py-2.5 text-sm font-bold rounded-2xl border border-border bg-surface text-text-primary focus:border-emerald-500 cursor-pointer max-w-56" @change="fetchLogs">
            <option value="">Toutes les entreprises</option>
            <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <button @click="fetchLogs" class="px-4 py-2.5 text-sm font-extrabold rounded-2xl bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/15 border border-emerald-500/20 transition active:scale-95">
            Rafraîchir
          </button>
          <button @click="exportCsv" :disabled="exporting" class="px-4 py-2.5 text-sm font-extrabold rounded-2xl bg-neutral-900 text-white dark:bg-white dark:text-neutral-900 transition active:scale-95 disabled:opacity-50">
            {{ exporting ? 'Export…' : 'Exporter CSV' }}
          </button>
        </div>
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
              <th class="px-5 py-4">Entreprise</th>
              <th class="px-5 py-4">Description (Sujet)</th>
              <th class="px-5 py-4">Détails techniques</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/30">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-surface-tertiary/40 group transition-all duration-200 hover:translate-x-0.5">
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
                <p v-if="log.causer?.company?.name" class="text-sm font-medium text-text-secondary">{{ log.causer.company.name }}</p>
                <p v-else class="text-sm text-text-tertiary italic">—</p>
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
            <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-border/50 hover:bg-surface-tertiary disabled:opacity-30">&larr; Précédent</button>
            <span class="px-2 text-xs font-bold text-text-secondary">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-border/50 hover:bg-surface-tertiary disabled:opacity-30">Suivant &rarr;</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Détails -->
    <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="selectedLog = null">
      <div class="bg-surface w-full max-w-3xl rounded-2xl shadow-2xl border border-border overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-5 border-b border-border">
          <h3 class="text-lg font-bold text-text-primary">Détails de l'interférence</h3>
          <button @click="selectedLog = null" class="text-text-tertiary hover:text-text-primary">&times;</button>
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
const filters = reactive({ search: '', event: '', company_id: '' })
const selectedLog = ref(null)
const companies = ref([])
const exporting = ref(false)

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchLogs, 400)
}

async function fetchCompanies() {
  try {
    const { data } = await axios.get('/admin/companies', { params: { per_page: 100, sort: 'name', order: 'asc' } })
    companies.value = data.data || []
  } catch (err) {
    console.error(err)
  }
}

function exportCsv() {
  exporting.value = true
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.event) params.event = filters.event
  if (filters.company_id) params.company_id = filters.company_id
  axios.get('/admin/activity-logs/export', { params, responseType: 'blob' })
    .then(res => {
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }))
      const a = document.createElement('a')
      a.href = url
      a.download = `activity-logs-${new Date().toISOString().slice(0, 10)}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    })
    .catch(err => console.error(err))
    .finally(() => { exporting.value = false })
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

onMounted(() => {
  fetchLogs()
  fetchCompanies()
})
</script>
