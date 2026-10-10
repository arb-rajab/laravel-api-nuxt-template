<script setup lang="ts">
/**
 * A labelled text input with a field-error slot, for use with `useFormErrors`:
 * `<AppTextInput v-model="email" label="Email" name="email" :error="fieldError('email')" />`.
 */
withDefaults(defineProps<{
  label: string
  name: string
  type?: string
  error?: string | null
  required?: boolean
  disabled?: boolean
  autocomplete?: string
}>(), {
  type: 'text',
  error: null,
  required: false,
  disabled: false,
  autocomplete: undefined,
})

const model = defineModel<string>({ default: '' })
</script>

<template>
  <FormField :label="label" :error="error">
    <input
      v-model="model"
      :type="type"
      :name="name"
      :required="required"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :aria-invalid="error ? 'true' : undefined"
    >
  </FormField>
</template>
