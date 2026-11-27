import { describe, it, expect, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import ExaminationView from '../ExaminationView.vue';
import ExaminationPatientView from '../ExaminationPatientView.vue';
import ExaminationDailyListCard from '../components/ExaminationDailyListCard.vue';
import ExaminationTable from '../components/ExaminationTable.vue';
import ExaminationStatsCards from '../components/ExaminationStatsCards.vue';
import ExaminationAudioDisplay from '../components/ExaminationAudioDisplay.vue';
import ExaminationPagination from '../components/ExaminationPagination.vue';
import { generateMockExaminations } from '@/mocks/examinations';

const mockPush = vi.fn();
vi.mock('vue-router', async (importOriginal) => {
  const actual = await importOriginal();
  return {
    ...actual,
    useRoute: () => ({
      params: { id: 'EXM-0001' },
      query: {}
    }),
    useRouter: () => ({
      push: mockPush
    })
  };
});

vi.mock('@/services/examination.service', async () => {
  const { generateMockExaminations } = await import('@/mocks/examinations');
  const mockData = generateMockExaminations();
  const mockExaminationService = {
    getExaminations: vi.fn().mockImplementation((params) => {
      let filtered = [...mockData];
      if (params?.q) {
        const q = params.q.toLowerCase();
        filtered = filtered.filter((x) =>
          (x.patient_name || '').toLowerCase().includes(q) ||
          (x.no_rm || '').toLowerCase().includes(q)
        );
      }
      return Promise.resolve({
        data: {
          data: filtered,
          meta: {
            total: filtered.length,
            current_page: 1,
            last_page: 1,
            per_page: 10,
            from: filtered.length > 0 ? 1 : 0,
            to: filtered.length
          }
        }
      });
    }),
    getExamination: vi.fn().mockImplementation((id) => {
      const match = mockData.find((x) => String(x.id) === String(id)) || mockData[0];
      return Promise.resolve({
        data: {
          data: {
            ...match,
            medical_record_number: match.medical_record_number || match.no_rm || 'RM-001234',
            patient_name: match.patient_name || 'H. Soedirman Wibowo',
            birth_place: match.birth_place || 'Yogyakarta',
            birth_date: match.birth_date || '1965-08-17',
            gender: match.gender || 'L',
            address: match.address || 'Jl. Malioboro No. 45, Danurejan, Yogyakarta',
            guarantor: match.guarantor || 'UMUM',
            guarantor_type: match.guarantor_type || 'UMUM',
            visit_history: match.visit_history || []
          }
        }
      });
    }),
    saveClinicalNotes: vi.fn().mockResolvedValue({ data: { success: true } }),
    saveClinical: vi.fn().mockResolvedValue({ data: { success: true } }),
    getAllergies: vi.fn().mockResolvedValue({
      data: {
        status: 'success',
        data: {
          visit_id: 'EXM-0001',
          allergy_note: 'Debu',
          notes: 'Debu',
          has_allergy: true,
          status: 'ada'
        }
      }
    }),
    saveAllergies: vi.fn().mockImplementation((id, payload) => {
      const note = payload?.allergy_note ?? payload?.notes ?? '';
      return Promise.resolve({
        data: {
          status: 'success',
          message: 'Data alergi berhasil disimpan.',
          data: {
            visit_id: id,
            allergy_note: note,
            notes: note,
            has_allergy: note !== '',
            status: note !== '' ? 'ada' : 'tidak'
          }
        }
      });
    }),
    saveVitals: vi.fn().mockResolvedValue({ data: { success: true } }),
    addDiagnosis: vi.fn().mockResolvedValue({ data: { success: true, data: { id: 1 } } }),
    deleteDiagnosis: vi.fn().mockResolvedValue({ data: { success: true } }),
    addProcedure: vi.fn().mockResolvedValue({ data: { success: true, data: { id: 1 } } }),
    deleteProcedure: vi.fn().mockResolvedValue({ data: { success: true } }),
    savePrescription: vi.fn().mockResolvedValue({ data: { success: true } }),
    saveLabOrder: vi.fn().mockResolvedValue({ data: { success: true } }),
    saveRadiologyOrder: vi.fn().mockResolvedValue({ data: { success: true } }),
    updateStatus: vi.fn().mockResolvedValue({ data: { success: true } })
  };

  return {
    examinationService: mockExaminationService,
    default: mockExaminationService
  };
});

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn().mockResolvedValue({
      data: {
        data: [
          { id: 1, name: 'Poli Penyakit Dalam' },
          { id: 2, name: 'Poli Umum' }
        ]
      }
    })
  }
}));

vi.mock('@/services/lookup.service', async () => {
  const { mockDoctors } = await import('@/mocks/examinations');
  return {
    default: {
      getDoctors: vi.fn().mockResolvedValue({
        data: mockDoctors.map((name, idx) => ({ id: idx + 1, name }))
      })
    }
  };
});

vi.mock('@/services/master-data/icd10.service', async () => {
  const { mockIcd10Catalog } = await import('@/mocks/examinations');
  return {
    default: {
      getIcd10Codes: vi.fn().mockResolvedValue({
        data: mockIcd10Catalog.map((item, idx) => ({ id: idx + 1, code: item.code, name: item.name }))
      })
    }
  };
});

vi.mock('@/services/master-data/icd9cm.service', async () => {
  const { mockIcd9Catalog } = await import('@/mocks/examinations');
  return {
    default: {
      lookup: vi.fn().mockResolvedValue({
        data: mockIcd9Catalog.map((item, idx) => ({ id: idx + 1, code: item.code, name: item.name }))
      })
    }
  };
});

vi.mock('@/services/procedure', async () => {
  const { mockClinicalProcedures } = await import('@/mocks/examinations');
  return {
    default: {
      getProcedures: vi.fn().mockResolvedValue({
        data: {
          data: mockClinicalProcedures.map((item, idx) => ({ id: idx + 1, ...item }))
        }
      })
    }
  };
});

vi.mock('@/services/master-data/medicines.service', async () => {
  const { mockMedicinesCatalog } = await import('@/mocks/examinations');
  return {
    medicinesService: {
      getMedicines: vi.fn().mockResolvedValue({
        data: mockMedicinesCatalog.map((item, idx) => ({ id: idx + 1, ...item }))
      })
    }
  };
});

import { examinationService } from '@/services/examination.service';

