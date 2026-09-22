import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' } },
  ],
});

describe('AppSidebar.vue', () => {
  it('renders branding with new logo and text', () => {
    const wrapper = mount(AppSidebar, {
      global: {
        plugins: [
          router,
          createPinia(),
        ],
      },
      props: {
        isOpen: true,
      },
    });

    const brandLink = wrapper.find('.sidebar-brand');
    expect(brandLink.exists()).toBe(true);
    expect(brandLink.attributes('href')).toBe('/dashboard');

    const logo = wrapper.find('.sidebar-brand__logo');
    expect(logo.exists()).toBe(true);
    // Since vite processes the asset, we can check if it contains logo-sisfo
    // In test environment, the src might be mocked or transformed
    expect(logo.attributes('src')).toContain('logo-sisfo');

    const brandText = wrapper.find('.sidebar-brand__text');
    expect(brandText.exists()).toBe(true);
    expect(brandText.text()).toContain('BigSirs SIMRS');
    expect(brandText.text()).toContain('PT SISFOMEDIKA');
  });

  it('renders branding elements properly for collapsed state (class based)', () => {
    // The collapsed state is mostly CSS handled by media query, 
    // but we verify the HTML structure remains intact.
    const wrapper = mount(AppSidebar, {
      global: {
        plugins: [
          router,
          createPinia(),
        ],
      },
      props: {
        isOpen: false,
      },
    });

    const logo = wrapper.find('.sidebar-brand__logo');
    expect(logo.exists()).toBe(true);

    const brandText = wrapper.find('.sidebar-brand__text');
    expect(brandText.exists()).toBe(true);
    // The CSS will handle hiding text on collapsed/mobile state
  });
});
