import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import BillingView from '../BillingView.vue';
import BillingOrderView from '../BillingOrderView.vue';
import BillingDailyListCard from '../components/BillingDailyListCard.vue';
import BillingTable from '../components/BillingTable.vue';
import BillingStatsCards from '../components/BillingStatsCards.vue';
import BillingQuickFilters from '../components/BillingQuickFilters.vue';
import BillingClosingShiftPanel from '../components/BillingClosingShiftPanel.vue';
import BillingPagination from '../components/BillingPagination.vue';
import { generateMockBillingRecords, mockCashierShift } from '@/mocks/billing';

const mockPush = vi.fn();
vi.mock('vue-router', () => ({
  useRoute: () => ({
    params: { id: 'BILL-0001' }
  }),
  useRouter: () => ({
    push: mockPush
  })
}));

beforeEach(() => {
  mockPush.mockClear();
});

describe('BillingView (Halaman Awal Billing Terbaru)', () => {
  it('renders Billing Pasien header, 2-col filter form, and 9 standardized columns', () => {
    const wrapper = mount(BillingView, {
      global: {
        stubs: {
          AppToast: true
        }
      }
    });

    expect(wrapper.text()).toContain('Billing Pasien');
    expect(wrapper.text()).toContain('Home');
    expect(wrapper.text()).toContain('DAFTAR PASIEN');

    expect(wrapper.find('input#billing-search-input').exists()).toBe(true);
    expect(wrapper.find('input#billing-start-date').exists()).toBe(true);
    expect(wrapper.find('input#billing-end-date').exists()).toBe(true);
    expect(wrapper.find('select#billing-payment-status-filter').exists()).toBe(true);
    expect(wrapper.find('select#billing-polyclinic-filter').exists()).toBe(true);
    expect(wrapper.find('select#billing-guarantor-filter').exists()).toBe(true);
    expect(wrapper.find('button.btn-search-billing').text()).toContain('Cari Data Pasien');
    expect(wrapper.find('button.btn-reset-billing').text()).toContain('Reset Filter');
    expect(wrapper.find('select#billing-records-perpage').exists()).toBe(true);

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

    expect(wrapper.find('.btn-action-bill').exists()).toBe(true);
    expect(wrapper.find('.btn-action-call').exists()).toBe(true);
  });

  it('filters billing list by patient keyword and payment status and resets cleanly', async () => {
    const wrapper = mount(BillingView, {
      global: {
        stubs: {
          AppToast: true
        }
      }
    });

    await wrapper.find('input#billing-search-input').setValue('Ahmad Pratama');
    expect(wrapper.find('.billing-daily-card tbody').text()).toContain('Ahmad Pratama');

    await wrapper.find('input#billing-search-input').setValue('');
    await wrapper.find('select#billing-payment-status-filter').setValue('Belum Bayar');
    const paymentLabels = wrapper
      .findAll('.billing-daily-card tbody tr td:nth-child(8) .status-pill')
      .map((el) => el.text().trim());
    expect(paymentLabels.length).toBeGreaterThan(0);
    expect(paymentLabels.every((lbl) => lbl === 'Belum Bayar')).toBe(true);

    await wrapper.find('button.btn-reset-billing').trigger('click');
    expect(wrapper.find('input#billing-search-input').element.value).toBe('');
    expect(wrapper.find('select#billing-payment-status-filter').element.value).toBe('Semua');
  });

  it('handles sorting and action emits on BillingDailyListCard', async () => {
    const sample = generateMockBillingRecords().slice(0, 3);
    const listWrapper = mount(BillingDailyListCard, {
      props: {
        records: sample,
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
    expect(listWrapper.emitted('open-bill')[0][0].id).toBe(sample[0].id);

    await listWrapper.find('.btn-action-call').trigger('click');
    expect(listWrapper.emitted('call-patient')[0][0].id).toBe(sample[0].id);
  });
});

describe('Billing Rawat Jalan Components', () => {
  const mockRecords = generateMockBillingRecords();

  describe('BillingTable', () => {
    it('renders the 10 columns in EXACT required order', () => {
      const wrapper = mount(BillingTable, {
        props: {
          records: mockRecords.slice(0, 5),
          totalCount: 5,
          pageSubtotal: 3500000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      const thList = wrapper.findAll('thead th').map((th) => th.text().trim());

      // Expected strict columns from Section 8:
      // 1. Checkbox Pilih Data
      // 2. Nomor Tagihan
      // 3. Waktu Pelayanan
      // 4. No. RM & Pasien
      // 5. Poliklinik & DPJP
      // 6. Penjamin
      // 7. Total Tagihan
      // 8. Tanggungan Pasien
      // 9. Status
      // 10. Aksi Kasir
      expect(thList).toEqual([
        '', // Checkbox
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
    });

    it('displays the found count and formatted page subtotal', () => {
      const wrapper = mount(BillingTable, {
        props: {
          records: mockRecords.slice(0, 10),
          totalCount: 248,
          pageSubtotal: 5485000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      expect(wrapper.find('.count-badge').text()).toContain('Ditemukan 248 data tagihan');
      expect(wrapper.find('.subtotal-badge').text()).toContain('Rp 5.485.000');
    });

    it('renders contextual action buttons based on status', () => {
      const sample = [
        { ...mockRecords[0], status: 'Siap Dibayar' },
        { ...mockRecords[1], status: 'Lunas' },
        { ...mockRecords[2], status: 'Menunggu Verifikasi' },
        { ...mockRecords[3], status: 'Dibayar Sebagian' },
        { ...mockRecords[4], status: 'Belum Lengkap' },
        { ...mockRecords[5], status: 'Dibatalkan' }
      ];

      const wrapper = mount(BillingTable, {
        props: {
          records: sample,
          totalCount: 6,
          pageSubtotal: 3000000,
          currentPage: 1,
          perPage: 10,
          loading: false,
          selectedIds: new Set(),
          areAllVisibleSelected: false
        }
      });

      expect(wrapper.find('.btn-pay').exists()).toBe(true);
      expect(wrapper.find('.btn-receipt').exists()).toBe(true);
      expect(wrapper.find('.btn-verify').exists()).toBe(true);
      expect(wrapper.find('.btn-settle').exists()).toBe(true);
      expect(wrapper.find('.btn-notif-poli').exists()).toBe(true);
      expect(wrapper.find('.btn-cancel-note').exists()).toBe(true);
    });
  });

  describe('BillingStatsCards', () => {
    it('renders 5 statistic cards with labels and numbers', () => {
      const stats = {
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
      };

      const wrapper = mount(BillingStatsCards, {
        props: { stats }
      });

      expect(wrapper.text()).toContain('Menunggu Verifikasi');
      expect(wrapper.text()).toContain('18');
      expect(wrapper.text()).toContain('Siap Dibayar');
      expect(wrapper.text()).toContain('24');
      expect(wrapper.text()).toContain('Lunas Hari Ini');
      expect(wrapper.text()).toContain('193');
      expect(wrapper.text()).toContain('Belum Lunas');
      expect(wrapper.text()).toContain('9');
      expect(wrapper.text()).toContain('Pendapatan Hari Ini');
      expect(wrapper.text()).toContain('Rp 48.250.000');
    });
  });

  describe('BillingQuickFilters', () => {
    it('renders all 5 quick filter segmented chips with counts and emits on click', async () => {
      const counts = {
        all: 248,
        ready_cashier: 24,
        verify_bpjs: 18,
        general_today: 57,
        tariff_adjustment: 3
      };

      const wrapper = mount(BillingQuickFilters, {
        props: {
          activeFilter: 'all',
          counts
        }
      });

      expect(wrapper.text()).toContain('Semua Tagihan (248)');
      expect(wrapper.text()).toContain('Siap Bayar Kasir (24)');
      expect(wrapper.text()).toContain('Verifikasi BPJS (18)');
      expect(wrapper.text()).toContain('Pasien Umum Hari Ini (57)');
      expect(wrapper.text()).toContain('Perlu Penyesuaian Tarif (3)');

      const chips = wrapper.findAll('.quick-chip');
      await chips[1].trigger('click');
      expect(wrapper.emitted('select')[0]).toEqual(['ready_cashier']);
    });
  });

  describe('BillingClosingShiftPanel', () => {
    it('renders shift details and action buttons', async () => {
      const wrapper = mount(BillingClosingShiftPanel, {
        props: {
          shift: mockCashierShift
        }
      });

      expect(wrapper.text()).toContain('Validasi Closing Shift Kasir #1');
      expect(wrapper.text()).toContain('07:00 – 14:00 WIB');
      expect(wrapper.text()).toContain('Kasir_Poli_04');

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
          lastPage: 25,
          perPage: 10,
          total: 248,
          from: 1,
          to: 10,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Menampilkan 1–10 dari 248 data tagihan');
      const nextBtn = wrapper.find('.btn-next');
      await nextBtn.trigger('click');
      expect(wrapper.emitted('page-change')[0]).toEqual([2]);
    });
  });

  describe('BillingDailyListCard UI Standardization', () => {
    it('renders standardized default dropdown labels, periode captions, and action controls', () => {
      const wrapper = mount(BillingView);

      expect(wrapper.find('h1').text()).toBe('Billing Pasien');
      expect(wrapper.find('#billing-payment-status-filter option[value="Semua"]').text()).toBe('---- Semua ----');
      expect(wrapper.find('#billing-polyclinic-filter option[value="Semua Poliklinik"]').text()).toBe('---- Semua Klinik ----');
      expect(wrapper.find('#billing-guarantor-filter option[value="Semua Jenis Penjamin"]').text()).toBe('---- Semua Jaminan ----');

      expect(wrapper.text()).toContain('*) tanggal awal');
      expect(wrapper.text()).toContain('*) tanggal akhir');
      expect(wrapper.find('.btn-search-billing').text()).toContain('Cari Data Pasien');
      expect(wrapper.find('.btn-reset-billing').text()).toContain('Reset Filter');
      expect(wrapper.find('.records-label').text()).toBe('records');
    });

    it('renders BillingDailyListCard status pills, patient meta format, and emits row actions', async () => {
      const sampleRecord = {
        ...generateMockBillingRecords()[0],
        queue_number: '001',
        service_date: '2026-11-11',
        service_time: '10:21 WIB',
        patient_name: 'Seto Bayu Aji',
        medical_record_number: '000344',
        gender: 'L',
        birth_date: '1999-09-09',
        guarantor_type: 'Pasien Umum / Tunai',
        polyclinic: 'Poliklinik Bedah',
        exam_status: 'Belum Dilayani',
        pharmacy_status: 'Tidak Ada Resep',
        status: 'Siap Dibayar'
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
      expect(wrapper.find('.patient-meta-italic').text()).toBe('000344 | L | 1999-09-09 | UMUM');
      expect(wrapper.text()).toContain('Poliklinik Bedah');
      expect(wrapper.text()).toContain('Belum Dilayani');
      expect(wrapper.text()).toContain('Tidak Ada Resep');
      expect(wrapper.text()).toContain('Belum Bayar');

      await wrapper.find('.btn-action-bill').trigger('click');
      expect(wrapper.emitted('open-bill')[0]).toEqual([sampleRecord]);

      await wrapper.find('.btn-action-call').trigger('click');
      expect(wrapper.emitted('call-patient')[0]).toEqual([sampleRecord]);
    });

    it('navigates to BillingOrderView when clicking + button on BillingView', async () => {
      const wrapper = mount(BillingView, {
        global: {
          stubs: { AppToast: true }
        }
      });

      await wrapper.find('.btn-action-bill').trigger('click');
      expect(mockPush).toHaveBeenCalledWith({
        name: 'clinical.billing-order',
        params: { id: 'BILL-0001' }
      });
    });
  });

  describe('BillingOrderView (Halaman Detail Billing Order)', () => {
    it('renders patient banner, 6 tabs, LIST ORDERS table, payment summary boxes, and footer controls', () => {
      const wrapper = mount(BillingOrderView, {
        global: {
          stubs: { AppToast: true }
        }
      });

      expect(wrapper.text()).toContain('Billing Order');
      expect(wrapper.find('.patient-hero-name').text()).toBe('Ahmad Pratama');
      expect(wrapper.text()).toContain('Kunjungan RS');
      expect(wrapper.find('.btn-call-patient').text()).toContain('Panggil');

      const tabLabels = wrapper.findAll('.billing-tab-btn').map((btn) => btn.text().trim());
      expect(tabLabels).toEqual(['Orders', 'Tindakan', 'Resep', 'Laboratorium', 'Radiologi', 'Ambulance']);

      expect(wrapper.text()).toContain('LIST ORDERS');
      expect(wrapper.find('select#billing-orders-type-filter').element.value).toBe('All');
      expect(wrapper.find('select#billing-orders-perpage').exists()).toBe(true);
      expect(wrapper.find('input#billing-orders-search').exists()).toBe(true);

      const orderHeaders = wrapper
        .find('.orders-table')
        .findAll('thead th')
        .map((th) => th.text().replace(/\s+/g, ' ').trim());
      expect(orderHeaders[1]).toContain('Tgl Order');
      expect(orderHeaders[2]).toContain('Jenis');
      expect(orderHeaders[3]).toContain('Nama');
      expect(orderHeaders[4]).toContain('Jumlah');
      expect(orderHeaders[5]).toContain('Harga');
      expect(orderHeaders[6]).toContain('Diskon');
      expect(orderHeaders[7]).toContain('Total');
      expect(orderHeaders[8]).toContain('Status');
      expect(orderHeaders[9]).toBe('Aksi');

      expect(wrapper.find('#billing-subtotal-display').text()).toBe('Rp 0');
      expect(wrapper.find('#billing-global-discount').exists()).toBe(true);
      expect(wrapper.find('#billing-grand-total').exists()).toBe(true);
      expect(wrapper.find('#billing-pay-amount').exists()).toBe(true);
      expect(wrapper.find('#billing-change-amount').exists()).toBe(true);
      expect(wrapper.find('#billing-payment-note').exists()).toBe(true);
      expect(wrapper.find('#billing-payment-method').exists()).toBe(true);
      expect(wrapper.find('#billing-payment-time').exists()).toBe(true);
      expect(wrapper.find('#billing-order-polyclinic').exists()).toBe(true);
      expect(wrapper.find('.btn-batal-bayar').text()).toBe('Batal Bayar');
      expect(wrapper.find('.btn-submit-order').text()).toBe('Submit');
      expect(wrapper.find('.btn-print-order').text()).toBe('Print');
      expect(wrapper.find('.btn-cancel-order').text()).toBe('Cancel');
    });

    it('calculates totals, applies row discount, submits payment, reverts payment, adds category item, prints, and cancels', async () => {
      const wrapper = mount(BillingOrderView, {
        global: {
          stubs: { AppToast: true }
        }
      });

      const firstCheckbox = wrapper.find('.row-order-checkbox');
      await firstCheckbox.setValue(true);

      // Default ADM item is Rp 5.000
      expect(wrapper.find('#billing-subtotal-display').text()).toBe('Rp 5.000');
      expect(wrapper.find('#billing-grand-total').element.value).toBe('5000');

      // Apply 20% row discount -> Rp 4.000
      await wrapper.find('.row-discount-select').setValue('20');
      expect(wrapper.find('#billing-subtotal-display').text()).toBe('Rp 4.000');
      expect(wrapper.find('#billing-grand-total').element.value).toBe('4000');

      // Pay Rp 10.000 -> Kembali Rp 6.000
      await wrapper.find('#billing-pay-amount').setValue(10000);
      expect(wrapper.find('#billing-change-amount').element.value).toBe('6000');

      // Submit payment -> first row becomes 'sudah bayar'
      await wrapper.find('.btn-submit-order').trigger('click');
      expect(wrapper.find('.orders-table tbody tr .order-status-pill').text().trim()).toBe('sudah bayar');

      // Batal Bayar -> reverts back to 'belum bayar'
      await wrapper.find('.btn-batal-bayar').trigger('click');
      expect(wrapper.find('.orders-table tbody tr .order-status-pill').text().trim()).toBe('belum bayar');

      // Switch to Ambulance tab & add item into LIST ORDERS
      const tabBtns = wrapper.findAll('.billing-tab-btn');
      await tabBtns[5].trigger('click');
      expect(wrapper.text()).toContain('Ambulance');
      await wrapper.find('#cat-name-AMB').setValue('Ambulance Dalam Kota Custom');
      await wrapper.find('#cat-price-AMB').setValue(250000);
      await wrapper.find('.cat-add-form').trigger('submit');

      await tabBtns[0].trigger('click');
      expect(wrapper.find('.orders-table tbody').text()).toContain('Ambulance Dalam Kota Custom');

      // Print opens BillingReceiptModal
      await wrapper.find('.btn-print-order').trigger('click');
      expect(wrapper.text()).toContain('Pratinjau Kuitansi Pembayaran Rawat Jalan');

      // Cancel navigates back to clinical.billing
      await wrapper.find('.btn-cancel-order').trigger('click');
      expect(mockPush).toHaveBeenCalledWith({ name: 'clinical.billing' });
    });
  });
});
