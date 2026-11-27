import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import BillingView from '../BillingView.vue';
import BillingOrderView from '../BillingOrderView.vue';
import BillingDailyListCard from '../components/BillingDailyListCard.vue';
import BillingOrdersTab from '../components/BillingOrdersTab.vue';
import BillingTable from '../components/BillingTable.vue';
import BillingStatsCards from '../components/BillingStatsCards.vue';
import BillingQuickFilters from '../components/BillingQuickFilters.vue';
import BillingClosingShiftPanel from '../components/BillingClosingShiftPanel.vue';
import BillingPagination from '../components/BillingPagination.vue';
import { billingService } from '@/services/billing.service';
import { polyclinicService } from '@/services/polyclinic';

const mockPush = vi.fn();
const mockRoute = {
  params: { id: '2101' }
};

vi.mock('vue-router', () => ({
  useRoute: () => mockRoute,
  useRouter: () => ({
    push: mockPush
  })
}));

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
    id: 2101,
    visit_id: 2101,
    admission_id: 3001,
    patient_id: 4001,
    billing_id: 5001,
    invoice_number: 'INV-RJ-2101',
    visit_number: 'RJ-2101',
    queue_number: '001',
    service_date: '2026-11-20',
    service_time: '08:30:00',
    patient_name: 'Ahmad Pratama',
    medical_record_number: '000241',
    nik: '3174012004860001',
    gender: 'L',
    birth_place: 'Jakarta',
    birth_date: '1986-04-20',
    address: 'Jl. Melati No. 10',
    polyclinic: 'Poli Penyakit Dalam',
    doctor: 'dr. Budi Santoso',
    guarantor_type: 'UMUM',
    guarantor_detail: 'UMUM',
    exam_status: 'Sudah Dilayani',
    pharmacy_status: 'Tidak Ada Resep',
    status: 'Belum Bayar',
    verified_subtotal: 150000,
    total_amount: 150000,
    paid_amount: 0,
    remaining_balance: 150000,
    unpriced_item_count: 1,
    item_count: 2,
    read_only_mode: true,
    payment_enabled: false
  },
  {
    id: 2102,
    visit_id: 2102,
    admission_id: 3002,
    patient_id: 4002,
    billing_id: 5002,
    invoice_number: 'INV-RJ-2102',
    visit_number: 'RJ-2102',
    queue_number: '002',
    service_date: '2026-11-20',
    service_time: '09:45:00',
    patient_name: 'Siti Rahmawati',
    medical_record_number: '000242',
    nik: '3174011508920002',
    gender: 'P',
    birth_place: 'Bandung',
    birth_date: '1992-08-15',
    address: 'Jl. Kenanga No. 5',
    polyclinic: 'Poliklinik Bedah',
    doctor: 'dr. Hendra',
    guarantor_type: 'BPJS Kesehatan',
    guarantor_detail: 'BPJS Mandiri',
    exam_status: 'Sudah Dilayani',
    pharmacy_status: 'Resep Selesai',
    status: 'Lunas',
    verified_subtotal: 320000,
    total_amount: 320000,
    paid_amount: 320000,
    remaining_balance: 0,
    unpriced_item_count: 0,
    item_count: 1,
    read_only_mode: true,
    payment_enabled: false
  }
];

const sampleDetailResponse = {
  ...sampleApiBills[0],
  items: [
    {
      id: 'TDK-1',
      source: 'trx_tindakan',
      order_date: '2026-11-20',
      jenis: 'TND',
      code: 'TND-100',
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
      id: 'LAB-1',
      source: 'trx_lab',
      order_date: '2026-11-20',
      jenis: 'LAB',
      code: 'LAB-1',
      name: 'Darah Lengkap',
      qty: 1,
      price: null,
      discount: null,
      discount_percent: 0,
      subtotal: null,
      is_verified_price: false,
      pricing_status: 'unpriced',
      status: 'Belum Bayar'
    }
  ]
};

