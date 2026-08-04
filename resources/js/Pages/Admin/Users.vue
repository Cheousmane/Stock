<template>
  <SuperAdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text-primary">Utilisateurs</h2>
        <span class="text-sm text-text-tertiary">{{ totalUsers }} utilisateurs</span>
      </div>

      <div class="flex flex-wrap gap-3">
        <input v-model="filters.search" placeholder="Nom ou email..." class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary placeholder:text-text-tertiary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-64" @input="debouncedSearch" />
      </div>

      <div class="rounded-xl bg-surface border border-border shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-text-tertiary">Chargement...</div>
        <table v-else class="w-full">
          <thead>
            <tr class="border-b border-border text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">
              <th class="px-4 py-3">Nom</th>
              <th class="px-4 py-3">Email</th>
              <th class="px-4 py-3">Entreprise</th>
              <th class="px-4 py-3">Rôles</th>
              <th class="px-4 py-3">Super Admin</th>
              <th class="px-4 py-3">Statut</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="u in users" :key="u.id" class="hover:bg-surface-tertiary/50 transition-colors">
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-text-primary">{{ u.name }}</p>
              </td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ u.email }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ u.company?.name || '-' }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span v-for="r in u.roles" :key="r" class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">{{ r }}</span>
                  <span v-if="!u.roles?.length" class="text-xs text-text-tertiary">-</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span v-if="u.is_super_admin" class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-700">Oui</span>
                <span v-else class="text-xs text-text-tertiary">Non</span>
              </td>
              <td class="px-4 py-3">
                <span v-if="u.is_active" class="inline-flex px-2 py-0.5 text-[11px] font-bold rounded-md bg-emerald-50 text-emerald-600 border border-emerald-200">ACTIF</span>
                <span v-else class="inline-flex px-2 py-0.5 text-[11px] font-bold rounded-md bg-rose-50 text-rose-600 border border-rose-200">SUSPENDU</span>
              </td>
              <td class="px-4 py-3 text-right">
                <button v-if="u.is_active && !u.is_super_admin" @click="suspendUser(u)" class="text-[11px] font-bold uppercase tracking-wider text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition-colors">Bloquer</button>
                <button v-if="!u.is_active && !u.is_super_admin" @click="activateUser(u)" class="text-[11px] font-bold uppercase tracking-wider text-emerald-500 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">Débloquer</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && users.length === 0" class="p-8 text-center text-sm text-text-tertiary">Aucun utilisateur trouvé</div>

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
