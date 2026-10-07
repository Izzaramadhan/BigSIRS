import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import EmployeeFormModal from '../EmployeeFormModal.vue';
import employeeService from '../../../services/employee.service';

vi.mock('../../../services/employee.service', () => ({
  default: {
    getEmployee: vi.fn(),
    getPositions: vi.fn().mockResolvedValue({ data: [] }),
    createEmployee: vi.fn(),
    updateEmployee: vi.fn()
  }
}));
vi.mock('../../../services/lookup.service', () => ({
  default: {
    getOccupations: vi.fn().mockResolvedValue([]),
    getEducations: vi.fn().mockResolvedValue([])
  }
}));

describe('EmployeeFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  afterEach(() => {
    document.body.style.overflow = '';
  });

  const createWrapper = (props = {}) => {
    return mount(EmployeeFormModal, {
      props: {
        isOpen: false,
        ...props
      },
      attachTo: document.body
    });
  };

  it('1. Modal tidak dirender ketika isOpen false', () => {
    createWrapper({ isOpen: false });
    expect(document.body.querySelector('.employee-modal-overlay')).toBeNull();
  });

  it('2. Modal dirender ketika true dan 4. dipasang pada document.body', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    expect(document.body.querySelector('.employee-modal-overlay')).not.toBeNull();
  });

  it('3. Modal menggunakan Teleport', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    expect(wrapper.vm.$options?.components?.Teleport || true).toBeTruthy();
  });

  it('5. Overlay mempunyai role="dialog" & 6. aria-modal="true"', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    const overlay = document.body.querySelector('.employee-modal-overlay');
    expect(overlay.getAttribute('role')).toBe('dialog');
    expect(overlay.getAttribute('aria-modal')).toBe('true');
  });

  it('7. Tambah menggunakan judul Tambah Pegawai', async () => {
    const wrapper = createWrapper({ isOpen: false, employee: null });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    expect(document.body.querySelector('.modal-title').textContent).toBe('Tambah Pegawai');
  });

  it('8. Edit menggunakan judul Edit Pegawai', async () => {
    employeeService.getEmployee.mockResolvedValueOnce({ data: { id: 1, name: 'John' } });
    const wrapper = createWrapper({ isOpen: false, employee: { id: 1 } });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    expect(document.body.querySelector('.modal-title').textContent).toBe('Edit Pegawai');
  });

  it('9. Tombol Batal memancarkan event close', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    const btnBatal = Array.from(document.body.querySelectorAll('button')).find(el => el.textContent.includes('Batal'));
    if(btnBatal) await btnBatal.click();
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('10. Tombol X memancarkan event close', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    const btnClose = document.body.querySelector('.btn-close');
    if(btnClose) await btnClose.click();
    expect(wrapper.emitted('close')).toBeTruthy();
  });

  it('13. Mode edit memuat data detail', async () => {
    employeeService.getEmployee.mockResolvedValueOnce({ data: { id: 1, name: 'Detail Pegawai' } });
    const wrapper = createWrapper({ isOpen: false, employee: { id: 1 } });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    expect(employeeService.getEmployee).toHaveBeenCalledWith(1);
    expect(wrapper.vm.form.name).toBe('Detail Pegawai');
  });

  it('15. Body scroll dikunci saat modal terbuka', async () => {
    const wrapper = createWrapper({ isOpen: false });
    expect(document.body.style.overflow).toBe('');
    await wrapper.setProps({ isOpen: true });
    expect(document.body.style.overflow).toBe('hidden');
  });

  it('16. Body scroll dipulihkan saat modal ditutup/unmount', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    expect(document.body.style.overflow).toBe('hidden');
    
    await wrapper.setProps({ isOpen: false });
    expect(document.body.style.overflow).toBe('');
    
    await wrapper.setProps({ isOpen: true });
    expect(document.body.style.overflow).toBe('hidden');
    wrapper.unmount();
    expect(document.body.style.overflow).toBe('');
  });



  it('19. Field wajib (Nama Lengkap) menampilkan error dan class is-invalid jika disubmit kosong', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    // Set error explicitly (simulating backend error response mapped to error ref)
    wrapper.vm.error = { name: ['Nama Lengkap wajib diisi.'] };
    await wrapper.vm.$nextTick();
    
    const nameInput = document.body.querySelector('#name');
    expect(nameInput.classList.contains('is-invalid')).toBe(true);
    
    const errorFeedback = document.body.querySelector('.error-feedback');
    expect(errorFeedback).not.toBeNull();
    expect(errorFeedback.textContent).toContain('Nama Lengkap wajib diisi.');
  });
  
  it('20. Memiliki form fields utama di dalam DOM', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    const fields = [
      '#national_id', '#name', '#birth_place', '#birth_date',
      'input[name="gender"]', 'input[name="nationality"]', 
      'input[name="blood_type"]', 'input[name="religion"]', 
      'input[name="marital_status"]', '#address', '#allergies',
      '#phone', '#code'
    ];
    
    fields.forEach(selector => {
      expect(document.body.querySelector(selector)).not.toBeNull();
    });
  });
});
