<template>
  <SuperAdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text-primary">Journal des connexions</h2>
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
            <tr v-for="log in logs" :key="log.id" class="hover:bg-surface-tertiary/50 transition-colors">
              <td class="px-4 py-3">
                <span class="w-2.5 h-2.5 rounded-full inline-block" :class="log.success ? 'bg-emerald-500' : 'bg-red-500'" :title="log.success ? 'Succès' : 'Échec'" />
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

const logs = ref([])
const loading = ref(true)
const pagination = ref(null)
const filters = reactive({ search: '', failed: false, date_from: '' })

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

onMounted(() => fetchLogs())
</script>
