<template>
  <div class="space-y-3">
    <div v-for="n in count" :key="n" class="flex items-center gap-4 px-4 py-3">
      <div class="w-8 h-8 rounded-lg skeleton" />
      <div class="flex-1 space-y-2">
        <div class="h-3 skeleton rounded w-1/3" />
        <div class="h-2.5 skeleton rounded w-1/5" />
      </div>
      <div class="h-3 skeleton rounded w-16" />
      <div class="h-5 skeleton rounded-full w-14" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  count: { type: Number, default: 5 },
})
</script>
