<script setup lang="ts">
defineProps<{ label: string, error?: string | null }>()
</script>

<template>
  <label class="form-field">
    <span>{{ label }}</span>
    <slot />
    <span v-if="error" class="field-error">{{ error }}</span>
  </label>
</template>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field-error {
  color: #a1281c;
  font-size: 0.85rem;
}
</style>
