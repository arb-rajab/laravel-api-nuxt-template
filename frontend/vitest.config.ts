import { defineVitestConfig } from '@nuxt/test-utils/config'

/**
 * `@nuxt/test-utils`'s vitest environment gives tests the real Nuxt
 * auto-import context (`useState`, `useRuntimeConfig`, `$fetch`, ...) that
 * the composables genuinely depend on. A plain Vitest setup with those calls
 * stubbed by hand would test a fiction, not the app's actual composables.
 */
export default defineVitestConfig({
  test: {
    environment: 'nuxt',
    include: ['tests/unit/**/*.test.ts'],
  },
})
