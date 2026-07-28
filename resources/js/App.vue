<template>
  <router-view v-slot="{ Component }">
    <transition name="page" mode="out-in">
      <component :is="Component" />
    </transition>
  </router-view>
  <Toast :show="toast.show" :message="toast.message" :type="toast.type" @close="toast.show = false" />
</template>

<script setup>
import { provide } from 'vue';
import Toast from './Components/Toast.vue';
import { useToast } from './composables/useToast';

const { toast, showToast } = useToast();
provide('showToast', showToast);
</script>
