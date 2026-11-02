import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import LoginView from '../../views/LoginView.vue'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '../../stores/auth'
import api from '../../utils/axios'

vi.mock('../../utils/axios', () => {
  return {
    default: {
      get: vi.fn(),
      post: vi.fn()
    }
  }
})

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
    currentRoute: {
      value: {
        query: {}
      }
    }
  })
}))

describe('LoginView.vue', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('renders login form properly', () => {
    api.get.mockResolvedValueOnce({
      data: { data: { captcha_id: '123', captcha_image: 'data:image/svg+xml;base64,xxx' } }
    })

    const wrapper = mount(LoginView)

    expect(wrapper.find('h1').text()).toContain('Masukkan Username dan Password')
    expect(wrapper.find('#username').exists()).toBe(true)
    expect(wrapper.find('#password').exists()).toBe(true)
    expect(wrapper.find('.captcha-input').exists()).toBe(true)
  })

  it('can toggle password visibility via eye icon button back and forth', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { captcha_id: '123', captcha_image: 'data:image/svg+xml;base64,xxx' } }
    })

    const wrapper = mount(LoginView)

    const passwordInput = wrapper.find('#password')
    const toggleButton = wrapper.find('.toggle-password')

    // Initial state: password hidden (type="password")
    expect(passwordInput.attributes('type')).toBe('password')
    expect(toggleButton.find('line').exists()).toBe(true) // eye-off line slash

    // Click to show password
    await toggleButton.trigger('click')
    expect(passwordInput.attributes('type')).toBe('text')
    expect(toggleButton.find('line').exists()).toBe(false) // open eye without line slash

    // Click again to hide password
    await toggleButton.trigger('click')
    expect(passwordInput.attributes('type')).toBe('password')
    expect(toggleButton.find('line').exists()).toBe(true)
  })

  it('does not render "Lihat Password" checkbox and renders "Bantuan Login?" link', () => {
    api.get.mockResolvedValueOnce({
      data: { data: { captcha_id: '123', captcha_image: 'data:image/svg+xml;base64,xxx' } }
    })

    const wrapper = mount(LoginView)

    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
    expect(wrapper.find('.checkbox-container').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Lihat Password')

    const helpLink = wrapper.find('.help-link')
    expect(helpLink.exists()).toBe(true)
    expect(helpLink.text()).toBe('Bantuan Login?')
    expect(wrapper.find('.form-options').exists()).toBe(true)
  })

  it('submits form to auth store', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { captcha_id: '123', captcha_image: 'data:image/svg+xml;base64,xxx' } }
    })

    const wrapper = mount(LoginView)

    const authStore = useAuthStore()
    authStore.login = vi.fn().mockResolvedValue(true)

    await wrapper.find('#username').setValue('testuser')
    await wrapper.find('#password').setValue('testpass')
    await wrapper.find('.captcha-input').setValue('12345')

    await wrapper.find('form').trigger('submit.prevent')

    expect(authStore.login).toHaveBeenCalledWith('testuser', 'testpass', '123', '12345')
  })
})
