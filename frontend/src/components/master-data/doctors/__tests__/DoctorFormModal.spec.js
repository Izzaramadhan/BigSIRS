import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import DoctorFormModal from '@/components/master-data/doctors/DoctorFormModal.vue';
import doctorService from '@/services/master-data/doctors.service';

vi.mock('@/services/master-data/doctors.service', () => ({
  default: {
    createDoctor: vi.fn().mockResolvedValue({ success: true }),
    updateDoctor: vi.fn().mockResolvedValue({ success: true })
  }
}));

vi.mock('@/services/lookup.service', () => ({
  default: {
    getEducations: vi.fn().mockResolvedValue([]),
    getOccupations: vi.fn().mockResolvedValue([]),
    getProvinces: vi.fn().mockResolvedValue([]),
    getCities: vi.fn().mockResolvedValue([]),
    getDistricts: vi.fn().mockResolvedValue([]),
    getVillages: vi.fn().mockResolvedValue([]),
    getSpecializations: vi.fn().mockResolvedValue([])
  }
}));

describe('DoctorFormModal', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('payload create berisi person.gender', async () => {
    const wrapper = mount(DoctorFormModal, {
      props: {
        open: true,
        mode: 'create'
      }
    });

    await flushPromises();
    
    // Set required fields to pass html validation
    await wrapper.find('input[type="text"]').setValue('1234567890123456');
    await wrapper.findAll('input[type="text"]')[1].setValue('Budi');
    await wrapper.find('select').setValue('L'); // gender

    // We will bypass actual DOM submission which might be blocked by JS dom implementation
    // and just call handleSubmit directly
    await wrapper.vm.handleSubmit();
    
    expect(doctorService.createDoctor).toHaveBeenCalled();
    const payload = doctorService.createDoctor.mock.calls[0][0];
    expect(payload.person).toBeDefined();
    expect(payload.person.gender).toBe('L');
  });

  it('payload update berisi person.gender', async () => {
    const wrapper = mount(DoctorFormModal, {
      props: {
        open: true,
        mode: 'edit',
        initialData: {
          id: 1,
          employee: {
            id: 1,
            name: 'Budi',
            gender: 'P'
          },
          specialization_id: 1
        }
      }
    });

    await flushPromises();
    
    await wrapper.vm.handleSubmit();
    
    expect(doctorService.updateDoctor).toHaveBeenCalled();
    const payload = doctorService.updateDoctor.mock.calls[0][1];
    expect(payload.person).toBeDefined();
    expect(payload.person.gender).toBe('P');
  });

  it('label Laki-laki menghasilkan value backend yang valid', async () => {
    const wrapper = mount(DoctorFormModal, {
      props: {
        open: true,
        mode: 'create'
      }
    });

    await flushPromises();
    
    const options = wrapper.findAll('select option');
    const lakiLakiOption = options.find(o => o.text() === 'Laki-laki');
    expect(lakiLakiOption.element.value).toBe('L');
  });

  it('error person.gender tampil pada field Jenis Kelamin', async () => {
    const wrapper = mount(DoctorFormModal, {
      props: {
        open: true,
        mode: 'create'
      }
    });

    await flushPromises();
    
    // Simulate error from backend
    wrapper.vm.errors = {
      'person.gender': ['The person.gender field is required.']
    };
    
    await wrapper.vm.$nextTick();
    
    // Gender select is the first one in this mock because BaseSelects are mocked out or not rendered correctly due to stub? No, BaseSelect is imported
    // Actually gender is the only native select now!
    const select = wrapper.find('select.has-error');
    expect(select.exists()).toBe(true);
    
    const errorMessage = wrapper.find('.error-message');
    expect(errorMessage.exists()).toBe(true);
    expect(errorMessage.text()).toBe('The person.gender field is required.');
  });
});
