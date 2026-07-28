<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader title="Paramètres" subtitle="Configuration de l'entreprise" />

      <div v-if="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-emerald-600 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <template v-else>
        <!-- Company Info -->
        <BaseCard title="Entreprise" padding="lg">
          <form @submit.prevent="submit" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Logo</label>
              <div class="flex items-center gap-4">
                <div v-if="logoPreview || logoUrl" class="flex-shrink-0 w-16 h-16 rounded-lg border border-border overflow-hidden bg-surface-tertiary flex items-center justify-center">
                  <img v-if="logoPreview || logoUrl" :src="logoPreview || logoUrl" class="w-full h-full object-contain" />
                </div>
                <div class="flex-1">
                  <input ref="logoInput" type="file" accept="image/*" @change="onLogoChange"
                    class="block w-full text-sm text-text-tertiary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" />
                  <div class="flex gap-2 mt-2">
                    <button v-if="selectedLogo" type="button" @click="uploadLogo" :disabled="uploadingLogo"
                      class="px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50">
                      {{ uploadingLogo ? 'Upload...' : 'Uploader le logo' }}
                    </button>
                    <button v-if="logoUrl && !selectedLogo" type="button" @click="deleteLogo" :disabled="deletingLogo"
                      class="px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 rounded-lg hover:bg-red-100 disabled:opacity-50">
                      {{ deletingLogo ? 'Suppression...' : 'Supprimer le logo' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Nom de l'entreprise <span class="text-red-500">*</span></label>
              <input v-model="form.company_name" type="text" required
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Slug</label>
              <input :value="form.slug" type="text" readonly
                class="block w-full px-3 py-2 bg-surface-tertiary border border-border rounded-lg text-sm text-text-tertiary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Email</label>
              <input v-model="form.email" type="email"
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Téléphone</label>
              <input v-model="form.phone" type="tel"
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Adresse</label>
              <textarea v-model="form.address" rows="2"
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Devise</label>
              <select v-model="form.currency"
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary">
                <option value="XOF">XOF (F CFA)</option>
                <option value="EUR">EUR (€)</option>
                <option value="USD">USD ($)</option>
                <option value="GBP">GBP (£)</option>
              </select>
            </div>
            <div class="flex gap-3 pt-4 border-t border-border">
              <BaseButton type="submit" :loading="submitting">Enregistrer</BaseButton>
            </div>
          </form>
        </BaseCard>

        <!-- Email Configuration -->
        <BaseCard title="Configuration email (SMTP)" padding="lg">
          <form @submit.prevent="saveEmailConfig" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Hôte SMTP</label>
                <input v-model="mailForm.mail_host" placeholder="smtp.example.com"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Port</label>
                <input v-model="mailForm.mail_port" placeholder="587"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Nom d'utilisateur</label>
                <input v-model="mailForm.mail_username" placeholder="user@example.com"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Mot de passe</label>
                <input v-model="mailForm.mail_password" type="password"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-text-secondary mb-1">Chiffrement</label>
              <select v-model="mailForm.mail_encryption"
                class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="">Aucun</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Adresse d'envoi</label>
                <input v-model="mailForm.mail_from_address" placeholder="noreply@example.com"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text-secondary mb-1">Nom d'envoi</label>
                <input v-model="mailForm.mail_from_name" placeholder="Mon Entreprise"
                  class="block w-full px-3 py-2 border border-border rounded-lg text-sm bg-surface focus:ring-emerald-500 focus:border-emerald-500 text-text-primary" />
              </div>
            </div>
            <div class="flex gap-3 pt-4 border-t border-border">
              <BaseButton type="submit" :loading="mailSaving">Enregistrer</BaseButton>
              <BaseButton variant="secondary" type="button" :loading="mailTesting" @click="testMailConfig">
                Tester la configuration
              </BaseButton>
            </div>
            <p v-if="mailTestResult" :class="['text-sm', mailTestResult.success ? 'text-green-600' : 'text-red-600']">{{ mailTestResult.message }}</p>
          </form>
        </BaseCard>

        <!-- Subscription -->
        <BaseCard title="Abonnement" subtitle="Gérez votre plan d'abonnement" padding="lg">
          <template #actions>
            <BaseButton variant="ghost" size="sm" disabled class="opacity-50 cursor-not-allowed">
              <ClockIcon class="w-4 h-4" /> Bientôt disponible
            </BaseButton>
          </template>
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
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowRightIcon, ClockIcon } from '@heroicons/vue/24/outline';

const showToast = inject('showToast');
const loading = ref(true);
const submitting = ref(false);
const logoInput = ref(null);
const selectedLogo = ref(null);
const logoPreview = ref('');
const logoUrl = ref('');
const uploadingLogo = ref(false);
const deletingLogo = ref(false);

const form = reactive({
  company_name: '', slug: '', email: '', phone: '', address: '', currency: 'XOF',
});

const mailForm = reactive({
  mail_host: '', mail_port: '587', mail_username: '', mail_password: '',
  mail_encryption: 'tls', mail_from_address: '', mail_from_name: '',
});
const mailSaving = ref(false);
const mailTesting = ref(false);
const mailTestResult = ref(null);

onMounted(async () => {
  try {
    const { data } = await axios.get('/company');
    const c = data.data ?? data;
    Object.assign(form, {
      company_name: c.company_name || c.name || '',
      slug: c.slug || '',
      email: (c.email || (c.metadata?.email)) || '',
      phone: (c.phone || (c.metadata?.phone)) || '',
      address: (c.address || (c.metadata?.address)) || '',
      currency: c.currency || (c.metadata?.currency) || 'XOF',
    });
    const meta = c.metadata || {};
    if (meta.logo) {
      logoUrl.value = '/storage/' + meta.logo;
    }
  } catch {} finally { loading.value = false; }

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
});

async function submit() {
  submitting.value = true;
  try {
    await axios.put('/company', form);
    showToast('Paramètres mis à jour', 'success');
  } catch { showToast('Erreur lors de la mise à jour', 'error'); } finally { submitting.value = false; }
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
    showToast('Logo mis à jour', 'success');
  } catch { showToast('Erreur upload logo', 'error'); } finally { uploadingLogo.value = false; }
}

async function deleteLogo() {
  if (!confirm('Confirmer la suppression du logo ?')) return;
  deletingLogo.value = true;
  try {
    await axios.delete('/company/logo');
    logoUrl.value = '';
    logoPreview.value = '';
    selectedLogo.value = null;
    showToast('Logo supprimé', 'success');
  } catch { showToast('Erreur suppression logo', 'error'); } finally { deletingLogo.value = false; }
}

async function saveEmailConfig() {
  mailSaving.value = true;
  mailTestResult.value = null;
  try {
    await axios.post('/settings', { ...mailForm });
    showToast('Configuration email enregistrée', 'success');
  } catch { showToast('Erreur lors de l\'enregistrement', 'error'); } finally { mailSaving.value = false; }
}

async function testMailConfig() {
  mailTesting.value = true;
  mailTestResult.value = null;
  try {
    await axios.post('/settings/test-mail');
    mailTestResult.value = { success: true, message: 'Email de test envoyé avec succès' };
    showToast('Test réussi', 'success');
  } catch (err) {
    const msg = err.response?.data?.message || 'Erreur lors du test';
    mailTestResult.value = { success: false, message: msg };
  } finally { mailTesting.value = false; }
}
</script>
