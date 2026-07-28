<template>
  <AdminLayout>
    <div class="max-w-2xl mx-auto space-y-6">
      <BasePageHeader title="Abonnement" subtitle="Gérez votre plan">
        <template #actions>
          <BaseButton variant="secondary" size="sm" :to="{ name: 'Settings' }">
            <ArrowLeftIcon class="w-4 h-4" /> Retour
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard padding="lg">
        <div class="flex flex-col items-center text-center py-12">
          <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-6">
            <ClockIcon class="w-8 h-8 text-emerald-600" />
          </div>
          <h2 class="text-xl font-semibold text-text-primary mb-3">Bientôt disponible</h2>
          <p class="text-text-tertiary max-w-md">
            La gestion des abonnements et plans tarifaires sera bientôt disponible.
            Vous serez notifié dès son lancement.
          </p>
        </div>
      </BaseCard>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '../../Components/AdminLayout.vue';
import BaseButton from '../../Components/ui/BaseButton.vue';
import BaseCard from '../../Components/ui/BaseCard.vue';
import BasePageHeader from '../../Components/ui/BasePageHeader.vue';
import { ArrowLeftIcon, ClockIcon } from '@heroicons/vue/24/outline';
</script>
