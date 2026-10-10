import { createServer, type IncomingMessage, type Server } from 'node:http'
import type { AddressInfo } from 'node:net'

/**
 * `@nuxt/test-utils`'s `registerEndpoint` mocks the current Nuxt app's own
 * server routes; it has no hook into a `$fetch` call to a different origin,
 * which is what every call in this app makes (a decoupled API). Rather than
 * stub internals, tests start a real, tiny HTTP server as a stand-in for the
 * Laravel API and point `useRuntimeConfig().public` at it, so the code under
 * test makes a real network call end to end.
 */
export type RouteHandler = (req: IncomingMessage) => { status: number, body: unknown }

export interface MockApi {
  route: (path: string, handler: RouteHandler) => void
  /** Paths requested so far, in order, as `METHOD path`. */
  requests: string[]
  close: () => Promise<void>
}

export async function startMockApi(): Promise<MockApi> {
  const routes: Record<string, RouteHandler> = {
    '/sanctum/csrf-cookie': () => ({ status: 204, body: null }),
  }
  const requests: string[] = []

  const server: Server = createServer((req, res) => {
    requests.push(`${req.method} ${req.url}`)
    const handler = req.url && Object.hasOwn(routes, req.url) ? routes[req.url] : undefined
    if (!handler) {
      res.writeHead(404, { 'Content-Type': 'application/json' }).end(JSON.stringify({ error: 'NOT_FOUND' }))
      return
    }
    const { status, body } = handler(req)
    res.writeHead(status, { 'Content-Type': 'application/json' })
    res.end(body === null ? undefined : JSON.stringify(body))
  })

  await new Promise<void>(resolve => server.listen(0, '127.0.0.1', resolve))
  const { port } = server.address() as AddressInfo

  const config = useRuntimeConfig()
  config.public.apiOrigin = `http://127.0.0.1:${port}`
  config.public.apiBase = `http://127.0.0.1:${port}/api`

  return {
    route: (path, handler) => {
      routes[path] = handler
    },
    requests,
    close: () => new Promise<void>(resolve => server.close(() => resolve())),
  }
}
