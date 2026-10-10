<script setup lang="ts">
withDefaults(defineProps<{ label?: string, size?: number }>(), { label: 'Loading', size: 24 })
</script>

<template>
  <span class="app-spinner" role="status" :aria-label="label" :style="{ width: `${size}px`, height: `${size}px` }" />
</template>

<style scoped>
.app-spinner {
  display: inline-block;
  border: 3px solid #ddd;
  border-top-color: #1a1a1a;
  border-radius: 50%;
  animation: app-spinner-rotate 0.8s linear infinite;
}

@keyframes app-spinner-rotate {
  to {
    transform: rotate(360deg);
  }
}

@media (prefers-reduced-motion: reduce) {
  .app-spinner {
    animation-duration: 3s;
  }
}
</style>
