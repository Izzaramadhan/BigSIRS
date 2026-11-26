import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import NewPatientView from '../NewPatientView.vue';
import lookupService from '@/services/lookup.service';
import { occupationsService } from '@/services/master-data/occupations.service';
import patientService from '@/services/patient.service';

// Mock vue-router
const mockPush = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRouter: () => ({
      push: mockPush
    }),
    useRoute: () => ({
      query: {},
      params: {}
    })
  };
});

// Mock services
vi.mock('@/services/lookup.service', () => ({
  default: {
    getEducations: vi.fn(),
    getOccupations: vi.fn(),
    getVillages: vi.fn(),
    getGuarantors: vi.fn()
  }
}));

vi.mock('@/services/master-data/occupations.service', () => ({
  occupationsService: {
    getOccupations: vi.fn()
  }
}));

vi.mock('@/services/patient.service', () => ({
  default: {
    createPatient: vi.fn()
  }
}));

const defaultGuarantors = [
  { id: 2, code: 'UMM', name: 'UMUM', type: 'self_pay', is_active: true },
  { id: 3, code: 'BPJS', name: 'BPJS Kesehatan', type: 'bpjs', is_active: true },
  { id: 7, code: 'CORP-1', name: 'Jaminan PT Maju Sejahtera', type: 'private_insurance', is_active: true }
];

