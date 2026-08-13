<template>
  <SuperAdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text-primary">Journal des connexions</h2>
      </div>

      <!-- Brute force alert -->
      <div v-if="bruteForceIps.length > 0" class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-10 h-10 rounded-xl bg-rose-100 flex items-center justify-center text-xl shrink-0">🚨</div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-text-primary">{{ bruteForceIps.length }} IP suspecte(s) détectée(s) — 5+ échecs en 24h</p>
          <p class="text-sm text-text-secondary mt-0.5 font-mono">{{ bruteForceIps.map(i => `${i.ip_address} (${i.attempts})`).join(' · ') }}</p>
        </div>
      </div>

      <!-- Hourly chart -->
      <div class="rounded-2xl bg-surface border border-border/50 shadow-sm p-5">
        <h3 class="text-lg font-bold text-text-primary mb-4">Connexions par heure (24h) — <span class="text-sm font-medium text-text-tertiary">{{ total24h }} tentatives, {{ failed24h }} échecs</span></h3>
        <div class="h-48">
          <Bar v-if="chartDataHourly" :data="chartDataHourly" :options="chartOptionsHourly" />
          <div v-else class="flex items-center justify-center h-full text-sm text-text-tertiary">Chargement...</div>
        </div>
      </div>

      <div class="flex flex-wrap gap-3">
        <input v-model="filters.search" placeholder="Email ou IP..." class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary placeholder:text-text-tertiary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-64" @input="debouncedSearch" />
        <label class="flex items-center gap-2 text-sm text-text-secondary">
          <input type="checkbox" v-model="filters.failed" @change="fetchLogs" class="rounded border-border text-emerald-500 focus:ring-emerald-500" />
          Échecs uniquement
        </label>
        <select v-model="filters.date_from" class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" @change="fetchLogs">
          <option value="">Du...</option>
          <option :value="daysAgo(1)">Hier</option>
          <option :value="daysAgo(7)">7 jours</option>
          <option :value="daysAgo(30)">30 jours</option>
        </select>
        <button @click="exportCsv" :disabled="exporting" class="px-3.5 py-2 text-sm font-medium rounded-xl bg-surface border border-border text-text-secondary hover:bg-surface-tertiary disabled:opacity-50">
          {{ exporting ? 'Export en cours...' : '⬇ Exporter CSV' }}
        </button>
      </div>

      <div class="rounded-xl bg-surface border border-border shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-text-tertiary">Chargement...</div>
        <table v-else class="w-full">
          <thead>
            <tr class="border-b border-border text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">
              <th class="px-4 py-3">Statut</th>
              <th class="px-4 py-3">Email</th>
              <th class="px-4 py-3">Entreprise</th>
              <th class="px-4 py-3">IP</th>
              <th class="px-4 py-3">Navigateur</th>
              <th class="px-4 py-3">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="log in logs" :key="log.id" class="hover:bg-surface-tertiary/50 transition-all duration-200 hover:translate-x-0.5">
              <td class="px-4 py-3">
                <span class="w-2.5 h-2.5 rounded-full inline-block transition-transform duration-300 group-hover:scale-125" :class="log.success ? 'bg-emerald-500' : 'bg-red-500'" :title="log.success ? 'Succès' : 'Échec'" />
              </td>
              <td class="px-4 py-3 text-sm font-medium text-text-primary">{{ log.email }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ log.company?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary font-mono">{{ log.ip_address || '-' }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary max-w-[200px] truncate" :title="log.user_agent">{{ log.user_agent ? shortenUA(log.user_agent) : '-' }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ formatDate(log.created_at) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && logs.length === 0" class="p-8 text-center text-sm text-text-tertiary">Aucun log trouvé</div>

        <div v-if="pagination" class="flex items-center justify-between px-4 py-3 border-t border-border">
          <p class="text-xs text-text-tertiary">{{ pagination.from }}-{{ pagination.to }} sur {{ pagination.total }}</p>
          <div class="flex items-center gap-1">
            <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="px-3 py-1 text-sm rounded-lg border border-border hover:bg-surface-tertiary disabled:opacity-40">&larr;</button>
            <span class="px-2 text-sm text-text-tertiary">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="px-3 py-1 text-sm rounded-lg border border-border hover:bg-surface-tertiary disabled:opacity-40">&rarr;</button>
          </div>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const logs = ref([])
const loading = ref(true)
const pagination = ref(null)
const filters = reactive({ search: '', failed: false, date_from: '' })
const bruteForceIps = ref([])
const total24h = ref(0)
const failed24h = ref(0)
const exporting = ref(false)
const chartDataHourly = ref(null)

const chartOptionsHourly = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true } }, tooltip: { mode: 'index', intersect: false } },
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, callback: v => `${v}h` } },
    y: { stacked: true, border: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, ticks: { precision: 0 } }
  }
}

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchLogs, 300)
}

function daysAgo(n) {
  const d = new Date()
  d.setDate(d.getDate() - n)
  return d.toISOString().split('T')[0]
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function shortenUA(ua) {
  if (ua.includes('Chrome')) return 'Chrome'
  if (ua.includes('Firefox')) return 'Firefox'
  if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari'
  if (ua.includes('Edge')) return 'Edge'
  return ua.substring(0, 40) + '...'
}

async function fetchSummary() {
  try {
    const { data } = await axios.get('/admin/login-logs/summary')
    total24h.value = data.total_24h ?? 0
    failed24h.value = data.failed_24h ?? 0
    bruteForceIps.value = data.brute_force_ips || []

    if (data.hourly) {
      chartDataHourly.value = {
        labels: data.hourly.map(i => i.hour),
        datasets: [
          { label: 'Succès', data: data.hourly.map(i => i.success), backgroundColor: '#10b981', borderRadius: 3 },
          { label: 'Échecs', data: data.hourly.map(i => i.failed), backgroundColor: '#f43f5e', borderRadius: 3 }
        ]
      }
    }
  } catch (err) {
    console.error(err)
  }
}

async function fetchLogs(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20 }
    if (filters.search) params.search = filters.search
    if (filters.failed) params.failed = '1'
    if (filters.date_from) params.date_from = filters.date_from
    const { data } = await axios.get('/admin/login-logs', { params })
    logs.value = data.data || []
    pagination.value = {
      ...data.meta,
      prev_page_url: data.links?.prev,
      next_page_url: data.links?.next,
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

function exportCsv() {
  exporting.value = true
  const params = {}
  if (filters.search) params.search = filters.search
  if (filters.failed) params.failed = '1'
  if (filters.date_from) params.date_from = filters.date_from
  axios.get('/admin/login-logs/export', { params, responseType: 'blob' })
    .then(res => {
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'text/csv' }))
      const a = document.createElement('a')
      a.href = url
      a.download = `connexions-${new Date().toISOString().slice(0, 10)}.csv`
      a.click()
      window.URL.revokeObjectURL(url)
    })
    .catch(err => console.error(err))
    .finally(() => { exporting.value = false })
}

onMounted(() => {
  fetchLogs()
  fetchSummary()
})
</script>
