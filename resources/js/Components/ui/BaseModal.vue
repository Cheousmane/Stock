<template>
  <Teleport to="body">
    <Transition name="modal-fade" appear>
      <div
        v-if="modelValue"
        class="fixed inset-0 z-[100] flex items-center justify-center p-0 sm:p-3 md:p-4"
      >
        <!-- Backdrop with light blur -->
        <div class="absolute inset-0 bg-neutral-950/30 backdrop-blur-[2px]" aria-hidden="true" @click="requestClose" />

        <!-- Modal panel -->
        <div
          ref="panelRef"
          role="dialog"
          aria-modal="true"
          tabindex="-1"
          class="modal-panel relative bg-surface border border-border/50 w-full max-h-[100dvh] sm:max-h-[calc(100dvh-1.5rem)] md:max-h-[calc(100dvh-2rem)] overflow-y-auto overscroll-contain focus:outline-none flex flex-col shadow-xl
            sm:rounded-2xl sm:border sm:w-full
            max-sm:rounded-none max-sm:border-0 max-sm:h-[100dvh]"
          :class="sizeClasses[size]"
          @click.stop
        >
          <!-- Progress bar -->
          <div v-if="progress !== null" class="sticky top-0 z-20 h-1 bg-surface-secondary">
            <div
              class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300 ease-out"
              :style="{ width: progress + '%' }"
            />
          </div>

          <!-- Sticky header -->
          <div v-if="$slots.header || title" class="sticky top-0 z-10 flex items-start justify-between gap-4 px-5 sm:px-6 py-4 bg-surface/90 backdrop-blur-sm border-b border-border/40">
            <div class="min-w-0 flex-1">
              <h3 v-if="title" class="text-base font-extrabold tracking-tight text-text-primary">{{ title }}</h3>
              <slot name="header" />
              <p v-if="subtitle" class="mt-1 text-[13px] font-medium text-text-tertiary">{{ subtitle }}</p>
            </div>
            <button
              type="button"
              @click="requestClose"
              class="flex items-center justify-center w-8 h-8 rounded-xl text-text-tertiary hover:text-text-primary hover:bg-surface-secondary transition-all shrink-0 -mr-1"
              :aria-label="t('common.close')"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 px-5 sm:px-6 py-5 relative">
            <slot />
            <!-- Loading overlay -->
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-surface/60 backdrop-blur-[2px] rounded-b-2xl">
              <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-surface border border-border shadow-lg">
                <svg class="w-5 h-5 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm font-semibold text-text-secondary">{{ t('common.loading') }}</span>
              </div>
            </div>
          </div>

          <!-- Sticky footer -->
          <div v-if="$slots.footer" class="sticky bottom-0 z-10 flex flex-wrap items-center justify-end gap-3 px-5 sm:px-6 py-4 bg-surface/90 backdrop-blur-sm border-t border-border/40">
            <slot name="footer" />
          </div>
        </div>

        <!-- Confirm close dialog -->
        <Transition name="modal-fade" appear>
          <div v-if="showConfirm" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-neutral-950/40 backdrop-blur-[2px]" @click="showConfirm = false" />
            <div class="relative bg-surface border border-border/50 rounded-2xl shadow-xl p-5 max-w-sm w-full">
              <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-amber-500/10">
                  <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-extrabold text-text-primary">{{ t('common.confirm_discard_title') }}</h4>
                  <p class="text-xs text-text-tertiary mt-0.5">{{ t('common.confirm_discard_message') }}</p>
                </div>
              </div>
              <div class="flex items-center justify-end gap-2.5">
                <button type="button" @click="showConfirm = false" class="px-3.5 py-2 text-xs font-bold rounded-xl border border-border bg-surface text-text-secondary hover:bg-surface-secondary transition">
                  {{ t('common.cancel') }}
                </button>
                <button type="button" @click="confirmClose" class="px-3.5 py-2 text-xs font-bold rounded-xl bg-rose-500 text-white hover:bg-rose-600 transition">
                  {{ t('common.discard') }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { default: false },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  dirty: { type: Boolean, default: false },
  progress: { type: Number, default: null },
})

const emit = defineEmits(['update:modelValue', 'submit'])
const { t } = useI18n()
const panelRef = ref(null)
const showConfirm = ref(false)
let previousOverflow = ''

function requestClose() {
  if (props.loading) return
  if (props.dirty) {
    showConfirm.value = true
  } else {
    close()
  }
}

function close() {
  emit('update:modelValue', false)
}

function confirmClose() {
  showConfirm.value = false
  close()
}

function onKeydown(event) {
  if (event.key === 'Escape' && props.modelValue && !props.loading) requestClose()

  // Ctrl+Enter / Cmd+Enter to submit
  if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && props.modelValue && !props.loading) {
    event.preventDefault()
    emit('submit')
  }

  // Focus trap
  if (event.key === 'Tab' && props.modelValue) {
    const focusable = panelRef.value?.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    if (!focusable || focusable.length === 0) return
    const first = focusable[0]
    const last = focusable[focusable.length - 1]
    if (event.shiftKey) {
      if (document.activeElement === first) {
        event.preventDefault()
        last.focus()
      }
    } else {
      if (document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    }
  }
}

watch(() => props.modelValue, async (open) => {
  if (open) {
    previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    document.addEventListener('keydown', onKeydown)
    await nextTick()
    panelRef.value?.focus()
  } else {
    document.body.style.overflow = previousOverflow
    document.removeEventListener('keydown', onKeydown)
    showConfirm.value = false
  }
}, { immediate: true })

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = previousOverflow
})

const sizeClasses = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  full: 'max-w-3xl',
}
</script>