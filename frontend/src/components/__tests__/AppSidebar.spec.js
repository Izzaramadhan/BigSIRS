import { describe, it, expect, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createPinia } from 'pinia';

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } },
    { path: '/dashboard', name: 'dashboard', component: { template: '<div>Dashboard</div>' } },
    { path: '/master-data/polyclinics', name: 'polyclinics', component: { template: '<div>Polyclinics</div>' } },
    { path: '/master-data/procedure-categories', name: 'procedure-categories', component: { template: '<div>Procedure Categories</div>' } },
  ],
});

describe('AppSidebar.vue', () => {
  beforeEach(async () => {
    await router.push('/');
    await router.isReady();
  });

  it('renders branding with new logo and text', () => {
    const wrapper = mount(AppSidebar, {
      global: {
        plugins: [router, createPinia()],
      },
      props: { isOpen: true },
    });

    const brandLink = wrapper.find('.sidebar-brand');
    expect(brandLink.exists()).toBe(true);
    expect(brandLink.attributes('href')).toBe('/dashboard');
    expect(wrapper.find('.sidebar-brand__logo').exists()).toBe(true);
    expect(wrapper.find('.sidebar-brand__text').text()).toContain('BigSirs SIMRS');
  });

  it('renders Master Data as parent, Poliklinik as child, and Master Data Tindakan as nested group', () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    // 1. Master Data tampil sebagai parent
    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    expect(masterDataBtn).toBeDefined();

    const mdSubmenu = wrapper.find('#submenu-master-data');
    expect(mdSubmenu.exists()).toBe(true);

    // 2. Poliklinik tampil sebagai child langsung Master Data
    const polyclinicLink = mdSubmenu.findAll('.submenu-link').find(l => l.text().includes('Poliklinik'));
    expect(polyclinicLink).toBeDefined();

    // 3. Master Data Tindakan tampil sebagai nested group
    const nestedGroupBtn = mdSubmenu.findAll('button.submenu-link--group').find(b => b.text().includes('Master Data Tindakan'));
    expect(nestedGroupBtn).toBeDefined();

    // 4. Kategori Tindakan berada di dalam Master Data Tindakan
    const nestedSubmenu = mdSubmenu.find('#submenu-master-data-tindakan');
    expect(nestedSubmenu.exists()).toBe(true);
    const categoryLink = nestedSubmenu.findAll('.nested-submenu-link').find(l => l.text().includes('Kategori Tindakan'));
    expect(categoryLink).toBeDefined();
    
    // 7. Klik Kategori Tindakan menuju route existing (check path)
    expect(categoryLink.attributes('href')).toBe('/master-data/procedure-categories');

    // 5. Penjamin tidak tampil
    const penjaminLink = mdSubmenu.findAll('*').find(el => el.text() === 'Penjamin');
    expect(penjaminLink).toBeUndefined();
  });

  it('toggles nested submenu on click and changes aria-expanded', async () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const nestedGroupBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Master Data Tindakan'));
    
    // 12. aria-expanded berubah sesuai keadaan menu
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('false');

    // 6. Klik Master Data Tindakan membuka/menutup submenu
    await nestedGroupBtn.trigger('click');
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('true');
    
    await nestedGroupBtn.trigger('click');
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('false');
  });

  it('automatically expands parents and sets active state for Procedure Categories route', async () => {
    await router.push('/master-data/procedure-categories');
    
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    // 8. Route Kategori Tindakan otomatis membuka kedua parent
    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const nestedGroupBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Master Data Tindakan'));
    
    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('true');

    // 9. Kategori Tindakan mendapatkan active state
    const categoryLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Kategori Tindakan'));
    expect(categoryLink.classes()).toContain('nested-submenu-link--active');
    
    // Parent group gets active parent state
    expect(nestedGroupBtn.classes()).toContain('submenu-link--active-parent');
    expect(masterDataBtn.classes()).toContain('nav-link--active-parent');
    
    // Polyclinic is NOT active
    const polyclinicLink = wrapper.findAll('.submenu-link').find(l => l.text().includes('Poliklinik'));
    expect(polyclinicLink.classes()).not.toContain('submenu-link--active');
  });

  it('automatically expands parents and sets active state for Polyclinics route', async () => {
    await router.push('/master-data/polyclinics');
    
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    // 10. Route Poliklinik mengaktifkan Poliklinik dan tidak mengaktifkan Kategori Tindakan
    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const nestedGroupBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Master Data Tindakan'));
    
    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    // Master Data Tindakan does not need to be expanded when we are in Polyclinic
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('false');

    const polyclinicLink = wrapper.findAll('.submenu-link').find(l => l.text().includes('Poliklinik'));
    expect(polyclinicLink.classes()).toContain('submenu-link--active');

    const categoryLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Kategori Tindakan'));
    expect(categoryLink.classes()).not.toContain('nested-submenu-link--active');
  });
  
  it('updates hierarchy correctly on route change (like refresh)', async () => {
    // 11. Refresh route mempertahankan hierarchy yang benar
    // Simulate mounting at dashboard, then routing to nested route
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();
    
    let masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    let nestedGroupBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Master Data Tindakan'));
    expect(masterDataBtn.attributes('aria-expanded')).toBe('false');
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('false');
    
    await router.push('/master-data/procedure-categories');
    await flushPromises();
    
    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(nestedGroupBtn.attributes('aria-expanded')).toBe('true');
  });
});