describe('BillingView & BillingOrderView (Fase 2B.19A Backend Read-Only Integration)', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockRoute.params.id = '2101';
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
          ...sampleDetailResponse,
          items: sampleDetailResponse.items.map((item) => ({ ...item }))
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

  it('loads billing list and polyclinics from backend API on mount without payment mutation', async () => {
    const wrapper = mount(BillingView, {
      global: {
        stubs: { AppToast: true }
      }
    });
    await flushPromises();

    expect(billingService.getBillings).toHaveBeenCalledTimes(1);
    expect(polyclinicService.getPolyclinics).toHaveBeenCalledTimes(1);

    expect(wrapper.text()).toContain('Billing Pasien');
    expect(wrapper.text()).toContain('DAFTAR PASIEN');
    expect(wrapper.find('.billing-daily-card tbody').text()).toContain('Ahmad Pratama');
    expect(wrapper.find('.billing-daily-card tbody').text()).toContain('Siti Rahmawati');

    const headers = wrapper
      .find('.billing-daily-card')
      .findAll('thead th')
      .map((th) => th.text().trim());

    expect(headers).toEqual([
      'No',
      'No. Antrian',
      'Waktu',
      'Nama',
      'Klinik',
      'Status Pemeriksaan',
      'Status Farmasi',
      'Status Bayar',
      'Aksi'
    ]);

    expect(wrapper.find('h1').text()).toBe('Billing Pasien');
    expect(wrapper.find('#billing-payment-status-filter option[value="Semua"]').text()).toBe('---- Semua ----');
    expect(wrapper.find('#billing-polyclinic-filter option[value="Semua Poliklinik"]').text()).toBe('---- Semua Klinik ----');
    expect(wrapper.find('#billing-guarantor-filter option[value="Semua Jenis Penjamin"]').text()).toBe('---- Semua Jaminan ----');
    expect(wrapper.find('#billing-polyclinic-filter').text()).toContain('Poli Penyakit Dalam');
  });

  it('filters billing list via backend API search & status filter and resets cleanly', async () => {
    const wrapper = mount(BillingView, {
      global: {
        stubs: { AppToast: true }
      }
    });
    await flushPromises();

    await wrapper.find('input#billing-search-input').setValue('Ahmad Pratama');
    await wrapper.find('select#billing-payment-status-filter').setValue('Belum Bayar');
    await wrapper.find('.billing-daily-card form').trigger('submit');
    await flushPromises();

    expect(billingService.getBillings).toHaveBeenLastCalledWith(expect.objectContaining({
      q: 'Ahmad Pratama',
      status: 'Belum Bayar'
    }));

    await wrapper.find('button.btn-reset-billing').trigger('click');
    await flushPromises();

    expect(wrapper.find('input#billing-search-input').element.value).toBe('');
    expect(wrapper.find('select#billing-payment-status-filter').element.value).toBe('Semua');
  });

  it('renders empty state, network error retry, and 401/403 authorization error on BillingView', async () => {
    billingService.getBillings.mockResolvedValueOnce({
      data: { data: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 } }
    });

    const emptyWrapper = mount(BillingView, {
      global: { stubs: { AppToast: true } }
    });
    await flushPromises();
    expect(emptyWrapper.find('.empty-title').text()).toContain('Tidak ada data billing pasien ditemukan.');

    billingService.getBillings.mockRejectedValueOnce(new Error('Network Error'));
    const errWrapper = mount(BillingView, {
      global: { stubs: { AppToast: true } }
    });
    await flushPromises();
    expect(errWrapper.find('.empty-title').text()).toContain('Gagal Memuat Data Tagihan');
    expect(errWrapper.find('.btn-retry-billing').exists()).toBe(true);

    billingService.getBillings.mockResolvedValueOnce({
      data: {
        data: [sampleApiBills[0]],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 1, from: 1, to: 1 }
      }
    });
    await errWrapper.find('.btn-retry-billing').trigger('click');
    await flushPromises();
    expect(errWrapper.find('.btn-retry-billing').exists()).toBe(false);
    expect(errWrapper.text()).toContain('Ahmad Pratama');

    billingService.getBillings.mockRejectedValueOnce({
      response: { status: 403, data: { message: 'Akses billing ditolak' } }
    });
    const forbiddenWrapper = mount(BillingView, {
      global: { stubs: { AppToast: true } }
    });
    await flushPromises();
    expect(forbiddenWrapper.find('.empty-desc').text()).toContain('Akses billing ditolak');
  });

  it('navigates to BillingOrderView using visit_id when clicking + action button', async () => {
    const wrapper = mount(BillingView, {
      global: {
        stubs: { AppToast: true }
      }
    });
    await flushPromises();

    await wrapper.find('.btn-action-bill').trigger('click');
    expect(mockPush).toHaveBeenCalledWith({
      name: 'clinical.billing-order',
      params: { id: 2101 }
    });
  });

  it('loads BillingOrderView detail from API, separates verified vs unpriced prices, and disables payment mutation', async () => {
    const wrapper = mount(BillingOrderView, {
      global: {
        stubs: { AppToast: true }
      }
    });
    await flushPromises();

    expect(billingService.getBilling).toHaveBeenCalledWith('2101');
    expect(wrapper.text()).toContain('Billing Order');
    expect(wrapper.find('.patient-hero-name').text()).toBe('Ahmad Pratama');
    expect(wrapper.find('.read-only-notice').exists()).toBe(true);

    const rows = wrapper.findAll('.orders-table tbody tr');
    expect(rows).toHaveLength(2);
    expect(rows[0].text()).toContain('Rp 150.000');
    expect(rows[0].find('.order-status-pill').text().trim()).toBe('belum bayar');
    expect(rows[1].text()).toContain('Tarif Belum Tersedia');

    // Read-only controls must be disabled
    expect(wrapper.find('.btn-submit-order').element.disabled).toBe(true);
    expect(wrapper.find('.btn-batal-bayar').element.disabled).toBe(true);
    expect(wrapper.find('.btn-delete-order').element.disabled).toBe(true);
    expect(wrapper.find('.row-discount-select').element.disabled).toBe(true);
    expect(wrapper.find('#billing-pay-amount').element.disabled).toBe(true);

    // Selecting both rows only sums verified tariff (Rp 150.000), ignoring null unpriced item
    const checkboxes = wrapper.findAll('.row-order-checkbox');
    await checkboxes[0].setValue(true);
    await checkboxes[1].setValue(true);
    expect(wrapper.find('#billing-subtotal-display').text()).toBe('Rp 150.000');

    // Triggering Submit or Batal Bayar does NOT turn row status into 'sudah bayar' or 'Lunas'
    await wrapper.find('.btn-submit-order').trigger('click');
    expect(rows[0].find('.order-status-pill').text().trim()).toBe('belum bayar');
    expect(wrapper.text()).not.toContain('sudah bayar');

    // Print modal works in read-only mode without LUNAS stamp for unpaid bill
    await wrapper.find('.btn-print-order').trigger('click');
    expect(wrapper.text()).toContain('PRATINJAU TAGIHAN (READ-ONLY)');
    expect(wrapper.find('.status-stamp').text().trim()).toBe('BELUM BAYAR');

    // Cancel navigates back cleanly
    await wrapper.find('.btn-cancel-order').trigger('click');
    expect(mockPush).toHaveBeenCalledWith({ name: 'clinical.billing' });
  });

  it('handles 404 error state and retry cleanly in BillingOrderView without mock[0] fallback', async () => {
    billingService.getBilling.mockRejectedValueOnce({
      response: { status: 404, data: { message: 'Kunjungan tidak ditemukan' } }
    });

    const wrapper = mount(BillingOrderView, {
      global: {
        stubs: { AppToast: true }
      }
    });
    await flushPromises();

    expect(wrapper.find('.billing-state-card--error').text()).toContain('Kunjungan tidak ditemukan');
    expect(wrapper.find('.orders-table').exists()).toBe(false);

    billingService.getBilling.mockResolvedValueOnce({
      data: { data: sampleDetailResponse }
    });
    await wrapper.find('.btn-retry-bill').trigger('click');
    await flushPromises();

    expect(wrapper.find('.billing-state-card--error').exists()).toBe(false);
    expect(wrapper.find('.patient-hero-name').text()).toBe('Ahmad Pratama');
  });
});

