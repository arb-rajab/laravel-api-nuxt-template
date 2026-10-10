<script setup lang="ts">
import type { PageMeta } from '~/types/api'

/** Previous/next controls for a `PageMeta`. Renders nothing when there is only one page. */
const props = defineProps<{ meta: PageMeta }>()
const emit = defineEmits<{ change: [page: number] }>()

const hasPrevious = computed(() => props.meta.current_page > 1)
const hasNext = computed(() => props.meta.current_page < props.meta.last_page)
</script>

<template>
  <nav v-if="meta.last_page > 1" class="app-pagination" aria-label="Pagination">
    <AppButton :disabled="!hasPrevious" @click="emit('change', meta.current_page - 1)">
      Previous
    </AppButton>
    <span class="app-pagination__status">
      Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} total)
    </span>
    <AppButton :disabled="!hasNext" @click="emit('change', meta.current_page + 1)">
      Next
    </AppButton>
  </nav>
</template>

<style scoped>
.app-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
}

.app-pagination__status {
  color: #555;
  font-size: 0.9rem;
}
</style>
