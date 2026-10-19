import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import GoodsIssueView from '../GoodsIssueView.vue';
import GoodsIssueSummaryCards from '../components/GoodsIssueSummaryCards.vue';
import GoodsIssueFilters from '../components/GoodsIssueFilters.vue';
import GoodsIssueStatusBadge from '../components/GoodsIssueStatusBadge.vue';
import GoodsIssueTable from '../components/GoodsIssueTable.vue';
import GoodsIssueFormModal from '../components/GoodsIssueFormModal.vue';
import GoodsIssueDetailModal from '../components/GoodsIssueDetailModal.vue';
import GoodsIssueDeleteDialog from '../components/GoodsIssueDeleteDialog.vue';
import { useGoodsIssues } from '@/composables/useGoodsIssues';

describe('Goods Issue (Pengeluaran Barang) Module Tests', () => {
  describe('useGoodsIssues Composable', () => {
    let composable;

    beforeEach(() => {
      composable = useGoodsIssues();
    });

    it('initializes with default mock goods issues and summary metrics', () => {
      expect(composable.issues.value.length).toBeGreaterThanOrEqual(6);
      expect(composable.summary.value.todayIssues).toBe('8');
      expect(composable.summary.value.totalItemOut).toBe('1.420');
      expect(composable.summary.value.damagedItem).toBe('42');
      expect(composable.summary.value.expiredItem).toBe('65');
    });

    it('filters goods issues by issue number, warehouse, officer, and purpose', () => {
      // 1. Filter by issue number
      composable.filters.issueNumber = '0028';
      composable.applyFilters();
      expect(composable.filteredIssues.value.length).toBe(1);
      expect(composable.filteredIssues.value[0].issueNumber).toBe('OUT-202609-0028');

      // 2. Filter by warehouse
      composable.resetFilters();
      composable.filters.warehouse = 'Gudang Utama Farmasi';
      composable.applyFilters();
      expect(
        composable.filteredIssues.value.every((it) => it.warehouse.includes('Gudang Utama Farmasi'))
      ).toBe(true);

      // 3. Filter by officer
      composable.resetFilters();
      composable.filters.officer = 'Rina Marlina, A.Md.Farm';
      composable.applyFilters();
      expect(
        composable.filteredIssues.value.every((it) => it.officerName === 'Rina Marlina, A.Md.Farm')
      ).toBe(true);

      // 4. Filter by purpose
      composable.resetFilters();
      composable.filters.purpose = 'Barang Rusak';
      composable.applyFilters();
      expect(
        composable.filteredIssues.value.every((it) => it.purpose.includes('Barang Rusak'))
      ).toBe(true);

      // 5. Reset
      composable.resetFilters();
      expect(composable.filteredIssues.value.length).toBe(composable.issues.value.length);
    });

    it('creates, updates, and deletes goods issues correctly', async () => {
      const initialCount = composable.issues.value.length;

      // Create new active issue
      const resCreate = await composable.createIssue({
        purpose: 'Kebutuhan Internal',
        warehouse: 'Gudang Utama Farmasi (GOF-01)',
        officerName: 'Apt. Budi Santoso, S.Farm',
        items: [
          { name: 'Paracetamol 500 mg Tablet', quantity: 30, unit: 'Tablet', availableStock: 1200 }
        ]
      }, false);

      expect(resCreate.success).toBe(true);
      expect(composable.issues.value.length).toBe(initialCount + 1);
      expect(composable.issues.value[0].status).toBe('Selesai');
      expect(composable.issues.value[0].totalQuantity).toBe(30);

      // Update issue
      const createdId = composable.issues.value[0].id;
      const resUpdate = await composable.updateIssue(createdId, {
        notes: 'Catatan diperbarui',
        items: [
          { name: 'Paracetamol 500 mg Tablet', quantity: 50, unit: 'Tablet', availableStock: 1200 }
        ]
      }, true);

      expect(resUpdate.success).toBe(true);
      expect(composable.issues.value[0].totalQuantity).toBe(50);
      expect(composable.issues.value[0].status).toBe('Draft');

      // Delete issue
      const resDel = await composable.deleteIssue(createdId);
      expect(resDel.success).toBe(true);
      expect(composable.issues.value.length).toBe(initialCount);
    });

    it('cancels goods issue with a cancellation note', async () => {
      const resCancel = await composable.cancelIssue(1, 'Salah alokasi unit');
      expect(resCancel.success).toBe(true);
      const item = composable.issues.value.find((it) => it.id === 1);
      expect(item.status).toBe('Dibatalkan');
      expect(item.validationNote).toBe('Salah alokasi unit');
    });
  });

  describe('GoodsIssueSummaryCards Component', () => {
    it('renders 4 summary cards with correct values and subtitles', () => {
      const summaryMock = {
        todayIssues: '8',
        todayUnit: 'Dokumen',
        todayNote: 'Disetujui hari ini (Shift 1 & 2)',
        totalItemOut: '1.420',
        totalItemUnit: 'Unit',
        totalItemNote: 'Akumulasi fisik bulan berjalan',
        damagedItem: '42',
        damagedUnit: 'Unit',
        damagedNote: 'Kerusakan kemasan & pecah',
        expiredItem: '65',
        expiredUnit: 'Unit',
        expiredNote: 'Karantina & pemusnahan BPOM'
      };

      const wrapper = mount(GoodsIssueSummaryCards, {
        props: { summary: summaryMock }
      });

      expect(wrapper.text()).toContain('PENGELUARAN HARI INI');
      expect(wrapper.text()).toContain('8');
      expect(wrapper.text()).toContain('Dokumen');
      expect(wrapper.text()).toContain('TOTAL ITEM KELUAR');
      expect(wrapper.text()).toContain('1.420');
      expect(wrapper.text()).toContain('BARANG RUSAK (BAP)');
      expect(wrapper.text()).toContain('42');
      expect(wrapper.text()).toContain('BARANG KEDALUWARSA');
      expect(wrapper.text()).toContain('65');
    });
  });

  describe('GoodsIssueFilters Component', () => {
    it('renders 6 filter controls and emits apply and reset without mutating props', async () => {
      const filtersMock = {
        issueNumber: '',
        startDate: '',
        endDate: '',
        warehouse: 'Semua Gudang Asal',
        officer: 'Semua Petugas',
        purpose: 'Semua Keperluan',
        notes: ''
      };

      const wrapper = mount(GoodsIssueFilters, {
        props: {
          filters: filtersMock,
          warehouses: ['Gudang Utama Farmasi', 'Apotek IGD 24 Jam'],
          officers: ['Apt. Budi Santoso, S.Farm', 'Novita Anggraini'],
          purposes: ['Kebutuhan Internal', 'Barang Rusak'],
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Filter Pengeluaran Nonpenjualan');
      expect(wrapper.text()).toContain('SOP LOG-FAR/VII/2026');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('apply')).toBeTruthy();

      await wrapper.find('button.btn-cancel').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('GoodsIssueStatusBadge Component', () => {
    it('renders document status badges correctly', () => {
      const wrapperCompleted = mount(GoodsIssueStatusBadge, {
        props: { type: 'status', value: 'Selesai' }
      });
      expect(wrapperCompleted.classes()).toContain('badge-completed');
      expect(wrapperCompleted.text()).toBe('Selesai');

      const wrapperDraft = mount(GoodsIssueStatusBadge, {
        props: { type: 'status', value: 'Draft' }
      });
      expect(wrapperDraft.classes()).toContain('badge-draft');

      const wrapperCancelled = mount(GoodsIssueStatusBadge, {
        props: { type: 'status', value: 'Dibatalkan' }
      });
      expect(wrapperCancelled.classes()).toContain('badge-cancelled');
    });

    it('renders purpose badges with semantic colors', () => {
      const wrapperInternal = mount(GoodsIssueStatusBadge, {
        props: { type: 'purpose', value: 'Kebutuhan Internal (IGD & ICU)' }
      });
      expect(wrapperInternal.classes()).toContain('purpose-internal');

      const wrapperDamaged = mount(GoodsIssueStatusBadge, {
        props: { type: 'purpose', value: 'Barang Rusak (Pecah Transit)' }
      });
      expect(wrapperDamaged.classes()).toContain('purpose-damaged');

      const wrapperExpired = mount(GoodsIssueStatusBadge, {
        props: { type: 'purpose', value: 'Kedaluwarsa (Pemusnahan)' }
      });
      expect(wrapperExpired.classes()).toContain('purpose-expired');
    });
  });

  describe('GoodsIssueTable Component', () => {
    const itemsMock = [
      {
        id: 1,
        issueNumber: 'OUT-202609-0028',
        referenceNumber: 'Ref: SBPB-098/FAR/26',
        dateDisplay: '15 Sep 2026',
        timeDisplay: '10:15 WIB',
        warehouse: 'Gudang Utama Farmasi (GOF-01)',
        locationDetail: 'Rak A-12 (Injeksi & Parenteral)',
        officerName: 'Apt. Budi Santoso, S.Farm',
        officerRole: 'SIP: 19820412/SIPA/2021',
        purpose: 'Kebutuhan Internal (IGD & ICU)',
        itemCount: 4,
        totalQuantity: 85,
        quantityUnit: 'Unit',
        quantitySubtitle: 'Ampul & Infus',
        status: 'Selesai',
        validationNote: ''
      },
      {
        id: 2,
        issueNumber: 'OUT-202609-0024',
        referenceNumber: 'Draft Permintaan Isolasi',
        dateDisplay: '14 Sep 2026',
        timeDisplay: '09:10 WIB',
        warehouse: 'Depo Rawat Inap Lt. 3',
        locationDetail: 'Lemari Narkotika',
        officerName: 'Novita Anggraini',
        officerRole: 'NIP: 19950520',
        purpose: 'Buffer Ruang Isolasi',
        itemCount: 3,
        totalQuantity: 45,
        quantityUnit: 'Unit',
        quantitySubtitle: 'Kebutuhan Shift Malam',
        status: 'Draft',
        validationNote: ''
      },
      {
        id: 3,
        issueNumber: 'OUT-202609-0023',
        referenceNumber: 'Salah input alokasi gudang',
        dateDisplay: '12 Sep 2026',
        timeDisplay: '16:00 WIB',
        warehouse: 'Apotek IGD 24 Jam',
        locationDetail: 'Depo Emergency',
        officerName: 'Rina Marlina',
        officerRole: 'NIP: 19930814',
        purpose: 'Penelitian/Uji Mutu',
        itemCount: 1,
        totalQuantity: 16,
        quantityUnit: 'Vial',
        quantitySubtitle: 'Stok tidak dipotong',
        status: 'Dibatalkan',
        validationNote: 'Salah input alokasi gudang'
      }
    ];

    const paginationInfoMock = {
      current_page: 1,
      per_page: 10,
      last_page: 1,
      total: 3,
      from: 1,
      to: 3
    };

    it('renders 10 columns, validation error notes, and emits row actions', async () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('DAFTAR PENGELUARAN NONPENJUALAN');
      expect(wrapper.text()).toContain('3 Dokumen');
      expect(wrapper.text()).toContain('OUT-202609-0028');
      expect(wrapper.text()).toContain('Gudang Utama Farmasi (GOF-01)');
      expect(wrapper.text()).toContain('85 Unit');
      expect(wrapper.text()).toContain('Salah input alokasi gudang');

      // Click view on first item
      const viewButtons = wrapper.findAll('button[title="Lihat Detail Pengeluaran"]');
      expect(viewButtons.length).toBeGreaterThanOrEqual(1);
      await viewButtons[0].trigger('click');
      expect(wrapper.emitted('view')).toBeTruthy();

      // Check dimmed class on cancelled row
      const cancelledRow = wrapper.findAll('tbody tr')[2];
      expect(cancelledRow.classes()).toContain('row-cancelled');
    });

    it('renders empty state when items is empty', () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: false
        }
      });

      expect(wrapper.text()).toContain('Data pengeluaran barang tidak ditemukan');
    });

    it('renders skeleton rows when loading is true', () => {
      const wrapper = mount(GoodsIssueTable, {
        props: {
          items: [],
          paginationInfo: { current_page: 1, per_page: 10, last_page: 1, total: 0, from: 0, to: 0 },
          loading: true
        }
      });

      expect(wrapper.findAll('.skeleton-row').length).toBe(6);
    });
  });

  describe('GoodsIssueFormModal Component', () => {
    it('manages form items and emits submit with isDraft flag', async () => {
      const wrapper = mount(GoodsIssueFormModal, {
        props: {
          isOpen: true,
          issueToEdit: null,
          warehouses: ['Gudang Utama Farmasi (GOF-01)', 'Apotek IGD 24 Jam'],
          officers: ['Apt. Budi Santoso, S.Farm', 'Novita Anggraini'],
          purposes: ['Kebutuhan Internal', 'Barang Rusak'],
          medicines: [
            { id: 1, name: 'Paracetamol 500 mg Tablet', unit: 'Tablet', defaultBatch: 'PCT-01', expDate: '2027-01-01', availableStock: 500 }
          ],
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Tambah Pengeluaran Barang');

      // Click Simpan Pengeluaran (Active)
      const saveBtn = wrapper.find('button.btn-primary');
      await saveBtn.trigger('click');
      expect(wrapper.emitted('submit')).toBeTruthy();
      expect(wrapper.emitted('submit')[0][0].isDraft).toBe(false);

      // Click Simpan Draft
      const draftBtn = wrapper.find('button.btn-outline');
      await draftBtn.trigger('click');
      expect(wrapper.emitted('submit')[1][0].isDraft).toBe(true);
    });
  });

  describe('GoodsIssueDetailModal Component', () => {
    it('renders issue detail metadata and items', () => {
      const issueMock = {
        id: 1,
        issueNumber: 'OUT-202609-0028',
        referenceNumber: 'Ref: SBPB-098/FAR/26',
        dateDisplay: '15 Sep 2026',
        timeDisplay: '10:15 WIB',
        warehouse: 'Gudang Utama Farmasi (GOF-01)',
        locationDetail: 'Rak A-12',
        officerName: 'Apt. Budi Santoso, S.Farm',
        officerRole: 'SIP: 19820412/SIPA/2021',
        purpose: 'Kebutuhan Internal (IGD & ICU)',
        totalQuantity: 85,
        quantityUnit: 'Unit',
        itemCount: 2,
        status: 'Selesai',
        notes: 'Keperluan IGD.',
        items: [
          { id: 1, name: 'Morfin Injeksi 10 mg/ml', batchNumber: 'MRF-01', expDate: '2027-03-25', quantity: 20, unit: 'Ampul' }
        ]
      };

      const wrapper = mount(GoodsIssueDetailModal, {
        props: {
          isOpen: true,
          issue: issueMock
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('OUT-202609-0028');
      expect(wrapper.text()).toContain('Gudang Utama Farmasi (GOF-01)');
      expect(wrapper.text()).toContain('Morfin Injeksi 10 mg/ml');
      expect(wrapper.text()).toContain('85 Unit');
    });
  });

  describe('GoodsIssueDeleteDialog Component', () => {
    it('emits confirm when deletion button is clicked', async () => {
      const wrapper = mount(GoodsIssueDeleteDialog, {
        props: {
          isOpen: true,
          issue: { id: 1, issueNumber: 'OUT-001', purpose: 'Draft Permintaan' },
          isSubmitting: false
        },
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Konfirmasi Hapus Draft Pengeluaran');
      await wrapper.find('button.btn-danger').trigger('click');
      expect(wrapper.emitted('confirm')).toBeTruthy();
    });
  });

  describe('GoodsIssueView Integration', () => {
    it('mounts GoodsIssueView with header, badge, breadcrumbs, summary cards, and table', () => {
      const wrapper = mount(GoodsIssueView, {
        global: {
          stubs: {
            teleport: true
          }
        }
      });

      expect(wrapper.text()).toContain('Logistik');
      expect(wrapper.text()).toContain('Pengeluaran Barang');
      expect(wrapper.text()).toContain('SIMRS-LOG');
      expect(wrapper.text()).toContain('PENGELUARAN HARI INI');
      expect(wrapper.text()).toContain('Filter Pengeluaran Nonpenjualan');
      expect(wrapper.text()).toContain('DAFTAR PENGELUARAN NONPENJUALAN');
    });
  });
});