describe('NewPatientView', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    lookupService.getEducations.mockResolvedValue([
      { id: 1, name: 'SD' },
      { id: 2, name: 'SMP' },
      { id: 3, name: 'SMA / SMK' },
      { id: 4, name: 'S1 / Sarjana' }
    ]);
    lookupService.getOccupations.mockResolvedValue([
      { id: 10, name: 'PNS / ASN' },
      { id: 11, name: 'Karyawan Swasta' },
      { id: 12, name: 'Wiraswasta' }
    ]);
    occupationsService.getOccupations.mockResolvedValue({
      data: [
        { id: 10, name: 'PNS / ASN' },
        { id: 11, name: 'Karyawan Swasta' },
        { id: 12, name: 'Wiraswasta' }
      ]
    });
    lookupService.getGuarantors.mockResolvedValue(defaultGuarantors);
  });

  it('renders FORM TAMBAH card header and all key fields from reference image', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    // Check title badge
    expect(wrapper.text()).toContain('FORM TAMBAH');

    // Check Left Column fields
    expect(wrapper.find('input#nik').exists()).toBe(true);
    expect(wrapper.find('input#ihs').exists()).toBe(true);
    expect(wrapper.find('input#name').exists()).toBe(true);
    expect(wrapper.find('input#birth_place').exists()).toBe(true);
    expect(wrapper.find('input#birth_date').exists()).toBe(true);
    expect(wrapper.find('textarea#address').exists()).toBe(true);
    expect(wrapper.find('input#wilayah').exists()).toBe(true);
    expect(wrapper.find('input#postal_code').exists()).toBe(true);
    expect(wrapper.find('input#phone').exists()).toBe(true);
    expect(wrapper.find('select#education').exists()).toBe(true);

    // Check Right Column fields
    expect(wrapper.find('select#occupation').exists()).toBe(true);
    expect(wrapper.find('select#guarantor').exists()).toBe(true);
    expect(wrapper.find('input#insurance_number').exists()).toBe(true);
    expect(wrapper.find('.emergency-contact-box').exists()).toBe(true);

    // Check Action buttons
    expect(wrapper.find('.btn-batal').exists()).toBe(true);
    expect(wrapper.find('.btn-simpan').exists()).toBe(true);
    expect(wrapper.find('.category-select').exists()).toBe(true);

    // Master Data guarantors rendered dynamically
    const optionTexts = wrapper.findAll('select#guarantor option').map((o) => o.text().trim());
    expect(optionTexts).toContain('Jaminan PT Maju Sejahtera');
    expect(lookupService.getGuarantors).toHaveBeenCalled();
  });

  it('disables insurance_number for self_pay guarantor and handles API error + retry cleanly', async () => {
    lookupService.getGuarantors
      .mockRejectedValueOnce(new Error('Network Error'))
      .mockResolvedValueOnce(defaultGuarantors);

    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Gagal memuat data penjamin');
    await wrapper.find('.btn-retry-guarantor').trigger('click');
    await flushPromises();

    expect(wrapper.text()).not.toContain('Gagal memuat data penjamin');

    // Selecting self_pay disables insurance_number
    await wrapper.find('select#guarantor').setValue('2');
    expect(wrapper.find('input#insurance_number').element.disabled).toBe(true);

    // Selecting corporate guarantor enables insurance_number
    await wrapper.find('select#guarantor').setValue('7');
    expect(wrapper.find('input#insurance_number').element.disabled).toBe(false);
  });

  it('shows empty state hint when no active master guarantors exist', async () => {
    lookupService.getGuarantors.mockResolvedValue([]);
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Belum ada data penjamin aktif di Master Data.');
  });

  it('populates master data dropdowns for pendidikan, pekerjaan, and jenis jaminan', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    const eduOptions = wrapper.findAll('select#education option');
    expect(eduOptions.length).toBeGreaterThan(1);
    expect(eduOptions[1].text()).toBe('SD');

    const occOptions = wrapper.findAll('select#occupation option');
    expect(occOptions.length).toBeGreaterThan(1);
    expect(occOptions[1].text()).toBe('Karyawan Swasta');
    expect(occOptions[2].text()).toBe('PNS / ASN');

    const guarOptions = wrapper.findAll('select#guarantor option');
    expect(guarOptions.length).toBeGreaterThan(1);
    expect(guarOptions[1].text()).toBe('UMUM');
  });

  it('calculates age automatically when birth_date is entered', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    const birthDateInput = wrapper.find('input#birth_date');
    await birthDateInput.setValue('2000-01-01');

    expect(wrapper.find('.age-badge').exists()).toBe(true);
    expect(wrapper.find('.age-badge').text()).toContain('Thn');
  });

  it('shows error messages when submitting empty required fields', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.findAll('.error-message').length).toBeGreaterThan(0);
    expect(wrapper.text()).toContain('NIK wajib diisi 16 digit');
  });

  it('handles wilayah search and selection correctly', async () => {
    lookupService.getVillages.mockResolvedValue([
      {
        id: '3404070001',
        code: '3404070001',
        name: 'Condongcatur',
        district_id: '3404070',
        district_name: 'Depok',
        regency_id: '3404',
        regency_name: 'Sleman',
        province_id: '34',
        province_name: 'D.I. Yogyakarta'
      }
    ]);

    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    const wilayahInput = wrapper.find('input#wilayah');
    await wilayahInput.setValue('Condong');
    await wilayahInput.trigger('input');

    // Fast-forward debounce timer
    await new Promise((r) => setTimeout(r, 300));
    await flushPromises();

    expect(lookupService.getVillages).toHaveBeenCalledWith({ search: 'Condong' });

    // Click result item
    const row = wrapper.find('.wilayah-row');
    expect(row.exists()).toBe(true);
    await row.trigger('click');

    expect(wrapper.find('input#wilayah').element.value).toContain('Condongcatur');
    expect(wrapper.find('input#postal_code').element.value).toBe('70001');
  });

  it('successfully submits valid registration data to patientService', async () => {
    patientService.createPatient.mockResolvedValue({
      data: {
        id: 999,
        medical_record_number: '000176',
        name: 'Budi Santoso',
        nik: '3404011205900001',
        gender: 'L',
        guarantor: 'UMUM',
        address: 'Jl. Kaliurang KM 5'
      }
    });

    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });
    await flushPromises();

    // Fill required fields
    await wrapper.find('input#nik').setValue('3404011205900001');
    await wrapper.find('input#name').setValue('Budi Santoso');
    await wrapper.find('input#birth_place').setValue('Sleman');
    await wrapper.find('input#birth_date').setValue('1990-05-12');
    await wrapper.find('textarea#address').setValue('Jl. Kaliurang KM 5');

    // Simulate wilayah selection
    lookupService.getVillages.mockResolvedValue([
      {
        id: '123',
        code: '55281',
        name: 'Sinduadi',
        district_id: '10',
        district_name: 'Mlati',
        regency_id: '20',
        regency_name: 'Sleman',
        province_id: '30',
        province_name: 'DIY'
      }
    ]);
    const wilayahInput = wrapper.find('input#wilayah');
    await wilayahInput.setValue('Sinduadi');
    await wilayahInput.trigger('input');
    await new Promise((r) => setTimeout(r, 300));
    await flushPromises();
    await wrapper.find('.wilayah-row').trigger('click');

    await wrapper.find('input#phone').setValue('08123456789');
    await wrapper.find('select#guarantor').setValue(2); // UMUM

    // Submit form
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(patientService.createPatient).toHaveBeenCalled();
    const payload = patientService.createPatient.mock.calls[0][0];
    expect(payload.nik).toBe('3404011205900001');
    expect(payload.name).toBe('Budi Santoso');
    expect(payload.guarantor_id).toBe(2);
    expect(payload.village_id).toBe('123');
    expect(payload.district_id).toBe('10');
    expect(payload.regency_id).toBe('20');
    expect(payload.province_id).toBe('30');

    // Success modal appears
    expect(wrapper.find('.modal-overlay').exists()).toBe(true);
    expect(wrapper.text()).toContain('000176');
  });

  it('selects Sendang Mulyo and submits full hierarchy without province error', async () => {
    patientService.createPatient.mockResolvedValue({
      data: {
        id: 1001,
        medical_record_number: '000177',
        name: 'Pasien Sendang Mulyo',
        nik: '3404021205980001'
      }
    });

    lookupService.getVillages.mockResolvedValue([
      {
        id: '39774',
        code: '3404020002',
        name: 'SENDANG MULYO',
        district_id: '3404020',
        district_name: 'MINGGIR',
        regency_id: '3404',
        regency_name: 'KABUPATEN SLEMAN',
        province_id: '34',
        province_name: 'DI YOGYAKARTA',
        postal_code: '55281'
      }
    ]);

    const wrapper = mount(NewPatientView, {
      global: {
        stubs: { MasterDataPageHeader: true, AppToast: true }
      }
    });
    await flushPromises();

    // Fill form
    await wrapper.find('input#nik').setValue('3404021205980001');
    await wrapper.find('input#name').setValue('Pasien Sendang Mulyo');
    await wrapper.find('input#birth_place').setValue('Sleman');
    await wrapper.find('input#birth_date').setValue('1998-05-12');
    await wrapper.find('textarea#address').setValue('Jl. Minggir Sendang Mulyo');

    // Search and pick Sendang Mulyo
    const wilayahInput = wrapper.find('input#wilayah');
    await wilayahInput.setValue('SENDANG MULYO');
    await wilayahInput.trigger('input');
    await new Promise((r) => setTimeout(r, 300));
    await flushPromises();

    await wrapper.find('.wilayah-row').trigger('click');
    await flushPromises();

    expect(wrapper.find('input#wilayah').element.value).toContain('SENDANG MULYO — Kec. MINGGIR — KABUPATEN SLEMAN — DI YOGYAKARTA');
    expect(wrapper.find('input#postal_code').element.value).toBe('55281');

    await wrapper.find('input#phone').setValue('081234567899');
    await wrapper.find('select#guarantor').setValue(2);

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(patientService.createPatient).toHaveBeenCalled();
    const payload = patientService.createPatient.mock.calls[0][0];
    expect(payload.village_id).toBe('39774');
    expect(payload.district_id).toBe('3404020');
    expect(payload.regency_id).toBe('3404');
    expect(payload.province_id).toBe('34');
  });

  it('rejects manual text input in Wilayah without choosing from autocomplete', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: { MasterDataPageHeader: true, AppToast: true }
      }
    });
    await flushPromises();

    await wrapper.find('input#nik').setValue('3404021205980001');
    await wrapper.find('input#name').setValue('Test Manual Wilayah');
    await wrapper.find('input#birth_place').setValue('Sleman');
    await wrapper.find('input#birth_date').setValue('1998-05-12');
    await wrapper.find('textarea#address').setValue('Jl. Asal');
    await wrapper.find('input#phone').setValue('081234567899');
    await wrapper.find('select#guarantor').setValue(2);

    // Type manual text without clicking any suggestion
    const wilayahInput = wrapper.find('input#wilayah');
    await wilayahInput.setValue('Ketik Manual Saja');
    await wilayahInput.trigger('input');

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(patientService.createPatient).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('Data wilayah belum lengkap atau tidak sesuai');
  });

  it('resets region hierarchy when user alters text or clicks clear and selects another region', async () => {
    lookupService.getVillages.mockResolvedValueOnce([
      {
        id: '101',
        name: 'Wilayah A',
        district_id: '11',
        district_name: 'Kec A',
        regency_id: '12',
        regency_name: 'Kab A',
        province_id: '13',
        province_name: 'Prov A'
      }
    ]);

    const wrapper = mount(NewPatientView, {
      global: {
        stubs: { MasterDataPageHeader: true, AppToast: true }
      }
    });
    await flushPromises();

    // Select Wilayah A
    const wilayahInput = wrapper.find('input#wilayah');
    await wilayahInput.setValue('Wilayah A');
    await wilayahInput.trigger('input');
    await new Promise((r) => setTimeout(r, 300));
    await flushPromises();
    await wrapper.find('.wilayah-row').trigger('click');
    await flushPromises();

    // Click clear button
    await wrapper.find('button.btn-clear-inline').trigger('click');
    expect(wilayahInput.element.value).toBe('');

    // Select Wilayah B
    lookupService.getVillages.mockResolvedValueOnce([
      {
        id: '202',
        name: 'Wilayah B',
        district_id: '21',
        district_name: 'Kec B',
        regency_id: '22',
        regency_name: 'Kab B',
        province_id: '23',
        province_name: 'Prov B'
      }
    ]);
    await wilayahInput.setValue('Wilayah B');
    await wilayahInput.trigger('input');
    await new Promise((r) => setTimeout(r, 300));
    await flushPromises();
    await wrapper.find('.wilayah-row').trigger('click');
    await flushPromises();

    // Fill other fields and submit
    await wrapper.find('input#nik').setValue('3404021205980001');
    await wrapper.find('input#name').setValue('Test Ganti Wilayah');
    await wrapper.find('input#birth_place').setValue('Sleman');
    await wrapper.find('input#birth_date').setValue('1998-05-12');
    await wrapper.find('textarea#address').setValue('Jl. Baru');
    await wrapper.find('input#phone').setValue('081234567899');
    await wrapper.find('select#guarantor').setValue(2);

    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();

    expect(patientService.createPatient).toHaveBeenCalled();
    const payload = patientService.createPatient.mock.calls[0][0];
    expect(payload.village_id).toBe('202');
    expect(payload.district_id).toBe('21');
    expect(payload.regency_id).toBe('22');
    expect(payload.province_id).toBe('23');
  });

  it('renders Hak Kelas and Cek button when non-Umum guarantor is selected and validates card checking', async () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Select BPJS guarantor (id: 3)
    const guarantorSelect = wrapper.find('select#guarantor');
    await guarantorSelect.setValue(3);
    await flushPromises();

    // Check that Cek button and Hak Kelas field appear
    const cekBtn = wrapper.find('button.btn-action-cek');
    expect(cekBtn.exists()).toBe(true);
    const hakKelasInput = wrapper.find('input#hak_kelas');
    expect(hakKelasInput.exists()).toBe(true);
    expect(wrapper.text()).toContain('*) Klik tombol Cek untuk mengisi hak kelas');

    // Click Cek with invalid/empty card
    await cekBtn.trigger('click');
    expect(wrapper.text()).toContain('No.Kartu Tidak Sesuai');

    // Enter valid card number and click Cek
    const insuranceInput = wrapper.find('input#insurance_number');
    await insuranceInput.setValue('000206418255');
    await cekBtn.trigger('click');

    expect(wrapper.text()).not.toContain('No.Kartu Tidak Sesuai');
    expect(hakKelasInput.element.value).toBe('Kelas 1');
  });
});
