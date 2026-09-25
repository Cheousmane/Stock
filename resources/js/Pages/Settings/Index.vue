<template>
  <AdminLayout>
    <div class="space-y-4">
      <BasePageHeader eyebrow="Système" title="Paramètres" subtitle="Configuration de l'entreprise, rôles et préférences">
        <template #actions>
          <BaseButton variant="ghost" size="sm" @click="fetchData()" title="Rafraîchir">
            <span class="text-current"><ArrowPathIcon class="w-4 h-4" /></span>
          </BaseButton>
        </template>
      </BasePageHeader>

      <template v-if="loading">
        <BaseSkeleton :count="6" />
      </template>

      <template v-else>
        <!-- Company Info -->
        <BaseCard title="Entreprise" padding="lg">
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-text-secondary tracking-wide mb-1">Logo</label>
              <div class="flex items-center gap-4">
                <div v-if="logoPreview || logoUrl" class="flex-shrink-0 w-16 h-16 rounded-lg border border-border overflow-hidden bg-surface-tertiary flex items-center justify-center">
                  <img :src="logoPreview || logoUrl" class="w-full h-full object-contain" />
                </div>
                <div class="flex-1">
                  <input ref="logoInput" type="file" accept="image/*" @change="onLogoChange"
                    class="block w-full text-sm text-text-tertiary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" />
                  <div class="flex gap-2 mt-2">
                    <BaseButton v-if="selectedLogo" variant="primary" size="xs" type="button" @click="uploadLogo" :loading="uploadingLogo">
                      {{ uploadingLogo ? 'Upload...' : 'Uploader le logo' }}
                    </BaseButton>
                    <BaseButton v-if="logoUrl && !selectedLogo" variant="danger-ghost" size="xs" type="button" @click="deleteLogo" :loading="deletingLogo">
                      {{ deletingLogo ? 'Suppression...' : 'Supprimer le logo' }}
                    </BaseButton>
                  </div>
                </div>
              </div>
            </div>

            <BaseInput v-model="form.company_name" :label="$t('settings.company_name')" required />

            <BaseInput v-model="form.slug" :label="$t('settings.slug')" disabled />

            <BaseInput v-model="form.email" type="email" :label="$t('settings.email')" />

            <BaseInput v-model="form.phone" type="tel" :label="$t('settings.phone')" />

            <div>
              <label class="block text-xs font-medium text-text-secondary tracking-wide mb-1">{{ $t('settings.address') }}</label>
              <textarea v-model="form.address" rows="2"
                class="block w-full bg-surface border border-border rounded-lg px-3 py-2 text-sm text-text-primary placeholder:text-text-tertiary focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20"></textarea>
            </div>

            <BaseSelect v-model="form.industry" :label="$t('auth.industry')" :options="[
              { value: '', label: $t('settings.not_defined') },
              ...industryOptions.map(s => ({ value: s, label: s })),
            ]" />
            <BaseInput
              v-if="form.industry === 'Autre'"
              v-model="form.otherIndustry"
              :label="$t('settings.specify_industry')"
              :placeholder="$t('settings.specify_industry') + '...'"
            />

            <BaseSelect v-model="form.size" :label="$t('auth.company_size')" :options="[
              { value: '', label: $t('settings.not_defined_f') },
              { value: 'petite', label: $t('auth.size_petite') },
              { value: 'moyenne', label: $t('auth.size_moyenne') },
              { value: 'grande', label: $t('auth.size_grande') },
            ]" />

            <BaseSelect v-model="form.currency" :label="$t('settings.currency')" :options="[
              { value: 'XOF', label: 'XOF (F CFA)' },
              { value: 'EUR', label: 'EUR (€)' },
              { value: 'USD', label: 'USD ($)' },
              { value: 'GBP', label: 'GBP (£)' },
            ]" />

            <div class="flex gap-3 pt-4 border-t border-border">
              <BaseButton type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
            </div>
          </form>
        </BaseCard>

        <!-- Legal & Payment Info -->
        <BaseCard :title="$t('settings.legal_payment_title')" :subtitle="$t('settings.legal_payment_subtitle')" padding="lg">
          <form @submit.prevent="submit" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <BaseSelect v-model="form.locale" :label="$t('settings.doc_language')" :options="[
                { value: 'fr', label: 'Français' },
                { value: 'en', label: 'English' },
              ]" />
              <div>
                <label class="block text-xs font-medium text-text-secondary tracking-wide mb-1">{{ $t('settings.accent_color') }}</label>
                <div class="flex items-center gap-2">
                  <input v-model="form.brand_color" type="color"
                    class="w-10 h-9 rounded-lg border border-border bg-surface cursor-pointer" />
                  <input v-model="form.brand_color" type="text" maxlength="7" placeholder="#2563eb"
                    class="flex-1 px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
                </div>
              </div>
            </div>
            <div class="pt-3 border-t border-border">
              <div class="text-xs font-semibold text-text-tertiary uppercase tracking-wide mb-2">{{ $t('settings.legal_ids') }}</div>
              <div class="grid grid-cols-3 gap-4">
                <BaseInput v-model="form.legal_rc" label="RC (Registre de commerce)" />
                <BaseInput v-model="form.legal_ifu" label="IFU / NIF" />
                <BaseInput v-model="form.legal_rccm" label="RCCM" />
              </div>
            </div>
            <div class="pt-3 border-t border-border">
              <div class="text-xs font-semibold text-text-tertiary uppercase tracking-wide mb-2">{{ $t('settings.bank_details') }}</div>
              <div class="grid grid-cols-3 gap-4">
                <BaseInput v-model="form.bank_name" :label="$t('payment.bank')" :placeholder="$t('settings.ph_bank')" />
                <BaseInput v-model="form.bank_account" :label="$t('settings.bank_account')" :placeholder="$t('settings.ph_account')" />
                <BaseInput v-model="form.bank_swift" :label="$t('settings.bank_swift')" :placeholder="$t('settings.ph_swift')" />
              </div>
            </div>
            <div class="flex gap-3 pt-4 border-t border-border">
              <BaseButton type="submit" :loading="submitting">{{ $t('common.save') }}</BaseButton>
            </div>
          </form>
        </BaseCard>

        <!-- Email Configuration -->
        <BaseCard :title="$t('settings.email_config')" padding="lg">
          <form @submit.prevent="saveEmailConfig" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <BaseInput v-model="mailForm.mail_host" :label="$t('settings.smtp_host')" :placeholder="$t('settings.smtp_host_placeholder')" />
              <BaseInput v-model="mailForm.mail_port" :label="$t('settings.smtp_port')" :placeholder="$t('settings.smtp_port_placeholder')" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <BaseInput v-model="mailForm.mail_username" :label="$t('settings.smtp_username')" :placeholder="$t('settings.smtp_username_placeholder')" />
              <BaseInput v-model="mailForm.mail_password" type="password" :label="$t('settings.smtp_password')" />
            </div>
            <BaseSelect v-model="mailForm.mail_encryption" :label="$t('settings.smtp_encryption')" :options="[
              { value: 'tls', label: 'TLS' },
              { value: 'ssl', label: 'SSL' },
              { value: '', label: $t('common.none') },
            ]" />
            <div class="grid grid-cols-2 gap-4">
              <BaseInput v-model="mailForm.mail_from_address" :label="$t('settings.from_address')" placeholder="noreply@example.com" />
              <BaseInput v-model="mailForm.mail_from_name" :label="$t('settings.from_name')" :placeholder="$t('settings.from_name_placeholder')" />
            </div>
            <div class="flex gap-3 pt-4 border-t border-border">
              <BaseButton type="submit" :loading="mailSaving">{{ $t('common.save') }}</BaseButton>
              <BaseButton variant="secondary" type="button" :loading="mailTesting" @click="testMailConfig">
                {{ $t('settings.test_config') }}
              </BaseButton>
            </div>
            <p v-if="mailTestResult" :class="['text-sm', mailTestResult.success ? 'text-green-600' : 'text-red-600']">{{ mailTestResult.message }}</p>
          </form>
        </BaseCard>

        <!-- Subscription -->
        <BaseCard :title="$t('settings.subscription')" :subtitle="$t('settings.manage_subscription')" padding="lg">
          <template #actions>
            <BaseButton variant="primary" size="sm" :to="{ name: 'Subscription' }">
              <CreditCardIcon class="w-4 h-4" /> {{ $t('settings.manage_btn') }}
            </BaseButton>
          </template>
          <div v-if="companyPlan" class="space-y-4 pt-4 border-t border-border">
            <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-surface-tertiary rounded-xl">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                  <CheckCircleIcon class="w-6 h-6 text-emerald-600" />
                </div>
                <div>
                  <h3 class="font-semibold text-text-primary">{{ companyPlan.name }}</h3>
                  <p class="text-sm text-text-tertiary">{{ $t('subscription.current_plan_btn') }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <BaseBadge variant="success">
                  {{ formatPlanPrice(companyPlan.price_xof, companyPlan.currency) }}
                </BaseBadge>
              </div>
            </div>
            <div v-if="companyPlan.features && companyPlan.features.length" class="text-sm">
              <p class="text-text-tertiary mb-2">{{ $t('settings.features_included') }}</p>
              <ul class="space-y-1">
                <li v-for="feature in companyPlan.features" :key="feature" class="flex items-center gap-2 text-text-secondary">
                  <CheckIcon class="w-4 h-4 text-emerald-500 shrink-0" />
                  {{ feature }}
                </li>
              </ul>
            </div>
          </div>
          <div v-else class="flex flex-col items-center text-center py-8">
            <BaseEmptyState
              :title="$t('subscription.no_plan')"
              :description="$t('settings.no_plan_desc')"
            />
          </div>
        </BaseCard>
      </template>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BaseInput from '../../Components/ui/BaseInput.vue';
import BaseSelect from '../../Components/ui/BaseSelect.vue';
import BaseBadge from '../../Components/ui/BaseBadge.vue';
import BaseSkeleton from '../../Components/ui/BaseSkeleton.vue';
import BaseEmptyState from '../../Components/ui/BaseEmptyState.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowPathIcon, CreditCardIcon, CheckCircleIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { formatPrice as formatPlanPrice, industryOptions } from '../../utils/format';

const showToast = inject('showToast');
const { t } = useI18n();
const loading = ref(true);
const submitting = ref(false);
const logoInput = ref(null);
const selectedLogo = ref(null);
const logoPreview = ref('');
const logoUrl = ref('');
const uploadingLogo = ref(false);
const deletingLogo = ref(false);
const companyPlan = ref(null);

const form = reactive({
  company_name: '', slug: '', email: '', phone: '', address: '', currency: 'XOF', industry: '', size: '', otherIndustry: '',
  locale: 'fr', brand_color: '#2563eb', legal_rc: '', legal_ifu: '', legal_rccm: '',
  bank_name: '', bank_account: '', bank_swift: '',
});

const mailForm = reactive({
  mail_host: '', mail_port: '587', mail_username: '', mail_password: '',
  mail_encryption: 'tls', mail_from_address: '', mail_from_name: '',
});
const mailSaving = ref(false);
const mailTesting = ref(false);
const mailTestResult = ref(null);

async function fetchData() {
  loading.value = true;
  try {
    const { data } = await axios.get('/company');
    const c = data.data ?? data;
    const industry = (c.industry || '') || '';
    const customIndustry = !!industry && !industryOptions.includes(industry);
    Object.assign(form, {
      company_name: c.company_name || c.name || '',
      slug: c.slug || '',
      email: (c.email || (c.metadata?.email)) || '',
      phone: (c.phone || (c.metadata?.phone)) || '',
      address: (c.address || (c.metadata?.address)) || '',
      currency: c.currency || (c.metadata?.currency) || 'XOF',
      industry: customIndustry ? 'Autre' : industry,
      size: c.size || '',
      otherIndustry: customIndustry ? industry : '',
      locale: (c.metadata?.locale) || 'fr',
      brand_color: (c.metadata?.brand_color) || '#2563eb',
      legal_rc: (c.metadata?.legal_rc) || '',
      legal_ifu: (c.metadata?.legal_ifu) || '',
      legal_rccm: (c.metadata?.legal_rccm) || '',
      bank_name: (c.metadata?.bank_name) || '',
      bank_account: (c.metadata?.bank_account) || '',
      bank_swift: (c.metadata?.bank_swift) || '',
    });
    const meta = c.metadata || {};
    if (meta.logo) {
      logoUrl.value = '/storage/' + meta.logo;
    }
    companyPlan.value = c.plan || null;
  } catch { showToast('Erreur lors du chargement', 'error'); } finally { loading.value = false; }

  try {
    const { data } = await axios.get('/settings');
    if (data.mail_host) mailForm.mail_host = data.mail_host;
    if (data.mail_port) mailForm.mail_port = data.mail_port;
    if (data.mail_username) mailForm.mail_username = data.mail_username;
    if (data.mail_password) mailForm.mail_password = data.mail_password;
    if (data.mail_encryption) mailForm.mail_encryption = data.mail_encryption;
    if (data.mail_from_address) mailForm.mail_from_address = data.mail_from_address;
    if (data.mail_from_name) mailForm.mail_from_name = data.mail_from_name;
  } catch {}
}

onMounted(fetchData);

async function submit() {
  submitting.value = true;
  try {
    const payload = { ...form };
    if (payload.industry === 'Autre') {
      payload.industry = payload.otherIndustry.trim() || 'Autre';
    }
    delete payload.otherIndustry;
    await axios.put('/company', payload);
    showToast(t('settings.updated'), 'success');
  } catch { showToast(t('common.update_error'), 'error'); } finally { submitting.value = false; }
}

function onLogoChange(e) {
  const file = e.target.files[0];
  if (!file) return;
  selectedLogo.value = file;
  logoPreview.value = URL.createObjectURL(file);
}

async function uploadLogo() {
  if (!selectedLogo.value) return;
  uploadingLogo.value = true;
  try {
    const fd = new FormData();
    fd.append('logo', selectedLogo.value);
    const { data } = await axios.post('/company/logo', fd);
    logoUrl.value = data.logo_url;
    selectedLogo.value = null;
    logoPreview.value = '';
    showToast(t('settings.logo_updated'), 'success');
  } catch { showToast(t('settings.logo_error'), 'error'); } finally { uploadingLogo.value = false; }
}

async function deleteLogo() {
  if (!confirm(t('settings.confirm_logo_delete'))) return;
  deletingLogo.value = true;
  try {
    await axios.delete('/company/logo');
    logoUrl.value = '';
    logoPreview.value = '';
    selectedLogo.value = null;
    showToast(t('settings.logo_deleted'), 'success');
  } catch { showToast(t('settings.logo_error'), 'error'); } finally { deletingLogo.value = false; }
}

async function saveEmailConfig() {
  mailSaving.value = true;
  mailTestResult.value = null;
  try {
    await axios.post('/settings', { ...mailForm });
    showToast(t('settings.email_saved'), 'success');
  } catch { showToast(t('common.save_error'), 'error'); } finally { mailSaving.value = false; }
}

async function testMailConfig() {
  mailTesting.value = true;
  mailTestResult.value = null;
  try {
    await axios.post('/settings/test-mail');
    mailTestResult.value = { success: true, message: t('settings.test_sent') };
    showToast(t('settings.test_ok'), 'success');
  } catch (err) {
    const msg = err.response?.data?.message || t('settings.test_error');
    mailTestResult.value = { success: false, message: msg };
  } finally { mailTesting.value = false; }
}
</script>
