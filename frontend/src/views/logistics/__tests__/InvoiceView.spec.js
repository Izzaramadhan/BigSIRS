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

    it('provides fetchInvoices, fetchSuppliers, fetchMedicines, and loadInitialData methods', async () => {
      expect(typeof composable.fetchInvoices).toBe('function');
      expect(typeof composable.fetchSuppliers).toBe('function');
      expect(typeof composable.fetchMedicines).toBe('function');
      expect(typeof composable.loadInitialData).toBe('function');

      await expect(composable.loadInitialData()).resolves.toBeUndefined();
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

    it('updates phone and address when selecting and clearing supplier', async () => {
      const mockSuppliers = [
        { id: 1, name: 'PT Kalbe Farma', city: 'Jl. Ahmad Yani No. 10', phone: '021-428738' }
      ];

      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: mockSuppliers,
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      // Initially empty
      const phoneInput = wrapper.find('#form-supplier-phone');
      expect(phoneInput.element.value).toBe('-');

      // Focus and select supplier
      const searchInput = wrapper.find('#supplier-search-input');
      await searchInput.trigger('focus');
      const item = wrapper.find('.dropdown-item');
      await item.trigger('click');

      expect(wrapper.find('#form-supplier-phone').element.value).toBe('021-428738');
      expect(wrapper.find('#form-supplier-address').element.value).toBe('Jl. Ahmad Yani No. 10');

      // Clear supplier
      const clearBtn = wrapper.find('.btn-clear-supplier');
      await clearBtn.trigger('click');
      expect(wrapper.find('#form-supplier-phone').element.value).toBe('-');
      expect(wrapper.find('#form-supplier-address').element.value).toBe('-');
    });

    it('adds medicine from search and increments qty if duplicate chosen', async () => {
      const mockMedicines = [
        { id: 10, name: 'Amoxicillin 500 mg', code: 'MED-AMX', unit: 'Strip', price: 25000 }
      ];

      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: mockMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      // Search medicine
      const searchInput = wrapper.find('.search-medicine-input');
      await searchInput.setValue('Amox');
      await searchInput.trigger('focus');

      const medItem = wrapper.find('.medicine-dropdown-item');
      expect(medItem.exists()).toBe(true);
      await medItem.trigger('click');

      // 1 item added
      expect(wrapper.findAll('.detail-row-card').length).toBe(1);
      expect(wrapper.text()).toContain('Amoxicillin 500 mg');

      // Select same medicine again -> duplicate prevented, qty incremented
      await searchInput.setValue('Amox');
      await searchInput.trigger('focus');
      await wrapper.find('.medicine-dropdown-item').trigger('click');

      expect(wrapper.findAll('.detail-row-card').length).toBe(1);
      expect(wrapper.find('.alert-info-toast').text()).toContain('sudah ada di daftar. Kuantitas ditambahkan');
    });

    it('recalculates preview subtotal when quantity, price, or discount changes', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            items: [
              { medicineId: 1, medicineName: 'Paracetamol', quantity: 2, unit: 'Box', price: 10000, discount: 0, subtotal: 20000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.text()).toContain('20.000');

      // Change QTY to 5
      const qtyInput = wrapper.find('input[type="number"][min="1"]');
      await qtyInput.setValue(5);
      expect(wrapper.text()).toContain('50.000');

      // Change discount to 10%
      const discInput = wrapper.find('input[placeholder="0"][aria-label="Diskon Item"]');
      await discInput.setValue(10);
      // 5 * 10000 - 10% = 45000
      expect(wrapper.text()).toContain('45.000');
    });

    it('calculates PPN 11% when PPN is selected and updates remaining bill on Lunas', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            taxType: 'non_tax',
            items: [
              { medicineId: 1, medicineName: 'Paracetamol', quantity: 10, unit: 'Box', price: 10000, discount: 0, subtotal: 100000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      // NON PPN: total = 100.000
      expect(wrapper.find('.highlight-total .value').text()).toBe('100.000');
      expect(wrapper.find('.sisa-tagihan-row .value').text()).toBe('100.000');

      // Select PPN (11%)
      const ppnRadio = wrapper.find('input[type="radio"][value="ppn_11"]');
      await ppnRadio.setValue(true);

      // PPN = 11.000, Total = 111.000
      expect(wrapper.find('.highlight-total .value').text()).toBe('111.000');
      expect(wrapper.find('.sisa-tagihan-row .value').text()).toBe('111.000');

      // Change status to Lunas
      const lunasRadio = wrapper.find('input[type="radio"][value="Lunas"]');
      await lunasRadio.setValue(true);

      // Sisa tagihan becomes 0
      expect(wrapper.find('.sisa-tagihan-row .value').text()).toBe('0');
    });

    it('removes item row and updates total amount', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            items: [
              { medicineId: 1, medicineName: 'Item 1', quantity: 1, price: 50000, subtotal: 50000 },
              { medicineId: 2, medicineName: 'Item 2', quantity: 1, price: 30000, subtotal: 30000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      expect(wrapper.findAll('.detail-row-card').length).toBe(2);
      expect(wrapper.find('.highlight-total .value').text()).toBe('80.000');

      // Delete first item
      await wrapper.find('.btn-delete-item').trigger('click');
      expect(wrapper.findAll('.detail-row-card').length).toBe(1);
      expect(wrapper.find('.highlight-total .value').text()).toBe('30.000');
    });

    it('disables submit button and shows loading spinner when isSubmitting is true', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: [],
          medicines: [],
          isSubmitting: true,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: {
          stubs: { teleport: true }
        }
      });

      const submitBtn = wrapper.find('button[type="submit"]');
      expect(submitBtn.attributes('disabled')).toBeDefined();
      expect(wrapper.find('.spin').exists()).toBe(true);
      expect(submitBtn.text()).toContain('Menyimpan...');
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

  describe('Form Faktur UI & Conditional Due Date Requirements', () => {
    const baseSuppliers = [{ id: 1, name: 'PT Kalbe Farma', phone: '021-123', city: 'Jakarta' }];
    const baseMedicines = [{ id: 1, name: 'Paracetamol', price: 10000, unit: 'Box' }];

    // 1. Default status Belum Lunas menampilkan Tanggal Jatuh Tempo.
    it('1. Default status Belum Lunas menampilkan Tanggal Jatuh Tempo', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      const belumLunasRadio = wrapper.find('input[type="radio"][value="Belum Lunas"]');
      expect(belumLunasRadio.element.checked).toBe(true);
      expect(wrapper.find('#form-due-date').exists()).toBe(true);
    });

    // 2. Memilih Lunas menyembunyikan Tanggal Jatuh Tempo.
    it('2. Memilih Lunas menyembunyikan Tanggal Jatuh Tempo', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.find('#form-due-date').exists()).toBe(true);
      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);
      expect(wrapper.find('#form-due-date').exists()).toBe(false);
    });

    // 3. Memilih Lunas membersihkan nilai Jatuh Tempo.
    it('3. Memilih Lunas membersihkan nilai Jatuh Tempo', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      const dueDateInput = wrapper.find('#form-due-date');
      await dueDateInput.setValue('2026-11-28');
      expect(dueDateInput.element.value).toBe('2026-11-28');

      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);
      expect(wrapper.find('#form-due-date').exists()).toBe(false);

      // Check that when switching back, it's empty
      await wrapper.find('input[type="radio"][value="Belum Lunas"]').setValue(true);
      expect(wrapper.find('#form-due-date').element.value).toBe('');
    });

    // 4. Memilih Lunas membersihkan error Jatuh Tempo.
    it('4. Memilih Lunas membersihkan error Jatuh Tempo', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      // Fill invoice number & date
      await wrapper.find('#form-invoice-number').setValue('INV-001');
      await wrapper.find('#form-invoice-date').setValue('2026-10-28');

      // Submit while Belum Lunas without dueDate -> triggers dueDate error
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.text()).toContain('Tanggal jatuh tempo wajib diisi.');

      // Switch to Lunas -> dueDate error cleared
      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);
      expect(wrapper.text()).not.toContain('Tanggal jatuh tempo wajib diisi.');
    });

    // 5. Mengubah kembali ke Belum Lunas menampilkan field kosong.
    it('5. Mengubah kembali ke Belum Lunas menampilkan field kosong', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-due-date').setValue('2026-12-31');
      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);
      await wrapper.find('input[type="radio"][value="Belum Lunas"]').setValue(true);

      const dueDateInput = wrapper.find('#form-due-date');
      expect(dueDateInput.exists()).toBe(true);
      expect(dueDateInput.element.value).toBe('');
    });

    // 6. Belum Lunas tanpa Jatuh Tempo tidak dapat disimpan.
    it('6. Belum Lunas tanpa Jatuh Tempo tidak dapat disimpan', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-invoice-number').setValue('INV-001');
      await wrapper.find('#form-invoice-date').setValue('2026-10-28');
      // Select supplier
      const searchInput = wrapper.find('#supplier-search-input');
      await searchInput.trigger('focus');
      await wrapper.find('.dropdown-item').trigger('click');

      // Add 1 item
      const medSearch = wrapper.find('.search-medicine-input');
      await medSearch.setValue('Para');
      await medSearch.trigger('focus');
      await wrapper.find('.medicine-dropdown-item').trigger('click');

      // Submit without due date
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeFalsy();
      expect(wrapper.text()).toContain('Tanggal jatuh tempo wajib diisi.');
    });

    // 7. Lunas tanpa Jatuh Tempo dapat disimpan.
    it('7. Lunas tanpa Jatuh Tempo dapat disimpan', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-invoice-number').setValue('INV-PAID-001');
      await wrapper.find('#form-invoice-date').setValue('2026-10-28');
      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);

      // Select supplier
      const searchInput = wrapper.find('#supplier-search-input');
      await searchInput.trigger('focus');
      await wrapper.find('.dropdown-item').trigger('click');

      // Add item
      const medSearch = wrapper.find('.search-medicine-input');
      await medSearch.setValue('Para');
      await medSearch.trigger('focus');
      await wrapper.find('.medicine-dropdown-item').trigger('click');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeTruthy();
      const emittedPayload = wrapper.emitted('submit')[0][0].payload;
      expect(emittedPayload.status).toBe('Lunas');
      expect(emittedPayload.dueDate).toBeNull();
    });

    // 8. Gudang Tujuan tidak tampil.
    it('8. Gudang Tujuan tidak tampil', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.find('#form-warehouse').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Gudang Tujuan');
    });

    // 9. Nomor PO tidak tampil.
    it('9. Nomor PO tidak tampil', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.find('#form-po-number').exists()).toBe(false);
      expect(wrapper.find('#form-reference-number').exists()).toBe(false);
      expect(wrapper.text()).not.toContain('Nomor PO');
      expect(wrapper.text()).not.toContain('Nomor Referensi/PO');
    });

    // 10. Payload tidak memuat Gudang Tujuan dan Nomor PO.
    it('10. Payload tidak memuat Gudang Tujuan dan Nomor PO', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: null,
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      await wrapper.find('#form-invoice-number').setValue('INV-TEST-001');
      await wrapper.find('#form-invoice-date').setValue('2026-10-28');
      await wrapper.find('#form-due-date').setValue('2026-11-28');

      // Select supplier
      const searchInput = wrapper.find('#supplier-search-input');
      await searchInput.trigger('focus');
      await wrapper.find('.dropdown-item').trigger('click');

      // Add item
      const medSearch = wrapper.find('.search-medicine-input');
      await medSearch.setValue('Para');
      await medSearch.trigger('focus');
      await wrapper.find('.medicine-dropdown-item').trigger('click');

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeTruthy();
      const payload = wrapper.emitted('submit')[0][0].payload;

      expect(payload).not.toHaveProperty('warehouseId');
      expect(payload).not.toHaveProperty('warehouse_id');
      expect(payload).not.toHaveProperty('referenceNumber');
      expect(payload).not.toHaveProperty('reference_number');
      expect(payload).not.toHaveProperty('purchase_order_number');
    });

    // 11. Payload Lunas tidak membawa tanggal Jatuh Tempo lama.
    it('11. Payload Lunas tidak membawa tanggal Jatuh Tempo lama', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'INV-EDIT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            taxType: 'non_tax',
            items: [{ medicineId: 1, medicineName: 'Paracetamol', quantity: 1, price: 10000, subtotal: 10000 }]
          },
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      // Switch to Lunas
      await wrapper.find('input[type="radio"][value="Lunas"]').setValue(true);

      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeTruthy();
      const payload = wrapper.emitted('submit')[0][0].payload;
      expect(payload.status).toBe('Lunas');
      expect(payload.dueDate).toBeNull();
    });

    // 12. Mode Edit Lunas tidak menampilkan Jatuh Tempo.
    it('12. Mode Edit Lunas tidak menampilkan Jatuh Tempo', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 2,
            invoiceNumber: 'INV-EDIT-PAID',
            invoiceDate: '2026-10-28',
            dueDate: null,
            supplierId: 1,
            status: 'Lunas',
            taxType: 'non_tax',
            items: [{ medicineId: 1, medicineName: 'Paracetamol', quantity: 1, price: 10000, subtotal: 10000 }]
          },
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.find('input[type="radio"][value="Lunas"]').element.checked).toBe(true);
      expect(wrapper.find('#form-due-date').exists()).toBe(false);
    });

    // 13. Mode Edit Belum Lunas menampilkan Jatuh Tempo lama.
    it('13. Mode Edit Belum Lunas menampilkan Jatuh Tempo lama', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 3,
            invoiceNumber: 'INV-EDIT-UNPAID',
            invoiceDate: '2026-10-28',
            dueDate: '2026-12-15',
            supplierId: 1,
            status: 'Belum Lunas',
            taxType: 'non_tax',
            items: [{ medicineId: 1, medicineName: 'Paracetamol', quantity: 1, price: 10000, subtotal: 10000 }]
          },
          suppliers: baseSuppliers,
          medicines: baseMedicines,
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      expect(wrapper.find('input[type="radio"][value="Belum Lunas"]').element.checked).toBe(true);
      const dueDateInput = wrapper.find('#form-due-date');
      expect(dueDateInput.exists()).toBe(true);
      expect(dueDateInput.element.value).toBe('2026-12-15');
    });
  });

  describe('Detail Data Faktur Currency Input Group & Sub Total Formatting', () => {
    it('renders Sub Total with separated currency prefix Rp. and formatted Indonesian number', () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            taxType: 'non_tax',
            items: [
              { medicineId: 1, medicineName: 'Paracetamol', quantity: 2, unit: 'Box', price: 8000, discount: 0, subtotal: 16000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      // Find Sub Total input container
      const subtotalInput = wrapper.find('input[aria-label="Subtotal Item"]');
      expect(subtotalInput.exists()).toBe(true);
      expect(subtotalInput.attributes('readonly')).toBeDefined();
      // Value should be formatted without Rp inside the input itself
      expect(subtotalInput.element.value).toBe('16.000');

      // The prefix element should exist as a separate element in the currency input group
      const inputGroup = subtotalInput.element.closest('.currency-input-group');
      expect(inputGroup).not.toBeNull();
      expect(inputGroup.classList.contains('is-readonly')).toBe(true);
      const prefix = inputGroup.querySelector('.currency-prefix');
      expect(prefix).not.toBeNull();
      expect(prefix.textContent.trim()).toBe('Rp.');
    });

    it('updates Sub Total in real-time when QTY, price, or discount changes and keeps payload numeric', async () => {
      const wrapper = mount(InvoiceFormModal, {
        props: {
          isOpen: true,
          invoice: {
            id: 1,
            invoiceNumber: 'FKT-001',
            invoiceDate: '2026-10-28',
            dueDate: '2026-11-28',
            supplierId: 1,
            status: 'Belum Lunas',
            taxType: 'non_tax',
            items: [
              { medicineId: 1, medicineName: 'Paracetamol', quantity: 2, unit: 'Box', price: 10000, discount: 20, discountType: 'percentage', subtotal: 16000 }
            ]
          },
          suppliers: [{ id: 1, name: 'PT Supplier' }],
          medicines: [],
          isSubmitting: false,
          formatRupiah: (v) => `Rp ${v}`
        },
        global: { stubs: { teleport: true } }
      });

      const subtotalInput = wrapper.find('input[aria-label="Subtotal Item"]');
      expect(subtotalInput.element.value).toBe('16.000');

      // Change price from 10000 to 20000 -> 2 * 20000 - 20% = 32000
      const priceInput = wrapper.find('input[aria-label="Harga Beli Satuan"]');
      await priceInput.setValue(20000);
      expect(wrapper.find('input[aria-label="Subtotal Item"]').element.value).toBe('32.000');

      // Change discount to 5000 nominal -> 2 * 20000 - 5000 = 35000
      const discType = wrapper.find('select[aria-label="Tipe Diskon"]');
      await discType.setValue('nominal');
      const discVal = wrapper.find('input[aria-label="Diskon Item"]');
      await discVal.setValue(5000);
      expect(wrapper.find('input[aria-label="Subtotal Item"]').element.value).toBe('35.000');

      // Change QTY from 2 to 3 -> 3 * 20000 - 5000 = 55000
      const qtyInput = wrapper.find('input[aria-label="Jumlah Kuantitas"]');
      await qtyInput.setValue(3);
      expect(wrapper.find('input[aria-label="Subtotal Item"]').element.value).toBe('55.000');

      // Submit form and verify subtotal in emitted payload remains a pure numeric value
      await wrapper.find('form').trigger('submit.prevent');
      expect(wrapper.emitted('submit')).toBeTruthy();
      const emittedItem = wrapper.emitted('submit')[0][0].payload.items[0];
      expect(typeof emittedItem.subtotal).toBe('number');
      expect(emittedItem.subtotal).toBe(55000);
    });
  });
});
