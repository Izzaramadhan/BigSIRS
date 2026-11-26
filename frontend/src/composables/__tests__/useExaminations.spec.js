import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useExaminations } from '../useExaminations';
import { examinationService } from '@/services/examination.service';

vi.mock('@/services/examination.service', () => ({
  examinationService: {
    getExaminations: vi.fn(),
    getExamination: vi.fn(),
    updateStatus: vi.fn(),
    saveVitals: vi.fn(),
    saveClinical: vi.fn(),
    addDiagnosis: vi.fn(),
    deleteDiagnosis: vi.fn(),
    addProcedure: vi.fn(),
    deleteProcedure: vi.fn(),
    savePrescription: vi.fn(),
    saveLabOrder: vi.fn(),
    saveRadiologyOrder: vi.fn(),
  }
}));

describe('useExaminations composable', () => {
  let exam;

  beforeEach(() => {
    vi.clearAllMocks();
    exam = useExaminations();
  });

  it('initializes with empty examinations list and default states', () => {
    expect(exam.examinations.value).toEqual([]);
    expect(exam.loading.value).toBe(false);
    expect(exam.error.value).toBeNull();
    expect(exam.paginationInfo.value.total).toBe(0);
    expect(exam.pagination.currentPage).toBe(1);
    expect(exam.pagination.perPage).toBe(10);
  });

  it('fetches examinations data from API successfully', async () => {
    const mockList = [
      {
        id: 786,
        visit_id: 786,
        admission_id: 544,
        patient_id: 789,
        patient_name: 'SURYO BROTO',
        medical_record_number: 'RM-000001',
        queue_number: '001',
        polyclinic: 'Poli Penyakit Dalam',
        doctor: 'dr. Budi Santoso',
        status: 'Menunggu'
      }
    ];

    examinationService.getExaminations.mockResolvedValueOnce({
      data: {
        data: mockList,
        meta: {
          total: 1,
          current_page: 1,
          per_page: 10,
          last_page: 1,
          from: 1,
          to: 1
        }
      }
    });

    await exam.fetchExaminations();

    expect(examinationService.getExaminations).toHaveBeenCalled();
    expect(exam.examinations.value.length).toBe(1);
    expect(exam.examinations.value[0].patient_name).toBe('SURYO BROTO');
    expect(exam.paginationInfo.value.total).toBe(1);
    expect(exam.paginationInfo.value.from).toBe(1);
    expect(exam.paginationInfo.value.to).toBe(1);
    expect(exam.error.value).toBeNull();
  });

  it('handles empty API response without falling back to mock data', async () => {
    examinationService.getExaminations.mockResolvedValueOnce({
      data: {
        data: [],
        meta: {
          total: 0,
          current_page: 1,
          per_page: 10,
          last_page: 1,
          from: 0,
          to: 0
        }
      }
    });

    await exam.fetchExaminations();

    expect(exam.examinations.value).toEqual([]);
    expect(exam.paginationInfo.value.total).toBe(0);
    expect(exam.paginationInfo.value.from).toBe(0);
    expect(exam.paginationInfo.value.to).toBe(0);
    expect(exam.error.value).toBeNull();
  });

  it('handles API error without falling back to mock data', async () => {
    examinationService.getExaminations.mockRejectedValueOnce({
      response: {
        data: {
          message: 'Gagal mengambil data dari database legacy'
        }
      }
    });

    await exam.fetchExaminations();

    expect(exam.examinations.value).toEqual([]);
    expect(exam.error.value).toBe('Gagal mengambil data dari database legacy');
    expect(exam.paginationInfo.value.total).toBe(0);
  });

  it('sends search query and filter parameters to API', async () => {
    examinationService.getExaminations.mockResolvedValueOnce({
      data: { data: [], meta: { total: 0 } }
    });

    exam.searchQuery.value = 'AYU';
    exam.filters.customStartDate = '2026-09-01';
    exam.filters.customEndDate = '2026-09-30';
    exam.filters.polyclinic = 'Poli Penyakit Dalam';
    exam.filters.status = 'Menunggu';

    await exam.applyFilters();

    expect(examinationService.getExaminations).toHaveBeenCalledWith(
      expect.objectContaining({
        q: 'AYU',
        date_from: '2026-09-01',
        date_to: '2026-09-30',
        polyclinic: 'Poli Penyakit Dalam',
        status: 'Menunggu',
        page: 1,
        per_page: 10
      })
    );
  });

  it('updates pagination with metadata from backend', async () => {
    examinationService.getExaminations.mockResolvedValueOnce({
      data: {
        data: [{ id: 780, patient_name: 'Patient Page 2' }],
        meta: {
          total: 25,
          current_page: 2,
          per_page: 10,
          last_page: 3,
          from: 11,
          to: 20
        }
      }
    });

    exam.pagination.lastPage = 3;
    exam.setPage(2);

    expect(exam.pagination.currentPage).toBe(2);
    expect(examinationService.getExaminations).toHaveBeenCalledWith(
      expect.objectContaining({ page: 2, per_page: 10 })
    );
  });

  it('resets filters and returns to initial state', async () => {
    examinationService.getExaminations.mockResolvedValueOnce({
      data: { data: [], meta: { total: 0 } }
    });

    exam.searchQuery.value = 'Custom Name';
    exam.filters.status = 'Menunggu';
    exam.filters.polyclinic = 'Poli Penyakit Dalam';
    exam.pagination.currentPage = 3;

    await exam.resetFilters();

    expect(exam.searchQuery.value).toBe('');
    expect(exam.filters.status).toBe('Semua Status');
    expect(exam.filters.polyclinic).toBe('Semua Poliklinik');
    expect(exam.pagination.currentPage).toBe(1);
    expect(examinationService.getExaminations).toHaveBeenCalled();
  });

  it('starts examination via API (Mulai Periksa)', async () => {
    examinationService.updateStatus.mockResolvedValueOnce({
      data: { success: true, message: 'Status pemeriksaan berhasil diperbarui.' }
    });

    exam.examinations.value = [
      { id: 786, status: 'Menunggu', patient_name: 'SURYO' }
    ];

    const result = await exam.startExamination(786);

    expect(examinationService.updateStatus).toHaveBeenCalledWith(786, 'Sedang Diperiksa');
    expect(result.success).toBe(true);
    expect(exam.examinations.value[0].status).toBe('Sedang Diperiksa');
    expect(exam.lastCalledPatient.value.id).toBe(786);
  });

  it('completes examination via API (Selesai)', async () => {
    examinationService.updateStatus.mockResolvedValueOnce({
      data: { success: true }
    });

    exam.examinations.value = [
      { id: 786, status: 'Sedang Diperiksa', patient_name: 'SURYO' }
    ];

    await exam.completeExamination(786);

    expect(examinationService.updateStatus).toHaveBeenCalledWith(786, 'Selesai');
    expect(exam.examinations.value[0].status).toBe('Selesai');
  });

  it('calls next queue and replays queue audio correctly', () => {
    exam.examinations.value = [
      { id: 786, queue_number: '001', patient_name: 'SURYO BROTO', polyclinic: 'Poli Dalam', room: 'Ruang 1', status: 'Menunggu' }
    ];

    const callRes = exam.callNextQueue();
    expect(callRes.success).toBe(true);
    expect(callRes.queueNumber).toBe('001');
    expect(callRes.patientName).toBe('SURYO BROTO');
    expect(exam.examinations.value[0].status).toBe('Dipanggil');

    const replayRes = exam.replayQueueAudio();
    expect(replayRes.success).toBe(true);
    expect(replayRes.queueNumber).toBe('001');
    expect(replayRes.patientName).toBe('SURYO BROTO');
  });
});
