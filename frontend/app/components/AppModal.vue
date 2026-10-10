<script setup lang="ts">
/**
 * A dialog rendered in place (no teleport). It closes on Escape, on a click
 * on the backdrop and via the close button; the parent owns the `open` state:
 * `<AppModal :open="show" title="Delete?" @close="show = false">...</AppModal>`.
 */
const props = defineProps<{ open: boolean, title: string }>()
const emit = defineEmits<{ close: [] }>()

const dialog = ref<HTMLElement | null>(null)
const titleId = useId()

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    dialog.value?.focus()
  }
})
</script>

<template>
  <div v-if="open" class="app-modal-backdrop" @click.self="emit('close')">
    <div
      ref="dialog"
      class="app-modal"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      tabindex="-1"
      @keydown.esc="emit('close')"
    >
      <header class="app-modal__header">
        <h2 :id="titleId">
          {{ title }}
        </h2>
        <button type="button" class="app-modal__close" aria-label="Close" @click="emit('close')">
          &times;
        </button>
      </header>
      <div class="app-modal__body">
        <slot />
      </div>
      <footer v-if="$slots.footer" class="app-modal__footer">
        <slot name="footer" />
      </footer>
    </div>
  </div>
</template>

<style scoped>
.app-modal-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgb(0 0 0 / 45%);
  padding: 1rem;
  z-index: 50;
}

.app-modal {
  background: #fff;
  border-radius: 8px;
  width: min(32rem, 100%);
  max-height: 90vh;
  overflow: auto;
  padding: 1rem 1.25rem;
  outline: none;
}

.app-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.app-modal__header h2 {
  margin: 0;
  font-size: 1.1rem;
}

.app-modal__close {
  border: none;
  background: none;
  font-size: 1.5rem;
  line-height: 1;
  cursor: pointer;
}

.app-modal__footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}
</style>
