<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader :eyebrow="$t('payroll.hr')" :title="$t('payroll.title')" :subtitle="$t('payroll.description')">
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
            <PlusIcon class="w-4 h-4" /> {{ $t('payroll.new_employee') }}
          </BaseButton>
        </div>

        <BaseEmptyState v-if="employees.length === 0" :title="$t('payroll.no_employees')" :description="$t('payroll.no_employees_description')" />

        <div v-else class="rounded-xl border border-border overflow-hidden bg-surface">
          <table class="w-full text-sm">
            <thead class="bg-surface-secondary/60">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">{{ $t('payroll.name') }}</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">{{ $t('payroll.position') }}</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-text-tertiary uppercase">{{ $t('payroll.contract') }}</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-text-tertiary uppercase">{{ $t('payroll.base_salary') }}</th>
                <th class="px-4 py-3 text-center text-xs font-bold text-text-tertiary uppercase">{{ $t('common.status') }}</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-text-tertiary uppercase">{{ $t('common.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <tr v-for="emp in employees" :key="emp.id" class="hover:bg-surface-secondary/40">
                <td class="px-4 py-3 font-semibold text-text-primary">{{ emp.first_name }} {{ emp.last_name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ emp.position || '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ contractLabel(emp.contract_type) }}</td>
                <td class="px-4 py-3 text-right tabular-nums font-bold text-text-primary">{{ formatPrice(emp.base_salary_xof) }}</td>
                <td class="px-4 py-3 text-center">
                  <BaseBadge :variant="emp.status === 'active' ? 'success' : 'default'" size="xs">{{ t(`payroll.status.${emp.status}`) }}</BaseBadge>
                </td>
                <td class="px-4 py-3 text-right">
                  <div class="flex justify-end gap-1.5">
                    <BaseButton variant="ghost" size="xs" @click="openEmployeeModal(emp)">{{ $t('common.edit') }}</BaseButton>
                    <BaseButton variant="danger-ghost" size="xs" @click="deleteEmployee(emp)">{{ $t('common.delete') }}</BaseButton>
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
            <PlusIcon class="w-4 h-4" /> {{ $t('payroll.new_run') }}
          </BaseButton>
        </div>

        <BaseEmptyState v-if="runs.length === 0" :title="$t('payroll.no_runs')" :description="$t('payroll.no_runs_description')" />

        <div v-else class="space-y-3">
          <div
            v-for="run in runs"
            :key="run.id"
            class="rounded-xl border border-border bg-surface p-4"
          >
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <p class="text-sm font-extrabold text-text-primary">{{ $t('payroll.run_title', { period: run.period }) }}</p>
                <p class="text-xs text-text-tertiary">{{ formatDate(run.period_start) }} → {{ formatDate(run.period_end) }}</p>
              </div>
              <div class="flex items-center gap-3 text-xs font-bold tabular-nums">
                <span class="text-text-secondary">{{ $t('payroll.gross') }}: {{ formatPrice(run.total_gross_xof) }}</span>
                <span class="text-text-secondary">{{ $t('payroll.net') }}: {{ formatPrice(run.total_net_xof) }}</span>
                <BaseBadge :variant="run.status === 'paid' ? 'success' : run.status === 'validated' ? 'info' : 'default'" size="xs">{{ t(`payroll.run_status.${run.status}`) }}</BaseBadge>
              </div>
              <div class="flex gap-1.5">
                <BaseButton v-if="run.status === 'draft'" variant="secondary" size="xs" @click="validateRun(run)">{{ $t('common.validate') }}</BaseButton>
                <BaseButton v-if="run.status !== 'paid'" variant="primary" size="xs" @click="payRun(run)">{{ $t('common.pay') }}</BaseButton>
                <BaseButton variant="ghost" size="xs" @click="viewRun(run)">{{ $t('common.details') }}</BaseButton>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Employee modal -->
    <BaseModal v-model="employeeModalOpen" :title="editingEmployee ? $t('payroll.edit_employee') : $t('payroll.new_employee')">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.first_name" :label="$t('payroll.first_name')" required />
          <BaseInput v-model="empForm.last_name" :label="$t('payroll.name')" required />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.email" :label="$t('common.email')" type="email" />
          <BaseInput v-model="empForm.phone" :label="$t('common.phone')" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.position" :label="$t('payroll.position')" />
          <BaseInput v-model="empForm.department" :label="$t('payroll.department')" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseSelect v-model="empForm.contract_type" :label="$t('payroll.contract')" :options="contractOptions" />
          <BaseSelect v-model="empForm.status" :label="$t('common.status')" :options="statusOptions" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model.number="empForm.base_salary_xof" :label="$t('payroll.base_salary_xof')" type="number" required />
          <BaseSelect v-model="empForm.payment_method" :label="$t('payroll.payment_method')" :options="paymentOptions" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.hire_date" :label="$t('payroll.hire_date')" type="date" />
          <BaseInput v-model="empForm.end_date" :label="$t('payroll.end_date')" type="date" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="empForm.bank_name" :label="$t('payroll.bank')" />
          <BaseInput v-model="empForm.bank_account" :label="$t('payroll.bank_account')" />
        </div>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="employeeModalOpen = false">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="saving" @click="saveEmployee">{{ $t('common.save') }}</BaseButton>
      </template>
    </BaseModal>

    <!-- New payroll run modal -->
    <BaseModal v-model="runModalOpen" :title="$t('payroll.new_period')">
      <div class="space-y-3">
        <BaseInput v-model="runForm.period" :label="$t('payroll.period')" placeholder="2026-11" required />
        <div class="grid grid-cols-2 gap-3">
          <BaseInput v-model="runForm.period_start" :label="$t('payroll.start')" type="date" required />
          <BaseInput v-model="runForm.period_end" :label="$t('payroll.end')" type="date" required />
        </div>
        <BaseInput v-model="runForm.notes" :label="$t('common.notes')" />
        <p class="text-xs text-text-tertiary">{{ $t('payroll.run_note') }}</p>
      </div>
      <template #footer>
        <BaseButton variant="ghost" @click="runModalOpen = false">{{ $t('common.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="saving" @click="createRun">{{ $t('common.create') }}</BaseButton>
      </template>
    </BaseModal>

    <!-- Run detail modal -->
    <BaseModal v-model="runDetailOpen" :title="selectedRun ? $t('payroll.run_title', { period: selectedRun.period }) : $t('common.details')">
      <div v-if="selectedRun" class="space-y-3">
        <div class="grid grid-cols-3 gap-3 text-center">
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">{{ $t('payroll.gross') }}</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_gross_xof) }}</p>
          </div>
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">{{ $t('payroll.deductions') }}</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_deductions_xof) }}</p>
          </div>
          <div class="p-3 rounded-xl bg-surface-secondary/60">
            <p class="text-xs text-text-tertiary">{{ $t('payroll.net') }}</p>
            <p class="text-sm font-bold tabular-nums">{{ formatPrice(selectedRun.total_net_xof) }}</p>
          </div>
        </div>
        <div class="rounded-xl border border-border overflow-hidden">
          <table class="w-full text-xs">
            <thead class="bg-surface-secondary/60">
              <tr>
                <th class="px-3 py-2 text-left font-bold text-text-tertiary uppercase">{{ $t('payroll.employee') }}</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">{{ $t('payroll.gross') }}</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">{{ $t('payroll.deductions') }}</th>
                <th class="px-3 py-2 text-right font-bold text-text-tertiary uppercase">{{ $t('payroll.net') }}</th>
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
        <BaseButton variant="ghost" @click="runDetailOpen = false">{{ $t('common.close') }}</BaseButton>
      </template>
    </BaseModal>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
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

const { t, locale } = useI18n()
const activeTab = ref('employees')
const tabs = computed(() => [
  { key: 'employees', label: t('payroll.employees') },
  { key: 'runs', label: t('payroll.runs') },
])
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

const contractOptions = computed(() => ['cdi', 'cdd', 'stage', 'freelance'].map(value => ({ value, label: t(`payroll.contract_types.${value}`) })))
const statusOptions = computed(() => ['active', 'suspended', 'terminated'].map(value => ({ value, label: t(`payroll.status.${value}`) })))
const paymentOptions = computed(() => ['bank', 'cash', 'mobile_money'].map(value => ({ value, label: t(`payroll.payment_methods.${value}`) })))

function contractLabel(v) { return v ? t(`payroll.contract_types.${v}`) : '—' }
function formatPrice(v) { return new Intl.NumberFormat(locale.value).format(v || 0) + ' XOF' }
function formatDate(v) { return v ? new Date(v).toLocaleDateString(locale.value) : '—' }

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
    alert(err.response?.data?.message || t('common.error'))
  } finally {
    saving.value = false
  }
}

async function deleteEmployee(emp) {
  if (!confirm(t('payroll.confirm_delete_employee', { name: `${emp.first_name} ${emp.last_name}` }))) return
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
    alert(err.response?.data?.message || t('common.error'))
  } finally {
    saving.value = false
  }
}

async function validateRun(run) {
  try {
    await axios.post(`/payroll-runs/${run.id}/validate`)
    await refreshAll()
  } catch (err) { console.error(err); alert(err.response?.data?.message || t('common.error')) }
}

async function payRun(run) {
  if (!confirm(t('payroll.confirm_pay_run'))) return
  try {
    await axios.post(`/payroll-runs/${run.id}/pay`)
    await refreshAll()
  } catch (err) { console.error(err); alert(err.response?.data?.message || t('common.error')) }
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
