import { expect, test, type Page } from '@playwright/test'

/**
 * Real browser, real Laravel API, real database. Requires the backend to be
 * migrated and seeded (`php artisan migrate:fresh --seed
 * --database=pgsql_migrator`), which creates admin@example.test / password.
 */
/**
 * Pages are server-rendered, then hydrated. A click before hydration
 * finishes submits the form natively (a GET with the credentials in the URL)
 * instead of running the Vue handler, so wait for the network to go idle
 * after navigating to a page with a form.
 */
async function gotoHydrated(page: Page, path: string): Promise<void> {
  await page.goto(path, { waitUntil: 'networkidle' })
}

test('the home page renders', async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading', { name: 'starter-app' })).toBeVisible()
})

test('a protected page redirects an anonymous visitor to the login form', async ({ page }) => {
  await page.goto('/dashboard')
  await expect(page).toHaveURL(/\/login$/)
  await expect(page.getByRole('heading', { name: 'Log in' })).toBeVisible()
})

test('an admin can log in, see the dashboard, and log out', async ({ page }) => {
  await gotoHydrated(page, '/login')

  await page.locator('input[name="email"]').fill('admin@example.test')
  await page.locator('input[name="password"]').fill('password')
  await page.getByRole('button', { name: 'Log in' }).click()

  await expect(page).toHaveURL(/\/dashboard$/)
  await expect(page.getByText('admin@example.test')).toBeVisible()

  await page.getByRole('button', { name: 'Log out' }).click()
  await expect(page).toHaveURL(/\/login$/)
})

test('a wrong password shows an error and stays on the form', async ({ page }) => {
  await gotoHydrated(page, '/login')

  await page.locator('input[name="email"]').fill('admin@example.test')
  await page.locator('input[name="password"]').fill('wrong-password')
  await page.getByRole('button', { name: 'Log in' }).click()

  await expect(page.getByRole('alert')).toContainText('Incorrect email or password.')
})
