import { FetchError } from 'ofetch'

/**
 * Client for the Laravel API. Every error the backend defines is a structured
 * JSON body (`{"error": "CODE", "fields"?: {...}}`), not a bare HTTP status,
 * so callers branch on `error.data.error` rather than parsing prose.
 */
export interface ApiErrorBody {
  error: string
  fields?: Record<string, string[]>
}

export function apiUrl(path: string): string {
  const config = useRuntimeConfig()
  return `${config.public.apiBase}${path}`
}

/**
 * Sanctum SPA (stateful/cookie) mode applies its session + CSRF middleware to
 * every /api/* request from a configured stateful origin, not only
 * authenticated ones: Laravel checks every state-changing method. The fix is
 * Sanctum's documented SPA pattern: GET /sanctum/csrf-cookie once to receive
 * the XSRF-TOKEN cookie, then echo it back as X-XSRF-TOKEN on state-changing
 * requests. ofetch does not do that automatically the way axios does.
 */
let csrfCookiePromise: Promise<void> | null = null

async function ensureCsrfCookie(): Promise<void> {
  if (!import.meta.client) return
  csrfCookiePromise ??= $fetch(`${useRuntimeConfig().public.apiOrigin}/sanctum/csrf-cookie`, {
    credentials: 'include',
  }) as Promise<void>
  await csrfCookiePromise
}

function readCookie(name: string): string | null {
  if (!import.meta.client) return null
  const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))
  return match ? decodeURIComponent(match[1] ?? '') : null
}

export async function apiFetch<T>(path: string, options: Parameters<typeof $fetch>[1] = {}): Promise<T> {
  const method = (options.method ?? 'GET').toString().toUpperCase()
  const isMutating = method !== 'GET' && method !== 'HEAD'

  if (isMutating) {
    await ensureCsrfCookie()
  }

  const headers: Record<string, string> = { ...((options.headers as Record<string, string> | undefined) ?? {}) }
  const xsrfToken = isMutating ? readCookie('XSRF-TOKEN') : null
  if (xsrfToken) {
    headers['X-XSRF-TOKEN'] = xsrfToken
  }

  return await $fetch<T>(apiUrl(path), {
    credentials: 'include',
    ...options,
    headers,
  })
}

/** Narrows a caught error to the backend's structured JSON body, or NETWORK_ERROR if the call never reached the API. */
export function apiErrorBody(error: unknown): ApiErrorBody {
  if (error instanceof FetchError && error.data && typeof error.data === 'object' && 'error' in error.data) {
    return error.data as ApiErrorBody
  }
  return { error: 'NETWORK_ERROR' }
}
