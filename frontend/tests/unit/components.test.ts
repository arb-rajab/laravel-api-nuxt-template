// @vitest-environment nuxt
import { describe, expect, it, vi } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import AppAlert from '~/components/AppAlert.vue'
import AppButton from '~/components/AppButton.vue'
import AppDataTable from '~/components/AppDataTable.vue'
import AppEmptyState from '~/components/AppEmptyState.vue'
import AppModal from '~/components/AppModal.vue'
import AppPagination from '~/components/AppPagination.vue'
import AppSpinner from '~/components/AppSpinner.vue'
import AppTextInput from '~/components/AppTextInput.vue'

describe('AppButton', () => {
  it('renders its slot, defaults to type=button and honours disabled', async () => {
    const wrapper = await mountSuspended(AppButton, { slots: { default: 'Save' }, props: { disabled: true } })

    expect(wrapper.text()).toBe('Save')
    expect(wrapper.attributes('type')).toBe('button')
    expect(wrapper.attributes('disabled')).toBeDefined()
  })

  it('emits click when enabled', async () => {
    const onClick = vi.fn()
    const wrapper = await mountSuspended(AppButton, { slots: { default: 'Go' }, attrs: { onClick } })

    await wrapper.find('button').trigger('click')

    expect(onClick).toHaveBeenCalledTimes(1)
  })
})

describe('AppAlert', () => {
  it('uses role=alert for errors and role=status for info', async () => {
    const error = await mountSuspended(AppAlert, { slots: { default: 'Broken' } })
    const info = await mountSuspended(AppAlert, { props: { kind: 'info' }, slots: { default: 'FYI' } })

    expect(error.attributes('role')).toBe('alert')
    expect(info.attributes('role')).toBe('status')
  })
})

describe('AppTextInput', () => {
  it('binds v-model and shows the field error', async () => {
    const wrapper = await mountSuspended(AppTextInput, {
      props: { label: 'Email', name: 'email', modelValue: '', error: 'The email field is required.' },
    })

    expect(wrapper.text()).toContain('Email')
    expect(wrapper.text()).toContain('The email field is required.')
    expect(wrapper.find('input').attributes('aria-invalid')).toBe('true')

    await wrapper.find('input').setValue('a@example.test')

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['a@example.test'])
  })

  it('shows no error text and no aria-invalid when valid', async () => {
    const wrapper = await mountSuspended(AppTextInput, { props: { label: 'Name', name: 'name' } })

    expect(wrapper.find('.field-error').exists()).toBe(false)
    expect(wrapper.find('input').attributes('aria-invalid')).toBeUndefined()
  })
})

describe('AppSpinner', () => {
  it('is an accessible status element with a label', async () => {
    const wrapper = await mountSuspended(AppSpinner, { props: { label: 'Saving' } })

    expect(wrapper.attributes('role')).toBe('status')
    expect(wrapper.attributes('aria-label')).toBe('Saving')
  })
})

describe('AppEmptyState', () => {
  it('shows the title, optional description and actions slot', async () => {
    const wrapper = await mountSuspended(AppEmptyState, {
      props: { title: 'No items', description: 'Create your first one.' },
      slots: { default: '<button>Create</button>' },
    })

    expect(wrapper.text()).toContain('No items')
    expect(wrapper.text()).toContain('Create your first one.')
    expect(wrapper.find('button').exists()).toBe(true)
  })

  it('omits the description and actions when not given', async () => {
    const wrapper = await mountSuspended(AppEmptyState, { props: { title: 'Nothing' } })

    expect(wrapper.find('.app-empty-state__description').exists()).toBe(false)
    expect(wrapper.find('.app-empty-state__actions').exists()).toBe(false)
  })
})

describe('AppModal', () => {
  it('renders nothing while closed', async () => {
    const wrapper = await mountSuspended(AppModal, { props: { open: false, title: 'Confirm' } })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('renders a labelled dialog and emits close on Escape, the close button and the backdrop', async () => {
    const wrapper = await mountSuspended(AppModal, {
      props: { open: true, title: 'Confirm' },
      slots: { default: 'Are you sure?', footer: '<button>OK</button>' },
    })
    const dialog = wrapper.find('[role="dialog"]')

    expect(dialog.attributes('aria-modal')).toBe('true')
    expect(dialog.attributes('aria-labelledby')).toBe(wrapper.find('h2').attributes('id'))
    expect(wrapper.text()).toContain('Are you sure?')

    await dialog.trigger('keydown', { key: 'Escape' })
    await wrapper.find('button[aria-label="Close"]').trigger('click')
    await wrapper.find('.app-modal-backdrop').trigger('click')

    expect(wrapper.emitted('close')).toHaveLength(3)
  })

  it('does not close when the dialog body itself is clicked', async () => {
    const wrapper = await mountSuspended(AppModal, { props: { open: true, title: 'Confirm' } })

    await wrapper.find('.app-modal').trigger('click')

    expect(wrapper.emitted('close')).toBeUndefined()
  })
})

describe('AppPagination', () => {
  const meta = { current_page: 2, per_page: 10, total: 35, last_page: 4 }

  it('emits the neighbouring page numbers', async () => {
    const wrapper = await mountSuspended(AppPagination, { props: { meta } })
    const [previous, next] = wrapper.findAll('button')

    await previous!.trigger('click')
    await next!.trigger('click')

    expect(wrapper.text()).toContain('Page 2 of 4')
    expect(wrapper.emitted('change')).toEqual([[1], [3]])
  })

  it('disables previous on the first page and next on the last', async () => {
    const first = await mountSuspended(AppPagination, { props: { meta: { ...meta, current_page: 1 } } })
    const last = await mountSuspended(AppPagination, { props: { meta: { ...meta, current_page: 4 } } })

    expect(first.findAll('button')[0]!.attributes('disabled')).toBeDefined()
    expect(last.findAll('button')[1]!.attributes('disabled')).toBeDefined()
  })

  it('renders nothing for a single page', async () => {
    const wrapper = await mountSuspended(AppPagination, { props: { meta: { ...meta, current_page: 1, last_page: 1 } } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })
})

describe('AppDataTable', () => {
  const columns = [{ key: 'name', label: 'Name' }, { key: 'role', label: 'Role' }]
  const rows = [{ id: 'a', name: 'Ada', role: 'admin' }, { id: 'b', name: 'Bo', role: 'member' }]

  it('renders a header per column and a row per record', async () => {
    const wrapper = await mountSuspended(AppDataTable, { props: { columns, rows } })

    expect(wrapper.findAll('th').map(th => th.text())).toEqual(['Name', 'Role'])
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)
    expect(wrapper.text()).toContain('Ada')
  })

  it('lets a cell slot override the default rendering', async () => {
    const wrapper = await mountSuspended(AppDataTable, {
      props: { columns, rows },
      slots: { 'cell-role': '<template #cell-role="{ value }"><strong>{{ value }}!</strong></template>' },
    })

    expect(wrapper.findAll('strong').map(el => el.text())).toEqual(['admin!', 'member!'])
  })

  it('shows a spinner while loading, and the empty state when there are no rows', async () => {
    const loading = await mountSuspended(AppDataTable, { props: { columns, rows, loading: true } })
    const empty = await mountSuspended(AppDataTable, { props: { columns, rows: [], emptyTitle: 'No users' } })

    expect(loading.find('[role="status"]').exists()).toBe(true)
    expect(loading.find('table').exists()).toBe(false)
    expect(empty.text()).toContain('No users')
    expect(empty.find('table').exists()).toBe(false)
  })
})
