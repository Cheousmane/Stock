<template>
  <div ref="triggerRef" class="relative inline-block">
    <slot :toggle="toggle" :open="open" />
    <Teleport to="body">
      <Transition name="pop">
        <div
          v-if="open"
          ref="menuRef"
          class="base-dropdown fixed z-[9999] bg-surface border border-border/70 rounded-2xl shadow-xl shadow-neutral-950/[0.08] p-1.5 overflow-hidden"
          :class="menuClass"
          :style="menuStyle"
          @click.stop
          role="menu"
        >
          <slot name="menu" />
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  align: { type: String, default: 'left' },
  menuClass: { type: String, default: 'min-w-[200px]' },
  offset: { type: Number, default: 8 },
  maxHeight: { type: Number, default: 320 },
})

const emit = defineEmits(['open', 'close'])

const open = ref(false)
const triggerRef = ref(null)
const menuRef = ref(null)

function toggle() {
  open.value ? close() : show()
}

function show() {
  open.value = true
  emit('open')
  nextTick(position)
}

function close() {
  if (!open.value) return
  open.value = false
  emit('close')
}

defineExpose({ open: show, close, toggle })

const menuStyle = computed(() => {
  if (!open.value || !triggerRef.value) return {}
  const rect = triggerRef.value.getBoundingClientRect()
  const style = {
    top: `${rect.bottom + props.offset}px`,
    maxHeight: `${props.maxHeight}px`,
    overflowY: 'auto',
  }
  if (props.align === 'right') {
    style.left = `${rect.right}px`
    style.transform = 'translateX(-100%)'
  } else if (props.align === 'center') {
    style.left = `${rect.left + rect.width / 2}px`
    style.transform = 'translateX(-50%)'
  } else {
    style.left = `${rect.left}px`
  }
  // Bascule au-dessus si pas de place en bas
  if (typeof window !== 'undefined' && rect.bottom + props.offset + 200 > window.innerHeight && rect.top > 240) {
    style.top = 'auto'
    style.bottom = `${window.innerHeight - rect.top + props.offset}px`
  }
  return style
})

function position() {
  // Re-calcule après le rendu (le computed se met à jour via nextTick)
}

function handleClick(e) {
  if (open.value && triggerRef.value && !triggerRef.value.contains(e.target)) {
    const menu = menuRef.value
    if (menu && menu.contains(e.target)) return
    close()
  }
}

function handleKey(e) {
  if (e.key === 'Escape') close()
}

function handleScroll() {
  if (open.value) close()
}

onMounted(() => {
  document.addEventListener('click', handleClick)
  document.addEventListener('keydown', handleKey)
  window.addEventListener('resize', handleScroll)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleClick)
  document.removeEventListener('keydown', handleKey)
  window.removeEventListener('resize', handleScroll)
})
</script>

<style scoped>
.pop-enter-active { animation: modal-pop 0.18s ease-out; transform-origin: top center; }
.pop-leave-active { transition: opacity 0.12s ease, transform 0.12s ease; }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: translateY(-4px) scale(0.98); }
.base-dropdown::-webkit-scrollbar { width: 5px; }
.base-dropdown::-webkit-scrollbar-thumb { background-color: rgb(16 185 129 / 0.3); border-radius: 99px; }
</style>
