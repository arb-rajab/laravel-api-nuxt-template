<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()

const is404 = computed(() => props.error.statusCode === 404)

function goHome(): void {
  clearError({ redirect: '/' })
}
</script>

<template>
  <main class="error-page">
    <h1>{{ is404 ? 'Page not found' : 'Something went wrong' }}</h1>
    <p class="muted">
      {{ is404 ? 'The page you are looking for does not exist.' : 'An unexpected error occurred. Please try again.' }}
    </p>
    <AppButton @click="goHome">Back to the home page</AppButton>
  </main>
</template>

<style scoped>
.error-page {
  max-width: 560px;
  margin: 4rem auto;
  padding: 0 1rem;
  font-family: system-ui, sans-serif;
}

.muted {
  color: #666;
}
</style>
