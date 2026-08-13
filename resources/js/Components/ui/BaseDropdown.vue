<template>
  <div ref="triggerRef" class="relative inline-block">
    <slot :toggle="toggle" :open="open" />
    <Teleport to="body">
      <div
        v-if="open"
        ref="menuRef"
        class="base-dropdown fixed z-[9999] min-w-[180px] bg-surface border border-border rounded-xl shadow-dropdown py-1 overflow-hidden dropdown-in-enter-active"
        :style="menuStyle"
        @click.stop
      >
        <slot name="menu" />
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  align: { type: String, default: 'left' },
})

const open = ref(false)
const triggerRef = ref(null)
const menuRef = ref(null)

function toggle() {
  open.value = !open.value
}

const menuStyle = computed(() => {
  if (!open.value || !triggerRef.value) return {}
  const rect = triggerRef.value.getBoundingClientRect()
  const top = rect.bottom + 4
  return {
    top: `${top}px`,
    left: props.align === 'right' ? `${rect.right}px` : `${rect.left}px`,
    transform: props.align === 'right' ? 'translateX(-100%)' : 'translateX(0)',
  }
})

function handleClick(e) {
  if (open.value && triggerRef.value && !triggerRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClick))
onBeforeUnmount(() => document.removeEventListener('click', handleClick))
</script>
