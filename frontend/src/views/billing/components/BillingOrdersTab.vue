<script setup>
import { ref, computed, watch } from 'vue';
import { formatRupiah } from '@/utils/billingFormatters';

const props = defineProps({
  orders: {
    type: Array,
    default: () => []
  },
  polyclinics: {
    type: Array,
    default: () => []
  },
  paymentMethods: {
    type: Array,
    default: () => ['UMUM', 'BPJS Kesehatan', 'Mandiri Inhealth', 'Asuransi Swasta', 'Tunai', 'QRIS', 'Debit / EDC', 'Transfer Bank']
  },
  discountOptions: {
    type: Array,
    default: () => [
      { label: 'Pilih', value: 0 },
      { label: '5%', value: 5 },
      { label: '10%', value: 10 },
      { label: '15%', value: 15 },
      { label: '20%', value: 20 },
      { label: '25%', value: 25 },
      { label: '50%', value: 50 },
      { label: '100%', value: 100 }
    ]
  },
  defaultPaymentMethod: {
    type: String,
    default: 'UMUM'
  },
  defaultPolyclinic: {
    type: String,
    default: ''
  }
});

const emit = defineEmits([
  'update-discount',
  'remove-order',
  'submit-payment',
  'cancel-payment',
  'print-order',
  'cancel'
]);

const typeFilter = ref('All');
const searchQuery = ref('');
const perPage = ref(10);
const currentPage = ref(1);
const perPageOptions = [10, 25, 50, 100];

const sortKey = ref('');
const sortDir = ref('asc');

const selectedIds = ref(new Set());
const globalDiscount = ref(0);
const payAmount = ref(0);
const paymentNote = ref('');
const selectedPaymentMethod = ref(props.defaultPaymentMethod || 'UMUM');
const selectedPolyclinic = ref('');

const formatNowDDMMYYYYHHMM = () => {
  const d = new Date();
  const dd = String(d.getDate()).padStart(2, '0');
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const yyyy = d.getFullYear();
  const hh = String(d.getHours()).padStart(2, '0');
  const min = String(d.getMinutes()).padStart(2, '0');
  return `${dd}-${mm}-${yyyy} ${hh}:${min}`;
};

const paymentTime = ref(formatNowDDMMYYYYHHMM());

watch(
  () => props.defaultPaymentMethod,
  (val) => {
    if (val) selectedPaymentMethod.value = val;
  }
);

watch(
  () => props.orders,
  (list) => {
    const validIds = new Set(list.map((o) => o.id));
    const next = new Set();
    selectedIds.value.forEach((id) => {
      if (validIds.has(id)) next.add(id);
    });
    selectedIds.value = next;
  },
  { deep: true }
);

const longMonthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formatOrderDateLong = (isoDate) => {
  if (!isoDate) return '-';
  const parts = String(isoDate).split('-');
  if (parts.length !== 3) return isoDate;
  const year = parts[0];
  const monthIdx = Number(parts[1]) - 1;
  const day = Number(parts[2]);
  return `${String(day).padStart(2, '0')} ${longMonthNames[monthIdx] || parts[1]} ${year}`;
};

const computeRowTotal = (item) => {
  const qty = Math.max(1, Number(item.qty) || 1);
  const price = Math.max(0, Number(item.price) || 0);
  const discPct = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
  return Math.max(0, Math.round(qty * price * (1 - discPct / 100)));
};

const toggleSort = (key) => {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortDir.value = 'asc';
  }
};

const filteredOrders = computed(() => {
  let list = [...props.orders];

  if (typeFilter.value && typeFilter.value !== 'All') {
    if (typeFilter.value === 'Belum Bayar' || typeFilter.value === 'Lunas') {
      list = list.filter((o) => o.status === typeFilter.value);
    } else {
      list = list.filter((o) => o.jenis === typeFilter.value);
    }
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase();
    list = list.filter((o) =>
      String(o.name || '').toLowerCase().includes(q) ||
      String(o.jenis || '').toLowerCase().includes(q) ||
      formatOrderDateLong(o.order_date).toLowerCase().includes(q) ||
      String(o.status || '').toLowerCase().includes(q)
    );
  }

  if (sortKey.value) {
    const k = sortKey.value;
    const dir = sortDir.value === 'asc' ? 1 : -1;
    list.sort((a, b) => {
      if (k === 'total') return (computeRowTotal(a) - computeRowTotal(b)) * dir;
      if (k === 'price' || k === 'qty' || k === 'discount_percent') {
        return ((Number(a[k]) || 0) - (Number(b[k]) || 0)) * dir;
      }
      return String(a[k] || '').localeCompare(String(b[k] || ''), 'id') * dir;
    });
  }

  return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredOrders.value.length / perPage.value)));

