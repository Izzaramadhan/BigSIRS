import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import NewPatientView from '../NewPatientView.vue';

// Mock vue-router
const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: mockPush
  })
}));

describe('NewPatientView', () => {
  it('renders FORM TAMBAH card header and all key fields from reference image', () => {
    const wrapper = mount(NewPatientView, {
      global: {
        stubs: {
          MasterDataPageHeader: true,
          AppToast: true
        }
      }
    });

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

    await wrapper.find('form').trigger('submit.prevent');

    expect(wrapper.findAll('.error-message').length).toBeGreaterThan(0);
  });
});

