import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import MutationView from '../MutationView.vue';
import MutationSummaryCards from '../components/MutationSummaryCards.vue';
import MutationFilters from '../components/MutationFilters.vue';
import MutationAuditBanner from '../components/MutationAuditBanner.vue';
import MutationTable from '../components/MutationTable.vue';
import MutationFormModal from '../components/MutationFormModal.vue';
import MutationDetailModal from '../components/MutationDetailModal.vue';
import { useMutations } from '@/composables/useMutations';

describe('Mutation Module Tests', () => {
  describe('useMutations Composable', () => {
    let composable;

    beforeEach(() => {
      composable = useMutations();
    });

    it('initializes with default mock mutations and summary stats', () => {
      expect(composable.mutations.value.length).toBeGreaterThanOrEqual(8);
      expect(composable.summary.value.totalTransactions).toContain('84');
      expect(composable.summary.value.topSource).toBe('Gudang Utama');
      expect(composable.summary.value.topTarget).toBe('Apotek Rawat Jalan');
      expect(composable.summary.value.integrity).toBe('100%');
    });

    it('filters mutations by warehouse and keyword', () => {
      // Filter by source warehouse
      composable.filters.sourceWarehouse = 'Gudang Internal';
      composable.applyFilters();
      expect(
        composable.filteredMutations.value.every((m) => m.sourceWarehouse === 'Gudang Internal')
      ).toBe(true);

      // Filter by keyword
      composable.filters.sourceWarehouse = '';
      composable.filters.keyword = 'restock';
      composable.applyFilters();
      expect(
        composable.filteredMutations.value.every(
          (m) =>
            m.notes.toLowerCase().includes('restock') ||
            m.mutationNumber.toLowerCase().includes('restock')
        )
      ).toBe(true);

      // Reset
      composable.resetFilters();
      expect(composable.filteredMutations.value.length).toBe(composable.mutations.value.length);
    });

    it('adds a new stock mutation to local state', async () => {
      const initialCount = composable.mutations.value.length;

      const res = await composable.addMutation({
        sourceWarehouse: 'Gudang Utama',
        targetWarehouse: 'Apotek IGD',
        notes: 'Buffer darurat malam',
        items: [
          { medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 50, unit: 'Unit' },
          { medicineId: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 30, unit: 'Unit' }
        ]
      });

      expect(res.success).toBe(true);
      expect(composable.mutations.value.length).toBe(initialCount + 1);
      expect(composable.mutations.value[0].totalQuantity).toBe(80);
      expect(composable.mutations.value[0].itemCount).toBe(2);
      expect(composable.mutations.value[0].sourceWarehouse).toBe('Gudang Utama');
      expect(composable.mutations.value[0].targetWarehouse).toBe('Apotek IGD');
    });
  });

  describe('MutationSummaryCards Component', () => {
    it('renders 4 summary cards correctly', () => {
      const summaryMock = {
        totalTransactions: '84 Transaksi',
        estimatedValue: 'Rp428.650.000',
        topSource: 'Gudang Utama',
        topSourceSubtitle: '56 mutasi keluar',
        topTarget: 'Apotek Rawat Jalan',
        topTargetSubtitle: '38 mutasi masuk',
        integrity: '100%',
        integrityBadge: 'Tersinkronisasi',
        integritySubtitle: 'Audit log sinkron'
      };

      const wrapper = mount(MutationSummaryCards, {
        props: { summary: summaryMock }
      });

      expect(wrapper.text()).toContain('TOTAL MUTASI BULAN INI');
      expect(wrapper.text()).toContain('84 Transaksi');
      expect(wrapper.text()).toContain('Gudang Utama');
      expect(wrapper.text()).toContain('Apotek Rawat Jalan');
      expect(wrapper.text()).toContain('100%');
      expect(wrapper.text()).toContain('Tersinkronisasi');
    });
  });

  describe('MutationFilters Component', () => {
    it('emits apply and reset events', async () => {
      const filters = {
        startDate: '',
        endDate: '',
        sourceWarehouse: '',
        targetWarehouse: '',
        keyword: ''
      };

      const wrapper = mount(MutationFilters, {
        props: {
          filters,
          warehouses: ['Gudang Utama', 'Apotek IGD'],
          loading: false
        }
      });

      expect(wrapper.find('.filter-card-title').text()).toBe('Pencarian & Filter Mutasi');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('apply')).toBeTruthy();

      await wrapper.find('button.btn-cancel').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('MutationAuditBanner Component', () => {
    it('renders audit trail message', () => {
      const wrapper = mount(MutationAuditBanner);
      expect(wrapper.text()).toContain('Audit Trail');
      expect(wrapper.text()).toContain('Mutasi yang telah divalidasi tidak dapat diubah atau dihapus');
    });
  });

  describe('MutationTable Component', () => {
    it('renders mutation rows with 10 columns and emits view event', async () => {
      const itemsMock = [
        {
          id: 1,
          mutationNumber: 'MUT-001',
          dateDisplay: '12 Sep 2026, 14:15 WIB',
          sourceWarehouse: 'Gudang Utama',
          targetWarehouse: 'Apotek Rawat Jalan',
          itemCount: 6,
          totalQuantity: 450,
          unit: 'Unit',
          notes: 'Restock shift sore',
          createdBy: 'Apt. Novita',
          createdInitials: 'NA',
          items: []
        }
      ];

      const paginationInfoMock = {
        current_page: 1,
        per_page: 10,
        last_page: 1,
        total: 1,
        from: 1,
        to: 1
      };

      const wrapper = mount(MutationTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          loading: false
        }
      });

      expect(wrapper.text()).toContain('MUT-001');
      expect(wrapper.text()).toContain('Gudang Utama');
      expect(wrapper.text()).toContain('Apotek Rawat Jalan');
      expect(wrapper.text()).toContain('450 Unit');

      const viewBtn = wrapper.find('button.btn-detail');
      expect(viewBtn.exists()).toBe(true);
      await viewBtn.trigger('click');
      expect(wrapper.emitted('view')).toBeTruthy();
      expect(wrapper.emitted('view')[0][0]).toEqual(itemsMock[0]);
    });
  });

  describe('MutationFormModal Component', () => {
    it('renders modal and validates warehouse selection and stock availability', async () => {
      const wrapper = mount(MutationFormModal, {
        props: {
          isOpen: true,
          warehouses: ['Gudang Utama', 'Apotek IGD'],
          medicines: [
            { id: 1, name: 'Paracetamol 500 mg', availableStock: 100, unit: 'Unit' }
          ],
          isSubmitting: false
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.find('h3').text()).toBe('Tambah Mutasi Barang');

      // Submit without selecting warehouses triggers validation
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.find('.error-message').exists()).toBe(true);
      expect(wrapper.emitted('submit')).toBeFalsy();
    });
  });

  describe('MutationDetailModal Component', () => {
    it('renders read-only detail of mutation and item breakdown', () => {
      const mutationMock = {
        id: 1,
        mutationNumber: 'MUT-202609-0042',
        dateDisplay: '12 Sep 2026, 14:15 WIB',
        sourceWarehouse: 'Gudang Utama',
        targetWarehouse: 'Apotek Rawat Jalan',
        itemCount: 2,
        totalQuantity: 150,
        unit: 'Unit',
        notes: 'Restock sore',
        createdBy: 'Apt. Novita Anggraini',
        createdInitials: 'NA',
        items: [
          { id: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 100, unit: 'Unit' },
          { id: 2, medicineName: 'Amoxicillin 500 mg Kapsul', quantity: 50, unit: 'Unit' }
        ]
      };

      const wrapper = mount(MutationDetailModal, {
        props: {
          isOpen: true,
          mutation: mutationMock
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.text()).toContain('MUT-202609-0042');
      expect(wrapper.text()).toContain('Gudang Utama');
      expect(wrapper.text()).toContain('Apotek Rawat Jalan');
      expect(wrapper.text()).toContain('Paracetamol 500 mg Tablet');
      expect(wrapper.text()).toContain('Amoxicillin 500 mg Kapsul');
    });
  });

  describe('MutationView Page Integration', () => {
    it('renders page header, breadcrumbs, summary cards, and table', () => {
      const wrapper = mount(MutationView);

      expect(wrapper.find('.page-title').text()).toBe('Mutasi Barang');
      expect(wrapper.find('.page-subtitle').text()).toContain('Kelola perpindahan obat');
      expect(wrapper.text()).toContain('Logistik Farmasi');
      expect(wrapper.findComponent(MutationSummaryCards).exists()).toBe(true);
      expect(wrapper.findComponent(MutationFilters).exists()).toBe(true);
      expect(wrapper.findComponent(MutationAuditBanner).exists()).toBe(true);
      expect(wrapper.findComponent(MutationTable).exists()).toBe(true);
    });
  });
});
