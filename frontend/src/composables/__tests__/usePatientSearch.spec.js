import { describe, it, expect, beforeEach } from 'vitest';
import { usePatientSearch } from '../usePatientSearch';

describe('usePatientSearch composable', () => {
  let patientSearch;

  beforeEach(() => {
    patientSearch = usePatientSearch();
  });

  it('initializes with all mock patients (at least 24 records)', () => {
    expect(patientSearch.allPatients.value.length).toBeGreaterThanOrEqual(24);
    expect(patientSearch.filteredPatients.value.length).toBe(patientSearch.allPatients.value.length);
    expect(patientSearch.paginationInfo.value.total).toBe(patientSearch.allPatients.value.length);
  });

  it('searches patient by name correctly', () => {
    patientSearch.searchQuery.value = 'Bambang';
    const result = patientSearch.search();
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.length).toBeGreaterThanOrEqual(1);
    expect(patientSearch.filteredPatients.value[0].name).toContain('Bambang');
  });

  it('searches patient by medical record number (No. RM)', () => {
    patientSearch.searchQuery.value = 'RM-000245';
    const result = patientSearch.search();
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.length).toBe(1);
    expect(patientSearch.filteredPatients.value[0].medical_record_number).toBe('RM-000245');
  });

  it('searches patient by NIK', () => {
    patientSearch.searchQuery.value = '3471011205860001';
    const result = patientSearch.search();
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.length).toBe(1);
    expect(patientSearch.filteredPatients.value[0].nik).toBe('3471011205860001');
  });

  it('validates minimum 3 characters for name searches', () => {
    patientSearch.searchQuery.value = 'Ba';
    const result = patientSearch.search();
    expect(result.success).toBe(false);
    expect(result.reason).toBe('validation_error');
    expect(patientSearch.validationError.value).toContain('minimal 3 karakter');
  });

  it('validates empty search when no filters are set', () => {
    patientSearch.searchQuery.value = '';
    const result = patientSearch.search();
    expect(result.success).toBe(false);
    expect(result.reason).toBe('validation_error');
  });

  it('filters by gender (Laki-laki / Perempuan)', () => {
    patientSearch.filters.gender = 'L';
    const result = patientSearch.search(true); // force allows searching without text query
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.every(p => p.gender === 'L')).toBe(true);
  });

  it('filters by active status', () => {
    patientSearch.filters.status = 'active';
    const result = patientSearch.search(true);
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.every(p => p.is_active === true)).toBe(true);
  });

  it('combines text search with advanced filters', () => {
    patientSearch.searchQuery.value = 'Dewi';
    patientSearch.filters.gender = 'P';
    const result = patientSearch.search();
    expect(result.success).toBe(true);
    expect(patientSearch.filteredPatients.value.every(p => p.gender === 'P')).toBe(true);
    expect(patientSearch.filteredPatients.value.some(p => p.name.includes('Dewi'))).toBe(true);
  });

  it('resets filters back to initial state', () => {
    patientSearch.searchQuery.value = 'Bambang';
    patientSearch.filters.gender = 'L';
    patientSearch.search();
    expect(patientSearch.filteredPatients.value.length).toBeLessThan(patientSearch.allPatients.value.length);

    patientSearch.resetFilters();
    expect(patientSearch.searchQuery.value).toBe('');
    expect(patientSearch.filters.gender).toBe('all');
    expect(patientSearch.filteredPatients.value.length).toBe(patientSearch.allPatients.value.length);
  });

  it('handles pagination correctly', () => {
    patientSearch.setPerPage(10);
    expect(patientSearch.paginatedPatients.value.length).toBe(10);
    expect(patientSearch.paginationInfo.value.current_page).toBe(1);
    expect(patientSearch.paginationInfo.value.from).toBe(1);
    expect(patientSearch.paginationInfo.value.to).toBe(10);

    patientSearch.setPage(2);
    expect(patientSearch.pagination.currentPage).toBe(2);
    expect(patientSearch.paginationInfo.value.from).toBe(11);
    expect(patientSearch.paginationInfo.value.to).toBe(20);

    patientSearch.setPerPage(25);
    expect(patientSearch.paginatedPatients.value.length).toBe(25);
  });

  it('selects a patient locally for simulation', () => {
    const firstPatient = patientSearch.allPatients.value[0];
    const selectRes = patientSearch.selectPatient(firstPatient);
    expect(selectRes.success).toBe(true);
    expect(patientSearch.selectedPatient.value).toEqual(firstPatient);
  });
});