watch([typeFilter, searchQuery, perPage], () => {
  currentPage.value = 1;
});

watch(totalPages, (maxPage) => {
  if (currentPage.value > maxPage) currentPage.value = maxPage;
});

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredOrders.value.slice(start, start + perPage.value);
});

const paginationMeta = computed(() => {
  const total = filteredOrders.value.length;
  if (total === 0) return { from: 0, to: 0 };
  const from = (currentPage.value - 1) * perPage.value + 1;
  const to = Math.min(total, currentPage.value * perPage.value);
  return { from, to };
});

const pageNumbers = computed(() => {
  const last = totalPages.value;
  const pages = [];
  for (let i = 1; i <= last; i++) pages.push(i);
  return pages;
});

const areAllVisibleSelected = computed(() => {
  if (paginatedOrders.value.length === 0) return false;
  return paginatedOrders.value.every((item) => selectedIds.value.has(item.id));
});

const toggleSelectAll = (checked) => {
  const next = new Set(selectedIds.value);
  paginatedOrders.value.forEach((item) => {
    if (checked) {
      next.add(item.id);
    } else {
      next.delete(item.id);
    }
  });
  selectedIds.value = next;
};

const toggleSelectRow = (id, checked) => {
  const next = new Set(selectedIds.value);
  if (checked) {
    next.add(id);
  } else {
    next.delete(id);
  }
  selectedIds.value = next;
};

const selectedOrders = computed(() => {
  return props.orders.filter((item) => selectedIds.value.has(item.id));
});

const selectedSubtotal = computed(() => {
  return selectedOrders.value.reduce((sum, item) => sum + computeRowTotal(item), 0);
});

const grandTotal = computed(() => {
  const disc = Math.max(0, Number(globalDiscount.value) || 0);
  return Math.max(0, selectedSubtotal.value - disc);
});

const changeAmount = computed(() => {
  const paid = Math.max(0, Number(payAmount.value) || 0);
  if (paid === 0 || grandTotal.value === 0) return 0;
  return Math.max(0, paid - grandTotal.value);
});

const getJenisBadgeClass = (jenis) => {
  switch (jenis) {
    case 'ADM': return 'jenis-adm';
    case 'TND': return 'jenis-tnd';
    case 'RSP': return 'jenis-rsp';
    case 'LAB': return 'jenis-lab';
    case 'RAD': return 'jenis-rad';
    case 'AMB': return 'jenis-amb';
    default: return 'jenis-adm';
  }
};

const handleSubmit = () => {
  emit('submit-payment', {
    selectedIds: Array.from(selectedIds.value),
    subtotal: selectedSubtotal.value,
    discount: Math.max(0, Number(globalDiscount.value) || 0),
    total: grandTotal.value,
    paid: Math.max(0, Number(payAmount.value) || 0),
    change: changeAmount.value,
    note: paymentNote.value,
    paymentMethod: selectedPaymentMethod.value,
    paymentTime: paymentTime.value,
    polyclinic: selectedPolyclinic.value || props.defaultPolyclinic
  });
};

const handleCancelPay = () => {
  emit('cancel-payment', {
    selectedIds: Array.from(selectedIds.value)
  });
};

const handlePrint = () => {
  emit('print-order', {
    selectedIds: Array.from(selectedIds.value),
    total: grandTotal.value,
    paymentMethod: selectedPaymentMethod.value
  });
};
</script>