describe('ExaminationView (Halaman Awal Pemeriksaan Terbaru)', () => {
  it('renders PEMERIKSAAN PASIEN header, filter section, and 7 standardized columns', async () => {
    const wrapper = mount(ExaminationView, {
      global: {
        stubs: {
          AppToast: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Pemeriksaan Pasien');
    expect(wrapper.text()).toContain('Home');
    expect(wrapper.text()).toContain('DAFTAR KUNJUNGAN PASIEN HARI INI');
    expect(wrapper.find('.header-visit-count-badge').exists()).toBe(true);

    expect(wrapper.find('input#exam-search-input').exists()).toBe(true);
    expect(wrapper.find('input#exam-start-date').exists()).toBe(true);
    expect(wrapper.find('input#exam-end-date').exists()).toBe(true);
    expect(wrapper.find('select#exam-polyclinic-filter').exists()).toBe(true);
    expect(wrapper.find('button.btn-search-exam').text()).toContain('Cari Data Pasien');
    expect(wrapper.find('button.btn-reset-exam').text()).toContain('Reset Filter');
    expect(wrapper.find('select#exam-records-perpage').exists()).toBe(true);

    const headers = wrapper
      .find('.exam-daily-card')
      .findAll('thead th')
      .map((th) => th.text().trim());

    expect(headers).toEqual([
      'No',
      'No. Antrian',
      'Waktu',
      'Nama',
      'Klinik',
      'Dokter',
      'Aksi'
    ]);

    expect(wrapper.find('.btn-action-exam').exists()).toBe(true);
    expect(wrapper.find('.btn-action-call').exists()).toBe(true);
  });

  it('filters visits by patient keyword and navigates to Pemeriksaan Pasien page on + click', async () => {
    mockPush.mockClear();
    const wrapper = mount(ExaminationView, {
      global: {
        stubs: {
          AppToast: true
        }
      }
    });

    await flushPromises();

    await wrapper.find('input#exam-search-input').setValue('Soedirman');
    expect(wrapper.find('.exam-daily-card tbody').text()).toContain('Soedirman');

    await wrapper.find('.btn-action-exam').trigger('click');
    expect(mockPush).toHaveBeenCalledWith({
      name: 'clinical.examination-patient',
      params: { id: 'EXM-0001' }
    });

    await wrapper.find('.btn-reset-exam').trigger('click');
    expect(wrapper.find('input#exam-search-input').element.value).toBe('');
  });

  it('handles sorting and action emits on ExaminationDailyListCard', async () => {
    const mockList = generateMockExaminations().slice(0, 3);
    const listWrapper = mount(ExaminationDailyListCard, {
      props: {
        examinations: mockList,
        totalCount: 3,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 3,
        searchQuery: '',
        filters: {
          customStartDate: '2026-11-10',
          customEndDate: '2026-11-10',
          polyclinic: 'Semua Poliklinik'
        },
        polyclinics: ['Poli Penyakit Dalam']
      }
    });

    await listWrapper.find('th.col-clinic').trigger('click');
    expect(listWrapper.emitted('sort')[0]).toEqual(['klinik']);

    await listWrapper.find('.btn-action-call').trigger('click');
    expect(listWrapper.emitted('call-patient')[0][0].id).toBe(mockList[0].id);
  });
});

describe('ExaminationPatientView (Halaman Pemeriksaan Pasien)', () => {
  it('renders header, DATA PASIEN summary, all 9 tabs with red ALERGI tab, and RIWAYAT split pane by default', async () => {
    const wrapper = mount(ExaminationPatientView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    expect(wrapper.text()).toContain('Pemeriksaan Pasien');
    expect(wrapper.text()).toContain('Home');
    expect(wrapper.text()).toContain('DATA PASIEN');
    expect(wrapper.text()).toContain('No. RM');
    expect(wrapper.text()).toContain('Nama');
    expect(wrapper.text()).toContain('TTL');
    expect(wrapper.text()).toContain('Alamat');
    expect(wrapper.text()).toContain('Jaminan');
    expect(wrapper.text()).toContain('H. Soedirman Wibowo');

    const tabTexts = wrapper.findAll('.clinical-tabs-bar [role="tab"]').map((el) => el.text().trim());
    expect(tabTexts.length).toBe(9);
    expect(tabTexts[0]).toBe('RIWAYAT');
    expect(tabTexts[1]).toContain('ALERGI');
    expect(wrapper.find('.tab-btn--allergy').exists()).toBe(true);
    expect(tabTexts).toContain('DIAGNOSIS');
    expect(tabTexts).toContain('TINDAKAN');
    expect(tabTexts).toContain('RESUME MEDIS');
    expect(tabTexts).toContain('TAGIHAN');

    // Default RIWAYAT split pane shows chronological visits on left and opens detail on click
    const historyRows = wrapper.findAll('.history-row-btn');
    expect(historyRows.length).toBe(5);
    expect(wrapper.find('.history-detail-card').exists()).toBe(false);

    await historyRows[0].trigger('click');
    expect(wrapper.find('.history-detail-card').exists()).toBe(true);
    expect(wrapper.find('.history-detail-card').text()).toContain('Subjektif');
  });

  it('supports switching tabs/dropdowns, managing allergies/diagnoses, saving SOAP, and completing exam', async () => {
    mockPush.mockClear();
    const wrapper = mount(ExaminationPatientView, {
      global: {
        stubs: {
          Teleport: true
        }
      }
    });
    await flushPromises();

    // 1. Switch to ALERGI tab and save allergy note
    const tabs = wrapper.findAll('.clinical-tabs-bar [role="tab"]');
    await tabs[1].trigger('click');
    expect(wrapper.find('.allergy-card-title').text()).toBe('ALERGI');
    await wrapper.find('#allergy-note').setValue('Debu');
    await wrapper.find('.btn-save-allergy').trigger('click');
    expect(wrapper.text()).toContain('Data Alergi Disimpan');
    expect(wrapper.find('.allergy-badge').exists()).toBe(true);

    // 2. Open PEMERIKSAAN dropdown -> must list exact 4 slices (excluding GIGI)
    await tabs[2].trigger('click');
    const dropdownLabels = wrapper.findAll('.tab-dropdown-item').map((el) => el.text().trim());
    expect(dropdownLabels).toEqual([
      'ASESMEN AWAL BIDAN',
      'ASESMEN AWAL DOKTER',
      'ASESMEN AWAL PERAWAT',
      'MONITOR HAIS'
    ]);
    expect(dropdownLabels).not.toContain('GIGI');

    // Open ASESMEN AWAL BIDAN slice
    await wrapper.findAll('.tab-dropdown-item')[0].trigger('click');
    expect(wrapper.text()).toContain('ASESMEN AWAL PASIEN RAWAT JALAN OLEH BIDAN');
    expect(wrapper.text()).toContain('ANAMNESIS (SUBJEKTIF)');
    expect(wrapper.text()).toContain('1. Riwayat Menstruasi');
    expect(wrapper.text()).toContain('2.1 Perkawinan');
    expect(wrapper.text()).toContain('2.3 Kehamilan persalinan dan nifas yang lalu');
    expect(wrapper.text()).toContain('5. Riwayat Kehamilan Sekarang');
    expect(wrapper.text()).toContain('PEMERIKSAAN FISIK (OBJEKTIF)');
    expect(wrapper.text()).toContain('Pemeriksaan Perineum dan Genitalia');
    expect(wrapper.find('#bidan-midwife').exists()).toBe(true);

    // Toggle Histori Pemeriksaan Fisik(Objektif)
    await wrapper.find('.btn-history-toggle').trigger('click');
    expect(wrapper.text()).toContain('Bdn. Siti Rahmawati');

    // Fill G/P/A, TFU, midwife & save
    await wrapper.find('#bidan-g').setValue('2');
    await wrapper.find('#bidan-tfu').setValue('28');
    await wrapper.find('#bidan-midwife').setValue('Bdn. Siti Rahmawati, S.Keb');
    await wrapper.find('.btn-save-bidan').trigger('click');
    expect(wrapper.text()).toContain('Catatan Klinis Disimpan');

    // Open ASESMEN AWAL DOKTER slice & verify all fields + auto BMI + DPJP + save
    await tabs[2].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[1].trigger('click');
    expect(wrapper.text()).toContain('PEMERIKSAAN');
    expect(wrapper.text()).toContain('DOKTER PENANGGUNG JAWAB');
    await wrapper.find('#dokter-sistole').setValue('130');
    await wrapper.find('#dokter-diastole').setValue('85');
    await wrapper.find('#dokter-bb').setValue('70');
    await wrapper.find('#dokter-tb').setValue('175');
    expect(wrapper.find('#dokter-bmi').element.value).toBe('22.9');
    await wrapper.find('#dokter-keluhan').setValue('Nyeri dada ringan saat aktivitas');
    await wrapper.find('#dokter-instruksi-ppa').setValue('Observasi tanda vital tiap 4 jam');
    await wrapper.find('#dokter-dpjp').setValue('dr. Budi Santoso, Sp.PD');
    await wrapper.find('.btn-save-dokter').trigger('click');
    expect(wrapper.text()).toContain('Asesmen Awal Dokter');

    // Open ASESMEN AWAL PERAWAT slice & verify TEKANAN DARAH, ANTROPOMETRI, PEMERIKSAAN AWAL, EKSTERNAL
    await tabs[2].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[2].trigger('click');
    expect(wrapper.text()).toContain('TEKANAN DARAH');
    expect(wrapper.text()).toContain('VITAL SIGN');
    expect(wrapper.text()).toContain('ANTROPOMETRI');
    expect(wrapper.text()).toContain('PEMERIKSAAN AWAL');
    expect(wrapper.text()).toContain('PEMERIKSAAN EKSTERNAL');
    expect(wrapper.text()).toContain('Tidak ada file yang dipilih');

    await wrapper.find('#nurse-waist').setValue('82');
    // Pick Golongan Darah (AB) via ClinicalSelect
    await wrapper.find('#nurse-blood-type').trigger('click');
    await wrapper
      .findAll('#nurse-blood-type ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().trim() === 'AB')
      .trigger('click');
    // Pick Status Merokok (Ya) via ClinicalSelect
    await wrapper.find('#nurse-smoking').trigger('click');
    await wrapper
      .findAll('#nurse-smoking ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().trim() === 'Ya')
      .trigger('click');
    // Pick Keadaan Umum (Apatis) via ClinicalSelect and verify modern popover options
    await wrapper.find('#nurse-general').trigger('click');
    const generalOptions = wrapper.findAll('#nurse-general ~ .clinical-select-popover .clinical-select-option');
    expect(generalOptions.map((opt) => opt.text().trim())).toEqual([
      'Compos mentis',
      'Apatis',
      'Somnolen',
      'Sopor',
      'Koma'
    ]);
    await generalOptions[1].trigger('click');
    expect(wrapper.find('#nurse-general').text()).toContain('Apatis');

    await wrapper.find('#nurse-advice').setValue('Kurangi konsumsi garam');
    await wrapper.find('.btn-save-perawat').trigger('click');
    expect(wrapper.text()).toContain('Asesmen Awal Perawat');

    // Open MONITOR HAIS slice & verify MONITORING HAIS split layout + 6 collapsible modules
    await tabs[2].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[3].trigger('click');
    expect(wrapper.text()).toContain('MONITORING HAIS');
    expect(wrapper.text()).toContain('Jenis Tindakan Operasi');
    expect(wrapper.text()).toContain('Tanggal Operasi');
    expect(wrapper.text()).toContain('Tanda Infeksi');
    expect(wrapper.find('.hais-table').text()).toContain('No data available in table');
    expect(wrapper.text()).toContain('1. TERPASANG INFUS / INTRA VENA');
    expect(wrapper.text()).toContain('2. TERPASANG KATETER URINE (DOWER CATETER)');
    expect(wrapper.text()).toContain('3. TIRAH BARING');
    expect(wrapper.text()).toContain('4. TERPASANG VENA CENTRAL / PRIMER (CVP)');
    expect(wrapper.text()).toContain('5. TERPASANG ETT / VENTILATOR');
    expect(wrapper.text()).toContain('6. TINDAKAN OPERASI');

    // Check sign Kemerahan (auto-selects Ada) -> save -> appears in right-hand table
    await wrapper.find('.hais-signs-stack input[value="Kemerahan"]').setValue(true);
    await wrapper.find('.btn-save-hais-main').trigger('click');
    expect(wrapper.find('.hais-table').text()).toContain('Tanda Infeksi');
    expect(wrapper.find('.hais-table').text()).toContain('Ada (Kemerahan)');
    expect(wrapper.text()).toContain('Showing 1 to 1 of 1 entries');

    // Expand section 1 (Infus) -> verify DataTable + Tanda Klinis Plebitis 4 items + ClinicalSelect save
    await wrapper.find('[data-hais-module="infus"] .hais-accordion-header').trigger('click');
    const infusModule = wrapper.find('[data-hais-module="infus"]');
    expect(infusModule.text()).toContain('Tanda Klinis Plebitis');
    expect(infusModule.text()).toContain('Suhu > 38°C');
    expect(infusModule.text()).toContain('Kemerahan');
    expect(infusModule.text()).toContain('Bercak');
    expect(infusModule.text()).toContain('Sakit / Ulkus Sampai Eksudet Purulen');
    expect(infusModule.find('.hais-infus-table').text()).toContain('No data available in table');

    await infusModule.find('.btn-save-hais-module').trigger('click');
    expect(infusModule.text()).toContain('Pilih minimal 1 status tanda klinis sebelum menyimpan.');

    await infusModule.find('#hais-infus-sign-0').trigger('click');
    await infusModule
      .findAll('#hais-infus-sign-0 ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await infusModule.find('#hais-infus-sign-1').trigger('click');
    await infusModule
      .findAll('#hais-infus-sign-1 ~ .clinical-select-popover .clinical-select-option')[1]
      .trigger('click');
    await infusModule.find('.btn-save-hais-module').trigger('click');

    expect(infusModule.find('.hais-infus-table').text()).toContain('Suhu > 38°C');
    expect(infusModule.find('.hais-infus-table').text()).toContain('Ya');
    expect(infusModule.find('.hais-infus-table').text()).toContain('Kemerahan');
    expect(infusModule.find('.hais-infus-table').text()).toContain('Tidak');
    expect(infusModule.text()).toContain('Showing 1 to 2 of 2 entries');

    // Expand section 2 (Kateter Urine) -> verify Dewasa, Terdapat Suatu Gejala Dari (a-d), Piuri, Kultur Urin & save
    await wrapper.find('[data-hais-module="kateter"] .hais-accordion-header').trigger('click');
    const kateterModule = wrapper.find('[data-hais-module="kateter"]');
    expect(kateterModule.text()).toContain('Tanda Klinis ISK');
    expect(kateterModule.text()).toContain('Dewasa');
    expect(kateterModule.text()).toContain('Terdapat Suatu Gejala Dari');
    expect(kateterModule.text()).toContain('a. Demam Suhu > 38°C');
    expect(kateterModule.text()).toContain('b. Nokturia');
    expect(kateterModule.text()).toContain('c. Disuria');
    expect(kateterModule.text()).toContain('d. Nyeri Supra Publik');
    expect(kateterModule.text()).toContain('Piuri (Leukosit Urine > 10/ml)');
    expect(kateterModule.text()).toContain('Kultur Urin Positif > 10⁵ CFU/ml');

    await kateterModule.find('#hais-kateter-sign-2').trigger('click');
    await kateterModule
      .findAll('#hais-kateter-sign-2 ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await kateterModule.find('.btn-save-hais-module').trigger('click');
    expect(kateterModule.find('.hais-kateter-table').text()).toContain('a. Demam Suhu > 38°C');
    expect(kateterModule.find('.hais-kateter-table').text()).toContain('Ya');

    // Expand section 3 (Tirah Baring) -> verify Tanda Klinis Decubitus (3 items) & save
    await wrapper.find('[data-hais-module="tirah_baring"] .hais-accordion-header').trigger('click');
    const tirahModule = wrapper.find('[data-hais-module="tirah_baring"]');
    expect(tirahModule.text()).toContain('Tanda Klinis Decubitus');
    expect(tirahModule.text()).toContain('Kemerahan');
    expect(tirahModule.text()).toContain('Melepuh');
    expect(tirahModule.text()).toContain('Lecet / Luka');

    await tirahModule.find('#hais-tirah_baring-sign-1').trigger('click');
    await tirahModule
      .findAll('#hais-tirah_baring-sign-1 ~ .clinical-select-popover .clinical-select-option')[1]
      .trigger('click');
    await tirahModule.find('.btn-save-hais-module').trigger('click');
    expect(tirahModule.find('.hais-tirah_baring-table').text()).toContain('Melepuh');
    expect(tirahModule.find('.hais-tirah_baring-table').text()).toContain('Tidak');

    // Expand section 4 (CVP) -> verify Umur > 1 th, biakan spesimen, gejala a-c & save
    await wrapper.find('[data-hais-module="cvp"] .hais-accordion-header').trigger('click');
    const cvpModule = wrapper.find('[data-hais-module="cvp"]');
    expect(cvpModule.text()).toContain('Umur > 1 th');
    expect(cvpModule.text()).toContain('Ditemukan patogen dari biakan spesimen darah');
    expect(cvpModule.text()).toContain('Ditemukan minimal satu gejala:');
    expect(cvpModule.text()).toContain('(Tanpa penyebab infeksi lain)');
    expect(cvpModule.text()).toContain('a. Demam (suhu > 38°C)');
    expect(cvpModule.text()).toContain('b. Menggigil');
    expect(cvpModule.text()).toContain('c. Hipotensi');

    await cvpModule.find('#hais-cvp-sign-3').trigger('click');
    await cvpModule
      .findAll('#hais-cvp-sign-3 ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await cvpModule.find('.btn-save-hais-module').trigger('click');
    expect(cvpModule.find('.hais-cvp-table').text()).toContain('a. Demam (suhu > 38°C)');
    expect(cvpModule.find('.hais-cvp-table').text()).toContain('Ya');

    // Expand section 5 (ETT / Ventilator) -> verify Tanda Klinis VAP items & save
    await wrapper.find('[data-hais-module="ett"] .hais-accordion-header').trigger('click');
    const ettModule = wrapper.find('[data-hais-module="ett"]');
    expect(ettModule.text()).toContain('Tanda Klinis VAP');
    expect(ettModule.text()).toContain('Ditemukan minimal satu dari gejala:');
    expect(ettModule.text()).toContain('a. Demam (Suhu > 38°C)');
    expect(ettModule.text()).toContain('b. Leukopenia (< 4000 WBC/mm³)');
    expect(ettModule.text()).toContain('c. Leukositosis (> 12.000 SDP/mm³)');
    expect(ettModule.text()).toContain('d. Batuk/dyspnoe/tachipnoe');
    expect(ettModule.text()).toContain('Dan disertai 2 dari tanda berikut:');
    expect(ettModule.text()).toContain('a. Timbul onset baru sputum purulent/perubahan sputum');
    expect(ettModule.text()).toContain('b. Peningkatan FIO2 (> 0,2 dari sebelumnya)');
    expect(ettModule.text()).toContain('c. Peningkatan PEEP setiap hari');
    expect(ettModule.text()).toContain('Kultur Aspirasi Trakheal > 10 mm');

    await ettModule.find('#hais-ett-sign-1').trigger('click');
    await ettModule
      .findAll('#hais-ett-sign-1 ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await ettModule.find('.btn-save-hais-module').trigger('click');
    expect(ettModule.find('.hais-ett-table').text()).toContain('a. Demam (Suhu > 38°C)');
    expect(ettModule.find('.hais-ett-table').text()).toContain('Ya');

    // Expand section 6 (Tindakan Operasi) -> verify A/B/C IDO sections & save
    await wrapper.find('[data-hais-module="operasi"] .hais-accordion-header').trigger('click');
    const opsHaisModule = wrapper.find('[data-hais-module="operasi"]');
    expect(opsHaisModule.text()).toContain('A. IDO SUPERFICIAL');
    expect(opsHaisModule.text()).toContain('Nanah purulent dari tempat insisi');
    expect(opsHaisModule.text()).toContain('B. IDO DALAM (PROFUNDA)');
    expect(opsHaisModule.text()).toContain('Pus keluar dari luka insisi dalam');
    expect(opsHaisModule.text()).toContain('C. IDO ORGAN/RONGGA');
    expect(opsHaisModule.text()).toContain('Terdapat Drainase purulent');

    await opsHaisModule.find('#hais-operasi-sign-1').trigger('click');
    await opsHaisModule
      .findAll('#hais-operasi-sign-1 ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await opsHaisModule.find('.btn-save-hais-module').trigger('click');
    expect(opsHaisModule.find('.hais-operasi-table').text()).toContain('IDO Superficial - Nanah purulent dari tempat insisi');
    expect(opsHaisModule.find('.hais-operasi-table').text()).toContain('Ya');

    // 3. Switch to DIAGNOSIS tab and test narrative fields, FORM ICD-10, FORM ICD-9, and Simpan
    await tabs[3].trigger('click');
    expect(wrapper.text()).toContain('Diagnosis Awal:');
    expect(wrapper.text()).toContain('Diagnosis Kerja:');
    expect(wrapper.text()).toContain('Diagnosis Akhir:');
    expect(wrapper.text()).toContain('Narasi Tindakan:');
    expect(wrapper.text()).toContain('FORM ICD-10');
    expect(wrapper.find('.icd10-table').text()).toContain('Data Tidak Ada');
    expect(wrapper.text()).toContain('FORM ICD-9');
    expect(wrapper.text()).toContain('Tidak Ada Data.');

    await wrapper.find('#dx-working-diagnosis').setValue('Hipertensi esensial terkontrol');
    await wrapper.find('#cari-icd10').setValue('I10');
    await wrapper.find('.icd10-suggestion-item').trigger('click');
    expect(wrapper.find('.icd10-table').text()).toContain('Essential (primary) hypertension');

    await wrapper.find('#cari-icd9').setValue('89.52');
    await wrapper.find('.icd9-suggestion-item').trigger('click');
    expect(wrapper.find('.icd9-table').text()).toContain('Electrocardiogram');

    await wrapper.find('.btn-save-diagnosis').trigger('click');
    expect(wrapper.text()).toContain('Data Diagnosis Disimpan');

    // 4. Switch to TINDAKAN tab, search/switch mode Satuan/Paket, add item & Simpan
    await tabs[4].trigger('click');
    expect(wrapper.find('.proc-panel-title').text()).toBe('TINDAKAN');
    const procHeaders = wrapper.findAll('.proc-table thead th').map((el) => el.text().trim());
    expect(procHeaders).toEqual(['Tindakan', 'Paramedis', 'Dokter', 'Aksi']);

    await wrapper.find('#cari-tindakan').setValue('Konsultasi');
    await wrapper.find('.proc-suggestion-item').trigger('click');
    expect(wrapper.find('.proc-table').text()).toContain('Konsultasi & Pemeriksaan Dokter Spesialis');

    await wrapper.find('#procedure-mode-select').setValue('Paket');
    await wrapper.find('#cari-tindakan').setValue('ANC');
    await wrapper.find('.proc-suggestion-item').trigger('click');
    expect(wrapper.find('.proc-table').text()).toContain('Paket Pemeriksaan ANC Terpadu');

    await wrapper.find('.btn-save-procedures').trigger('click');
    expect(wrapper.text()).toContain('Data Tindakan Disimpan');

    // 5. Open ORDER dropdown -> verify exact 3 items (RESEP, LABORATORIUM, RADIOLOGI), pick RESEP & exercise new ORDER -> RESEP + Racikan flow
    await tabs[5].trigger('click');
    const orderDropdownLabels = wrapper.findAll('.tab-dropdown-item').map((el) => el.text().trim());
    expect(orderDropdownLabels).toEqual(['RESEP', 'LABORATORIUM', 'RADIOLOGI']);
    await wrapper.findAll('.tab-dropdown-item')[0].trigger('click');

    // Verify history toggle, RESEP 7-col table empty state, List Order Racikan & FORM RACIKAN
    expect(wrapper.text()).toContain('Tampilkan Riwayat');
    expect(wrapper.text()).toContain('Sembunyikan Riwayat');
    expect(wrapper.text()).toContain('Data Tidak Ada');
    expect(wrapper.text()).toContain('List Order Racikan');
    expect(wrapper.text()).toContain('FORM RACIKAN');

    await wrapper.find('.btn-history-show').trigger('click');
    expect(wrapper.find('.rx-history-drawer').exists()).toBe(true);
    await wrapper.find('.btn-history-hide').trigger('click');
    expect(wrapper.find('.rx-history-drawer').exists()).toBe(false);

    // Search medicine in Satuan mode and add to RESEP table
    await wrapper.find('#cari-obat-resep').trigger('focus');
    await wrapper.find('#cari-obat-resep').setValue('Paracetamol');
    await wrapper.find('.resep-suggestion-item').trigger('click');
    expect(wrapper.find('.resep-table').text()).toContain('Paracetamol 500 mg Tablet');

    // Switch mode via ClinicalSelect (#resep-mode-select -> Paket) & pick package
    await wrapper.find('#resep-mode-select').trigger('click');
    await wrapper
      .findAll('#resep-mode-select ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().trim() === 'Paket')
      .trigger('click');
    await wrapper.find('#cari-obat-resep').trigger('focus');
    await wrapper.find('.resep-suggestion-item').trigger('click');
    expect(wrapper.find('.resep-table').text()).toContain('Paket Terapi ISPA Ringan');

    // Build Racikan order & add via Tambah
    await wrapper.find('#racikan-name').setValue('Puyer Batuk');
    await wrapper.find('#racikan-signa').setValue('3 x 1');
    await wrapper.find('#racikan-qty').setValue('10');
    await wrapper.find('#racikan-usage').setValue('Sesudah makan');
    await wrapper.find('#cari-obat-racikan').trigger('focus');
    await wrapper.find('#cari-obat-racikan').setValue('Ambroxol');
    await wrapper.find('.racikan-suggestion-item').trigger('click');
    expect(wrapper.find('.racikan-comp-table').text()).toContain('Ambroxol 30 mg Tablet');
    await wrapper.find('.btn-add-racikan').trigger('click');
    expect(wrapper.find('.racikan-list-table').text()).toContain('Puyer Batuk');

    await wrapper.find('.btn-save-resep').trigger('click');
    expect(wrapper.text()).toContain('Data Resep Disimpan');

    // 5b. Open ORDER dropdown -> LABORATORIUM, toggle category & individual checkboxes, validate metadata & save
    await tabs[5].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[1].trigger('click');
    expect(wrapper.text()).toContain('RIWAYAT LABORATORIUM');
    expect(wrapper.text()).toContain('ORDER LABORATORIUM');
    expect(wrapper.find('.lab-summary-table').text()).toContain('Data Tidak Ada');
    expect(wrapper.text()).toContain('Fungsi Hati');
    expect(wrapper.text()).toContain('Fungsi Ginjal');
    expect(wrapper.text()).toContain('Mata/Telinga/Hidung');

    // Toggle Fungsi Ginjal master checkbox (second card in left column)
    const labCards = wrapper.findAll('.lab-cat-card');
    const fungsiGinjalCard = labCards.find((c) => c.find('.lab-cat-title').text() === 'Fungsi Ginjal');
    await fungsiGinjalCard.find('.lab-check-row--master input[type="checkbox"]').setValue(true);
    const summaryText = wrapper.find('.lab-summary-table').text();
    expect(summaryText).toContain('UREUM');
    expect(summaryText).toContain('KREATININ');
    expect(summaryText).toContain('ASAM URAT');

    // Remove ASAM URAT via summary table Hapus button -> remaining 2 items
    await wrapper.findAll('.lab-summary-table .cw-btn-danger-icon')[2].trigger('click');
    expect(wrapper.find('.lab-summary-table').text()).not.toContain('ASAM URAT');

    // Validation requires DPJP Laboratorium & Staf Laboratorium Klinis
    expect(wrapper.text()).toContain('Staf Laboratorium Klinis');
    await wrapper.find('.btn-save-lab').trigger('click');
    expect(wrapper.text()).toContain('DPJP Laboratorium wajib dipilih.');

    await wrapper.find('#lab-dpjp-select').trigger('click');
    await wrapper
      .findAll('#lab-dpjp-select ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('.btn-save-lab').trigger('click');
    expect(wrapper.text()).toContain('Staf Laboratorium Klinis wajib dipilih.');

    await wrapper.find('#lab-staff-select').trigger('click');
    await wrapper
      .findAll('#lab-staff-select ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('.btn-save-lab').trigger('click');
    expect(wrapper.text()).toContain('Data Order Laboratorium Disimpan');

    // 5c. Open ORDER dropdown -> RADIOLOGI, check UMUM/RESIDEN & KOAS sections, summary table, save & remove
    await tabs[5].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[2].trigger('click');
    expect(wrapper.text()).toContain('RIWAYAT RADIOLOGI');
    expect(wrapper.text()).toContain('ORDER ITEM RADIOLOGI UMUM/RESIDEN');
    expect(wrapper.text()).toContain('ORDER ITEM RADIOLOGI KOAS');
    expect(wrapper.find('.rad-summary-table').text()).toContain('Data Tidak Ada');
    expect(wrapper.text()).toContain('ANALOG/KONVENSIONAL');
    expect(wrapper.text()).toContain('DIGITAL (CD)');
    expect(wrapper.text()).toContain('DIGITAL (Film)');
    expect(wrapper.text()).toContain('CBCT');

    // Empty save validation on Umum/Residen
    await wrapper.find('.btn-save-rad-umum').trigger('click');
    expect(wrapper.text()).toContain('Pilih minimal 1 pemeriksaan pada ORDER ITEM RADIOLOGI UMUM/RESIDEN.');

    // Check Panoramik(OPG) in Umum/Residen -> summary shows Umum/Residen -> save
    await wrapper.find('[data-section="umum"] input[value="RAD-UA-05"]').setValue(true);
    expect(wrapper.find('.rad-summary-table').text()).toContain('Panoramik(OPG)');
    expect(wrapper.find('.rad-summary-table').text()).toContain('Umum/Residen');
    await wrapper.find('.btn-save-rad-umum').trigger('click');
    expect(wrapper.text()).toContain('Data Order Radiologi Disimpan');

    // Check Oklusal koas & CCC in Koas -> save -> remove CCC via table Hapus
    await wrapper.find('[data-section="koas"] input[value="RAD-KA-04"]').setValue(true);
    await wrapper.find('[data-section="koas"] input[value="RAD-KDC-08"]').setValue(true);
    expect(wrapper.find('.rad-summary-table').text()).toContain('Koas');
    await wrapper.find('.btn-save-rad-koas').trigger('click');
    expect(wrapper.text()).toContain('Data Order Radiologi Disimpan');

    await wrapper.findAll('.rad-summary-table .cw-btn-danger-icon')[2].trigger('click');
    expect(wrapper.find('.rad-summary-table').text()).not.toContain('CCC');
    expect(wrapper.find('.rad-summary-table').text()).toContain('Panoramik(OPG)');
    expect(wrapper.find('.rad-summary-table').text()).toContain('Oklusal koas');

    // 6. Open OPERASI dropdown -> verify exact 3 items (VK, IBS, LAPORAN OPERASI), exercise VK & IBS
    await tabs[6].trigger('click');
    const operasiDropdownLabels = wrapper.findAll('.tab-dropdown-item').map((el) => el.text().trim());
    expect(operasiDropdownLabels).toEqual(['VK', 'IBS', 'LAPORAN OPERASI']);

    // 6a. Pick VK & test OPERASI VK cascading selects, validation, save & 7-column DataTable
    await wrapper.findAll('.tab-dropdown-item')[0].trigger('click');
    expect(wrapper.text()).toContain('OPERASI VK');
    expect(wrapper.find('.vk-table').text()).toContain('No data available in table');
    expect(wrapper.text()).toContain('Showing 0 to 0 of 0 entries');

    await wrapper.find('.btn-save-vk').trigger('click');
    expect(wrapper.text()).toContain('Spesialisasi wajib dipilih.');

    await wrapper.find('#vk-spesialisasi').trigger('click');
    await wrapper
      .findAll('#vk-spesialisasi ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('#vk-dokter').trigger('click');
    await wrapper
      .findAll('#vk-dokter ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().includes('Hendra Pratama'))
      .trigger('click');
    await wrapper.find('#vk-tindakan').setValue('Persalinan Normal');

    await wrapper.find('#vk-kelas').trigger('click');
    await wrapper
      .findAll('#vk-kelas ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().trim() === 'Kelas I')
      .trigger('click');
    await wrapper.find('#vk-kamar').trigger('click');
    await wrapper
      .findAll('#vk-kamar ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('#vk-bed').trigger('click');
    await wrapper
      .findAll('#vk-bed ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');

    await wrapper.find('.btn-save-vk').trigger('click');
    expect(wrapper.find('.vk-table').text()).toContain('Persalinan Normal');
    expect(wrapper.find('.vk-table').text()).toContain('Ruang VK Mawar I');
    expect(wrapper.text()).toContain('Showing 1 to 1 of 1 entries');

    await wrapper.find('#vk-search').setValue('TidakAda');
    expect(wrapper.find('.vk-table').text()).toContain('No data available in table');
    await wrapper.find('#vk-search').setValue('');
    expect(wrapper.find('.vk-table').text()).toContain('Persalinan Normal');

    // 6b. Switch to IBS & verify VK row does not mix into IBS table, fill cascading IBS fields & save
    await tabs[6].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[1].trigger('click');
    expect(wrapper.text()).not.toContain('Persalinan Normal');
    expect(wrapper.find('.ibs-table').text()).toContain('No data available in table');

    await wrapper.find('#ibs-spesialisasi').trigger('click');
    await wrapper
      .findAll('#ibs-spesialisasi ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('#ibs-dokter').trigger('click');
    await wrapper
      .findAll('#ibs-dokter ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().includes('Bayu Aji Pamungkas'))
      .trigger('click');
    await wrapper.find('#ibs-tindakan').setValue('Eksisi Lipoma');
    await wrapper.find('#ibs-kelas').trigger('click');
    await wrapper
      .findAll('#ibs-kelas ~ .clinical-select-popover .clinical-select-option')
      .find((opt) => opt.text().trim() === 'Kelas I')
      .trigger('click');
    await wrapper.find('#ibs-kamar').trigger('click');
    await wrapper
      .findAll('#ibs-kamar ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('#ibs-bed').trigger('click');
    await wrapper
      .findAll('#ibs-bed ~ .clinical-select-popover .clinical-select-option')[0]
      .trigger('click');
    await wrapper.find('.btn-save-ibs').trigger('click');
    expect(wrapper.find('.ibs-table').text()).toContain('Eksisi Lipoma');
    expect(wrapper.find('.ibs-table').text()).toContain('Kamar Operasi Sentral 01 (OK-01)');
    expect(wrapper.text()).toContain('Showing 1 to 1 of 1 entries');

    // 6c. Switch to LAPORAN OPERASI, enter report findings & click Simpan
    await tabs[6].trigger('click');
    await wrapper.findAll('.tab-dropdown-item')[2].trigger('click');
    expect(wrapper.text()).toContain('LAPORAN OPERASI');
    await wrapper.find('#laporan-operasi-text').setValue('Eksisi lipoma regio antebrachii dextra berjalan lancar tanpa komplikasi.');
    await wrapper.find('.btn-save-oprep').trigger('click');
    expect(wrapper.text()).toContain('Laporan Operasi Disimpan');

    // 7. Switch to TAGIHAN, verify date accordion strip, calculated charges, and Selesai button
    await tabs[8].trigger('click');
    expect(wrapper.find('.bill-panel-title').text()).toBe('TAGIHAN');
    expect(wrapper.find('.bill-date-label').text().length).toBeGreaterThan(5);
    expect(wrapper.text()).toContain('Total Estimasi Tagihan');
    expect(wrapper.text()).toContain('Konsultasi & Pemeriksaan Dokter Spesialis');
    expect(wrapper.text()).toContain('Obat Racikan');
    expect(wrapper.text()).toContain('UREUM (Fungsi Ginjal)');
    expect(wrapper.text()).toContain('Panoramik(OPG) (ANALOG/KONVENSIONAL - Umum/Residen)');
    expect(wrapper.text()).toContain('Oklusal koas (ANALOG/KONVENSIONAL - Koas)');
    expect(wrapper.find('.btn-complete-billing').text()).toContain('Selesai');

    // Collapsing/expanding date accordion hides/shows breakdown cleanly
    await wrapper.find('.bill-date-bar').trigger('click');
    expect(wrapper.find('.bill-date-bar').attributes('aria-expanded')).toBe('false');
    await wrapper.find('.bill-date-bar').trigger('click');
    expect(wrapper.find('.bill-date-bar').attributes('aria-expanded')).toBe('true');

    // 8. Switch to RESUME MEDIS, verify 8 summary panels (excluding PEMERIKSAAN GIGI), print, validate & Selesai
    await tabs[7].trigger('click');
    expect(wrapper.find('.btn-print-resume').text()).toBe('Cetak Resume');
    expect(wrapper.text()).toContain('PEMERIKSAAN UMUM');
    expect(wrapper.text()).not.toContain('PEMERIKSAAN GIGI');
    expect(wrapper.text()).toContain('VITAL SIGN');
    expect(wrapper.text()).toContain('TEKANAN DARAH');
    expect(wrapper.text()).toContain('ANTROPOMETRI');
    expect(wrapper.text()).toContain('BMI / IMT');
    expect(wrapper.text()).toContain('Hipertensi esensial terkontrol');
    expect(wrapper.text()).toContain('Paket Pemeriksaan ANC Terpadu');
    expect(wrapper.text()).toContain('Paracetamol 500 mg Tablet');
    expect(wrapper.text()).toContain('Racikan: Puyer Batuk');
    expect(wrapper.text()).toContain('UREUM (Fungsi Ginjal)');
    expect(wrapper.text()).toContain('Panoramik(OPG) (ANALOG/KONVENSIONAL)');
    expect(wrapper.text()).toContain('Oklusal koas (ANALOG/KONVENSIONAL)');
    expect(wrapper.text()).toContain('Debu');

    await wrapper.find('.btn-print-resume').trigger('click');
    expect(wrapper.text()).toContain('Cetak Resume Medis');

    // Verify Dokter * required validation on Selesai
    await wrapper.find('#res-doctor').setValue('');
    await wrapper.find('.btn-complete-exam').trigger('click');
    expect(wrapper.text()).toContain('Dokter pemeriksa wajib dipilih.');

    await wrapper.find('#res-disposition').setValue('Pulang / Kontrol Poliklinik');
    await wrapper.find('#res-doctor').setValue('dr. Budi Santoso, Sp.PD');
    await wrapper.find('#res-paramedic').setValue('Ns. Ratna Puspita, S.Kep');
    await wrapper.find('.btn-complete-exam').trigger('click');
    expect(wrapper.find('.exam-status-pill').text()).toBe('Selesai');

    // 9. Header back button navigates to /pemeriksaan
    await wrapper.find('.btn-header-back').trigger('click');
    expect(mockPush).toHaveBeenCalledWith('/pemeriksaan');
  });
});

describe('Examination Rawat Jalan Components', () => {
  const mockList = generateMockExaminations();

  describe('ExaminationTable', () => {
    it('renders the 11 columns in EXACT required order', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: mockList.slice(0, 5),
          totalCount: 5,
          activeQueueCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const thList = wrapper.findAll('thead th').map((th) => th.text().trim());

      // Expected strict 11 columns from Prompt Section 8:
      // 1. No. Antrean
      // 2. Waktu
      // 3. No. RM
      // 4. Pasien & Prioritas
      // 5. Usia / Gender
      // 6. Poliklinik Tujuan
      // 7. Dokter Pemeriksa
      // 8. Penjamin
      // 9. Waktu Tunggu
      // 10. Status
      // 11. Aksi Klinis
      expect(thList).toEqual([
        '1. No. Antrean',
        '2. Waktu',
        '3. No. RM',
        '4. Pasien & Prioritas',
        '5. Usia / Gender',
        '6. Poliklinik Tujuan',
        '7. Dokter Pemeriksa',
        '8. Penjamin',
        '9. Waktu Tunggu',
        '10. Status',
        '11. Aksi Klinis'
      ]);
    });

    it('displays summary count text accurately matching mock dataset', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: mockList.slice(0, 10),
          totalCount: 55,
          activeQueueCount: 55,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      const badge = wrapper.find('.count-badge');
      expect(badge.text()).toContain('Menampilkan 10 dari 55 pasien dalam antrean aktif poliklinik');
    });

    it('renders priority badges when priority is not Normal', () => {
      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: [
            mockList[0], // Lansia
            mockList[1]  // Cito
          ],
          totalCount: 2,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.find('.priority-elderly').exists()).toBe(true);
      expect(wrapper.find('.priority-cito').exists()).toBe(true);
    });

    it('renders contextual action buttons based on status', () => {
      const sampleRows = [
        { ...mockList[0], status: 'Menunggu' },
        { ...mockList[1], status: 'Dipanggil' },
        { ...mockList[2], status: 'Sedang Diperiksa' },
        { ...mockList[3], status: 'Selesai' },
        { ...mockList[4], status: 'Dibatalkan' }
      ];

      const wrapper = mount(ExaminationTable, {
        props: {
          examinations: sampleRows,
          totalCount: 5,
          currentPage: 1,
          perPage: 10,
          loading: false
        }
      });

      expect(wrapper.find('.btn-start-exam').exists()).toBe(true);
      expect(wrapper.find('.btn-enter-clinic').exists()).toBe(true);
      expect(wrapper.find('.btn-open-rme').exists()).toBe(true);
      expect(wrapper.find('.btn-resume').exists()).toBe(true);
      expect(wrapper.find('.btn-cancel-detail').exists()).toBe(true);
    });
  });

  describe('ExaminationStatsCards', () => {
    it('renders 4 statistic cards with expected values and labels', () => {
      const stats = {
        waiting: 37,
        avgWaitTime: '~24 Menit',
        inService: 18,
        activePolyCount: 8,
        activeDoctorsCount: 14,
        completed: 193,
        completedPercentage: '77,8%',
        cancelled: 4
      };

      const wrapper = mount(ExaminationStatsCards, {
        props: { stats }
      });

      expect(wrapper.text()).toContain('MENUNGGU PEMERIKSAAN');
      expect(wrapper.text()).toContain('37');
      expect(wrapper.text()).toContain('SEDANG DIPERIKSA');
      expect(wrapper.text()).toContain('18');
      expect(wrapper.text()).toContain('PEMERIKSAAN SELESAI');
      expect(wrapper.text()).toContain('193');
      expect(wrapper.text()).toContain('DIBATALKAN / NO SHOW');
      expect(wrapper.text()).toContain('4');
    });
  });

  describe('ExaminationAudioDisplay', () => {
    it('renders the bottom audio display panel and emits replay-audio event on click', async () => {
      const wrapper = mount(ExaminationAudioDisplay, {
        props: {
          lastCalledPatient: mockList[0]
        }
      });

      expect(wrapper.text()).toContain('DISPLAY PEMANGGIL SUARA POLIKLINIK');
      expect(wrapper.text()).toContain('Speaker Ruang Antrean Poliklinik Aktif');
      expect(wrapper.text()).toContain('Simulasi display antrean — belum terhubung ke perangkat audio maupun SMS Gateway.');

      const replayBtn = wrapper.find('.btn-replay');
      expect(replayBtn.exists()).toBe(true);
      await replayBtn.trigger('click');
      expect(wrapper.emitted('replay-audio')).toBeTruthy();
    });
  });

  describe('ExaminationPagination', () => {
    it('renders pagination options and handles page navigation', async () => {
      const wrapper = mount(ExaminationPagination, {
        props: {
          currentPage: 1,
          lastPage: 6,
          perPage: 10,
          total: 55,
          from: 1,
          to: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Menampilkan 1–10 dari 55 total antrean terdaftar');
      const nextBtn = wrapper.find('.btn-next');
      await nextBtn.trigger('click');
      expect(wrapper.emitted('page-change')[0]).toEqual([2]);
    });
  });

  describe('Fase 2B.18 API Integration, Error & Anti-Fake-Success Guards', () => {
    it('shows API error on ExaminationView without mock fallback and blocks navigation if startExamination fails', async () => {
      mockPush.mockClear();
      examinationService.getExaminations.mockRejectedValueOnce({
        response: { status: 500, data: { message: 'Koneksi database gagal' } }
      });

      const wrapper = mount(ExaminationView, {
        global: { stubs: { AppToast: true } }
      });
      await flushPromises();

      expect(wrapper.text()).toContain('Gagal Memuat Data Pemeriksaan');
      expect(wrapper.text()).toContain('Koneksi database gagal');

      // Retry succeeds with backend data
      examinationService.getExaminations.mockResolvedValueOnce({
        data: {
          data: [{
            id: 901,
            visit_id: 901,
            admission_id: 501,
            patient_id: 42,
            queue_number: 'A-01',
            visit_date: '2026-11-26',
            visit_time: '09:00 WIB',
            patient_name: 'BUDI API',
            medical_record_number: 'RM-999001',
            gender: 'L',
            birth_date: '1990-01-01',
            guarantor_type: 'UMUM',
            polyclinic: 'Poli Penyakit Dalam',
            doctor: 'dr. Budi Santoso, Sp.PD',
            status: 'Menunggu'
          }],
          meta: { total: 1, current_page: 1, last_page: 1, per_page: 10, from: 1, to: 1 }
        }
      });

      await wrapper.find('.btn-empty-reset').trigger('click');
      await flushPromises();
      expect(wrapper.text()).toContain('BUDI API');

      // Failed updateStatus must NOT navigate
      examinationService.updateStatus.mockRejectedValueOnce({
        response: { status: 403, data: { message: 'Akses klinik ditolak' } }
      });
      await wrapper.find('.btn-action-exam').trigger('click');
      await flushPromises();
      expect(mockPush).not.toHaveBeenCalled();
    });

    it('shows 404/network error with retry button in ExaminationPatientView and never fakes success when clinical APIs fail', async () => {
      examinationService.getExamination.mockRejectedValueOnce({
        response: { status: 404, data: { message: 'Kunjungan 999 tidak ditemukan' } }
      });

      const wrapper = mount(ExaminationPatientView, {
        global: { stubs: { Teleport: true } }
      });
      await flushPromises();

      expect(wrapper.text()).toContain('Gagal Memuat Pasien:');
      expect(wrapper.text()).toContain('Kunjungan 999 tidak ditemukan');
      expect(wrapper.find('.btn-retry-patient').exists()).toBe(true);

      // Retry loads valid backend record
      examinationService.getExamination.mockResolvedValueOnce({
        data: {
          data: {
            id: 901,
            visit_id: 901,
            admission_id: 501,
            patient_id: 42,
            queue_number: 'A-01',
            medical_record_number: 'RM-999001',
            patient_name: 'BUDI API',
            gender: 'L',
            birth_date: '1990-01-01',
            address: 'Jl. Merdeka',
            guarantor: 'BPJS Kesehatan',
            guarantor_type: 'BPJS',
            polyclinic: 'Poli Penyakit Dalam',
            doctor: 'dr. Budi Santoso, Sp.PD',
            status: 'Sedang Diperiksa',
            visit_date: '2026-11-26',
            visit_history: []
          }
        }
      });

      await wrapper.find('.btn-retry-patient').trigger('click');
      await flushPromises();
      expect(wrapper.text()).toContain('BUDI API');

      // 422 on saveClinical must show failure toast and NOT show success toast
      const tabs = wrapper.findAll('.clinical-tabs-bar [role="tab"]');
      await tabs[2].trigger('click');
      await wrapper.findAll('.tab-dropdown-item')[1].trigger('click');
      examinationService.saveClinical.mockRejectedValueOnce({
        response: { status: 422, data: { message: 'Catatan klinis tidak valid' } }
      });
      await wrapper.find('.btn-save-dokter').trigger('click');
      await flushPromises();
      expect(wrapper.text()).toContain('Gagal Menyimpan Catatan Klinis');
      expect(wrapper.text()).not.toContain('Catatan Klinis Disimpan');

      // 409 on addDiagnosis must NOT add row into diagnoses table
      await tabs[3].trigger('click');
      examinationService.addDiagnosis.mockRejectedValueOnce({
        response: { status: 409, data: { message: 'Diagnosis duplikat' } }
      });
      await wrapper.find('#cari-icd10').setValue('I10');
      await wrapper.find('.icd10-suggestion-item').trigger('click');
      await flushPromises();
      expect(wrapper.find('.icd10-table').text()).toContain('Data Tidak Ada');
      expect(wrapper.text()).toContain('Diagnosis duplikat');

      // 500 on addProcedure must NOT add row into procedures table
      await tabs[4].trigger('click');
      examinationService.addProcedure.mockRejectedValueOnce({
        response: { status: 500, data: { message: 'Gagal menyimpan tindakan' } }
      });
      await wrapper.find('#cari-tindakan').setValue('Konsultasi');
      await wrapper.find('.proc-suggestion-item').trigger('click');
      await flushPromises();
      expect(wrapper.find('.proc-table').text()).toContain('Belum ada tindakan yang dipilih.');

      // Prescription save error does not mark persisted; repeat save skips already-persisted items
      await tabs[5].trigger('click');
      await wrapper.findAll('.tab-dropdown-item')[0].trigger('click');
      await wrapper.find('#cari-obat-resep').trigger('focus');
      await wrapper.find('#cari-obat-resep').setValue('Paracetamol');
      await wrapper.find('.resep-suggestion-item').trigger('click');

      examinationService.savePrescription.mockClear();
      examinationService.savePrescription.mockRejectedValueOnce(new Error('Network Error'));
      await wrapper.find('.btn-save-resep').trigger('click');
      await flushPromises();
      expect(wrapper.text()).toContain('Gagal Menyimpan Resep');

      examinationService.savePrescription.mockResolvedValueOnce({ data: { success: true, data: { id: 88 } } });
      await wrapper.find('.btn-save-resep').trigger('click');
      await flushPromises();
      expect(wrapper.text()).toContain('Data Resep Disimpan');
      expect(examinationService.savePrescription).toHaveBeenCalledTimes(2);

      // Subsequent save with no new pharmacy items must NOT create duplicate backend prescription
      await wrapper.find('.btn-save-resep').trigger('click');
      await flushPromises();
      expect(examinationService.savePrescription).toHaveBeenCalledTimes(2);

      // Failed updateStatus on completeExamination keeps original status
      await tabs[7].trigger('click');
      examinationService.updateStatus.mockRejectedValueOnce({
        response: { status: 403, data: { message: 'Tidak diizinkan menyelesaikan' } }
      });
      await wrapper.find('.btn-complete-exam').trigger('click');
      await flushPromises();
      expect(wrapper.find('.exam-status-pill').text()).toBe('Sedang Diperiksa');
      expect(wrapper.text()).toContain('Gagal Menyelesaikan Pemeriksaan');
    });
  });
});
