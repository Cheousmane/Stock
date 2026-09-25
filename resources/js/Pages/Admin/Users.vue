<template>
  <SuperAdminLayout>
    <div class="space-y-5">
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
          <p class="inline-flex items-center gap-1.5 text-[11px] font-extrabold uppercase tracking-[0.16em] text-violet-600 bg-violet-500/10 border border-violet-500/20 rounded-full px-3 py-1">Sécurité · {{ totalUsers }} comptes</p>
          <h2 class="mt-2 text-2xl sm:text-3xl font-black tracking-tight text-text-primary">Utilisateurs</h2>
          <p class="text-sm text-text-tertiary mt-1 font-medium">Comptes tous locataires — bloquez un accès compromis en un clic.</p>
        </div>
      </div>

      <div class="p-3 rounded-3xl bg-surface border border-border/70 shadow-sm">
        <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-2xl bg-surface-secondary/70 border border-border/60 focus-within:border-emerald-500/60 focus-within:ring-4 focus-within:ring-emerald-500/10 transition">
          <svg class="w-4 h-4 text-text-tertiary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
          <input v-model="filters.search" placeholder="Nom, email ou entreprise…" class="w-full bg-transparent text-sm font-semibold text-text-primary placeholder:text-text-tertiary outline-none" @input="debouncedSearch" />
        </label>
      </div>

      <div class="rounded-3xl bg-surface border border-border/70 shadow-sm overflow-hidden">
        <div v-if="loading" class="p-6 space-y-3">
          <div v-for="i in 6" :key="i" class="flex items-center gap-4">
            <div class="w-10 h-10 rounded-2xl bg-surface-tertiary animate-pulse shrink-0" />
            <div class="flex-1 space-y-2"><div class="h-3.5 w-1/3 rounded bg-surface-tertiary animate-pulse" /><div class="h-3 w-1/4 rounded bg-surface-tertiary animate-pulse" /></div>
          </div>
        </div>
        <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[820px]">
          <thead>
            <tr class="border-b border-border/70 text-left text-[11px] font-extrabold text-text-tertiary uppercase tracking-[0.1em] bg-surface-secondary/50">
              <th class="px-5 py-4">Nom</th>
              <th class="px-5 py-4">Email</th>
              <th class="px-5 py-4">Entreprise</th>
              <th class="px-5 py-4">Rôles</th>
              <th class="px-5 py-4">Super Admin</th>
              <th class="px-5 py-4">Statut</th>
              <th class="px-5 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/50">
            <tr v-for="u in users" :key="u.id" class="hover:bg-emerald-500/[0.04] transition-colors group">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <span class="flex items-center justify-center w-9 h-9 rounded-2xl bg-gradient-to-br from-violet-500 to-purple-600 text-white text-xs font-black shrink-0 shadow">{{ (u.name || '?').charAt(0).toUpperCase() }}</span>
                  <p class="text-sm font-extrabold text-text-primary whitespace-nowrap">{{ u.name }}</p>
                </div>
              </td>
              <td class="px-5 py-3.5 text-[13px] font-semibold text-text-secondary">{{ u.email }}</td>
              <td class="px-5 py-3.5 text-[13px] font-semibold text-text-secondary">{{ u.company?.name || '-' }}</td>
              <td class="px-5 py-3.5">
                <div class="flex flex-wrap gap-1">
                  <span v-for="r in u.roles" :key="r" class="inline-flex px-2 py-0.5 text-[11px] font-extrabold rounded-full bg-blue-500/10 text-blue-600 border border-blue-500/20">{{ r }}</span>
                  <span v-if="!u.roles?.length" class="text-xs text-text-tertiary">-</span>
                </div>
              </td>
              <td class="px-5 py-3.5">
                <span v-if="u.is_super_admin" class="inline-flex px-2.5 py-1 text-[11px] font-extrabold rounded-full bg-violet-500/10 text-violet-600 border border-violet-500/20">SUPER</span>
                <span v-else class="text-xs font-bold text-text-tertiary">—</span>
              </td>
              <td class="px-5 py-3.5">
                <span v-if="u.is_active" class="inline-flex px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">ACTIF</span>
                <span v-else class="inline-flex px-2.5 py-1 text-[11px] font-extrabold rounded-lg bg-rose-500/10 text-rose-600 border border-rose-500/20">SUSPENDU</span>
              </td>
              <td class="px-5 py-3.5 text-right">
                <button v-if="u.is_active && !u.is_super_admin" @click="suspendUser(u)" class="text-[11px] font-extrabold uppercase tracking-wider text-rose-600 hover:text-white bg-rose-500/10 hover:bg-rose-600 border border-rose-500/20 px-3.5 py-2 rounded-xl transition">Bloquer</button>
                <button v-if="!u.is_active && !u.is_super_admin" @click="activateUser(u)" class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-600 hover:text-white bg-emerald-500/10 hover:bg-emerald-600 border border-emerald-500/20 px-3.5 py-2 rounded-xl transition">Débloquer</button>
              </td>
            </tr>
          </tbody>
        </table>
        </div>
        <div v-if="!loading && users.length === 0" class="p-12 text-center">
          <p class="text-sm font-extrabold text-text-primary">Aucun utilisateur trouvé</p>
          <p class="text-xs text-text-tertiary mt-1">Essayez une autre recherche.</p>
        </div>

        <div v-if="pagination" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-t border-border/70 bg-surface-secondary/40">
          <p class="text-xs font-bold text-text-tertiary tabular-nums">{{ pagination.from }}–{{ pagination.to }} sur {{ pagination.total }}</p>
          <div class="flex items-center gap-1.5">
            <button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.current_page - 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">←</button>
            <span class="px-3 text-[13px] font-extrabold text-text-primary tabular-nums">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
            <button :disabled="!pagination.next_page_url" @click="goToPage(pagination.current_page + 1)" class="w-9 h-9 inline-flex items-center justify-center rounded-xl border border-border bg-surface text-sm hover:border-emerald-400/50 hover:text-emerald-600 disabled:opacity-40 transition">→</button>
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

const users = ref([])
const loading = ref(true)
const totalUsers = ref(0)
const pagination = ref(null)
const filters = reactive({ search: '' })

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchUsers, 300)
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20 }
    if (filters.search) params.search = filters.search
    const { data } = await axios.get('/admin/users', { params })
    users.value = data.data || []
    totalUsers.value = data.total || 0
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
  fetchUsers(page)
}

async function suspendUser(user) {
  if (!confirm(`Bloquer l'accès pour ${user.name} ?\nIl sera immédiatement déconnecté.`)) return
  try {
    await axios.post(`/admin/users/${user.id}/suspend`)
    user.is_active = false
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de la suspension')
  }
}

async function activateUser(user) {
  try {
    await axios.post(`/admin/users/${user.id}/activate`)
    user.is_active = true
  } catch (err) {
    alert(err.response?.data?.message || 'Erreur lors de l\'activation')
  }
}

onMounted(fetchUsers)
</script>
