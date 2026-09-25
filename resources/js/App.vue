<template>
  <div v-if="navigationInProgress" class="fixed top-0 left-0 right-0 h-[3px] z-[99999] pointer-events-none overflow-hidden">
    <div class="progress-track w-full h-full" />
  </div>
  <router-view v-slot="{ Component }">
    <transition name="page" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <Toast :show="toast.show" :message="toast.message" :type="toast.type" @close="toast.show = false" />
</template>

<script setup>
import { provide, onMounted } from 'vue';
import Toast from './Components/Toast.vue';
import { navigationInProgress } from './router';
import { useToast } from './composables/useToast';
import { useRoute } from 'vue-router';
import { initAnalytics, trackPageView } from './composables/useAnalytics';

const { toast, showToast } = useToast();
provide('showToast', showToast);

const route = useRoute();

onMounted(() => {
  if (route.meta.requiresSuperAdmin) {
    initAnalytics().then(() => {
      trackPageView(route.fullPath, route.meta.title || document.title);
    });
  }
});
</script>