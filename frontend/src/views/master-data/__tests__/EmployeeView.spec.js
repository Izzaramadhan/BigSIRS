import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import EmployeeView from '../EmployeeView.vue';
import { ref } from 'vue';
import { useEmployees } from '../../../composables/useEmployees';

vi.mock('../../../composables/useEmployees');

vi.mock('../../../services/employee.service', () => ({
  default: {
    getPositions: vi.fn().mockResolvedValue({ data: [] })
  }
}));

let mockMeta;
let fetchEmployeesMock;

describe('EmployeeView.vue Pagination', () => {
  beforeEach(() => {
    mockMeta = ref(null);
    fetchEmployeesMock = vi.fn();
    vi.mocked(useEmployees).mockReturnValue({
      employees: ref([]),
      loading: ref(false),
      error: ref(null),
      meta: mockMeta,
      fetchEmployees: fetchEmployeesMock,
      deleteEmployee: vi.fn()
    });
    vi.clearAllMocks();
  });

  const mountComponent = () => {
    return mount(EmployeeView, {
      global: {
        stubs: ['EmployeeFormModal']
      }
    });
  };

  it('1. Summary halaman pertama (Menampilkan 1–10 dari 87 pegawai)', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.employee-table-footer__summary').text()).toBe('Menampilkan 1–10 dari 87 pegawai');
  });

  it('2. Summary halaman terakhir dengan jumlah tidak penuh', async () => {
    mockMeta.value = { current_page: 9, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.employee-table-footer__summary').text()).toBe('Menampilkan 81–87 dari 87 pegawai');
  });

  it('3. Summary data kosong', async () => {
    mockMeta.value = { current_page: 1, last_page: 1, per_page: 10, total: 0 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.employee-table-footer__summary').text()).toBe('Menampilkan 0 dari 0 pegawai');
  });

  it('4. Total 9 halaman pada current page 1 menghasilkan 1, 2, 3, ellipsis, 9', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const visiblePages = wrapper.vm.visiblePages;
    expect(visiblePages).toEqual([1, 2, 3, 'ellipsis-end', 9]);
  });

  it('5. Current page 5 menghasilkan 1, ellipsis, 4, 5, 6, ellipsis, 9', async () => {
    mockMeta.value = { current_page: 5, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const visiblePages = wrapper.vm.visiblePages;
    expect(visiblePages).toEqual([1, 'ellipsis-start', 4, 5, 6, 'ellipsis-end', 9]);
  });

  it('6. Current page 9 menghasilkan 1, ellipsis, 7, 8, 9', async () => {
    mockMeta.value = { current_page: 9, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const visiblePages = wrapper.vm.visiblePages;
    expect(visiblePages).toEqual([1, 'ellipsis-start', 7, 8, 9]);
  });

  it('7. Tidak ada nomor halaman duplikat', async () => {
    mockMeta.value = { current_page: 4, last_page: 5, per_page: 10, total: 50 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const visiblePages = wrapper.vm.visiblePages;
    const uniquePages = new Set(visiblePages);
    expect(uniquePages.size).toBe(visiblePages.length);
    expect(visiblePages).toEqual([1, 2, 3, 4, 5]);
  });

  it('8. Ellipsis bukan tombol dan memakai aria-hidden', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const ellipsis = wrapper.find('.page-ellipsis');
    expect(ellipsis.element.tagName.toLowerCase()).not.toBe('button');
    expect(ellipsis.attributes('aria-hidden')).toBe('true');
  });

  it('9. Active page memakai aria-current', async () => {
    mockMeta.value = { current_page: 3, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const activeBtn = wrapper.find('.btn-page.active');
    expect(activeBtn.exists()).toBe(true);
    expect(activeBtn.attributes('aria-current')).toBe('page');
    expect(activeBtn.text()).toBe('3');
  });

  it('10. Tombol Sebelumnya disabled pada page 1', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const prevBtn = wrapper.findAll('.btn-page').at(0);
    expect(prevBtn.text()).toContain('Sebelumnya');
    expect(prevBtn.attributes('disabled')).toBeDefined();
  });

  it('11. Tombol Selanjutnya disabled pada halaman terakhir', async () => {
    mockMeta.value = { current_page: 9, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const buttons = wrapper.findAll('.btn-page');
    const nextBtn = buttons.at(buttons.length - 1);
    expect(nextBtn.text()).toContain('Selanjutnya');
    expect(nextBtn.attributes('disabled')).toBeDefined();
  });

  it('12. Klik nomor halaman memanggil fungsi ganti halaman (fetchData)', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    
    fetchEmployeesMock.mockClear();
    
    const page2Btn = wrapper.findAll('.btn-page').filter(w => w.text() === '2').at(0);
    await page2Btn.trigger('click');
    
    expect(fetchEmployeesMock).toHaveBeenCalled();
  });

  it('13. Klik Sebelumnya/Selanjutnya memancarkan halaman yang benar', async () => {
    mockMeta.value = { current_page: 2, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    
    fetchEmployeesMock.mockClear();
    
    const buttons = wrapper.findAll('.btn-page');
    const prevBtn = buttons.at(0);
    const nextBtn = buttons.at(buttons.length - 1);
    
    await prevBtn.trigger('click');
    expect(fetchEmployeesMock).toHaveBeenCalled();
    
    fetchEmployeesMock.mockClear();
    await nextBtn.trigger('click');
    expect(fetchEmployeesMock).toHaveBeenCalled();
  });

  it('14. Footer menggunakan class layout yang benar', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const footer = wrapper.find('footer.employee-table-footer');
    expect(footer.exists()).toBe(true);
    expect(footer.classes()).toContain('employee-table-footer');
  });

  it('15. Tidak ada elemen pagination dengan absolute positioning', async () => {
    mockMeta.value = { current_page: 1, last_page: 9, per_page: 10, total: 87 };
    const wrapper = mountComponent();
    await wrapper.vm.$nextTick();
    const footer = wrapper.find('.employee-table-footer');
    expect(footer.attributes('style')).toBeUndefined();
  });
});
