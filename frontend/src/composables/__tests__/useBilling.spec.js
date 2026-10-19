import { describe, it, expect, beforeEach } from 'vitest';
import { useBilling } from '../useBilling';

describe('useBilling composable', () => {
  let billing;

  beforeEach(() => {
    billing = useBilling();
  });

  it('initializes with 248 billing records and exact statistical distribution', () => {
    expect(billing.billingList.value.length).toBe(248);
    expect(billing.statistics.value.waiting).toBe(18);
    expect(billing.statistics.value.inhealthClaims).toBe(4);
    expect(billing.statistics.value.bpjsClaims).toBe(14);
    expect(billing.statistics.value.ready).toBe(24);
    expect(billing.statistics.value.elderlyReady).toBe(6);
    expect(billing.statistics.value.cashEdcReady).toBe(18);
    expect(billing.statistics.value.paid).toBe(193);
    expect(billing.statistics.value.pending).toBe(9);
    expect(billing.statistics.value.totalExcess).toBe(3420000);
    expect(billing.statistics.value.todayIncome).toBe(48250000);
  });

  it('provides accurate quick filter counts', () => {
    expect(billing.quickFilterCounts.value.all).toBe(248);
    expect(billing.quickFilterCounts.value.ready_cashier).toBe(24);
    expect(billing.quickFilterCounts.value.verify_bpjs).toBe(18);
    expect(billing.quickFilterCounts.value.general_today).toBe(57);
    expect(billing.quickFilterCounts.value.tariff_adjustment).toBe(3);
  });

  it('searches tagihan by patient name', () => {
    billing.searchQuery.value = 'Ahmad Pratama';
    expect(billing.filteredRecords.value.length).toBeGreaterThanOrEqual(1);
    expect(billing.filteredRecords.value[0].patient_name).toContain('Ahmad Pratama');
  });

  it('searches tagihan by No. RM', () => {
    billing.searchQuery.value = 'RM-000241';
    expect(billing.filteredRecords.value.length).toBe(1);
    expect(billing.filteredRecords.value[0].medical_record_number).toBe('RM-000241');
  });

  it('searches tagihan by NIK', () => {
    billing.searchQuery.value = '3174012004860001';
    expect(billing.filteredRecords.value.length).toBe(1);
    expect(billing.filteredRecords.value[0].patient_name).toBe('Ahmad Pratama');
  });

  it('searches tagihan by Nomor Tagihan (Invoice)', () => {
    billing.searchQuery.value = 'INV-RJ-20260912-0001';
    expect(billing.filteredRecords.value.length).toBe(1);
    expect(billing.filteredRecords.value[0].invoice_number).toBe('INV-RJ-20260912-0001');
  });

  it('filters by polyclinic', () => {
    billing.filters.polyclinic = 'Poli Penyakit Dalam';
    expect(billing.filteredRecords.value.every((b) => b.polyclinic === 'Poli Penyakit Dalam')).toBe(true);
  });

  it('filters by status tagihan', () => {
    billing.filters.status = 'Siap Dibayar';
    expect(billing.filteredRecords.value.length).toBe(24);
    expect(billing.filteredRecords.value.every((b) => b.status === 'Siap Dibayar')).toBe(true);
  });

  it('applies quick filter segmented chips', () => {
    billing.setQuickFilter('ready_cashier');
    expect(billing.filteredRecords.value.length).toBe(24);
    expect(billing.filteredRecords.value.every((b) => b.status === 'Siap Dibayar')).toBe(true);

    billing.setQuickFilter('verify_bpjs');
    expect(billing.filteredRecords.value.length).toBe(18);

    billing.setQuickFilter('all');
    expect(billing.filteredRecords.value.length).toBe(248);
  });

  it('calculates page subtotal dynamically for currently visible page', () => {
    billing.setPerPage(10);
    const visible10 = billing.paginatedRecords.value;
    const expectedSum = visible10.reduce((acc, curr) => acc + curr.total_amount, 0);
    expect(billing.pageSubtotal.value).toBe(expectedSum);
  });

  it('handles row selection checkboxes', () => {
    const firstBill = billing.paginatedRecords.value[0];
    expect(billing.isRowSelected(firstBill.id)).toBe(false);

    billing.toggleSelectRow(firstBill.id);
    expect(billing.isRowSelected(firstBill.id)).toBe(true);

    billing.toggleSelectAll(true);
    expect(billing.areAllVisibleSelected.value).toBe(true);

    billing.toggleSelectAll(false);
    expect(billing.areAllVisibleSelected.value).toBe(false);
  });

  it('simulates cashier payment and status transition to Lunas', () => {
    const target = billing.billingList.value.find((b) => b.status === 'Siap Dibayar');
    expect(target).toBeDefined();

    const res = billing.processPayment(target.id, {
      amount: target.remaining_balance,
      method: 'Tunai'
    });

    expect(res.success).toBe(true);
    expect(target.status).toBe('Lunas');
    expect(target.remaining_balance).toBe(0);
    expect(target.payment_method).toBe('Tunai');
  });

  it('simulates claim verification and status transition to Siap Dibayar', () => {
    const target = billing.billingList.value.find((b) => b.status === 'Menunggu Verifikasi');
    expect(target).toBeDefined();

    const res = billing.verifyBill(target.id);
    expect(res.success).toBe(true);
    expect(target.status).toBe('Siap Dibayar');
    expect(target.sep_status).toBe('SEP Terverifikasi');
  });

  it('resets all filters back to default', () => {
    billing.searchQuery.value = 'Soedirman';
    billing.filters.status = 'Lunas';
    billing.setQuickFilter('general_today');
    billing.pagination.currentPage = 2;

    billing.resetFilters();

    expect(billing.searchQuery.value).toBe('');
    expect(billing.filters.status).toBe('Semua');
    expect(billing.activeQuickFilter.value).toBe('all');
    expect(billing.pagination.currentPage).toBe(1);
  });
});