<template>
  <div class="orders-tab-panel">
    <!-- LIST ORDERS Card Header -->
    <div class="orders-card-header">
      <div class="orders-title-left">
        <div class="orders-icon-box" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"></circle>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
          </svg>
        </div>
        <h3 class="orders-section-title">LIST ORDERS</h3>
        <span class="orders-count-pill">{{ filteredOrders.length }} Item</span>
      </div>

      <div class="orders-header-filter">
        <div class="select-wrap">
          <select
            id="billing-orders-type-filter"
            v-model="typeFilter"
            class="order-header-select"
            aria-label="Filter Tipe Order"
          >
            <option value="All">All</option>
            <option value="Belum Bayar">Belum Bayar</option>
            <option value="Lunas">Lunas</option>
            <option value="ADM">ADM (Administrasi)</option>
            <option value="TND">TND (Tindakan)</option>
            <option value="RSP">RSP (Resep)</option>
            <option value="LAB">LAB (Laboratorium)</option>
            <option value="RAD">RAD (Radiologi)</option>
            <option value="AMB">AMB (Ambulance)</option>
          </select>
          <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
      </div>
    </div>

    <!-- Table Controls Bar (records & Search:) -->
    <div class="table-controls-bar">
      <div class="records-control">
        <div class="select-wrap">
          <select
            id="billing-orders-perpage"
            v-model.number="perPage"
            class="records-select"
            aria-label="Jumlah data per halaman"
          >
            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
          </select>
          <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>
        <span class="records-label">records</span>
      </div>

      <div class="search-control">
        <label for="billing-orders-search" class="search-label">Search:</label>
        <div class="search-input-wrap">
          <input
            id="billing-orders-search"
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Cari layanan / jenis..."
            autocomplete="off"
          />
          <button
            v-if="searchQuery"
            type="button"
            class="btn-clear-search"
            aria-label="Hapus pencarian"
            @click="searchQuery = ''"
          >
            &times;
          </button>
        </div>
      </div>
    </div>

    <!-- Orders Data Table -->
    <div class="table-scroll-wrap">
      <table class="orders-table" aria-label="Tabel List Orders Pasien">
        <thead>
          <tr>
            <th class="col-check text-center">
              <input
                id="billing-orders-select-all"
                type="checkbox"
                class="order-checkbox"
                :checked="areAllVisibleSelected"
                aria-label="Pilih Semua Order"
                @change="toggleSelectAll($event.target.checked)"
              />
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'order_date' }" @click="toggleSort('order_date')">
              <div class="th-flex">
                <span>Tgl Order</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'jenis' }" @click="toggleSort('jenis')">
              <div class="th-flex">
                <span>Jenis</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'name' }" @click="toggleSort('name')">
              <div class="th-flex">
                <span>Nama</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable text-center" :class="{ sorted: sortKey === 'qty' }" @click="toggleSort('qty')">
              <div class="th-flex th-center">
                <span>Jumlah</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'price' }" @click="toggleSort('price')">
              <div class="th-flex">
                <span>Harga</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'discount_percent' }" @click="toggleSort('discount_percent')">
              <div class="th-flex">
                <span>Diskon</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable" :class="{ sorted: sortKey === 'total' }" @click="toggleSort('total')">
              <div class="th-flex">
                <span>Total</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="sortable text-center" :class="{ sorted: sortKey === 'status' }" @click="toggleSort('status')">
              <div class="th-flex th-center">
                <span>Status</span>
                <span class="sort-arrows">▲▼</span>
              </div>
            </th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="paginatedOrders.length === 0">
            <td colspan="10" class="empty-orders-cell">
              Tidak ada data order tagihan pada filter ini.
            </td>
          </tr>
          <tr
            v-for="item in paginatedOrders"
            :key="item.id"
            :class="{ 'row-selected': selectedIds.has(item.id) }"
          >
            <td class="col-check text-center">
              <input
                type="checkbox"
                class="order-checkbox row-order-checkbox"
                :checked="selectedIds.has(item.id)"
                :aria-label="`Pilih order ${item.name}`"
                @change="toggleSelectRow(item.id, $event.target.checked)"
              />
            </td>
            <td class="col-date">{{ formatOrderDateLong(item.order_date) }}</td>
            <td class="col-jenis">
              <span class="jenis-pill font-mono" :class="getJenisBadgeClass(item.jenis)">{{ item.jenis }}</span>
            </td>
            <td class="col-item-name">{{ item.name }}</td>
            <td class="col-qty text-center">{{ item.qty }}</td>
            <td class="col-price font-mono">{{ formatRupiah(item.price) }}</td>
            <td class="col-discount">
              <div class="select-wrap select-wrap--compact">
                <select
                  class="row-discount-select"
                  :value="item.discount_percent || 0"
                  :aria-label="`Diskon ${item.name}`"
                  @change="emit('update-discount', { id: item.id, discount: Number($event.target.value) })"
                >
                  <option v-for="opt in discountOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
                <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </div>
            </td>
            <td class="col-total font-mono">{{ formatRupiah(computeRowTotal(item)) }}</td>
            <td class="col-status text-center">
              <span
                class="order-status-pill"
                :class="item.status === 'Lunas' ? 'pill-paid' : 'pill-unpaid'"
              >
                {{ item.status === 'Lunas' ? 'sudah bayar' : 'belum bayar' }}
              </span>
            </td>
            <td class="col-action text-center">
              <button
                type="button"
                class="btn-delete-order"
                title="Hapus Order"
                aria-label="Hapus Order"
                @click="emit('remove-order', item.id)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div class="table-footer-bar">
      <div class="entries-info">
        Showing <strong class="entries-num">{{ paginationMeta.from }}</strong> to
        <strong class="entries-num">{{ paginationMeta.to }}</strong> of
        <strong class="entries-num">{{ filteredOrders.length }}</strong> entries
      </div>

      <div class="pagination-controls">
        <button
          type="button"
          class="page-btn"
          :disabled="currentPage <= 1"
          aria-label="Halaman sebelumnya"
          @click="currentPage > 1 && (currentPage -= 1)"
        >
          &lsaquo;
        </button>
        <button
          v-for="p in pageNumbers"
          :key="p"
          type="button"
          class="page-btn"
          :class="{ active: p === currentPage }"
          @click="currentPage = p"
        >
          {{ p }}
        </button>
        <button
          type="button"
          class="page-btn"
          :disabled="currentPage >= totalPages"
          aria-label="Halaman berikutnya"
          @click="currentPage < totalPages && (currentPage += 1)"
        >
          &rsaquo;
        </button>
      </div>
    </div>

    <!-- 2-Column Payment Calculation & Details Boxes -->
    <div class="payment-summary-grid">
      <!-- Left Box: Sub Total, Diskon, Total, Bayar, Kembali -->
      <div class="summary-box">
        <div class="summary-row summary-row--subtotal">
          <span class="summary-label">Sub Total:</span>
          <span id="billing-subtotal-display" class="summary-subtotal-val font-mono">
            {{ formatRupiah(selectedSubtotal) }}
          </span>
        </div>

        <div class="summary-row">
          <label for="billing-global-discount" class="summary-label">Diskon :</label>
          <input
            id="billing-global-discount"
            v-model.number="globalDiscount"
            type="number"
            min="0"
            class="summary-input font-mono"
          />
        </div>

        <div class="summary-row">
          <label for="billing-grand-total" class="summary-label">Total :</label>
          <input
            id="billing-grand-total"
            type="number"
            :value="grandTotal"
            readonly
            class="summary-input summary-input--readonly font-mono"
          />
        </div>

        <div class="summary-row">
          <label for="billing-pay-amount" class="summary-label">Bayar :</label>
          <input
            id="billing-pay-amount"
            v-model.number="payAmount"
            type="number"
            min="0"
            class="summary-input font-mono"
          />
        </div>

        <div class="summary-row">
          <label for="billing-change-amount" class="summary-label">Kembali :</label>
          <input
            id="billing-change-amount"
            type="number"
            :value="changeAmount"
            readonly
            class="summary-input summary-input--readonly font-mono"
          />
        </div>
      </div>

      <!-- Right Box: Catatan, Cara Bayar, Waktu Pembayaran -->
      <div class="summary-box">
        <div class="detail-row detail-row--top">
          <label for="billing-payment-note" class="summary-label">Catatan:</label>
          <textarea
            id="billing-payment-note"
            v-model="paymentNote"
            rows="3"
            class="summary-textarea"
            placeholder="Catatan pembayaran..."
          ></textarea>
        </div>

        <div class="detail-row">
          <label for="billing-payment-method" class="summary-label">Cara Bayar:</label>
          <div class="select-wrap">
            <select
              id="billing-payment-method"
              v-model="selectedPaymentMethod"
              class="summary-select"
            >
              <option v-for="m in paymentMethods" :key="m" :value="m">{{ m }}</option>
            </select>
            <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>

        <div class="detail-row">
          <label for="billing-payment-time" class="summary-label">Waktu Pembayaran:</label>
          <input
            id="billing-payment-time"
            v-model="paymentTime"
            type="text"
            class="summary-input font-mono"
          />
        </div>
      </div>
    </div>

    <!-- Bottom Action Footer Bar -->
    <div class="orders-footer-bar">
      <div class="footer-left-actions">
        <div class="select-wrap footer-poly-wrap">
          <select
            id="billing-order-polyclinic"
            v-model="selectedPolyclinic"
            class="footer-poly-select"
            aria-label="Pilih Poliklinik"
          >
            <option value="">Pilih Poliklinik</option>
            <option v-for="poly in polyclinics" :key="poly" :value="poly">{{ poly }}</option>
          </select>
          <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </div>

        <button type="button" class="btn-batal-bayar" @click="handleCancelPay">
          Batal Bayar
        </button>
      </div>

      <div class="footer-right-actions">
        <button type="button" class="btn-submit-order" @click="handleSubmit">
          Submit
        </button>
        <button type="button" class="btn-print-order" @click="handlePrint">
          Print
        </button>
        <button type="button" class="btn-cancel-order" @click="emit('cancel')">
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.orders-tab-panel {
  display: flex;
  flex-direction: column;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  font-size: 0.85rem;
  color: var(--color-text-navy);
}

