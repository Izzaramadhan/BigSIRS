import { describe, it, expect, beforeEach } from 'vitest';
import { useAdmissions } from '../useAdmissions';

describe('useAdmissions composable', () => {
  let admissions;

  beforeEach(() => {
    admissions = useAdmissions();
  });

  it('initializes with 248 admissions and correct statistical distribution', () => {
    expect(admissions.admissionsList.value.length).toBe(248);
    expect(admissions.statistics.value.total).toBe(248);
    expect(admissions.statistics.value.waiting).toBe(37);
    expect(admissions.statistics.value.inService).toBe(18);
    expect(admissions.statistics.value.completed).toBe(193);
    expect(admissions.statistics.value.percentage).toBe('77,8%');
  });

  it('searches admission by patient name', () => {
    admissions.searchQuery.value = 'Ahmad Pratama';
    expect(admissions.filteredAdmissions.value.length).toBeGreaterThanOrEqual(1);
    expect(admissions.filteredAdmissions.value[0].patient_name).toContain('Ahmad Pratama');
  });

  it('searches admission by No. RM', () => {
    admissions.searchQuery.value = 'RM-000245';
    expect(admissions.filteredAdmissions.value.length).toBe(1);
    expect(admissions.filteredAdmissions.value[0].medical_record_number).toBe('RM-000245');
  });

  it('searches admission by No. Antrean', () => {
    admissions.searchQuery.value = 'A-024';
    expect(admissions.filteredAdmissions.value.length).toBe(1);
    expect(admissions.filteredAdmissions.value[0].queue_number).toBe('A-024');
  });

  it('filters by polyclinic', () => {
    admissions.filters.period = 'all';
    admissions.filters.polyclinic = 'Poli Penyakit Dalam';
    expect(admissions.filteredAdmissions.value.every(a => a.polyclinic === 'Poli Penyakit Dalam')).toBe(true);
  });

  it('filters by service status', () => {
    admissions.filters.period = 'all';
    admissions.filters.status = 'Menunggu';
    expect(admissions.filteredAdmissions.value.every(a => a.status === 'Menunggu')).toBe(true);
  });

  it('filters by guarantor', () => {
    admissions.filters.period = 'all';
    admissions.filters.guarantor = 'BPJS Kesehatan';
    expect(admissions.filteredAdmissions.value.every(a => a.guarantor_type === 'BPJS Kesehatan')).toBe(true);
  });

  it('manages queue calling and navigation correctly', () => {
    expect(admissions.currentQueueNumber.value).toBe('A-024');
    expect(admissions.nextQueueNumber.value).toBe('A-025');

    // Call current queue
    const currentRes = admissions.callCurrentQueue();
    expect(currentRes.success).toBe(true);
    expect(currentRes.queueNumber).toBe('A-024');

    // Call next queue
    const nextRes = admissions.callNextQueue();
    expect(nextRes.success).toBe(true);
    expect(nextRes.queueNumber).toBe('A-025');
    expect(admissions.currentQueueNumber.value).toBe('A-025');
    expect(admissions.nextQueueNumber.value).toBe('A-026');

    // Move queue back and forward
    admissions.prevQueue();
    expect(admissions.currentQueueNumber.value).toBe('A-024');
    admissions.nextQueue();
    expect(admissions.currentQueueNumber.value).toBe('A-025');
  });

  it('cancels an admission and updates local state', () => {
    const target = admissions.admissionsList.value[0];
    const initialCancelled = admissions.statistics.value.cancelled;

    const res = admissions.cancelAdmission(target.id, 'Pasien Berhalangan Hadir');
    expect(res.success).toBe(true);
    expect(res.item.status).toBe('Dibatalkan');
    expect(res.item.cancellation_reason).toBe('Pasien Berhalangan Hadir');
    expect(admissions.statistics.value.cancelled).toBe(initialCancelled + 1);
  });

  it('updates an admission record locally', () => {
    const target = admissions.admissionsList.value[0];
    const res = admissions.updateAdmission(target.id, {
      polyclinic: 'Poli Gigi & Mulut',
      notes: 'Pemeriksaan darurat gigi'
    });

    expect(res.success).toBe(true);
    expect(res.item.polyclinic).toBe('Poli Gigi & Mulut');
    expect(res.item.notes).toBe('Pemeriksaan darurat gigi');
  });

  it('adds a new admission record locally', () => {
    const initialCount = admissions.admissionsList.value.length;
    const res = admissions.addAdmission({
      patient_name: 'Pasien Uji Coba',
      polyclinic: 'Poli Anak',
      doctor: 'dr. Siti Aminah, Sp.A'
    });

    expect(res.success).toBe(true);
    expect(admissions.admissionsList.value.length).toBe(initialCount + 1);
    expect(admissions.admissionsList.value[0].patient_name).toBe('Pasien Uji Coba');
  });

  it('handles pagination properly across 248 records', () => {
    admissions.filters.period = 'all';
    admissions.setPerPage(10);
    expect(admissions.paginatedAdmissions.value.length).toBe(10);
    expect(admissions.paginationInfo.value.from).toBe(1);
    expect(admissions.paginationInfo.value.to).toBe(10);

    admissions.setPage(2);
    expect(admissions.pagination.currentPage).toBe(2);
    expect(admissions.paginationInfo.value.from).toBe(11);
    expect(admissions.paginationInfo.value.to).toBe(20);

    admissions.setPerPage(25);
    expect(admissions.paginatedAdmissions.value.length).toBe(25);
  });

  it('resets all filters cleanly', () => {
    admissions.searchQuery.value = 'Budi';
    admissions.filters.status = 'Menunggu';
    admissions.filters.polyclinic = 'Poli Umum';
    admissions.resetFilters();

    expect(admissions.searchQuery.value).toBe('');
    expect(admissions.filters.status).toBe('all');
    expect(admissions.filters.polyclinic).toBe('all');
    expect(admissions.filters.period).toBe('today');
  });
});
