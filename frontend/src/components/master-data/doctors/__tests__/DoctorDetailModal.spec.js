import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import DoctorDetailModal from '@/components/master-data/doctors/DoctorDetailModal.vue';

describe('DoctorDetailModal', () => {
  it('Semua section detail dirender dan nilai null dirender sebagai —', () => {
    const wrapper = mount(DoctorDetailModal, {
      props: {
        open: true,
        loading: false,
        detail: {
          person: {
            name: 'dr. Andi',
            gender: 'L',
            blood_type: null // To test null
          },
          professional: {
            is_active: true,
            str_number: null
          }
        }
      }
    });

    const fullText = wrapper.text();
    expect(fullText).toContain('Identitas Person/Pegawai');
    expect(fullText).toContain('Alamat dan Kontak');
    expect(fullText).toContain('Profil Profesional Dokter');

    expect(fullText).toContain('dr. Andi');
    expect(fullText).toContain('Laki-laki');
    
    // Test that empty fields render as '—'
    const values = wrapper.findAll('.detail-value');
    const emptyValues = values.filter(v => v.text() === '—');
    expect(emptyValues.length).toBeGreaterThan(0);
  });

  it('Loading detail tampil', () => {
    const wrapper = mount(DoctorDetailModal, {
      props: {
        open: true,
        loading: true,
        detail: null
      }
    });

    expect(wrapper.text()).toContain('Memuat detail dokter...');
    expect(wrapper.find('.spinner').exists()).toBe(true);
  });
});
