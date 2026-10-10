<script setup lang="ts">
defineProps<{ title: string, description?: string }>()
</script>

<template>
  <div class="app-empty-state">
    <h2 class="app-empty-state__title">
      {{ title }}
    </h2>
    <p v-if="description" class="app-empty-state__description">
      {{ description }}
    </p>
    <div v-if="$slots.default" class="app-empty-state__actions">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.app-empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #444;
}

.app-empty-state__title {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
}

.app-empty-state__description {
  margin: 0 0 1rem;
  color: #666;
}
</style>
