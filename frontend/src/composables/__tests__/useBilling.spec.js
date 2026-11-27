import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useBilling } from '../useBilling';
import { billingService } from '@/services/billing.service';
import { polyclinicService } from '@/services/polyclinic';

vi.mock('@/services/billing.service', () => ({
  billingService: {
    getBillings: vi.fn(),
    getBilling: vi.fn()
  }
}));

vi.mock('@/services/polyclinic', () => ({
  polyclinicService: {
    getPolyclinics: vi.fn()
  }
}));

const sampleApiBills = [
  {
    id: 101,
    visit_id: 101,
    admission_id: 501,
    patient_id: 901,
    billing_id: 701,
    invoice_number: 'INV-RJ-001',
    visit_number: 'RJ-001',
    queue_number: '001',
    service_date: '2026-11-20',
    service_time: '09:15 WIB',
    patient_name: 'Siti Aminah',
    medical_record_number: '000901',
    nik: '3174010101900001',
    gender: 'P',
    birth_date: '1990-01-01',
    polyclinic: 'Poli Penyakit Dalam',
    doctor: 'dr. Budi Santoso',
    guarantor_type: 'UMUM',
    guarantor_detail: 'UMUM',
    exam_status: 'Sedang Dilayani',
    pharmacy_status: 'Menunggu Obat',
    status: 'Belum Bayar',
    verified_subtotal: 150000,
    total_amount: 150000,
    unpriced_item_count: 1,
    item_count: 2,
    read_only_mode: true,
    payment_enabled: false
  },
  {
    id: 102,
    visit_id: 102,
    admission_id: 502,
    patient_id: 902,
    billing_id: 702,
    invoice_number: 'INV-RJ-002',
    visit_number: 'RJ-002',
    queue_number: '002',
    service_date: '2026-11-20',
    service_time: '10:00 WIB',
    patient_name: 'Rudi Hartono',
    medical_record_number: '000902',
    nik: '3174010202850002',
    gender: 'L',
    birth_date: '1985-02-02',
    polyclinic: 'Poliklinik Bedah',
    doctor: 'dr. Andi Wijaya',
    guarantor_type: 'BPJS Kesehatan',
    guarantor_detail: 'BPJS PBI',
    exam_status: 'Selesai Dilayani',
    pharmacy_status: 'Resep Selesai',
    status: 'Lunas',
    verified_subtotal: 250000,
    total_amount: 250000,
    unpriced_item_count: 0,
    item_count: 1,
    read_only_mode: true,
    payment_enabled: false
  }
];

