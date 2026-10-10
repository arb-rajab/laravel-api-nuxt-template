// @vitest-environment nuxt
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { FetchError } from 'ofetch'
import { apiErrorBody, apiFetch } from '~/composables/useApi'
import { startMockApi, type MockApi } from './support/mockApi'

describe('apiErrorBody', () => {
  it('extracts the backend\'s structured { error, fields } body from a FetchError', () => {
    const error = new FetchError('Unprocessable')
    // @ts-expect-error - test double for the ofetch response shape apiErrorBody reads
    error.data = { error: 'VALIDATION_FAILED', fields: { name: ['required'] } }

    expect(apiErrorBody(error)).toEqual({ error: 'VALIDATION_FAILED', fields: { name: ['required'] } })
  })

  it('falls back to NETWORK_ERROR when the request never reached the API at all', () => {
    expect(apiErrorBody(new TypeError('Failed to fetch'))).toEqual({ error: 'NETWORK_ERROR' })
  })

  it('falls back to NETWORK_ERROR for a FetchError whose data carries no structured error code', () => {
    const error = new FetchError('Server error')
    // @ts-expect-error - simulating an unstructured 500 (an HTML error page)
    error.data = '<html>500</html>'

    expect(apiErrorBody(error)).toEqual({ error: 'NETWORK_ERROR' })
  })
})

describe('apiFetch', () => {
  let api: MockApi

  beforeEach(async () => {
    api = await startMockApi()
  })

  afterEach(() => api.close())

  it('does not fetch a CSRF cookie for a plain GET', async () => {
    api.route('/api/ready', () => ({ status: 200, body: { status: 'ready' } }))

    await expect(apiFetch('/ready')).resolves.toEqual({ status: 'ready' })
    expect(api.requests).toEqual(['GET /api/ready'])
  })

  it('fetches the CSRF cookie before a state-changing request, and echoes the XSRF token header', async () => {
    document.cookie = 'XSRF-TOKEN=token%3Dvalue; path=/'
    let seenHeader: string | string[] | undefined
    api.route('/api/login', (req) => {
      seenHeader = req.headers['x-xsrf-token']
      return { status: 200, body: { ok: true } }
    })

    await apiFetch('/login', { method: 'POST', body: { email: 'a@example.test' } })

    expect(api.requests).toContain('GET /sanctum/csrf-cookie')
    expect(api.requests).toContain('POST /api/login')
    expect(seenHeader).toBe('token=value')
  })

  it('rejects with a FetchError carrying the structured error body on a 4xx', async () => {
    api.route('/api/me', () => ({ status: 401, body: { error: 'UNAUTHENTICATED' } }))

    const error = await apiFetch('/me').catch((e: unknown) => e)

    expect(apiErrorBody(error)).toEqual({ error: 'UNAUTHENTICATED' })
  })
})