describe('Billing Rawat Jalan Components (Design System Unit Tests)', () => {
  it('handles sorting and action emits on BillingDailyListCard', async () => {
    const listWrapper = mount(BillingDailyListCard, {
      props: {
        records: sampleApiBills,
        totalCount: 2,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 2,
        searchQuery: '',
        filters: {
          customStartDate: '2026-11-20',
          customEndDate: '2026-11-20',
          status: 'Semua',
          polyclinic: 'Semua Poliklinik',
          guarantor: 'Semua Jenis Penjamin'
        },
        polyclinics: ['Poli Penyakit Dalam']
      }
    });

    await listWrapper.find('th.col-clinic').trigger('click');
    expect(listWrapper.emitted('sort')[0]).toEqual(['klinik']);

    await listWrapper.find('.btn-action-bill').trigger('click');
    expect(listWrapper.emitted('open-bill')[0][0].visit_id).toBe(2101);

    await listWrapper.find('.btn-action-call').trigger('click');
    expect(listWrapper.emitted('call-patient')[0][0].visit_id).toBe(2101);
  });

  it('renders BillingDailyListCard status pills and dash fallback for missing guarantor', async () => {
    const sampleRecord = {
      ...sampleApiBills[0],
      queue_number: '001',
      service_date: '2026-11-11',
      service_time: '10:21 WIB',
      patient_name: 'Seto Bayu Aji',
      medical_record_number: '000344',
      gender: 'L',
      birth_date: '1999-09-09',
      guarantor_type: '',
      polyclinic: 'Poliklinik Bedah',
      exam_status: 'Belum Dilayani',
      pharmacy_status: 'Tidak Ada Resep',
      status: 'Belum Bayar'
    };

    const wrapper = mount(BillingDailyListCard, {
      props: {
        searchQuery: '',
        records: [sampleRecord],
        totalCount: 1,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 1,
        filters: {
          customStartDate: '2026-11-11',
          customEndDate: '2026-11-12',
          status: 'Semua',
          polyclinic: 'Semua Poliklinik',
          guarantor: 'Semua Jenis Penjamin'
        },
        polyclinics: ['Poliklinik Bedah']
      }
    });

    expect(wrapper.text()).toContain('11 November 2026');
    expect(wrapper.text()).toContain('10:21:00');
    expect(wrapper.find('.patient-name').text()).toBe('Seto Bayu Aji');
    expect(wrapper.find('.patient-meta-italic').text()).toBe('000344 | L | 1999-09-09 | —');
    expect(wrapper.text()).toContain('Poliklinik Bedah');
    expect(wrapper.text()).toContain('Belum Bayar');
  });

  it('preserves padded Indonesian long dates across boundaries, SQL/ISO timestamps, empty & invalid values in BillingDailyListCard', () => {
    const dateCases = [
      { id: 1, service_date: '2026-11-05', patient_name: 'LeadZero', medical_record_number: '01', gender: 'L', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 2, service_date: '2024-02-29', patient_name: 'LeapYear', medical_record_number: '02', gender: 'P', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 3, service_date: '2025-12-31', patient_name: 'YearEnd', medical_record_number: '03', gender: 'L', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 4, service_date: '2026-01-01', patient_name: 'YearStart', medical_record_number: '04', gender: 'P', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 5, service_date: '2026-08-05 14:30:00', patient_name: 'SqlNaive', medical_record_number: '05', gender: 'L', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 6, service_date: '2026-03-09T15:00:00+07:00', patient_name: 'IsoOffset', medical_record_number: '06', gender: 'P', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 7, service_date: null, patient_name: 'NullDate', medical_record_number: '07', gender: 'L', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 8, service_date: undefined, patient_name: 'UndefDate', medical_record_number: '08', gender: 'P', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 9, service_date: '', patient_name: 'EmptyDate', medical_record_number: '09', gender: 'L', birth_date: '1990-01-01', polyclinic: 'Poli A' },
      { id: 10, service_date: '2026-02-30', patient_name: 'InvalidDate', medical_record_number: '10', gender: 'P', birth_date: '1990-01-01', polyclinic: 'Poli A' }
    ];

    const wrapper = mount(BillingDailyListCard, {
      props: {
        records: dateCases,
        totalCount: dateCases.length,
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        from: 1,
        to: 10,
        searchQuery: '',
        filters: {
          customStartDate: '',
          customEndDate: '',
          status: 'Semua',
          polyclinic: 'Semua Poliklinik',
          guarantor: 'Semua Jenis Penjamin'
        },
        polyclinics: ['Poli A']
      }
    });

    const dateCells = wrapper.findAll('.visit-date').map((el) => el.text().trim());
    expect(dateCells).toEqual([
      '05 November 2026',
      '29 Februari 2024',
      '31 Desember 2025',
      '01 Januari 2026',
      '05 Agustus 2026',
      '09 Maret 2026',
      '—',
      '—',
      '—',
      '2026-02-30'
    ]);
  });

  it('formats order dates with leading zero, preserves search by formatted date, empty hyphen fallback, and unpriced guard in BillingOrdersTab', async () => {
    const orders = [
      { id: 'O-1', order_date: '2026-11-05', jenis: 'TND', name: 'Konsultasi', qty: 1, price: 100000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-2', order_date: '2024-02-29', jenis: 'LAB', name: 'Kabisat Test', qty: 1, price: null, pricing_status: 'unpriced', discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-3', order_date: '2025-12-31', jenis: 'RAD', name: 'Akhir Tahun', qty: 1, price: 200000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-4', order_date: '2026-01-01', jenis: 'ADM', name: 'Awal Tahun', qty: 1, price: 25000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-5', order_date: '2026-04-03 10:15:00', jenis: 'RSP', name: 'SQL Datetime', qty: 1, price: 50000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-6', order_date: '2026-06-07T15:00:00+07:00', jenis: 'AMB', name: 'ISO Offset', qty: 1, price: 300000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-7', order_date: null, jenis: 'TND', name: 'Tanggal Null', qty: 1, price: 10000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-8', order_date: '', jenis: 'TND', name: 'Tanggal Kosong', qty: 1, price: 10000, discount_percent: 0, status: 'Belum Bayar' },
      { id: 'O-9', order_date: '2026-02-30', jenis: 'TND', name: 'Tanggal Invalid', qty: 1, price: 10000, discount_percent: 0, status: 'Belum Bayar' }
    ];

    const wrapper = mount(BillingOrdersTab, {
      props: {
        orders,
        polyclinics: ['Poli Penyakit Dalam'],
        readOnlyMode: true,
        unpricedCount: 1
      }
    });

    const dateCells = wrapper.findAll('.orders-table td.col-date').map((td) => td.text().trim());
    expect(dateCells).toEqual([
      '05 November 2026',
      '29 Februari 2024',
      '31 Desember 2025',
      '01 Januari 2026',
      '03 April 2026',
      '07 Juni 2026',
      '-',
      '-',
      '2026-02-30'
    ]);
    expect(wrapper.findAll('.orders-table tbody tr')[1].text()).toContain('Tarif Belum Tersedia');

    await wrapper.find('#billing-orders-search').setValue('05 november');
    const filteredRows = wrapper.findAll('.orders-table tbody tr');
    expect(filteredRows).toHaveLength(1);
    expect(filteredRows[0].find('td.col-item-name').text()).toBe('Konsultasi');
  });

  describe('BillingTable', () => {
    it('renders the 10 columns in EXACT required order and displays badges', () => {
      const wrapper = mount(BillingTable, {
        props: {
          records: sampleApiBills,
          totalCount: 2,
          pageSubtotal: 470000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      const thList = wrapper.findAll('thead th').map((th) => th.text().trim());
      expect(thList).toEqual([
        '',
        '1. Nomor Tagihan',
        '2. Waktu Pelayanan',
        '3. No. RM & Pasien',
        '4. Poliklinik & DPJP',
        '5. Penjamin',
        '6. Total Tagihan',
        '7. Tanggungan Pasien',
        '8. Status',
        '9. Aksi Kasir'
      ]);
      expect(wrapper.find('.count-badge').text()).toContain('Ditemukan 2 data tagihan');
      expect(wrapper.find('.subtotal-badge').text()).toContain('Rp 470.000');
    });
  });

  describe('BillingStatsCards', () => {
    it('renders 5 statistic cards with labels and numbers', () => {
      const wrapper = mount(BillingStatsCards, {
        props: {
          stats: {
            waiting: 18,
            inhealthClaims: 4,
            bpjsClaims: 14,
            ready: 24,
            elderlyReady: 6,
            cashEdcReady: 18,
            paid: 193,
            completionRatio: '91,2%',
            pending: 9,
            totalExcess: 3420000,
            todayIncome: 48250000,
            yesterdayIncome: 42180000,
            growth: '+14,2%'
          }
        }
      });

      expect(wrapper.text()).toContain('Menunggu Verifikasi');
      expect(wrapper.text()).toContain('Siap Dibayar');
      expect(wrapper.text()).toContain('Lunas Hari Ini');
      expect(wrapper.text()).toContain('Belum Lunas');
      expect(wrapper.text()).toContain('Pendapatan Hari Ini');
      expect(wrapper.text()).toContain('Rp 48.250.000');
    });
  });

  describe('BillingQuickFilters', () => {
    it('renders all 5 quick filter segmented chips with counts and emits on click', async () => {
      const wrapper = mount(BillingQuickFilters, {
        props: {
          activeFilter: 'all',
          counts: {
            all: 2,
            ready_cashier: 1,
            verify_bpjs: 0,
            general_today: 1,
            tariff_adjustment: 1
          }
        }
      });

      expect(wrapper.text()).toContain('Semua Tagihan (2)');
      await wrapper.findAll('.quick-chip')[1].trigger('click');
      expect(wrapper.emitted('select')[0]).toEqual(['ready_cashier']);
    });
  });

  describe('BillingClosingShiftPanel', () => {
    it('renders shift details and action buttons', async () => {
      const wrapper = mount(BillingClosingShiftPanel, {
        props: {
          shift: {
            shift_title: 'Validasi Closing Shift Kasir #1',
            shift_time: '07:00 – 14:00 WIB',
            cashier_username: 'Kasir_Poli_04',
            cashier_fullname: 'Rina Kasir'
          }
        }
      });

      expect(wrapper.text()).toContain('Validasi Closing Shift Kasir #1');
      const buttons = wrapper.findAll('button');
      await buttons[0].trigger('click');
      expect(wrapper.emitted('reconcile')).toBeTruthy();
      await buttons[1].trigger('click');
      expect(wrapper.emitted('handover')).toBeTruthy();
    });
  });

  describe('BillingPagination', () => {
    it('renders pagination info and handles page change', async () => {
      const wrapper = mount(BillingPagination, {
        props: {
          currentPage: 1,
          lastPage: 2,
          perPage: 10,
          total: 15,
          from: 1,
          to: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Menampilkan 1–10 dari 15 data tagihan');
      await wrapper.find('.btn-next').trigger('click');
      expect(wrapper.emitted('page-change')[0]).toEqual([2]);
    });
  });
});
