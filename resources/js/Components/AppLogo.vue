<template>
  <span
    class="app-logo"
    :style="{ width: size + 'px', height: size + 'px', borderRadius: Math.round(size * 0.28) + 'px' }"
    role="img"
    aria-label="SIDIBE CORPORATE"
  >
    <svg :width="size" :height="size" viewBox="0 0 48 48" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <linearGradient :id="gradId" x1="8" y1="4" x2="40" y2="44" gradientUnits="userSpaceOnUse">
          <stop offset="0" stop-color="#34d399" />
          <stop offset="0.55" stop-color="#10b981" />
          <stop offset="1" stop-color="#0d9488" />
        </linearGradient>
        <linearGradient :id="sheenId" x1="24" y1="3" x2="24" y2="22" gradientUnits="userSpaceOnUse">
          <stop offset="0" stop-color="#ffffff" stop-opacity="0.28" />
          <stop offset="1" stop-color="#ffffff" stop-opacity="0" />
        </linearGradient>
        <clipPath :id="clipId">
          <rect x="3" y="3" width="42" height="42" :rx="radius" />
        </clipPath>
      </defs>
      <rect x="3" y="3" width="42" height="42" :rx="radius" :fill="`url(#${gradId})`" />
      <g :clip-path="`url(#${clipId})`">
        <rect x="3" y="3" width="42" height="20" :fill="`url(#${sheenId})`" />
        <circle cx="38.5" cy="36.5" r="11" fill="#064e3b" opacity="0.18" />
      </g>
      <rect x="3.75" y="3.75" width="40.5" height="40.5" :rx="radius - 1" stroke="#ffffff" stroke-opacity="0.35" stroke-width="1.5" />
      <!-- S monogram -->
      <path
        d="M30.5 15.2c-2.5-2.5-7-3.1-9.8-1.6-3 1.6-3.3 4.9.1 6.5l5.4 2.3c3.5 1.5 3.2 5.3.1 7-2.7 1.6-7.1 1.2-10-1.2"
        stroke="#ffffff"
        :stroke-width="4.4"
        stroke-linecap="round"
      />
      <!-- growth arrow -->
      <path
        d="M28.5 35.5 36.5 27.5"
        stroke="#fcd34d"
        :stroke-width="3.4"
        stroke-linecap="round"
      />
      <path
        d="M31.6 27.5h4.9v4.9"
        stroke="#fcd34d"
        :stroke-width="3.4"
        stroke-linecap="round"
        stroke-linejoin="round"
      />
    </svg>
  </span>
</template>

<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  size: { type: Number, default: 40 },
})

const uid = useId().replace(/[^a-zA-Z0-9]/g, '')
const gradId = computed(() => `sc-grad-${uid}`)
const sheenId = computed(() => `sc-sheen-${uid}`)
const clipId = computed(() => `sc-clip-${uid}`)
const radius = computed(() => Math.round(props.size * 0.28))
</script>

<style scoped>
.app-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  filter: drop-shadow(0 6px 14px rgb(5 150 105 / 0.35));
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), filter 0.25s ease;
}
a:hover > .app-logo, button:hover > .app-logo, .group:hover .app-logo {
  transform: scale(1.06) rotate(-2deg);
  filter: drop-shadow(0 8px 20px rgb(5 150 105 / 0.45));
}
a:active > .app-logo, button:active > .app-logo {
  transform: scale(0.96);
}
</style>
