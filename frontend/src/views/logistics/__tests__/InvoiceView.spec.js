import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import InvoiceView from '../InvoiceView.vue';
import InvoiceSummaryCards from '../components/InvoiceSummaryCards.vue';
import InvoiceFilters from '../components/InvoiceFilters.vue';
import InvoiceTable from '../components/InvoiceTable.vue';
import InvoiceStatusBadge from '../components/InvoiceStatusBadge.vue';
import InvoiceFormModal from '../components/InvoiceFormModal.vue';
import InvoiceDetailModal from '../components/InvoiceDetailModal.vue';
import InvoiceDeleteDialog from '../components/InvoiceDeleteDialog.vue';
import { useInvoices } from '@/composables/useInvoices';

describe('Invoice Module Tests', () => {
  describe('useInvoices Composable', () => {
    let composable;

    beforeEach(() => {
      composable = useInvoices();
    });

    it('initializes with default mock invoices and valid summary stats', () => {
      expect(composable.invoices.value.length).toBeGreaterThanOrEqual(10);
      expect(composable.summary.value.totalCount).toBe(composable.invoices.value.length);
      expect(composable.summary.value.paidCount).toBeGreaterThan(0);
      expect(composable.summary.value.unpaidCount).toBeGreaterThan(0);
      expect(composable.summary.value.totalAmount).toBeGreaterThan(0);
      expect(Number(composable.summary.value.paidAmount) + Number(composable.summary.value.unpaidAmount))
        .toBe(composable.summary.value.totalAmount);
    });

    it('calculates overdue status correctly', () => {
      expect(composable.isOverdue('2020-01-01', 'Belum Lunas')).toBe(true);
      expect(composable.isOverdue('2020-01-01', 'Lunas')).toBe(false);
      expect(composable.isOverdue('2099-01-01', 'Belum Lunas')).toBe(false);
    });

    it('adds, updates, and deletes an invoice in local state', async () => {
      const initialCount = composable.invoices.value.length;

      // Add
      const addRes = await composable.addInvoice({
        invoiceNumber: 'FKT-TEST-999',
        referenceNumber: 'PO-TEST-999',
        invoiceDate: '2026-09-15',
        dueDate: '2026-10-15',
        supplierId: 1,
        status: 'Belum Lunas',
        notes: 'Test note',
        items: [
          { medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 10, unit: 'Box', price: 50000, discount: 0, subtotal: 500000 }
        ]
      });

      expect(addRes.success).toBe(true);
      expect(composable.invoices.value.length).toBe(initialCount + 1);
      expect(composable.invoices.value[0].invoiceNumber).toBe('FKT-TEST-999');

      const createdId = addRes.data.id;

      // Update
      const updateRes = await composable.updateInvoice(createdId, {
        invoiceNumber: 'FKT-TEST-999-EDITED',
        referenceNumber: 'PO-TEST-999',
        invoiceDate: '2026-09-15',
        dueDate: '2026-10-15',
        supplierId: 1,
        status: 'Lunas',
        items: [
          { medicineId: 1, medicineName: 'Paracetamol 500 mg Tablet', quantity: 20, unit: 'Box', price: 50000, discount: 0, subtotal: 1000000 }
        ]
      });

      expect(updateRes.success).toBe(true);
      const updated = composable.invoices.value.find((i) => i.id === createdId);
      expect(updated.invoiceNumber).toBe('FKT-TEST-999-EDITED');
      expect(updated.status).toBe('Lunas');
      expect(updated.totalAmount).toBe(1000000);

      // Delete
      const deleteRes = await composable.deleteInvoice(createdId);
      expect(deleteRes.success).toBe(true);
      expect(composable.invoices.value.length).toBe(initialCount);
    });

    it('filters and sorts invoices accurately', () => {
      // Filter by status
      composable.filters.status = 'Lunas';
      composable.applyFilters();
      expect(composable.filteredInvoices.value.every((i) => i.status === 'Lunas')).toBe(true);

      // Reset
      composable.resetFilters();
      expect(composable.filteredInvoices.value.length).toBe(composable.invoices.value.length);

      // Sort by totalAmount ascending
      composable.setSort('totalAmount');
      const sorted = composable.sortedInvoices.value;
      for (let i = 0; i < sorted.length - 1; i++) {
        expect(sorted[i].totalAmount).toBeLessThanOrEqual(sorted[i + 1].totalAmount);
      }
    });
  });

  describe('InvoiceStatusBadge Component', () => {
    it('renders Lunas badge correctly', () => {
      const wrapper = mount(InvoiceStatusBadge, {
        props: { status: 'Lunas', isOverdue: false }
      });
      expect(wrapper.text()).toContain('Lunas');
      expect(wrapper.find('.badge-lunas').exists()).toBe(true);
      expect(wrapper.find('.badge-overdue').exists()).toBe(false);
    });

    it('renders Belum Lunas with Lewat Jatuh Tempo badge when overdue', () => {
      const wrapper = mount(InvoiceStatusBadge, {
        props: { status: 'Belum Lunas', isOverdue: true }
      });
      expect(wrapper.text()).toContain('Belum Lunas');
      expect(wrapper.text()).toContain('Lewat Jatuh Tempo');
      expect(wrapper.find('.badge-overdue').exists()).toBe(true);
    });
  });

  describe('InvoiceSummaryCards Component', () => {
    it('displays summary metrics matching props', () => {
      const summaryMock = {
        totalCount: 148,
        totalAmount: 1485620000,
        paidCount: 112,
        paidAmount: 1120450000,
        paidPercentage: '75.7',
        unpaidCount: 36,
        unpaidAmount: 365170000,
        unpaidPercentage: '24.3',
        overdueCount: 12
      };

      const wrapper = mount(InvoiceSummaryCards, {
        props: {
          summary: summaryMock,
          formatRupiah: (val) => `Rp ${val}`
        }
      });

      expect(wrapper.text()).toContain('TOTAL FAKTUR');
      expect(wrapper.text()).toContain('148');
      expect(wrapper.text()).toContain('FAKTUR LUNAS');
      expect(wrapper.text()).toContain('112');
      expect(wrapper.text()).toContain('75.7%');
      expect(wrapper.text()).toContain('FAKTUR BELUM LUNAS');
      expect(wrapper.text()).toContain('36');
      expect(wrapper.text()).toContain('12 Lewat Jatuh Tempo');
    });
  });

  describe('InvoiceFilters Component', () => {
    it('emits apply and reset events', async () => {
      const filters = {
        invoiceNumber: '',
        supplierId: '',
        status: '',
        invoiceDateStart: '',
        invoiceDateEnd: '',
        dueDateStart: '',
        dueDateEnd: ''
      };

      const wrapper = mount(InvoiceFilters, {
        props: {
          filters,
          suppliers: [{ id: 1, name: 'PT Test' }],
          loading: false
        }
      });

      expect(wrapper.find('.filter-card-title').text()).toBe('Pencarian Faktur');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('apply')).toBeTruthy();

      await wrapper.find('button.btn-cancel').trigger('click');
      expect(wrapper.emitted('reset')).toBeTruthy();
    });
  });

  describe('InvoiceTable Component', () => {
    it('renders table headers and rows correctly', () => {
      const itemsMock = [
        {
          id: 1,
          invoiceNumber: 'FKT-001',
          referenceNumber: 'PO-001',
          invoiceDate: '2026-09-12',
          dueDate: '2026-10-12',
          supplierName: 'PT Kalbe Farma',
          supplierCity: 'Cikarang',
          supplierNpwp: '01.234',
          totalAmount: 10000000,
          status: 'Lunas',
          paidAt: '2026-09-14'
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

      const wrapper = mount(InvoiceTable, {
        props: {
          items: itemsMock,
          paginationInfo: paginationInfoMock,
          sort: { column: 'invoiceDate', direction: 'desc' },
          searchQuery: '',
          loading: false,
          formatRupiah: (v) => `Rp ${v}`,
          formatDateIndo: (d) => d,
          isOverdue: () => false,
          getDueStatusText: () => '(Lunas)'
        }
      });

      expect(wrapper.text()).toContain('FKT-001');
      expect(wrapper.text()).toContain('PT Kalbe Farma');
      expect(wrapper.find('button.btn-action.view').exists()).toBe(true);
      expect(wrapper.find('button.btn-action.edit').exists()).toBe(true);
      expect(wrapper.find('button.btn-action.delete').exists()).toBe(true);
    });
  });

  describe('InvoiceFormModal Component', () => {
    it('renders Add Invoice modal with calculated totals and validates required fields', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: [{ id: 1, name: 'PT Supplier A', city: 'Jakarta' }],
          medicines: [{ id: 1, name: 'Paracetamol', price: 10000, unit: 'Box' }],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.find('h3').text()).toBe('Tambah Faktur');

      // Submit without required fields triggers validation
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.find('.error-message').exists()).toBe(true);
      expect(wrapper.emitted('submit')).toBeFalsy();
    });

    it('renders Edit Faktur title in edit mode', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-09-01',
            dueDate: '2026-10-01',
            supplierId: 1,
            status: 'Belum Lunas',
            items: [
              { medicineId: 1, medicineName: 'Paracetamol', quantity: 2, unit: 'Box', price: 10000, discount: 0, subtotal: 20000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier A', city: 'Jakarta' }],
          medicines: [{ id: 1, name: 'Paracetamol', price: 10000, unit: 'Box' }],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.find('h3').text()).toBe('Edit Faktur');
    });
  });

  describe('InvoiceDetailModal Component', () => {
    it('displays invoice and item details correctly', () => {
      const invoiceMock = {
        id: 1,
        invoiceNumber: 'FKT-001',
        referenceNumber: 'PO-001',
        invoiceDate: '2026-09-12',
        dueDate: '2026-10-12',
        supplierName: 'PT Kalbe Farma',
        supplierCity: 'Cikarang',
        supplierNpwp: '01.234',
        totalAmount: 5000000,
        status: 'Lunas',
        notes: 'Catatan pengiriman',
        items: [
          { id: 1, medicineName: 'Amoxicillin', quantity: 50, unit: 'Box', price: 100000, discount: 0, subtotal: 5000000 }
        ]
      };

      const wrapper = mount(InvoiceDetailModal, {
        props: {
          isOpen: true,
          invoice: invoiceMock,
          formatRupiah: (v) => `Rp ${v}`,
          formatDateIndo: (d) => d,
          isOverdue: () => false,
          getDueStatusText: () => '(Lunas)'
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.text()).toContain('FKT-001');
      expect(wrapper.text()).toContain('PT Kalbe Farma');
      expect(wrapper.text()).toContain('Amoxicillin');
      expect(wrapper.text()).toContain('Catatan pengiriman');
    });
  });

  describe('InvoiceDeleteDialog Component', () => {
    it('renders delete confirmation and emits confirm', async () => {
      const invoiceMock = {
        id: 1,
        invoiceNumber: 'FKT-001',
        supplierName: 'PT Kalbe Farma'
      };

      const wrapper = mount(InvoiceDeleteDialog, {
        props: {
          isOpen: true,
          invoice: invoiceMock,
          isSubmitting: false
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.text()).toContain('FKT-001');
      await wrapper.find('button.btn-danger').trigger('click');
      expect(wrapper.emitted('confirm')).toBeTruthy();
    });
  });

  describe('InvoiceView Page Integration', () => {
    it('renders page header, breadcrumbs, summary cards, and table', () => {
      const wrapper = mount(InvoiceView);

      expect(wrapper.find('.page-title').text()).toBe('Data Faktur');
      expect(wrapper.find('.page-subtitle').text()).toContain('Kelola faktur pembelian obat');
      expect(wrapper.text()).toContain('Logistik');
      expect(wrapper.text()).toContain('Faktur');
      expect(wrapper.findComponent(InvoiceSummaryCards).exists()).toBe(true);
      expect(wrapper.findComponent(InvoiceFilters).exists()).toBe(true);
      expect(wrapper.findComponent(InvoiceTable).exists()).toBe(true);
    });
  });
});
