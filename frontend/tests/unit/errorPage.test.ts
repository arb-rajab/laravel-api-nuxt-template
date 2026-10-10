// @vitest-environment nuxt
import { describe, expect, it } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import ErrorPage from '~/error.vue'

function nuxtError(statusCode: number) {
  return { statusCode, statusMessage: '', message: '', fatal: false, unhandled: false }
}

describe('error.vue', () => {
  it('explains a 404 as a missing page', async () => {
    const wrapper = await mountSuspended(ErrorPage, { props: { error: nuxtError(404) as never } })

    expect(wrapper.find('h1').text()).toBe('Page not found')
  })

  it('shows a generic message for other errors', async () => {
    const wrapper = await mountSuspended(ErrorPage, { props: { error: nuxtError(500) as never } })

    expect(wrapper.find('h1').text()).toBe('Something went wrong')
  })
})
