import type { SessionUser } from '~/types/api'

/**
 * Shared auth/session state for every page. `useState` (not a module-level
 * `ref`) so every component gets the same reactive instance within one client
 * session.
 */
export type AuthState = 'checking' | 'unauthenticated' | 'authenticated'

export function useSession() {
  const authState = useState<AuthState>('auth-state', () => 'checking')
  const currentUser = useState<SessionUser | null>('current-user', () => null)

  /**
   * Asks the backend who the session belongs to. Global users are served by
   * `/me`; with the backend's tenancy module, tenant users have a different
   * session shape and are served by `/tenant/me`.
   */
  async function checkSession(): Promise<void> {
    const endpoints = useRuntimeConfig().public.tenancy ? ['/me', '/tenant/me'] : ['/me']

    for (const endpoint of endpoints) {
      try {
        const response = await apiFetch<{ user: SessionUser }>(endpoint)
        currentUser.value = response.user
        authState.value = 'authenticated'
        return
      } catch {
        // Try the next endpoint; falling through means unauthenticated.
      }
    }

    currentUser.value = null
    authState.value = 'unauthenticated'
  }

  /** Pass a tenant slug to sign in as a tenant user; omit it for a global account. */
  async function login(email: string, password: string, tenantSlug?: string): Promise<void> {
    const path = tenantSlug ? `/tenants/${encodeURIComponent(tenantSlug)}/login` : '/login'
    const response = await apiFetch<{ user: SessionUser }>(path, {
      method: 'POST',
      body: { email, password },
    })
    currentUser.value = response.user
    authState.value = 'authenticated'
  }

  /**
   * Never throws: local session state is cleared whether or not the
   * server-side call succeeds, so a failed `/logout` (expired session, a
   * transient 500) cannot strand the caller mid-navigation.
   */
  async function logout(): Promise<void> {
    try {
      await apiFetch('/logout', { method: 'POST' })
    } catch {
      // Local state is cleared below regardless of the reason.
    } finally {
      currentUser.value = null
      authState.value = 'unauthenticated'
    }
  }

  return { authState, currentUser, checkSession, login, logout }
}
