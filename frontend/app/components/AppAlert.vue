<script setup lang="ts">
withDefaults(defineProps<{ kind?: 'error' | 'info' }>(), { kind: 'error' })
</script>

<template>
  <p :class="['app-alert', `app-alert--${kind}`]" :role="kind === 'error' ? 'alert' : 'status'">
    <slot />
  </p>
</template>

<style scoped>
.app-alert {
  border: 1px solid;
  border-radius: 6px;
  padding: 0.75rem 1rem;
  margin: 1rem 0;
}

.app-alert--error {
  background: #fdecea;
  color: #611a15;
  border-color: #f5c6cb;
}

.app-alert--info {
  background: #eef5ff;
  color: #14325c;
  border-color: #c9dcf5;
}
</style>
