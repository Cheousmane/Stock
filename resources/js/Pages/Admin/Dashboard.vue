<template>
  <SuperAdminLayout>
    <div class="space-y-8 animate-fade-in pb-12">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold bg-gradient-to-r from-emerald-600 to-teal-400 bg-clip-text text-transparent">Tableau de bord Global</h2>
          <p class="text-sm text-text-tertiary mt-1">Supervision de l'ensemble des locataires et de l'activité du système.</p>
        </div>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="card in statCards" :key="card.label" class="relative overflow-hidden p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm hover:shadow-md transition-all duration-300 group">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 rounded-full blur-2xl group-hover:scale-110 transition-transform"></div>
          <p class="text-sm font-semibold text-text-tertiary uppercase tracking-wider">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-black text-text-primary tracking-tight">{{ card.value }}</p>
          <div class="mt-3 flex items-center gap-1.5 text-xs font-medium" :class="card.color">
            <span>{{ card.sublabel }}</span>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Inscriptions Chart -->
        <div class="p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm">
          <h3 class="text-lg font-bold text-text-primary mb-4">Nouvelles entreprises (30 jours)</h3>
          <div class="h-64">
            <Line v-if="chartDataRegistrations" :data="chartDataRegistrations" :options="chartOptionsRegistrations" />
            <div v-else class="flex items-center justify-center h-full text-text-tertiary text-sm">Chargement du graphique...</div>
          </div>
        </div>

        <!-- Logins Chart -->
        <div class="p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm">
          <h3 class="text-lg font-bold text-text-primary mb-4">Connexions (14 jours)</h3>
          <div class="h-64">
            <Bar v-if="chartDataLogins" :data="chartDataLogins" :options="chartOptionsLogins" />
            <div v-else class="flex items-center justify-center h-full text-text-tertiary text-sm">Chargement du graphique...</div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Connexions recentes -->
        <div class="p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm flex flex-col h-full">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary">Connexions récentes</h3>
            <router-link to="/admin/login-logs" class="text-sm font-semibold text-emerald-500 hover:text-emerald-600 transition-colors">Tout voir &rarr;</router-link>
          </div>
          <div v-if="logins.length === 0" class="flex-1 flex items-center justify-center text-sm text-text-tertiary">Aucune connexion récente</div>
          <div v-else class="space-y-3 flex-1 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
            <div v-for="log in logins" :key="log.id" class="group flex items-start gap-4 p-3.5 rounded-xl bg-surface-tertiary/30 hover:bg-surface-tertiary/80 border border-transparent hover:border-border/50 transition-all">
              <div class="mt-1 w-2.5 h-2.5 rounded-full shrink-0 shadow-sm" :class="log.success ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-rose-500 shadow-rose-500/30'" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-text-primary truncate">{{ log.email }}</p>
                <p class="text-xs font-medium text-text-secondary mt-0.5 truncate">{{ log.company?.name || 'Sans entreprise' }}</p>
                <p class="text-[11px] text-text-tertiary mt-1 font-mono">{{ log.ip_address }}</p>
              </div>
              <span class="text-[11px] font-medium text-text-tertiary shrink-0 bg-surface px-2 py-1 rounded-md border border-border">{{ timeAgo(log.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Dernieres entreprises inscrites -->
        <div class="p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm flex flex-col h-full">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-text-primary">Dernières inscriptions</h3>
            <router-link to="/admin/companies" class="text-sm font-semibold text-emerald-500 hover:text-emerald-600 transition-colors">Tout voir &rarr;</router-link>
          </div>
          <div v-if="recentCompanies.length === 0" class="flex-1 flex items-center justify-center text-sm text-text-tertiary">Aucune entreprise</div>
          <div v-else class="space-y-3 flex-1 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
            <div v-for="c in recentCompanies" :key="c.id" class="group flex items-center gap-4 p-3.5 rounded-xl bg-surface-tertiary/30 hover:bg-surface-tertiary/80 border border-transparent hover:border-border/50 transition-all">
              <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-50 border border-emerald-200 shrink-0">
                <span class="text-sm font-bold text-emerald-700">{{ c.name.charAt(0).toUpperCase() }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-text-primary truncate">{{ c.name }}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium uppercase tracking-wider" :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                    {{ c.status }}
                  </span>
                  <span class="text-xs text-text-tertiary">{{ c.users_count || 0 }} utilisateur(s)</span>
                </div>
              </div>
              <span class="text-[11px] font-medium text-text-tertiary shrink-0">{{ timeAgo(c.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'
import { Line, Bar } from 'vue-chartjs'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const logins = ref([])
const recentCompanies = ref([])
const stats = reactive({
  total_companies: 0, active_companies: 0, suspended_companies: 0,
  total_users: 0, total_revenue: 0, total_invoices: 0,
  recent_logins: 0, failed_logins: 0,
})

// Charts Data
const chartDataRegistrations = ref(null)
const chartDataLogins = ref(null)

const chartOptionsRegistrations = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
  scales: {
    x: { grid: { display: false } },
    y: { border: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, ticks: { precision: 0 } }
  },
  interaction: { mode: 'nearest', axis: 'x', intersect: false },
  elements: { line: { tension: 0.4 } }
}

const chartOptionsLogins = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, usePointStyle: true } }, tooltip: { mode: 'index', intersect: false } },
  scales: {
    x: { stacked: true, grid: { display: false } },
    y: { stacked: true, border: { display: false }, grid: { color: 'rgba(0,0,0,0.05)' }, beginAtZero: true, ticks: { precision: 0 } }
  }
}

const statCards = computed(() => [
  { label: 'Entreprises', value: stats.total_companies, sublabel: `${stats.active_companies} actives · ${stats.suspended_companies} suspendues`, color: 'text-emerald-600' },
  { label: 'Utilisateurs', value: stats.total_users, sublabel: 'Total comptes créés', color: 'text-blue-600' },
  { label: 'Connexions (24h)', value: stats.recent_logins, sublabel: `${stats.failed_logins} tentatives échouées`, color: stats.failed_logins > 0 ? 'text-rose-600' : 'text-emerald-600' },
])

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

onMounted(async () => {
  try {
    const { data } = await axios.get('/admin/dashboard')
    const d = data.data ?? data
    Object.assign(stats, d)
    logins.value = d.logins_today || []
    recentCompanies.value = d.recent_companies || []
    
    // Format Registration Chart
    if (d.chart_registrations) {
      chartDataRegistrations.value = {
        labels: d.chart_registrations.map(i => new Date(i.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit'})),
        datasets: [{
          label: 'Nouvelles entreprises',
          data: d.chart_registrations.map(i => i.count),
          borderColor: '#10b981', // emerald-500
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#10b981',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 5
        }]
      }
    }

    // Format Logins Chart
    if (d.chart_logins) {
      chartDataLogins.value = {
        labels: d.chart_logins.map(i => new Date(i.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit'})),
        datasets: [
          {
            label: 'Succès',
            data: d.chart_logins.map(i => i.success),
            backgroundColor: '#10b981', // emerald-500
            borderRadius: 4
          },
          {
            label: 'Échecs',
            data: d.chart_logins.map(i => i.failed),
            backgroundColor: '#f43f5e', // rose-500
            borderRadius: 4
          }
        ]
      }
    }

  } catch (err) {
    console.error('Erreur chargement dashboard admin:', err)
  }
})
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.3);
  border-radius: 10px;
}
</style>

