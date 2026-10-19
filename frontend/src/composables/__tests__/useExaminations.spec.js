import { describe, it, expect, beforeEach } from 'vitest';
import { useExaminations } from '../useExaminations';

describe('useExaminations composable', () => {
  let exam;

  beforeEach(() => {
    exam = useExaminations();
  });

  it('initializes with 252 mock examinations matching required statistics', () => {
    expect(exam.examinations.value.length).toBe(252);
    expect(exam.statistics.value.waiting).toBe(37);
    expect(exam.statistics.value.inService).toBe(18);
    expect(exam.statistics.value.completed).toBe(193);
    expect(exam.statistics.value.cancelled).toBe(4);
    expect(exam.paginationInfo.value.activeQueueCount).toBe(55);
  });

  it('searches examinations by patient name', () => {
    exam.searchQuery.value = 'Soedirman';
    expect(exam.filteredExaminations.value.length).toBeGreaterThanOrEqual(1);
    expect(exam.filteredExaminations.value[0].patient_name).toContain('Soedirman');
  });

  it('searches examinations by No. RM', () => {
    exam.searchQuery.value = 'RM-000181';
    expect(exam.filteredExaminations.value.length).toBe(1);
    expect(exam.filteredExaminations.value[0].medical_record_number).toBe('RM-000181');
  });

  it('searches examinations by No. Antrean', () => {
    exam.searchQuery.value = 'A-001';
    expect(exam.filteredExaminations.value.length).toBeGreaterThanOrEqual(1);
    expect(exam.filteredExaminations.value[0].queue_number).toBe('A-001');
  });

  it('searches examinations by NIK', () => {
    exam.searchQuery.value = '3174021204520001';
    expect(exam.filteredExaminations.value.length).toBe(1);
    expect(exam.filteredExaminations.value[0].patient_name).toContain('Soedirman');
  });

  it('filters by polyclinic', () => {
    exam.filters.polyclinic = 'Poli Penyakit Dalam';
    expect(exam.filteredExaminations.value.every((i) => i.polyclinic === 'Poli Penyakit Dalam')).toBe(true);
  });

  it('filters by doctor', () => {
    exam.filters.doctor = 'dr. Budi Santoso, Sp.PD';
    expect(exam.filteredExaminations.value.every((i) => i.doctor === 'dr. Budi Santoso, Sp.PD')).toBe(true);
  });

  it('filters by examination status', () => {
    exam.filters.status = 'Menunggu';
    expect(exam.filteredExaminations.value.length).toBe(37);
    expect(exam.filteredExaminations.value.every((i) => i.status === 'Menunggu')).toBe(true);
  });

  it('filters by guarantor', () => {
    exam.filters.guarantor = 'BPJS Kesehatan';
    expect(exam.filteredExaminations.value.every((i) => i.guarantor_type.includes('BPJS'))).toBe(true);
  });

  it('resets filters and returns to initial state', () => {
    exam.searchQuery.value = 'Custom Name';
    exam.filters.status = 'Menunggu';
    exam.filters.polyclinic = 'Poli Penyakit Dalam';
    exam.pagination.currentPage = 3;

    exam.resetFilters();

    expect(exam.searchQuery.value).toBe('');
    expect(exam.filters.status).toBe('Semua Status');
    expect(exam.filters.polyclinic).toBe('Semua Poliklinik');
    expect(exam.pagination.currentPage).toBe(1);
  });

  it('paginates records correctly', () => {
    exam.setPerPage(10);
    expect(exam.paginatedExaminations.value.length).toBe(10);
    expect(exam.paginationInfo.value.from).toBe(1);
    expect(exam.paginationInfo.value.to).toBe(10);

    exam.setPage(2);
    expect(exam.paginationInfo.value.from).toBe(11);
    expect(exam.paginationInfo.value.to).toBe(20);

    exam.setPerPage(25);
    expect(exam.paginatedExaminations.value.length).toBe(25);
    expect(exam.pagination.currentPage).toBe(1);
  });

  it('simulates starting examination (Mulai Periksa)', () => {
    const waitingPatient = exam.examinations.value.find((p) => p.status === 'Menunggu');
    expect(waitingPatient).toBeDefined();

    const result = exam.startExamination(waitingPatient.id);
    expect(result.success).toBe(true);
    expect(waitingPatient.status).toBe('Sedang Diperiksa');
    expect(exam.lastCalledPatient.value.id).toBe(waitingPatient.id);
  });

  it('simulates entering clinic (Masuk Poli)', () => {
    const target = exam.examinations.value[0];
    const result = exam.enterClinic(target.id);
    expect(result.success).toBe(true);
    expect(target.status).toBe('Sedang Diperiksa');
  });

  it('simulates calling next queue (Panggil Antrean Berikut)', () => {
    const result = exam.callNextQueue();
    expect(result.success).toBe(true);
    expect(result.patient.status).toBe('Dipanggil');
    expect(exam.lastCalledPatient.value.id).toBe(result.patient.id);
  });

  it('simulates replaying queue audio', () => {
    const result = exam.replayQueueAudio();
    expect(result.success).toBe(true);
    expect(result.queueNumber).toBeDefined();
    expect(result.patientName).toBeDefined();
  });
});
