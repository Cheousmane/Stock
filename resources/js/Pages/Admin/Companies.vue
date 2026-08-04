<template>
  <SuperAdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-text-primary">Entreprises</h2>
      </div>

      <!-- Filters -->
      <div class="flex flex-wrap gap-3">
        <input v-model="filters.search" placeholder="Rechercher..." class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary placeholder:text-text-tertiary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 w-64" @input="debouncedSearch" />
        <select v-model="filters.status" class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" @change="fetchCompanies">
          <option value="">Tous les statuts</option>
          <option value="active">Actif</option>
          <option value="suspended">Suspendu</option>
          <option value="trial">Essai</option>
          <option value="disabled">Désactivé</option>
        </select>
        <select v-model="filters.size" class="px-3.5 py-2 text-sm rounded-xl border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" @change="fetchCompanies">
          <option value="">Toute taille</option>
          <option value="petite">Petite</option>
          <option value="moyenne">Moyenne</option>
          <option value="grande">Grande</option>
        </select>
      </div>

      <!-- Stats -->
      <div v-if="companyStats" class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-text-primary">{{ companyStats.total }}</p>
          <p class="text-xs text-text-tertiary">Total</p>
        </div>
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-emerald-500">{{ companyStats.active }}</p>
          <p class="text-xs text-text-tertiary">Actives</p>
        </div>
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-red-500">{{ companyStats.suspended }}</p>
          <p class="text-xs text-text-tertiary">Suspendues</p>
        </div>
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-amber-500">{{ companyStats.trial }}</p>
          <p class="text-xs text-text-tertiary">Essai</p>
        </div>
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-text-primary">{{ companyStats.registrations_last_7_days }}</p>
          <p class="text-xs text-text-tertiary">7 jours</p>
        </div>
        <div class="p-3 rounded-lg bg-surface border border-border text-center">
          <p class="text-lg font-bold text-text-primary">{{ companyStats.registrations_last_30_days }}</p>
          <p class="text-xs text-text-tertiary">30 jours</p>
        </div>
      </div>

      <!-- Table -->
      <div class="rounded-xl bg-surface border border-border shadow-sm overflow-hidden">
        <div v-if="loading" class="p-8 text-center text-sm text-text-tertiary">Chargement...</div>
        <table v-else class="w-full">
          <thead>
            <tr class="border-b border-border text-left text-xs font-semibold text-text-tertiary uppercase tracking-wider">
              <th class="px-4 py-3">Entreprise</th>
              <th class="px-4 py-3">Statut</th>
              <th class="px-4 py-3">Taille</th>
              <th class="px-4 py-3">Secteur</th>
              <th class="px-4 py-3">Utilisateurs</th>
              <th class="px-4 py-3">Date inscription</th>
              <th class="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="c in companies" :key="c.id" class="hover:bg-surface-tertiary/50 transition-colors">
              <td class="px-4 py-3">
                <p class="text-sm font-medium text-text-primary">{{ c.name }}</p>
                <p class="text-xs text-text-tertiary">{{ c.slug }}</p>
              </td>
              <td class="px-4 py-3">
                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full" :class="statusClass(c.status)">{{ c.status }}</span>
              </td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ c.size ? sizeLabel(c.size) : '-' }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ c.industry || '-' }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ c.users_count }}</td>
              <td class="px-4 py-3 text-sm text-text-secondary">{{ formatDate(c.created_at) }}</td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button @click="showCompanyDetails(c.id)" class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-blue-500 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">Détails</button>
                  <button v-if="c.status !== 'suspended'" @click="suspend(c)" class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">Bloquer</button>
                  <button v-if="c.status === 'suspended'" @click="activate(c)" class="px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-emerald-500 hover:text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">Débloquer</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && companies.length === 0" class="p-8 text-center text-sm text-text-tertiary">Aucune entreprise trouvée</div>

        <!-- Pagination -->
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

    <!-- Modal Détails Entreprise -->
    <div v-if="selectedCompany" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm animate-fade-in" @click.self="selectedCompany = null">
      <div class="bg-surface w-full max-w-2xl rounded-2xl shadow-2xl border border-border overflow-hidden flex flex-col max-h-[90vh]">
        <div class="flex items-center justify-between p-6 border-b border-border">
          <div>
            <h3 class="text-xl font-bold text-text-primary">{{ selectedCompany.name }}</h3>
            <p class="text-sm text-text-tertiary">ID: {{ selectedCompany.uuid || selectedCompany.id }}</p>
          </div>
          <button @click="selectedCompany = null" class="text-text-tertiary hover:text-text-primary transition-colors">&times;</button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 bg-surface-secondary/30 space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-surface p-4 rounded-xl border border-border">
              <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-text-tertiary uppercase font-bold tracking-wider">Informations</p>
                <button v-if="!editing" @click="startEdit" class="text-[11px] font-bold uppercase tracking-wider text-blue-500 hover:text-blue-700">Modifier</button>
              </div>
              <template v-if="editing">
                <div class="space-y-2.5 mt-3">
                  <div>
                    <label class="block text-xs text-text-secondary mb-0.5">Email</label>
                    <input v-model="editForm.email" type="email" class="w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                  <div>
                    <label class="block text-xs text-text-secondary mb-0.5">Téléphone</label>
                    <input v-model="editForm.phone" type="text" class="w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20" />
                  </div>
                  <div>
                    <label class="block text-xs text-text-secondary mb-0.5">Secteur d'activité</label>
                    <select v-model="editForm.industry" class="w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                      <option value="">Non défini</option>
                      <option value="Agriculture & Agroalimentaire">Agriculture & Agroalimentaire</option>
                      <option value="Commerce & Distribution">Commerce & Distribution</option>
                      <option value="BTP & Immobilier">BTP & Immobilier</option>
                      <option value="Industrie & Manufacture">Industrie & Manufacture</option>
                      <option value="Services & Consulting">Services & Consulting</option>
                      <option value="Transport & Logistique">Transport & Logistique</option>
                      <option value="Santé & Pharmacie">Santé & Pharmacie</option>
                      <option value="Éducation & Formation">Éducation & Formation</option>
                      <option value="Technologie & Télécoms">Technologie & Télécoms</option>
                      <option value="Finance & Assurance">Finance & Assurance</option>
                      <option value="Hôtellerie & Restauration">Hôtellerie & Restauration</option>
                      <option value="Énergie & Environnement">Énergie & Environnement</option>
                      <option value="Autre">Autre</option>
                    </select>
                    <input
                      v-if="editForm.industry === 'Autre'"
                      v-model="editForm.otherIndustry"
                      type="text"
                      placeholder="Précisez le secteur..."
                      class="mt-1.5 w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary placeholder:text-text-tertiary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-text-secondary mb-0.5">Taille</label>
                    <select v-model="editForm.size" class="w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                      <option value="">Non définie</option>
                      <option value="petite">Petite</option>
                      <option value="moyenne">Moyenne</option>
                      <option value="grande">Grande</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs text-text-secondary mb-0.5">Adresse</label>
                    <textarea v-model="editForm.address" rows="2" class="w-full px-3 py-1.5 text-sm rounded-lg border border-border bg-surface text-text-primary focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"></textarea>
                  </div>
                  <div class="flex gap-2 pt-1">
                    <button @click="saveCompany" class="px-3 py-1.5 text-xs font-semibold bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">Enregistrer</button>
                    <button @click="cancelEdit" class="px-3 py-1.5 text-xs font-medium text-text-secondary hover:bg-surface-tertiary rounded-lg transition-colors">Annuler</button>
                  </div>
                </div>
              </template>
              <template v-else>
                <p class="text-sm"><span class="font-medium text-text-secondary">Email :</span> {{ selectedCompany.email || '-' }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Téléphone :</span> {{ selectedCompany.phone || '-' }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Secteur :</span> {{ selectedCompany.industry || '-' }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Taille :</span> {{ selectedCompany.size ? sizeLabel(selectedCompany.size) : '-' }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Adresse :</span> {{ selectedCompany.address || '-' }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Devise :</span> {{ selectedCompany.currency || selectedCompany.currency_code || '-' }}</p>
                <p class="text-sm mt-2"><span class="font-medium text-text-secondary">Date création :</span> {{ formatDate(selectedCompany.created_at) }}</p>
                <p class="text-sm"><span class="font-medium text-text-secondary">Dernière connexion :</span> {{ formatDate(selectedCompany.last_login) }}</p>
              </template>
            </div>
            <div class="bg-surface p-4 rounded-xl border border-border">
              <p class="text-xs text-text-tertiary uppercase font-bold tracking-wider mb-1">Activité & Utilisation</p>
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-text-secondary">Plan Actuel</span>
                <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-purple-50 text-purple-600">{{ selectedCompany.plan?.name || 'Aucun' }}</span>
              </div>
              <p class="text-sm"><span class="font-medium text-text-secondary">Propriétaire :</span> {{ selectedCompany.owner?.name || '-' }}</p>
              <p class="text-sm"><span class="font-medium text-text-secondary">Utilisateurs :</span> {{ selectedCompany.users_count || (selectedCompany.users ? selectedCompany.users.length : 0) }}</p>
              <p class="text-sm"><span class="font-medium text-text-secondary">Clients enregistrés :</span> {{ selectedCompany.customers_count || 0 }}</p>
              <p class="text-sm"><span class="font-medium text-text-secondary">Produits :</span> {{ selectedCompany.products_count || 0 }}</p>
              <p class="text-sm"><span class="font-medium text-text-secondary">Factures émises :</span> {{ selectedCompany.invoices_count || 0 }}</p>
            </div>
          </div>
          
          <div v-if="selectedCompany.status === 'suspended'" class="p-4 rounded-xl bg-rose-50 border border-rose-200">
            <div class="flex items-start gap-3">
              <span class="text-rose-500 text-xl">⚠️</span>
              <div>
                <h4 class="text-sm font-bold text-rose-800">Entreprise Suspendue</h4>
                <p class="text-xs text-rose-600 mt-1">Cette entreprise est actuellement bloquée. Ses utilisateurs ne peuvent plus se connecter ni utiliser l'API.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="p-4 border-t border-border bg-surface flex justify-end gap-3">
          <button @click="selectedCompany = null" class="px-4 py-2 text-sm font-medium text-text-secondary hover:bg-surface-tertiary rounded-lg transition-colors">Fermer</button>
          <button v-if="selectedCompany.status !== 'suspended'" @click="suspend(selectedCompany); selectedCompany.status = 'suspended'" class="px-4 py-2 text-sm font-medium bg-rose-500 text-white hover:bg-rose-600 rounded-lg transition-colors shadow-sm">Bloquer l'entreprise</button>
          <button v-if="selectedCompany.status === 'suspended'" @click="activate(selectedCompany); selectedCompany.status = 'active'" class="px-4 py-2 text-sm font-medium bg-emerald-500 text-white hover:bg-emerald-600 rounded-lg transition-colors shadow-sm">Débloquer l'entreprise</button>
        </div>
      </div>
    </div>
  </SuperAdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import SuperAdminLayout from './SuperAdminLayout.vue'

const companies = ref([])
const companyStats = ref(null)
const loading = ref(true)
const pagination = ref(null)
const selectedCompany = ref(null)
const editing = ref(false)
const editForm = reactive({ email: '', phone: '', industry: '', size: '', address: '', otherIndustry: '' })
const filters = reactive({ search: '', status: '', size: '' })

let debounceTimer = null
function debouncedSearch() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(fetchCompanies, 300)
}

function statusClass(status) {
  const map = { active: 'bg-emerald-100 text-emerald-700', suspended: 'bg-red-100 text-red-700', trial: 'bg-amber-100 text-amber-700', disabled: 'bg-gray-100 text-gray-500' }
  return map[status] || 'bg-gray-100 text-gray-500'
}

function sizeLabel(size) {
  const map = { petite: 'Petite', moyenne: 'Moyenne', grande: 'Grande' }
  return map[size] || size
}

const industryOptions = [
  'Agriculture & Agroalimentaire',
  'Commerce & Distribution',
  'BTP & Immobilier',
  'Industrie & Manufacture',
  'Services & Consulting',
  'Transport & Logistique',
  'Santé & Pharmacie',
  'Éducation & Formation',
  'Technologie & Télécoms',
  'Finance & Assurance',
  'Hôtellerie & Restauration',
  'Énergie & Environnement',
  'Autre',
]

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function fetchCompanies(page = 1) {
  loading.value = true
  try {
    const params = { page, per_page: 20, ...filters }
    Object.keys(params).forEach(k => { if (!params[k]) delete params[k] })
    const { data } = await axios.get('/admin/companies', { params })
    companies.value = data.data || []
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
  fetchCompanies(page)
}

async function showCompanyDetails(id) {
  try {
    const { data } = await axios.get(`/admin/companies/${id}`)
    selectedCompany.value = data.data || data // Handles Resource wrapper
    editing.value = false
  } catch (err) {
    console.error('Erreur chargement détails entreprise', err)
  }
}

function startEdit() {
  const currentIndustry = selectedCompany.value.industry || ''
  const customIndustry = !!currentIndustry && !industryOptions.includes(currentIndustry)
  Object.assign(editForm, {
    email: selectedCompany.value.email || '',
    phone: selectedCompany.value.phone || '',
    industry: customIndustry ? 'Autre' : currentIndustry,
    size: selectedCompany.value.size || '',
    address: selectedCompany.value.address || '',
    otherIndustry: customIndustry ? currentIndustry : '',
  })
  editing.value = true
}

function cancelEdit() {
  editing.value = false
}

async function saveCompany() {
  const payload = { ...editForm }
  if (payload.industry === 'Autre') {
    payload.industry = payload.otherIndustry.trim() || 'Autre'
  }
  delete payload.otherIndustry
  try {
    await axios.put(`/admin/companies/${selectedCompany.value.id}`, payload)
    await showCompanyDetails(selectedCompany.value.id)
    fetchCompanies()
  } catch (err) { console.error(err) }
}

async function suspend(company) {
  if (!confirm(`Attention : Bloquer l'entreprise "${company.name}" ?\nTOUS ses utilisateurs seront immédiatement déconnectés et ne pourront plus accéder à l'application.`)) return
  try {
    await axios.post(`/admin/companies/${company.id}/suspend`)
    company.status = 'suspended'
    fetchCompanies()
  } catch (err) { console.error(err) }
}

async function activate(company) {
  try {
    await axios.post(`/admin/companies/${company.id}/activate`)
    company.status = 'active'
    fetchCompanies()
  } catch (err) { console.error(err) }
}

onMounted(async () => {
  await fetchCompanies()
  try {
    const { data } = await axios.get('/admin/companies/stats')
    companyStats.value = data
  } catch {}
})
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
