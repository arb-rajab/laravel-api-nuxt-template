// @vitest-environment nuxt
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { useSession } from '~/composables/useSession'
import { startMockApi, type MockApi } from './support/mockApi'

const globalUser = { id: '1', name: 'Una', email: 'una@example.test', role: 'admin', tenant_id: null }
const tenantUser = { id: '2', name: 'Tess', email: 'tess@example.test', role: 'member', tenant_id: 't-1' }

let api: MockApi

beforeEach(async () => {
  api = await startMockApi()
  // useState is a shared singleton across this file (a real Nuxt app is not
  // re-created per `it()`), so reset it to keep tests independent.
  const { authState, currentUser } = useSession()
  authState.value = 'checking'
  currentUser.value = null
})

afterEach(() => api.close())

describe('useSession', () => {
  it('checkSession() authenticates a global user via /me', async () => {
    api.route('/api/me', () => ({ status: 200, body: { user: globalUser } }))

    const { authState, currentUser, checkSession } = useSession()
    await checkSession()

    expect(authState.value).toBe('authenticated')
    expect(currentUser.value?.email).toBe('una@example.test')
  })

  it('checkSession() falls back to /tenant/me for a tenant user', async () => {
    api.route('/api/me', () => ({ status: 401, body: { error: 'UNAUTHENTICATED' } }))
    api.route('/api/tenant/me', () => ({ status: 200, body: { user: tenantUser } }))

    const { authState, currentUser, checkSession } = useSession()
    await checkSession()

    expect(authState.value).toBe('authenticated')
    expect(currentUser.value?.tenant_id).toBe('t-1')
  })

  it('checkSession() lands on unauthenticated and clears the user when both probes 401', async () => {
    api.route('/api/me', () => ({ status: 401, body: { error: 'UNAUTHENTICATED' } }))
    api.route('/api/tenant/me', () => ({ status: 401, body: { error: 'UNAUTHENTICATED' } }))

    const { authState, currentUser, checkSession } = useSession()
    currentUser.value = globalUser

    await checkSession()

    expect(authState.value).toBe('unauthenticated')
    expect(currentUser.value).toBeNull()
  })

  it('login() posts to /login without a slug and stores the returned user', async () => {
    api.route('/api/login', () => ({ status: 200, body: { user: globalUser } }))

    const { authState, currentUser, login } = useSession()
    await login('una@example.test', 'password')

    expect(authState.value).toBe('authenticated')
    expect(currentUser.value?.name).toBe('Una')
  })

  it('login() posts to the tenant login route when a slug is given', async () => {
    api.route('/api/tenants/acme/login', () => ({ status: 200, body: { user: tenantUser } }))

    const { currentUser, login } = useSession()
    await login('tess@example.test', 'password', 'acme')

    expect(api.requests).toContain('POST /api/tenants/acme/login')
    expect(currentUser.value?.tenant_id).toBe('t-1')
  })

  it('login() surfaces INVALID_CREDENTIALS without authenticating', async () => {
    api.route('/api/login', () => ({ status: 401, body: { error: 'INVALID_CREDENTIALS' } }))

    const { authState, login } = useSession()

    await expect(login('una@example.test', 'wrong')).rejects.toThrow()
    expect(authState.value).not.toBe('authenticated')
  })

  it('logout() clears the user and flips to unauthenticated even if the request fails', async () => {
    api.route('/api/logout', () => ({ status: 500, body: { error: 'HTTP_ERROR' } }))

    const { authState, currentUser, logout } = useSession()
    currentUser.value = globalUser
    authState.value = 'authenticated'

    await logout()

    expect(authState.value).toBe('unauthenticated')
    expect(currentUser.value).toBeNull()
  })
})
