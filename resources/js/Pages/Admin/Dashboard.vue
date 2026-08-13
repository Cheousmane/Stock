<template>
  <SuperAdminLayout>
    <div class="space-y-8 pb-12">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-3xl font-extrabold bg-gradient-to-r from-emerald-600 to-teal-400 bg-clip-text text-transparent">Tableau de bord Global</h2>
          <p class="text-sm text-text-tertiary mt-1">Supervision de l'ensemble des locataires et de l'activité du système.</p>
        </div>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="card in statCards" :key="card.label" class="relative overflow-hidden p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm transition-all duration-300 ease-out will-change-transform group hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-500/10 hover:border-emerald-400/50 active:scale-[0.98]">
          <div class="absolute -right-6 -top-6 w-24 h-24 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 rounded-full blur-2xl transition-transform duration-500 group-hover:scale-125"></div>
          <p class="text-sm font-semibold text-text-tertiary uppercase tracking-wider">{{ card.label }}</p>
          <p class="mt-2 text-3xl font-black text-text-primary tracking-tight transition-colors duration-300" :class="card.color">{{ card.value }}</p>
          <div class="mt-3 flex items-center gap-1.5 text-xs font-medium" :class="card.color">
            <span>{{ card.sublabel }}</span>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="alerts.length > 0" class="space-y-3">
        <div v-for="(alert, i) in alerts" :key="i"
          class="group flex items-center gap-4 p-4 rounded-2xl border shadow-sm transition-all duration-300 ease-out hover:-translate-y-0.5 hover:shadow-md"
          :class="{
            'bg-rose-50/80 border-rose-200 hover:shadow-rose-500/10': alert.severity === 'critical',
            'bg-amber-50/80 border-amber-200 hover:shadow-amber-500/10': alert.severity === 'warning',
            'bg-red-50/80 border-red-200 hover:shadow-red-500/10': alert.severity === 'danger',
            'bg-sky-50/80 border-sky-200 hover:shadow-sky-500/10': alert.severity === 'info',
          }">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-lg transition-transform duration-300 group-hover:scale-110"
            :class="{ 'bg-rose-100 text-rose-600': alert.severity === 'critical', 'bg-amber-100 text-amber-600': alert.severity === 'warning', 'bg-red-100 text-red-600': alert.severity === 'danger', 'bg-sky-100 text-sky-600': alert.severity === 'info' }">
            {{ alert.severity === 'info' ? 'ℹ️' : '⚠️' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-text-primary">{{ alert.title }}</p>
            <p class="text-sm text-text-secondary mt-0.5">{{ alert.message }}</p>
          </div>
          <router-link :to="alert.link" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 shrink-0">Voir &rarr;</router-link>
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
            <router-link to="/admin/login-logs" class="text-sm font-semibold text-emerald-500 hover:text-emerald-600">Tout voir &rarr;</router-link>
          </div>
          <div v-if="logins.length === 0" class="flex-1 flex items-center justify-center text-sm text-text-tertiary">Aucune connexion récente</div>
          <div v-else class="space-y-3 flex-1 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
            <div v-for="log in logins" :key="log.id" class="group flex items-start gap-4 p-3.5 rounded-xl bg-surface-tertiary/30 hover:bg-surface-tertiary/80 border border-transparent hover:border-border/50 transition-all duration-300 ease-out hover:translate-x-1">
              <div class="mt-1 w-2.5 h-2.5 rounded-full shrink-0 shadow-sm transition-transform duration-300 group-hover:scale-125" :class="log.success ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-rose-500 shadow-rose-500/30'" />
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
            <router-link to="/admin/companies" class="text-sm font-semibold text-emerald-500 hover:text-emerald-600">Tout voir &rarr;</router-link>
          </div>
          <div v-if="recentCompanies.length === 0" class="flex-1 flex items-center justify-center text-sm text-text-tertiary">Aucune entreprise</div>
          <div v-else class="space-y-3 flex-1 overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
            <div v-for="c in recentCompanies" :key="c.id" class="group flex items-center gap-4 p-3.5 rounded-xl bg-surface-tertiary/30 hover:bg-surface-tertiary/80 border border-transparent hover:border-border/50 transition-all duration-300 ease-out hover:translate-x-1">
              <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-50 border border-emerald-200 shrink-0 transition-transform duration-300 group-hover:scale-110">
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

      <!-- Top entreprises -->
      <div class="p-6 rounded-2xl bg-surface/80 backdrop-blur-xl border border-border/50 shadow-sm">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-bold text-text-primary">Top entreprises — volume facturé (30 jours)</h3>
          <router-link to="/admin/companies" class="text-sm font-semibold text-emerald-500 hover:text-emerald-600">Tout voir &rarr;</router-link>
        </div>
        <div v-if="topCompanies.length === 0" class="text-sm text-text-tertiary text-center py-8">Aucune facturation sur la période.</div>
        <div v-else class="space-y-2.5">
          <div v-for="(c, i) in topCompanies" :key="c.id" class="group flex items-center gap-4 p-3 rounded-xl bg-surface-tertiary/30 transition-all duration-300 ease-out hover:bg-surface-tertiary/80 hover:translate-x-1">
            <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-black shrink-0 transition-transform duration-300 group-hover:scale-110" :class="i === 0 ? 'bg-amber-100 text-amber-600' : i === 1 ? 'bg-gray-200 text-gray-600' : i === 2 ? 'bg-orange-100 text-orange-700' : 'bg-surface-tertiary text-text-tertiary'">{{ i + 1 }}</span>
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <p class="text-sm font-bold text-text-primary truncate">{{ c.name }}</p>
                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium uppercase tracking-wider" :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">{{ c.status }}</span>
              </div>
              <p class="text-xs text-text-tertiary mt-0.5">{{ c.invoices_count }} facture(s)</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
              <div class="w-32 h-1.5 rounded-full bg-surface-tertiary overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400" :style="{ width: `${volumePct(c.volume)}%` }"></div>
              </div>
              <span class="text-sm font-black tabular-nums text-text-primary">{{ formatXof(c.volume) }}</span>
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
const topCompanies = ref([])
const alerts = ref([])
const stats = reactive({
  total_companies: 0, active_companies: 0, suspended_companies: 0,
  total_users: 0, active_users: 0, adoption_rate: 0,
  total_revenue: 0, paying_companies: 0, total_invoices: 0,
  trial_companies: 0, trial_expired_companies: 0,
  invoiced_volume_30d: 0, invoices_count_30d: 0,
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

function formatXof(v) {
  return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v || 0)
}

const maxTopVolume = computed(() => Math.max(1, ...topCompanies.value.map(c => Number(c.volume) || 0)))
function volumePct(volume) {
  return Math.round((Number(volume) / maxTopVolume.value) * 100)
}

const statCards = computed(() => [
  { label: 'Entreprises', value: stats.total_companies, sublabel: `${stats.active_companies} actives · ${stats.suspended_companies} suspendues`, color: 'text-emerald-600' },
  { label: 'Revenu mensuel (MRR)', value: formatXof(stats.total_revenue), sublabel: `${stats.paying_companies} entreprise(s) payante(s)`, color: 'text-emerald-600' },
  { label: 'Facturation 30j', value: formatXof(stats.invoiced_volume_30d), sublabel: `${stats.invoices_count_30d} facture(s) émises`, color: 'text-blue-600' },
  { label: 'Utilisateurs', value: stats.total_users, sublabel: `${stats.adoption_rate}% actifs · ${stats.active_users} comptes`, color: 'text-blue-600' },
  { label: 'Connexions (24h)', value: stats.recent_logins, sublabel: `${stats.failed_logins} tentatives échouées`, color: stats.failed_logins > 0 ? 'text-rose-600' : 'text-emerald-600' },
  { label: 'Essais', value: stats.trial_companies, sublabel: stats.trial_expired_companies > 0 ? `${stats.trial_expired_companies} expiré(s) !` : 'Aucun essai expiré', color: stats.trial_expired_companies > 0 ? 'text-rose-600' : 'text-emerald-600' },
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
    topCompanies.value = d.top_companies || []
    alerts.value = d.alerts || []
    
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

