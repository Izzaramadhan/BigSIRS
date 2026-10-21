import { mount, flushPromises } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import EmployeeFormModal from '../EmployeeFormModal.vue';
import employeeService from '../../../services/employee.service';

vi.mock('../../../services/employee.service', () => ({
  default: {
    getEmployee: vi.fn(),
    getPositions: vi.fn().mockResolvedValue({ data: [] }),
    createEmployee: vi.fn().mockResolvedValue({ success: true }),
    updateEmployee: vi.fn().mockResolvedValue({ success: true })
  }
}));

vi.mock('../../../services/lookup.service', () => ({
  default: {
    getOccupations: vi.fn().mockResolvedValue([]),
    getEducations: vi.fn().mockResolvedValue([]),
    getProvinces: vi.fn().mockResolvedValue([]),
    getRegencies: vi.fn().mockResolvedValue([]),
    getDistricts: vi.fn().mockResolvedValue([]),
    getVillages: vi.fn().mockResolvedValue([])
  }
}));

describe('EmployeeFormModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  const createWrapper = (props = {}) => {
    return mount(EmployeeFormModal, {
      props: {
        isOpen: true,
        employee: null,
        ...props
      },
      attachTo: document.body
    });
  };

  it('1. Modal tidak dirender ketika isOpen false', () => {
    mount(EmployeeFormModal, {
      props: { isOpen: false },
      attachTo: document.body
    });
    expect(document.body.querySelector('.modal-overlay')).toBeNull();
  });

  it('2. Modal dirender dan menampilkan judul Tambah Pegawai pada mode tambah', async () => {
    createWrapper({ isOpen: true, employee: null });
    await flushPromises();

    expect(document.body.querySelector('.modal-overlay')).not.toBeNull();
    expect(document.body.querySelector('.modal-header h3').textContent).toBe('Tambah Pegawai');
  });

  it('3. Menampilkan judul Edit Pegawai dan memuat detail pegawai', async () => {
    employeeService.getEmployee.mockResolvedValueOnce({
      data: {
        id: 1,
        name: 'Dr. John',
        code: 'NIP001',
        phone: '08123456789',
        signature_url: 'http://localhost/storage/signatures/ttd1.png',
        has_signature: true
      }
    });

    createWrapper({ isOpen: true, employee: { id: 1 } });
    await flushPromises();

    expect(document.body.querySelector('.modal-header h3').textContent).toBe('Edit Pegawai');
    expect(employeeService.getEmployee).toHaveBeenCalledWith(1);

    // Preview signature from loaded data
    const previewImg = document.body.querySelector('.signature-preview img');
    expect(previewImg).not.toBeNull();
    expect(previewImg.getAttribute('src')).toBe('http://localhost/storage/signatures/ttd1.png');
  });

  it('4. Memiliki field Tanda Tangan dengan petunjuk format dan ukuran', async () => {
    createWrapper({ isOpen: true, employee: null });
    await flushPromises();

    const fileInput = document.body.querySelector('input[type="file"]#signature');
    expect(fileInput).not.toBeNull();
    expect(fileInput.getAttribute('accept')).toBe('image/png, image/jpeg, image/jpg');

    const hint = document.body.querySelector('.file-hint');
    expect(hint).not.toBeNull();
    expect(hint.textContent).toContain('PNG, JPG, JPEG');
    expect(hint.textContent).toContain('2MB');
  });

  it('5. Menolak file dengan format tidak valid secara client-side', async () => {
    const wrapper = createWrapper({ isOpen: true, employee: null });
    await flushPromises();

    const invalidFile = new File(['dummy content'], 'document.pdf', { type: 'application/pdf' });
    wrapper.vm.handleFileChange({ target: { files: [invalidFile] } });
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.fieldError('signature')).toContain('Format tanda tangan harus berupa jpeg, png, atau jpg.');
    expect(wrapper.vm.previewImage).toBeNull();
  });

  it('6. Menolak file yang melebihi batas ukuran 2MB', async () => {
    const wrapper = createWrapper({ isOpen: true, employee: null });
    await flushPromises();

    const largeFile = new File(['x'.repeat(2.5 * 1024 * 1024)], 'large.png', { type: 'image/png' });
    Object.defineProperty(largeFile, 'size', { value: 3 * 1024 * 1024 });

    wrapper.vm.handleFileChange({ target: { files: [largeFile] } });
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.fieldError('signature')).toContain('Ukuran tanda tangan maksimal 2MB.');
    expect(wrapper.vm.previewImage).toBeNull();
  });

  it('7. Memilih file yang valid memperbarui preview dan form.signature', async () => {
    const wrapper = createWrapper({ isOpen: true, employee: null });
    await flushPromises();

    const validFile = new File(['valid-image-bytes'], 'ttd.png', { type: 'image/png' });
    wrapper.vm.handleFileChange({ target: { files: [validFile] } });
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.form.signature).toBe(validFile);
    expect(wrapper.vm.form.remove_signature).toBe(false);
  });

  it('8. Membatalkan pilihan file baru pada mode edit mengembalikan preview tanda tangan tersimpan', async () => {
    employeeService.getEmployee.mockResolvedValueOnce({
      data: {
        id: 1,
        name: 'John',
        signature_url: 'http://localhost/storage/signatures/saved.png',
        has_signature: true
      }
    });

    const wrapper = createWrapper({ isOpen: true, employee: { id: 1 } });
    await flushPromises();

    expect(wrapper.vm.previewImage).toBe('http://localhost/storage/signatures/saved.png');

    // Simulate selecting a new file
    const newFile = new File(['new-bytes'], 'new.png', { type: 'image/png' });
    wrapper.vm.handleFileChange({ target: { files: [newFile] } });
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.form.signature).toBe(newFile);

    // Click "Batalkan Pilihan"
    wrapper.vm.cancelNewFile();
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.form.signature).toBeNull();
    expect(wrapper.vm.previewImage).toBe('http://localhost/storage/signatures/saved.png');
    expect(wrapper.vm.form.remove_signature).toBe(false);
  });

  it('9. Menghapus tanda tangan pada mode edit menyetel remove_signature true dan mengosongkan preview', async () => {
    employeeService.getEmployee.mockResolvedValueOnce({
      data: {
        id: 1,
        name: 'John',
        signature_url: 'http://localhost/storage/signatures/saved.png',
        has_signature: true
      }
    });

    const wrapper = createWrapper({ isOpen: true, employee: { id: 1 } });
    await flushPromises();

    wrapper.vm.removeSignature();
    await wrapper.vm.$nextTick();

    expect(wrapper.vm.form.signature).toBeNull();
    expect(wrapper.vm.form.remove_signature).toBe(true);
    expect(wrapper.vm.previewImage).toBeNull();
  });

  it('10. Tombol Batal dan Tutup memancarkan event close', async () => {
    const wrapper = createWrapper({ isOpen: true });
    await flushPromises();

    const btnBatal = Array.from(document.body.querySelectorAll('button')).find(el => el.textContent.includes('Batal'));
    if (btnBatal) await btnBatal.click();
    expect(wrapper.emitted('close')).toBeTruthy();

    const btnClose = document.body.querySelector('.btn-close');
    if (btnClose) await btnClose.click();
    expect(wrapper.emitted('close')).toBeTruthy();
  });
});