.orders-card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.4rem;
  background: #ffffff;
  border-bottom: 1px solid var(--color-border-soft);
  gap: 1rem;
  flex-wrap: wrap;
}

.orders-title-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.orders-icon-box {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.orders-icon-box svg {
  width: 16px;
  height: 16px;
}

.orders-section-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.01em;
}

.orders-count-pill {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.18rem 0.6rem;
  border-radius: 9999px;
}

.orders-header-filter {
  min-width: 185px;
}

.select-wrap {
  position: relative;
  width: 100%;
}

.order-header-select,
.records-select,
.row-discount-select,
.summary-select,
.footer-poly-select {
  width: 100%;
  appearance: none;
  -webkit-appearance: none;
  height: 36px;
  padding: 0 2rem 0 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 7px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.825rem;
  font-weight: 500;
  font-family: inherit;
  cursor: pointer;
}

.row-discount-select {
  height: 32px;
  padding: 0 1.75rem 0 0.55rem;
  min-width: 78px;
}

.order-header-select:focus,
.records-select:focus,
.row-discount-select:focus,
.summary-select:focus,
.footer-poly-select:focus,
.search-input:focus,
.summary-input:focus,
.summary-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.chevron-icon {
  position: absolute;
  right: 0.55rem;
  top: 50%;
  transform: translateY(-50%);
  width: 14px;
  height: 14px;
  color: var(--color-text-secondary);
  pointer-events: none;
}

