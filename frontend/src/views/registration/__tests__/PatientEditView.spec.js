import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import PatientEditView from '../PatientEditView.vue';
import patientService from '@/services/patient.service';
import lookupService from '@/services/lookup.service';
import { guarantorsService } from '@/services/master-data/guarantors.service';
import { occupationsService } from '@/services/master-data/occupations.service';

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: '777' },
    query: {}
  }),
  useRouter: () => ({
    push: mockPush
  })
}));

vi.mock('@/services/patient.service', () => ({
  default: {
    getPatient: vi.fn(),
    updatePatient: vi.fn(),
  },
  patientService: {
    getPatient: vi.fn(),
    updatePatient: vi.fn(),
  }
}));

vi.mock('@/services/lookup.service', () => ({
  default: {
    getProvinces: vi.fn(),
    getRegencies: vi.fn(),
    getDistricts: vi.fn(),
    getVillages: vi.fn(),
    getEducations: vi.fn(),
    getOccupations: vi.fn(),
  }
}));

vi.mock('@/services/master-data/guarantors.service', () => ({
  guarantorsService: {
    getGuarantors: vi.fn(),
  }
}));

vi.mock('@/services/master-data/occupations.service', () => ({
  occupationsService: {
    getOccupations: vi.fn(),
  }
}));

describe('PatientEditView.vue', () => {
  const mockPatientData = {
    id: 777,
    medical_record_number: '000352',
    nik: '3402164610910006',
    ihs_number: 'P01766951443',
    name: 'AYU PUSPITASARI SULISTYO',
    birth_place: 'Lampung',
    birth_date: '1991-10-06',
    gender: 'P',
    nationality: 'WNI',
    address: 'Lampung',
    postal_code: '35141',
    phone: '085353432343',
    region: {
      province: { id: '12', name: 'SUMATERA UTARA' },
      regency: { id: '1202', name: 'KABUPATEN MANDAILING NATAL' },
      district: { id: '120205', name: 'BATANG NATAL' },
      village: { id: '1202052001', name: 'MUARA PARLAMPUNGAN' },
    },
    education: { id: 7, name: 'S1' },
    occupation: { id: 11, name: 'Karyawan Swasta' },
    guarantor: { id: 2, name: 'UMUM', type: 'UMUM' },
    insurance_number: null,
    allergies: { status: 'tidak', notes: null },
    fast_track: false,
    blood_type: '-',
    religion: 'ISLAM',
    marital_status: 'BELUM KAWIN',
    emergency_contact: {
      name: 'Budi Santoso',
      relationship: 'Suami',
      phone: '081234567890',
      address: 'Lampung'
    }
  };

  beforeEach(() => {
    vi.clearAllMocks();
    mockPush.mockReset();

    lookupService.getProvinces.mockResolvedValue([
      { id: '12', name: 'SUMATERA UTARA' },
      { id: '33', name: 'JAWA TENGAH' }
    ]);
    lookupService.getRegencies.mockResolvedValue([
      { id: '1202', name: 'KABUPATEN MANDAILING NATAL' }
    ]);
    lookupService.getDistricts.mockResolvedValue([
      { id: '120205', name: 'BATANG NATAL' }
    ]);
    lookupService.getVillages.mockResolvedValue([
      { id: '1202052001', name: 'MUARA PARLAMPUNGAN', postal_code: '22983' }
    ]);
    lookupService.getEducations.mockResolvedValue([
      { id: 7, name: 'S1' }
    ]);
    lookupService.getOccupations.mockResolvedValue([
      { id: 11, name: 'Karyawan Swasta' }
    ]);
    occupationsService.getOccupations.mockResolvedValue({
      data: [
        { id: 11, name: 'Karyawan Swasta' }
      ]
    });
    guarantorsService.getGuarantors.mockResolvedValue([
      { id: 2, name: 'UMUM' },
      { id: 3, name: 'BPJS KESEHATAN' }
    ]);

    patientService.getPatient.mockResolvedValue({
      data: { data: mockPatientData }
    });
    patientService.updatePatient.mockResolvedValue({
      data: { success: true }
    });
  });

  it('renders FORM EDIT card and loads patient demographics matching media_1794975712652_0db20492.png', async () => {
    const wrapper = mount(PatientEditView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Verify title and card
    expect(wrapper.text()).toContain('FORM EDIT');

    // Verify form fields populated
    const nikInput = wrapper.find('#edit-nik');
    expect(nikInput.exists()).toBe(true);
    expect(nikInput.element.value).toBe('3402164610910006');

    const ihsInput = wrapper.find('#edit-ihs');
    expect(ihsInput.element.value).toBe('P01766951443');

    const nameInput = wrapper.find('#edit-name');
    expect(nameInput.element.value).toBe('AYU PUSPITASARI SULISTYO');

    const birthPlaceInput = wrapper.find('#edit-birth-place');
    expect(birthPlaceInput.element.value).toBe('Lampung');

    const birthDateInput = wrapper.find('#edit-birth-date');
    expect(birthDateInput.element.value).toBe('1991-10-06');

    // Gender radio: PEREMPUAN (P)
    const genderRadios = wrapper.findAll('input[name="edit_gender"]');
    expect(genderRadios[1].element.checked).toBe(true);

    // Nationality: WNI
    const natRadios = wrapper.findAll('input[name="edit_nationality"]');
    expect(natRadios[0].element.checked).toBe(true);

    // Address
    const addressInput = wrapper.find('#edit-address');
    expect(addressInput.element.value).toBe('Lampung');

    // Regions
    const provSelect = wrapper.find('#edit-province');
    expect(provSelect.element.value).toBe('12');

    const regSelect = wrapper.find('#edit-regency');
    expect(regSelect.element.value).toBe('1202');

    // Right Column: Guarantor UMUM should disable No Jaminan
    const insuranceInput = wrapper.find('#edit-insurance-no');
    expect(insuranceInput.element.disabled).toBe(true);

    // Blood type '-'
    const bloodChecked = wrapper.find('input[name="edit_blood"]:checked');
    expect(bloodChecked.element.value).toBe('-');

    // Religion 'ISLAM'
    const relChecked = wrapper.find('input[name="edit_religion"]:checked');
    expect(relChecked.element.value).toBe('ISLAM');

    // Marital Status 'BELUM KAWIN'
    const marChecked = wrapper.find('input[name="edit_marital"]:checked');
    expect(marChecked.element.value).toBe('BELUM KAWIN');
  });

  it('submits updated data to patientService.updatePatient when Update button is clicked', async () => {
    const wrapper = mount(PatientEditView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    // Modify a field
    const nameInput = wrapper.find('#edit-name');
    await nameInput.setValue('AYU PUSPITASARI UPDATED');

    // Submit form
    await wrapper.find('form.edit-patient-form').trigger('submit.prevent');
    await flushPromises();

    expect(patientService.updatePatient).toHaveBeenCalledWith(
      '777',
      expect.objectContaining({
        name: 'AYU PUSPITASARI UPDATED',
        nik: '3402164610910006',
        gender: 'P',
        nationality: 'WNI',
        guarantor_id: 2
      })
    );
  });

  it('navigates back to /pendaftaran when Batal button is clicked', async () => {
    const wrapper = mount(PatientEditView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

    await flushPromises();

    const batalBtn = wrapper.find('button.btn-batal');
    await batalBtn.trigger('click');

    expect(mockPush).toHaveBeenCalledWith('/pendaftaran');
  });
});
