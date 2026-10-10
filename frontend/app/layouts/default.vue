<script setup lang="ts">
const { authState, currentUser, checkSession, logout } = useSession()

onMounted(() => {
  if (authState.value === 'checking') checkSession()
})

async function submitLogout(): Promise<void> {
  await logout()
  await navigateTo('/login')
}
</script>

<template>
  <div class="shell">
    <header class="shell-header">
      <NuxtLink to="/" class="brand">starter-app</NuxtLink>
      <nav class="shell-nav">
        <NuxtLink v-if="authState === 'authenticated'" to="/dashboard">Dashboard</NuxtLink>
        <NuxtLink v-if="authState === 'unauthenticated'" to="/login">Log in</NuxtLink>
        <template v-if="authState === 'authenticated'">
          <span v-if="currentUser" class="muted">{{ currentUser.name }}</span>
          <AppButton variant="link" @click="submitLogout">Log out</AppButton>
        </template>
      </nav>
    </header>
    <main class="shell-main">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.shell {
  max-width: 1080px;
  margin: 0 auto;
  padding: 1rem;
  font-family: system-ui, sans-serif;
  color: #1a1a1a;
}

.shell-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.brand {
  font-weight: 700;
  color: inherit;
  text-decoration: none;
}

.shell-nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.muted {
  color: #666;
  font-size: 0.9rem;
}

.shell-main {
  padding: 1.5rem 0;
}
</style>