.table-controls-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.9rem 1.4rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.records-control {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.records-control .select-wrap {
  width: 76px;
}

.records-select {
  height: 34px;
  font-weight: 600;
}

.records-label,
.search-label {
  font-size: 0.825rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.search-control {
  display: flex;
  align-items: center;
  gap: 0.55rem;
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  height: 34px;
  width: 210px;
  padding: 0 1.75rem 0 0.7rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 7px;
  font-size: 0.825rem;
  color: var(--color-text-navy);
  background: #ffffff;
}

.btn-clear-search {
  position: absolute;
  right: 0.45rem;
  border: none;
  background: transparent;
  color: var(--color-text-secondary);
  cursor: pointer;
  font-size: 1rem;
  line-height: 1;
}

/* Table */
.table-scroll-wrap {
  width: 100%;
  overflow-x: auto;
  border-top: 1px solid var(--color-border-soft);
  border-bottom: 1px solid var(--color-border-soft);
}

.orders-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
  font-size: 0.83rem;
}

.orders-table th {
  background: #f8fafc;
  padding: 0.75rem 0.9rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.orders-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.orders-table th.sortable:hover {
  background: #f1f5f9;
}

.orders-table th.sorted {
  color: var(--color-primary-dark);
}

.th-flex {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.35rem;
}

.th-flex > span:first-child {
  font-weight: 600;
}

.th-center {
  justify-content: center;
}

.sort-arrows {
  font-size: 0.62rem;
  color: #cbd5e1;
  letter-spacing: -1px;
}

.orders-table td {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.orders-table tbody tr:last-child td {
  border-bottom: none;
}

.orders-table tbody tr:hover {
  background: #f8fafc;
}

.orders-table tbody tr.row-selected {
  background: #f0fdfa;
}

.col-check {
  width: 44px;
}

.order-checkbox {
  width: 16px;
  height: 16px;
  accent-color: var(--color-primary);
  cursor: pointer;
}

.text-center {
  text-align: center !important;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
}

.col-item-name {
  font-weight: 600;
}

.jenis-pill {
  display: inline-block;
  padding: 0.18rem 0.5rem;
  border-radius: 5px;
  font-size: 0.75rem;
  font-weight: 700;
}

.jenis-adm { background: #e0f2fe; color: #0369a1; }
.jenis-tnd { background: #ccfbf1; color: #0f766e; }
.jenis-rsp { background: #fef3c7; color: #b45309; }
.jenis-lab { background: #ede9fe; color: #6d28d9; }
.jenis-rad { background: #fce7f3; color: #be185d; }
.jenis-amb { background: #fee2e2; color: #b91c1c; }

.order-status-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.22rem 0.65rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.pill-unpaid {
  background: #fff1f2;
  color: #e11d48;
  border: 1px solid #fecdd3;
}

.pill-paid {
  background: #dcfce7;
  color: #047857;
  border: 1px solid #bbf7d0;
}

.btn-delete-order {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: none;
  background: #fee2e2;
  color: #dc2626;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-delete-order:hover {
  background: #ef4444;
  color: #ffffff;
}

.btn-delete-order svg {
  width: 15px;
  height: 15px;
}

.empty-orders-cell {
  text-align: center;
  padding: 2rem 1rem !important;
  color: var(--color-text-secondary);
}

/* Pagination */
.table-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.85rem 1.4rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.entries-info {
  font-size: 0.82rem;
  color: var(--color-text-secondary);
}

.entries-num {
  font-weight: 600;
  color: var(--color-text-navy);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-btn {
  min-width: 32px;
  height: 32px;
  padding: 0 0.55rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.82rem;
  font-weight: 500;
  cursor: pointer;
}

.page-btn.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
  font-weight: 600;
}

.page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

/* 2-Column Payment Summary */
.payment-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
  padding: 1rem 1.4rem 1.35rem;
}

.summary-box {
  background: #f1f5f9;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 1.15rem 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}

.summary-row,
.detail-row {
  display: grid;
  grid-template-columns: 140px minmax(0, 1fr);
  align-items: center;
  gap: 0.85rem;
}

.detail-row--top {
  align-items: start;
}

.detail-row--top .summary-label {
  padding-top: 0.4rem;
}

.summary-label {
  text-align: right;
  font-size: 0.84rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.summary-subtotal-val {
  text-align: right;
  font-size: 0.98rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.summary-input,
.summary-textarea {
  width: 100%;
  padding: 0.48rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.84rem;
  font-family: inherit;
  box-sizing: border-box;
}

.summary-input--readonly {
  background: #f8fafc;
  color: var(--color-text-navy);
  font-weight: 600;
}

.summary-textarea {
  resize: vertical;
  min-height: 76px;
}

/* Footer Action Bar */
.orders-footer-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.4rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
  border-radius: 0 0 12px 12px;
  gap: 1rem;
  flex-wrap: wrap;
}

.footer-left-actions,
.footer-right-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.footer-poly-wrap {
  width: 240px;
}

.btn-batal-bayar,
.btn-submit-order,
.btn-print-order,
.btn-cancel-order {
  height: 36px;
  padding: 0 1.1rem;
  border-radius: 7px;
  font-size: 0.84rem;
  font-weight: 600;
  font-family: inherit;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-batal-bayar {
  background: #0284c7;
  color: #ffffff;
}

.btn-batal-bayar:hover {
  background: #0369a1;
}

.btn-submit-order {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-submit-order:hover {
  background: var(--color-primary-dark);
}

.btn-print-order {
  background: #f59e0b;
  color: #ffffff;
}

.btn-print-order:hover {
  background: #d97706;
}

.btn-cancel-order {
  background: #e2e8f0;
  color: var(--color-text-navy);
}

.btn-cancel-order:hover {
  background: #cbd5e1;
}

@media (max-width: 900px) {
  .payment-summary-grid {
    grid-template-columns: 1fr;
  }

  .summary-row,
  .detail-row {
    grid-template-columns: 120px minmax(0, 1fr);
  }
}

@media (max-width: 640px) {
  .orders-card-header,
  .table-controls-bar,
  .table-footer-bar,
  .payment-summary-grid,
  .orders-footer-bar {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .summary-row,
  .detail-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }

  .summary-label {
    text-align: left;
  }

  .footer-poly-wrap {
    width: 100%;
  }
}
</style>

