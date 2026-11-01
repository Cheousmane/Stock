<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="RH" title="Paie & Employés" subtitle="Gestion des employés et des bulletins de paie">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="refreshAll" :title="$t('common.refresh')">
            <ArrowPathIcon class="w-4 h-4" />
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Tabs -->
      <div class="flex gap-2 border-b border-border">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-4 py-2 text-sm font-bold border-b-2 transition"
          :class="activeTab === tab.key ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-text-tertiary hover:text-text-primary'"
        >
          {{ tab.label }}
        </button>
      </div>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>

      <!-- Employees tab -->
      <template v-else-if="activeTab === 'employees'">
        <div class="flex justify-end">
          <BaseButton variant="primary" size="sm" @click="openEmployeeModal()">
            <PlusIcon class="w-4 h-4" /> Nouvel employé
          </BaseButton>
        </div>

        <BaseEmptyState v-if="employees.length === 0" title="Aucun employé" description="Ajoutez votre premier employé pour commencer la paie." />

        <div v-else class="rounded-xl border border-border overflow-hidden bg-surface">
          <table class="w-full text-sm">
            <thead class="bg-surface-secondary/60">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">Nom</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">Poste</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">Contrat</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-text-tertiary uppercase">Salaire de base</th>
                <th class="px-4 py-3 text-center text-xs font-bold text-text-tertiary uppercase">Statut</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-text-tertiary uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="emp in employees" :key="emp.id" class="hover:bg-surface-secondary/40">
                <td class="px-4 py-3 font-semibold text-text-primary">{{ emp.first_name }} {{ emp.last_name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ emp.position || '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ contractLabel(emp.contract_type) }}</td>
                <td class="px-4 py-3 text-right tabular-nums font-bold text-text-primary">{{ formatPrice(emp.base_salary_xof) }}</td>
                <td class="px-4 py-3 text-center">
                  <BaseBadge :variant="emp.status === 'active' ? 'success' : 'default'" size="xs">{{ emp.status }}</BaseBadge>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-1.5">
                    <BaseButton variant="ghost" size="xs" @click="openEmployeeModal(emp)">Modifier</BaseButton>
                    <BaseButton variant="danger-ghost" size="xs" @click="deleteEmployee(emp)">Supprimer</BaseButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Payroll runs tab -->
      <template v-else>
        <div class="flex justify-end">
          <BaseButton variant="primary" size="sm" @click="openRunModal">
            <PlusIcon class="w-4 h-4" /> Nouvelle paie
          </BaseButton>
        </div>

        <BaseEmptyState v-if="runs.length === 0" title="Aucune paie" description="Créez une période de paie pour générer les bulletins." />

        <div v-else class="space-y-3">
          <div
            v-for="run in runs"
            :key="run.id"
            class="rounded-xl border border-border bg-surface p-4"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="text-sm font-extrabold text-text-primary">Paie {{ run.period }}</p>
                <p class="text-xs text-text-tertiary">{{ formatDate(run.period_start) }} → {{ formatDate(run.period_end) }}</p>
              </div>
              <div class="flex items-center gap-3 text-xs font-bold tabular-nums">
                <span class="text-text-secondary">Brut: {{ formatPrice(run.total_gross_xof) }}</span>
                <span class="text-text-secondary">Net: {{ formatPrice(run.total_net_xof) }}</span>
                <BaseBadge :variant="run.status === 'paid' ? 'success' : run.status === 'validated' ? 'info' : 'default'" size="xs">{{ run.status }}</BaseBadge>
              </div>
              <div class="flex gap-1.5">
                <BaseButton v-if="run.status === 'draft'" variant="secondary" size="xs" @click="validateRun(run)">Valider</BaseButton>
                <BaseButton v-if="run.status !== 'paid'" variant="primary" size="xs" @click="payRun(run)">Payer</BaseButton>
                <BaseButton variant="ghost" size="xs" @click="viewRun(run)">Détails</BaseButton>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Employee modal -->
    <BaseModal v-model="employeeModalOpen" :title="editingEmployee ? 'Modifier employe' : 'Nouvel employe'">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.first_name" label="Prénom" required />
          <BaseInput v-model="empForm.last_name" label="Nom" required />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.email" label="Email" type="email" />
          <BaseInput v-model="empForm.phone" label="Téléphone" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.position" label="Poste" />
          <BaseInput v-model="empForm.department" label="Département" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseSelect v-model="empForm.contract_type" label="Contrat" :options="contractOptions" />
          <BaseSelect v-model="empForm.status" label="Statut" :options="statusOptions" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model.number="empForm.base_salary_xof" label="Salaire de base (XOF)" type="number" required />
          <BaseSelect v-model="empForm.payment_method" label="Paiement" :options="paymentOptions" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.hire_date" label="Date d'embauche" type="date" />
          <BaseInput v-model="empForm.end_date" label="Date de fin" type="date" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.bank_name" label="Banque" />
          <BaseInput v-model="empForm.bank_account" label="Compte bancaire" />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="employeeModalOpen = false">Annuler</BaseButton>
        <BaseButton variant="primary" :loading="saving" @click="saveEmployee">Enregistrer</BaseButton>
      </template>
    </BaseModal>

    <!-- New payroll run modal -->
    <BaseModal v-model="runModalOpen" title="Nouvelle période de paie">
      <div class="space-y-3">
        <BaseInput v-model="runForm.period" label="Période (YYYY-MM)" placeholder="2026-11" required />
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="runForm.period_start" label="Début" type="date" required />
          <BaseInput v-model="runForm.period_end" label="Fin" type="date" required />
        </div>
        <BaseInput v-model="runForm.notes" label="Notes" />
        <p class="text-xs text-text-tertiary">Un bulletin sera généré pour chaque employé actif.</p>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="runModalOpen = false">Annuler</BaseButton>
        <BaseButton variant="primary" :loading="saving" @click="createRun">Créer</BaseButton>
      </template>
    </BaseModal>

    <!-- Run detail modal -->
    <BaseModal v-model="runDetailOpen" :title="selectedRun ? 'Paie ' + selectedRun.period : 'Détails'">
      <div v-if="selectedRun" class="space-y-3">
        <div class="grid grid-cols-3 gap-3 text-center">
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">Brut</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_gross_xof) }}</p>
          </div>
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">Déductions</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_deductions_xof) }}</p>
          </div>
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">Net</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_net_xof) }}</p>
          </div>
        </div>
        <div class="rounded-xl border border-border overflow-hidden">
          <table class="w-full text-xs">
            <thead class="bg-surface-secondary/60">
              <tr>
                <th class="px-3 py-2 text-left font-bold text-text-tertiary uppercase">Employé</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">Brut</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">Déductions</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">Net</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="slip in (selectedRun.payslips || [])" :key="slip.id">
                <td class="px-3 py-2 text-text-primary font-semibold">{{ slip.employee?.first_name }} {{ slip.employee?.last_name }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ formatPrice(slip.gross_salary_xof) }}</td>
                <td class="px-3 py-2 text-right tabular-nums">{{ formatPrice(slip.deductions_xof) }}</td>
                <td class="px-3 py-2 text-right tabular-nums font-bold">{{ formatPrice(slip.net_salary_xof) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="runDetailOpen = false">Fermer</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import AdminLayout from '../../Components/AdminLayout.vue'
import BaseButton from '../../Components/ui/BaseButton.vue'
import BaseCard from '../../Components/ui/BaseCard.vue'
import BaseBadge from '../../Components/ui/BaseBadge.vue'
import BasePageHeader from '../../Components/ui/BasePageHeader.vue'
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue'
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue'
import BaseModal from '../../Components/ui/BaseModal.vue'
import BaseInput from '../../Components/ui/BaseInput.vue'
import BaseSelect from '../../Components/ui/BaseSelect.vue'
import { PlusIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { formatPrice as fmtPrice } from '../../utils/format'

const activeTab = ref('employees')
const tabs = [
  { key: 'employees', label: 'Employés' },
  { key: 'runs', label: 'Paies' },
]
const loading = ref(true)
const employees = ref([])
const runs = ref([])
const saving = ref(false)

const employeeModalOpen = ref(false)
const editingEmployee = ref(null)
const empForm = ref({})

const runModalOpen = ref(false)
const runForm = ref({ period: '', period_start: '', period_end: '', notes: '' })

const runDetailOpen = ref(false)
const selectedRun = ref(null)

const contractOptions = [
  { value: 'cdi', label: 'CDI' },
  { value: 'cdd', label: 'CDD' },
  { value: 'stage', label: 'Stage' },
  { value: 'freelance', label: 'Freelance' },
]
const statusOptions = [
  { value: 'active', label: 'Actif' },
  { value: 'suspended', label: 'Suspendu' },
  { value: 'terminated', label: 'Terminé' },
]
const paymentOptions = [
  { value: 'bank', label: 'Virement bancaire' },
  { value: 'cash', label: 'Espèces' },
  { value: 'mobile_money', label: 'Mobile Money' },
]

function contractLabel(v) {
  const m = { cdi: 'CDI', cdd: 'CDD', stage: 'Stage', freelance: 'Freelance' }
  return m[v] || v || '—'
}
function formatPrice(v) { return fmtPrice(v || 0) + ' XOF' }
function formatDate(v) { return v ? new Date(v).toLocaleDateString('fr-FR') : '—' }

async function refreshAll() {
  loading.value = true
  try {
    const [e, r] = await Promise.all([
      axios.get('/employees'),
      axios.get('/payroll-runs'),
    ])
    employees.value = e.data.data || []
    runs.value = r.data.data || []
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

function openEmployeeModal(emp = null) {
  editingEmployee.value = emp
  empForm.value = emp ? { ...emp } : {
    first_name: '', last_name: '', email: '', phone: '', position: '', department: '',
    contract_type: 'cdi', status: 'active', base_salary_xof: 0, payment_method: 'bank',
    bank_name: '', bank_account: '', hire_date: '', end_date: '',
  }
  employeeModalOpen.value = true
}

async function saveEmployee() {
  saving.value = true
  try {
    if (editingEmployee.value) {
      await axios.put(`/employees/${editingEmployee.value.id}`, empForm.value)
    } else {
      await axios.post('/employees', empForm.value)
    }
    employeeModalOpen.value = false
    await refreshAll()
  } catch (err) {
    console.error(err)
    alert(err.response?.data?.message || 'Erreur')
  } finally {
    saving.value = false
  }
}

async function deleteEmployee(emp) {
  if (!confirm(`Supprimer ${emp.first_name} ${emp.last_name} ?`)) return
  try {
    await axios.delete(`/employees/${emp.id}`)
    await refreshAll()
  } catch (err) { console.error(err) }
}

function openRunModal() {
  const now = new Date()
  const ym = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  const start = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().slice(0, 10)
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10)
  runForm.value = { period: ym, period_start: start, period_end: end, notes: '' }
  runModalOpen.value = true
}

async function createRun() {
  saving.value = true
  try {
    await axios.post('/payroll-runs', runForm.value)
    runModalOpen.value = false
    await refreshAll()
  } catch (err) {
    console.error(err)
    alert(err.response?.data?.message || 'Erreur')
  } finally {
    saving.value = false
  }
}

async function validateRun(run) {
  try {
    await axios.post(`/payroll-runs/${run.id}/validate`)
    await refreshAll()
  } catch (err) { console.error(err); alert(err.response?.data?.message || 'Erreur') }
}

async function payRun(run) {
  if (!confirm('Marquer cette paie comme payée ? Des dépenses seront créées.')) return
  try {
    await axios.post(`/payroll-runs/${run.id}/pay`)
    await refreshAll()
  } catch (err) { console.error(err); alert(err.response?.data?.message || 'Erreur') }
}

async function viewRun(run) {
  try {
    const { data } = await axios.get(`/payroll-runs/${run.id}`)
    selectedRun.value = data.data
    runDetailOpen.value = true
  } catch (err) { console.error(err) }
}

onMounted(refreshAll)
</script>
