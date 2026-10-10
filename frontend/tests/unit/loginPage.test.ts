// @vitest-environment nuxt
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import LoginPage from '~/pages/login.vue'
import { flushUntil } from './support/waitFor'
import { startMockApi, type MockApi } from './support/mockApi'

let api: MockApi

beforeEach(async () => {
  api = await startMockApi()
  useSession().authState.value = 'unauthenticated'
})

afterEach(() => api.close())

describe('pages/login.vue', () => {
  it('shows the server\'s per-field validation message and a banner on a 422', async () => {
    api.route('/api/login', () => ({
      status: 422,
      body: { error: 'VALIDATION_FAILED', fields: { email: ['The email field must be a valid email address.'] } },
    }))

    const wrapper = await mountSuspended(LoginPage)

    await wrapper.find('input[name="email"]').setValue('not-an-email')
    await wrapper.find('input[name="password"]').setValue('whatever')
    await wrapper.find('form').trigger('submit.prevent')

    await flushUntil(() => wrapper.text().includes('The email field must be a valid email address.'))

    expect(wrapper.text()).toContain('Please check the highlighted fields.')
  })

  it('shows an incorrect-credentials banner on a 401', async () => {
    api.route('/api/login', () => ({ status: 401, body: { error: 'INVALID_CREDENTIALS' } }))

    const wrapper = await mountSuspended(LoginPage)

    await wrapper.find('input[name="email"]').setValue('una@example.test')
    await wrapper.find('input[name="password"]').setValue('bad-password')
    await wrapper.find('form').trigger('submit.prevent')

    await flushUntil(() => wrapper.text().includes('Incorrect email or password.'))
  })
})
