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
    { path: '/master-data/laboratory-groups', name: 'laboratory-groups', component: { template: '<div>Laboratory Groups</div>' } },
    { path: '/master-data/basic-data/regencies', name: 'regencies', component: { template: '<div>Regencies</div>' } },
    { path: '/master-data/basic-data/districts', name: 'districts', component: { template: '<div>Districts</div>' } },
    { path: '/master-data/bed/buildings', name: 'buildings', component: { template: '<div>Buildings</div>' } },
    { path: '/master-data/bed/floors', name: 'floors', component: { template: '<div>Floors</div>' } },
    { path: '/master-data/bed/wards', name: 'wards', component: { template: '<div>Wards</div>' } },
    { path: '/master-data/bed/rooms', name: 'rooms', component: { template: '<div>Rooms</div>' } },
    { path: '/master-data/bed/beds', name: 'beds', component: { template: '<div>Beds</div>' } },
    { path: '/master-data/bed/room-classes', name: 'room-classes', component: { template: '<div>Room Classes</div>' } },
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

  it('renders Data Lab as nested group with 3 submenus including Grup Lab', () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    expect(masterDataBtn).toBeDefined();

    const mdSubmenu = wrapper.find('#submenu-master-data');
    const dataLabBtn = mdSubmenu.findAll('button.submenu-link--group').find(b => b.text().includes('Data Lab'));
    expect(dataLabBtn).toBeDefined();

    const dataLabSubmenu = mdSubmenu.find('#submenu-data-lab');
    expect(dataLabSubmenu.exists()).toBe(true);

    const categories = dataLabSubmenu.findAll('.nested-submenu-link');
    // Ensure we have 3 links: Kategori, Item, Grup
    expect(categories.length).toBe(3);

    expect(categories[0].text()).toContain('Kategori Lab');
    expect(categories[1].text()).toContain('Item Lab');
    expect(categories[2].text()).toContain('Grup Lab');
    expect(categories[2].attributes('href')).toBe('/master-data/laboratory-groups');
  });

  it('automatically expands parents and sets active state for Grup Lab route', async () => {
    await router.push('/master-data/laboratory-groups');
    
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataLabBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Lab'));
    
    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataLabBtn.attributes('aria-expanded')).toBe('true');

    const groupLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Grup Lab'));
    expect(groupLink.classes()).toContain('nested-submenu-link--active');
  });

  it('applies smooth collapse classes and rotates chevron icon when submenu is toggled', async () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const chevronIcon = masterDataBtn.find('.submenu-icon');
    const collapseWrapper = wrapper.find('.submenu-collapse');

    // Initially closed
    expect(collapseWrapper.classes()).not.toContain('submenu-collapse--open');
    expect(chevronIcon.classes()).not.toContain('submenu-icon--open');
    expect(masterDataBtn.attributes('aria-expanded')).toBe('false');

    // Open submenu
    await masterDataBtn.trigger('click');
    expect(collapseWrapper.classes()).toContain('submenu-collapse--open');
    expect(chevronIcon.classes()).toContain('submenu-icon--open');
    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');

    // Close submenu
    await masterDataBtn.trigger('click');
    expect(collapseWrapper.classes()).not.toContain('submenu-collapse--open');
    expect(chevronIcon.classes()).not.toContain('submenu-icon--open');
    expect(masterDataBtn.attributes('aria-expanded')).toBe('false');
  });

  it('supports nested submenu collapse with small and large item sets', async () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    await masterDataBtn.trigger('click');

    const mdSubmenu = wrapper.find('#submenu-master-data');

    // Test large submenu: Data Dasar (10 items)
    const dataDasarBtn = mdSubmenu.findAll('button.submenu-link--group').find(b => b.text().includes('Data Dasar'));
    expect(dataDasarBtn).toBeDefined();

    const dataDasarSubmenu = mdSubmenu.find('#submenu-data-dasar');
    expect(dataDasarSubmenu.exists()).toBe(true);
    const dataDasarLinks = dataDasarSubmenu.findAll('.nested-submenu-link');
    expect(dataDasarLinks.length).toBe(10);

    const dataDasarCollapse = dataDasarSubmenu.element.closest('.submenu-collapse');
    expect(dataDasarCollapse.classList.contains('submenu-collapse--open')).toBe(false);

    await dataDasarBtn.trigger('click');
    expect(dataDasarCollapse.classList.contains('submenu-collapse--open')).toBe(true);

    // Test small submenu: Data Dokter (2 items)
    const dataDokterBtn = mdSubmenu.findAll('button.submenu-link--group').find(b => b.text().includes('Data Dokter'));
    expect(dataDokterBtn).toBeDefined();

    const dataDokterSubmenu = mdSubmenu.find('#submenu-data-dokter');
    expect(dataDokterSubmenu.exists()).toBe(true);
    const dataDokterLinks = dataDokterSubmenu.findAll('.nested-submenu-link');
    expect(dataDokterLinks.length).toBe(2);

    const dataDokterCollapse = dataDokterSubmenu.element.closest('.submenu-collapse');
    expect(dataDokterCollapse.classList.contains('submenu-collapse--open')).toBe(false);

    await dataDokterBtn.trigger('click');
    expect(dataDokterCollapse.classList.contains('submenu-collapse--open')).toBe(true);
  });

  it('handles rapid consecutive clicks without getting stuck in a wrong state', async () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const collapseWrapper = wrapper.find('.submenu-collapse');

    // Rapid 5 clicks
    await masterDataBtn.trigger('click'); // 1: open
    await masterDataBtn.trigger('click'); // 2: close
    await masterDataBtn.trigger('click'); // 3: open
    await masterDataBtn.trigger('click'); // 4: close
    await masterDataBtn.trigger('click'); // 5: open

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(collapseWrapper.classes()).toContain('submenu-collapse--open');

    // 6th click: closes cleanly
    await masterDataBtn.trigger('click');
    expect(masterDataBtn.attributes('aria-expanded')).toBe('false');
    expect(collapseWrapper.classes()).not.toContain('submenu-collapse--open');
  });

  it('maintains open state across route transitions within the same group', async () => {
    await router.push('/master-data/basic-data/regencies');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataDasarBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Dasar'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataDasarBtn.attributes('aria-expanded')).toBe('true');

    // Navigate to another route in the same group
    await router.push('/master-data/basic-data/districts');
    await flushPromises();

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataDasarBtn.attributes('aria-expanded')).toBe('true');
  });

  it('renders Data Bed as nested group with Gedung submenu', () => {
    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });

    const mdSubmenu = wrapper.find('#submenu-master-data');
    const dataBedBtn = mdSubmenu.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));
    expect(dataBedBtn).toBeDefined();

    const dataBedSubmenu = mdSubmenu.find('#submenu-data-bed');
    expect(dataBedSubmenu.exists()).toBe(true);

    const gedungLink = dataBedSubmenu.findAll('.nested-submenu-link').find(l => l.text().includes('Gedung'));
    expect(gedungLink).toBeDefined();
    expect(gedungLink.attributes('href')).toBe('/master-data/bed/buildings');

    const lantaiLink = dataBedSubmenu.findAll('.nested-submenu-link').find(l => l.text().includes('Lantai'));
    expect(lantaiLink).toBeDefined();
    expect(lantaiLink.attributes('href')).toBe('/master-data/bed/floors');

    const bangsalLink = dataBedSubmenu.findAll('.nested-submenu-link').find(l => l.text().includes('Bangsal'));
    expect(bangsalLink).toBeDefined();
    expect(bangsalLink.attributes('href')).toBe('/master-data/bed/wards');
  });

  it('automatically expands parents and sets active state for Gedung route', async () => {
    await router.push('/master-data/bed/buildings');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const gedungLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Gedung'));
    expect(gedungLink.classes()).toContain('nested-submenu-link--active');
  });

  it('automatically expands parents and sets active state for Lantai route', async () => {
    await router.push('/master-data/bed/floors');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const lantaiLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Lantai'));
    expect(lantaiLink.classes()).toContain('nested-submenu-link--active');
  });

  it('automatically expands parents and sets active state for Bangsal route', async () => {
    await router.push('/master-data/bed/wards');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const bangsalLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Bangsal'));
    expect(bangsalLink.classes()).toContain('nested-submenu-link--active');
  });

  it('automatically expands parents and sets active state for Kamar route', async () => {
    await router.push('/master-data/bed/rooms');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const kamarLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().includes('Kamar'));
    expect(kamarLink.classes()).toContain('nested-submenu-link--active');
  });

  it('automatically expands parents and sets active state for Bed route', async () => {
    await router.push('/master-data/bed/beds');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const bedLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().trim() === 'Bed');
    expect(bedLink.classes()).toContain('nested-submenu-link--active');
  });

  it('automatically expands parents and sets active state for Kelas route', async () => {
    await router.push('/master-data/bed/room-classes');

    const wrapper = mount(AppSidebar, {
      global: { plugins: [router, createPinia()] },
    });
    await flushPromises();

    const masterDataBtn = wrapper.findAll('button.nav-link--submenu').find(b => b.text().includes('Master Data'));
    const dataBedBtn = wrapper.findAll('button.submenu-link--group').find(b => b.text().includes('Data Bed'));

    expect(masterDataBtn.attributes('aria-expanded')).toBe('true');
    expect(dataBedBtn.attributes('aria-expanded')).toBe('true');

    const kelasLink = wrapper.findAll('.nested-submenu-link').find(l => l.text().trim() === 'Kelas');
    expect(kelasLink.classes()).toContain('nested-submenu-link--active');
  });
});


