<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const { currentUser } = useSession()
</script>

<template>
  <section>
    <h1>Dashboard</h1>
    <p v-if="currentUser">
      Signed in as <strong>{{ currentUser.name }}</strong> ({{ currentUser.email }}), role <code>{{ currentUser.role }}</code>.
    </p>
  </section>
</template>
