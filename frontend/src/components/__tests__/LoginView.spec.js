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

  it('can toggle password visibility', async () => {
    api.get.mockResolvedValueOnce({
      data: { data: { captcha_id: '123', captcha_image: 'data:image/svg+xml;base64,xxx' } }
    })

    const wrapper = mount(LoginView)

    const passwordInput = wrapper.find('#password')
    expect(passwordInput.attributes('type')).toBe('password')

    await wrapper.find('.toggle-password').trigger('click')
    expect(passwordInput.attributes('type')).toBe('text')
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
