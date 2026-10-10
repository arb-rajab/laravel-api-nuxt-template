// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/eslint'],
  // This app never talks to a database directly, only to the Laravel API.
  // Override via NUXT_PUBLIC_API_ORIGIN / NUXT_PUBLIC_API_BASE for any
  // non-local deployment; the defaults match `php artisan serve`.
  runtimeConfig: {
    public: {
      apiOrigin: process.env.NUXT_PUBLIC_API_ORIGIN || 'http://localhost:8000',
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://localhost:8000/api',
      // Set NUXT_PUBLIC_TENANCY=false if you removed the backend's optional
      // tenancy module: the login form then hides the tenant field.
      tenancy: process.env.NUXT_PUBLIC_TENANCY !== 'false',
    },
  },
})
