<template>
  <div ref="wrap" class="google-btn-wrap"></div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { loadGoogleScript, getGoogleClientId, currentLocale } from '../utils/google';

const props = defineProps({
  text: { type: String, default: 'continue_with' },
});
const emit = defineEmits(['credential', 'error']);
const wrap = ref(null);

onMounted(async () => {
  try {
    await loadGoogleScript();
    const clientId = getGoogleClientId();
    if (!clientId) throw new Error('google_not_configured');
    window.google.accounts.id.initialize({
      client_id: clientId,
      callback: (resp) => emit('credential', resp?.credential || ''),
      auto_select: false,
      cancel_on_tap_outside: true,
    });
    window.google.accounts.id.renderButton(wrap.value, {
      theme: 'outline',
      size: 'large',
      width: 320,
      text: props.text,
      locale: currentLocale(),
    });
  } catch (e) {
    emit('error', e);
  }
});
</script>

<style scoped>
.google-btn-wrap {
  display: flex;
  justify-content: center;
  min-height: 44px;
}
.google-btn-wrap > div {
  width: 100%;
  display: flex;
  justify-content: center;
}
</style>
