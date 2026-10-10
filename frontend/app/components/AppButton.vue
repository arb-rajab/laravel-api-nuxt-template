<script setup lang="ts">
withDefaults(defineProps<{ type?: 'button' | 'submit', disabled?: boolean, variant?: 'primary' | 'link' }>(), {
  type: 'button',
  disabled: false,
  variant: 'primary',
})
</script>

<template>
  <button :type="type" :disabled="disabled" :class="['app-button', `app-button--${variant}`]">
    <slot />
  </button>
</template>

<style scoped>
.app-button {
  cursor: pointer;
  border: 1px solid #1a1a1a;
  background: #1a1a1a;
  color: #fff;
  border-radius: 6px;
  padding: 0.5rem 0.9rem;
  font-size: 0.9rem;
}

.app-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.app-button--link {
  border: none;
  background: none;
  color: #333;
  text-decoration: underline;
  padding: 0;
}
</style>