describe('useBilling composable (Fase 2B.19A Read-Only API)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    billingService.getBillings.mockResolvedValue({
      data: {
        data: sampleApiBills.map((item) => ({ ...item })),
        meta: {
          current_page: 1,
          last_page: 1,
          per_page: 10,
          total: 2,
          from: 1,
          to: 2
        }
      }
    });
    billingService.getBilling.mockResolvedValue({
      data: {
        data: {
          ...sampleApiBills[0],
          items: [
            {
              id: 'TDK-1',
              source: 'trx_tindakan',
              order_date: '2026-11-20',
              jenis: 'TND',
              code: 'TND-01',
              name: 'Konsultasi Dokter Spesialis',
              qty: 1,
              price: 150000,
              discount: 0,
              discount_percent: 0,
              subtotal: 150000,
              is_verified_price: true,
              pricing_status: 'verified',
              status: 'Belum Bayar'
            },
            {
              id: 'RSP-1',
              source: 'trx_resep',
              order_date: '2026-11-20',
              jenis: 'RSP',
              code: 'RSP-1',
              name: 'Amoxicillin 500mg',
              qty: 10,
              price: null,
              discount: null,
              discount_percent: 0,
              subtotal: null,
              is_verified_price: false,
              pricing_status: 'unpriced',
              status: 'Belum Bayar'
            }
          ]
        }
      }
    });
    polyclinicService.getPolyclinics.mockResolvedValue({
      data: {
        data: [
          { id: 1, name: 'Poli Penyakit Dalam' },
          { id: 2, name: 'Poliklinik Bedah' }
        ]
      }
    });
  });

  it('starts empty without fabricated 248 mock records before fetching backend API', () => {
    const billing = useBilling();
    expect(billing.billingList.value).toEqual([]);
    expect(billing.pagination.total).toBe(0);
    expect(billing.statistics.value.ready).toBe(0);
  });

  it('fetches billings from backend API and computes verified subtotal & pagination metadata', async () => {
    const billing = useBilling();
    await billing.loadPolyclinics();
    await billing.fetchBillings();

    expect(billingService.getBillings).toHaveBeenCalledTimes(1);
    expect(billing.polyclinics.value).toEqual(['Poli Penyakit Dalam', 'Poliklinik Bedah']);
    expect(billing.billingList.value).toHaveLength(2);
    expect(billing.paginationInfo.value.total).toBe(2);
    expect(billing.pageSubtotal.value).toBe(400000);
    expect(billing.error.value).toBeNull();
  });

  it('handles empty backend response cleanly without falling back to mocks', async () => {
    billingService.getBillings.mockResolvedValueOnce({
      data: { data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 } }
    });

    const billing = useBilling();
    await billing.fetchBillings();

    expect(billing.billingList.value).toEqual([]);
    expect(billing.paginationInfo.value.total).toBe(0);
    expect(billing.pageSubtotal.value).toBe(0);
  });

  it('captures network, 401/403, and 404 errors cleanly without mock fallback', async () => {
    const billing = useBilling();

    billingService.getBillings.mockRejectedValueOnce(new Error('Network Error'));
    await billing.fetchBillings();
    expect(billing.error.value).toContain('Gagal terhubung ke server backend');
    expect(billing.billingList.value).toEqual([]);

    billingService.getBillings.mockRejectedValueOnce({
      response: { status: 403, data: { message: 'Forbidden access' } }
    });
    await billing.fetchBillings();
    expect(billing.error.value).toBe('Forbidden access');

    billingService.getBilling.mockRejectedValueOnce({
      response: { status: 404, data: { message: 'Visit billing not found' } }
    });
    const detailErr = await billing.fetchBillingDetail(999999);
    expect(detailErr.success).toBe(false);
    expect(detailErr.status).toBe(404);
    expect(detailErr.message).toBe('Visit billing not found');
  });

  it('fetches single visit billing detail preserving verified vs unpriced null items', async () => {
    const billing = useBilling();
    const res = await billing.fetchBillingDetail(101);

    expect(billingService.getBilling).toHaveBeenCalledWith(101);
    expect(res.success).toBe(true);
    expect(res.data.visit_id).toBe(101);
    expect(res.data.admission_id).toBe(501);
    expect(res.data.patient_id).toBe(901);
    expect(res.data.items[0].price).toBe(150000);
    expect(res.data.items[1].price).toBeNull();
    expect(res.data.items[1].pricing_status).toBe('unpriced');
  });

  it('applies quick filters, sorting, pagination, and resets filters against backend API', async () => {
    const billing = useBilling();
    await billing.fetchBillings();

    billing.setQuickFilter('tariff_adjustment');
    expect(billing.filteredRecords.value).toHaveLength(1);
    expect(billing.filteredRecords.value[0].visit_id).toBe(101);

    billing.setQuickFilter('all');
    await billing.setSort('patient_name');
    expect(billingService.getBillings).toHaveBeenLastCalledWith(expect.objectContaining({
      sort_by: 'patient_name',
      sort_direction: 'asc',
      page: 1
    }));

    billing.searchQuery.value = 'Siti';
    billing.filters.status = 'Belum Bayar';
    await billing.resetFilters();
    expect(billing.searchQuery.value).toBe('');
    expect(billing.filters.status).toBe('Semua');
    expect(billing.activeQuickFilter.value).toBe('all');
  });

  it('prevents fake payment, verification, or settlement status transitions locally (read-only guard)', async () => {
    const billing = useBilling();
    await billing.fetchBillings();
    const target = billing.billingList.value[0];

    expect(billing.processPayment(target.id, { amount: 150000 })).toEqual(
      expect.objectContaining({ success: false, disabled: true })
    );
    expect(billing.verifyBill(target.id)).toEqual(
      expect.objectContaining({ success: false, disabled: true })
    );
    expect(billing.settleRemainder(target.id)).toEqual(
      expect.objectContaining({ success: false, disabled: true })
    );
    expect(billing.notifyClinic(target.id)).toEqual(
      expect.objectContaining({ success: false, disabled: true })
    );
    expect(billing.saveBillOrders(target.id, [])).toEqual(
      expect.objectContaining({ success: false, disabled: true })
    );

    expect(target.status).toBe('Belum Bayar');
    expect(billing.billingList.value[0].status).not.toBe('Lunas');
  });
});
