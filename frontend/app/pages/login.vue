<script setup lang="ts">
const { authState, login } = useSession()
const { formError, fieldError, applyError, clear } = useFormErrors()
const showTenantField = useRuntimeConfig().public.tenancy

const email = ref('')
const password = ref('')
const tenantSlug = ref('')
const submitting = ref(false)

if (authState.value === 'authenticated') {
  await navigateTo('/dashboard')
}

function describeError(code: string): string {
  const messages: Record<string, string> = {
    VALIDATION_FAILED: 'Please check the highlighted fields.',
    INVALID_CREDENTIALS: 'Incorrect email or password.',
    NOT_FOUND: 'No tenant with that slug exists.',
    TOO_MANY_REQUESTS: 'Too many attempts. Please wait a minute and try again.',
    NETWORK_ERROR: 'Could not reach the server. Is the API running?',
  }
  return messages[code] ?? `Something went wrong (${code}).`
}

async function submit(): Promise<void> {
  submitting.value = true
  clear()
  try {
    await login(email.value, password.value, tenantSlug.value.trim() || undefined)
    await navigateTo('/dashboard')
  } catch (e) {
    applyError(e, describeError)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section>
    <h1>Log in</h1>
    <AppAlert v-if="formError">{{ formError }}</AppAlert>
    <form class="login-form" @submit.prevent="submit">
      <AppTextInput
        v-if="showTenantField"
        v-model="tenantSlug"
        label="Tenant slug (leave empty for a global account)"
        name="tenant"
        autocomplete="organization"
      />
      <AppTextInput
        v-model="email"
        label="Email"
        name="email"
        type="email"
        required
        autocomplete="username"
        :error="fieldError('email')"
      />
      <AppTextInput
        v-model="password"
        label="Password"
        name="password"
        type="password"
        required
        autocomplete="current-password"
        :error="fieldError('password')"
      />
      <AppButton type="submit" :disabled="submitting">{{ submitting ? 'Logging in…' : 'Log in' }}</AppButton>
    </form>
  </section>
</template>

<style scoped>
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  max-width: 360px;
}
</style>
