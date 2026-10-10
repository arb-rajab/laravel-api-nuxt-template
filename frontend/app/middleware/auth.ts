/**
 * Route middleware for pages that need a signed-in user:
 * `definePageMeta({ middleware: 'auth' })`. Client-side only: the session
 * cookie belongs to the API origin, so server-side rendering cannot know it.
 */
export default defineNuxtRouteMiddleware(async () => {
  if (import.meta.server) return

  const { authState, checkSession } = useSession()

  if (authState.value === 'checking') {
    await checkSession()
  }

  if (authState.value !== 'authenticated') {
    return navigateTo('/login')
  }
})
