<script setup>
import { ref, computed, watch } from 'vue';
import { formatRupiah } from '@/utils/billingFormatters';

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  jenis: {
    type: String,
    required: true
  },
  catalog: {
    type: Array,
    default: () => []
  },
  orders: {
    type: Array,
    default: () => []
  },
  serviceDate: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['add-order', 'remove-order']);

const selectedCatalogCode = ref('');
const customName = ref('');
const qty = ref(1);
const price = ref(0);

watch(
  () => props.jenis,
  () => {
    selectedCatalogCode.value = '';
    customName.value = '';
    qty.value = 1;
    price.value = 0;
  }
);

const handleCatalogChange = () => {
  const found = props.catalog.find((c) => c.code === selectedCatalogCode.value);
  if (found) {
    customName.value = found.name;
    price.value = Number(found.price) || 0;
  }
};

const categoryOrders = computed(() => {
  return props.orders.filter((item) => item.jenis === props.jenis);
});

const categoryTotal = computed(() => {
  return categoryOrders.value.reduce((sum, item) => {
    const gross = (Number(item.price) || 0) * (Math.max(1, Number(item.qty) || 1));
    const discPct = Math.min(100, Math.max(0, Number(item.discount_percent) || 0));
    return sum + Math.max(0, Math.round(gross * (1 - discPct / 100)));
  }, 0);
});

const handleAdd = () => {
  const itemName = customName.value.trim();
  if (!itemName) return;

  emit('add-order', {
    jenis: props.jenis,
    name: itemName,
    qty: Math.max(1, Number(qty.value) || 1),
    price: Math.max(0, Number(price.value) || 0),
    order_date: props.serviceDate || new Date().toISOString().split('T')[0]
  });

  selectedCatalogCode.value = '';
  customName.value = '';
  qty.value = 1;
  price.value = 0;
};
</script>

<template>
  <div class="cat-tab-panel">
    <!-- Header Bar -->
    <div class="cat-header-bar">
      <div class="cat-title-left">
        <h3 class="cat-title">{{ title }}</h3>
        <span class="cat-count-pill">{{ categoryOrders.length }} Item</span>
      </div>
      <div class="cat-subtotal-wrap">
        <span class="cat-subtotal-label">Total {{ title }}:</span>
        <span class="cat-subtotal-val font-mono">{{ formatRupiah(categoryTotal) }}</span>
      </div>
    </div>

    <!-- Add Order Form -->
    <form class="cat-add-form" @submit.prevent="handleAdd">
      <div class="cat-form-grid">
        <div class="cat-field">
          <label :for="`cat-select-${jenis}`" class="cat-label">Pilih Layanan / Item {{ title }}</label>
          <div class="select-wrap">
            <select
              :id="`cat-select-${jenis}`"
              v-model="selectedCatalogCode"
              class="cat-select"
              @change="handleCatalogChange"
            >
              <option value="">-- Pilih dari Katalog atau Ketik Manual --</option>
              <option v-for="item in catalog" :key="item.code" :value="item.code">
                {{ item.name }} ({{ formatRupiah(item.price) }})
              </option>
            </select>
            <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </div>
        </div>

        <div class="cat-field">
          <label :for="`cat-name-${jenis}`" class="cat-label">Nama Order *</label>
          <input
            :id="`cat-name-${jenis}`"
            v-model="customName"
            type="text"
            class="cat-input"
            placeholder="Masukkan nama layanan..."
            required
          />
        </div>

        <div class="cat-field cat-field--sm">
          <label :for="`cat-qty-${jenis}`" class="cat-label">Jumlah</label>
          <input
            :id="`cat-qty-${jenis}`"
            v-model.number="qty"
            type="number"
            min="1"
            class="cat-input font-mono"
          />
        </div>

        <div class="cat-field cat-field--md">
          <label :for="`cat-price-${jenis}`" class="cat-label">Harga (Rp)</label>
          <input
            :id="`cat-price-${jenis}`"
            v-model.number="price"
            type="number"
            min="0"
            class="cat-input font-mono"
          />
        </div>

        <div class="cat-field cat-field--btn">
          <button type="submit" class="btn-add-cat-order">
            + Tambah ke Orders
          </button>
        </div>
      </div>
    </form>

    <!-- Filtered Category Items Table -->
    <div class="table-scroll-wrap">
      <table class="cat-table" :aria-label="`Tabel Order ${title}`">
        <thead>
          <tr>
            <th class="col-no text-center">No</th>
            <th>Jenis</th>
            <th>Nama Layanan / Order</th>
            <th class="text-center">Jumlah</th>
            <th>Harga</th>
            <th>Diskon</th>
            <th>Total</th>
            <th class="text-center">Status</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="categoryOrders.length === 0">
            <td colspan="9" class="empty-cat-cell">
              Belum ada order {{ title.toLowerCase() }} untuk kunjungan pasien ini.
            </td>
          </tr>
          <tr v-for="(item, idx) in categoryOrders" :key="item.id">
            <td class="col-no text-center text-muted">{{ idx + 1 }}</td>
            <td>
              <span class="jenis-code font-mono">{{ item.jenis }}</span>
            </td>
            <td class="item-name">{{ item.name }}</td>
            <td class="text-center">{{ item.qty }}</td>
            <td class="font-mono">{{ formatRupiah(item.price) }}</td>
            <td>{{ item.discount_percent ? `${item.discount_percent}%` : '-' }}</td>
            <td class="font-mono">
              {{ formatRupiah(Math.max(0, Math.round((Number(item.price) || 0) * (Number(item.qty) || 1) * (1 - (Number(item.discount_percent) || 0) / 100)))) }}
            </td>
            <td class="text-center">
              <span
                class="status-pill"
                :class="item.status === 'Lunas' ? 'pill-paid' : 'pill-unpaid'"
              >
                {{ item.status === 'Lunas' ? 'sudah bayar' : 'belum bayar' }}
              </span>
            </td>
            <td class="text-center">
              <button
                type="button"
                class="btn-delete-cat"
                title="Hapus item"
                aria-label="Hapus item"
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
  </div>
</template>

<style scoped>
.cat-tab-panel {
  display: flex;
  flex-direction: column;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  font-size: 0.85rem;
  color: var(--color-text-navy);
}

.cat-header-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 1.4rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #ffffff;
  gap: 1rem;
  flex-wrap: wrap;
}

.cat-title-left {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.cat-title {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  text-transform: uppercase;
}

.cat-count-pill {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.18rem 0.6rem;
  border-radius: 9999px;
}

.cat-subtotal-wrap {
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.cat-subtotal-label {
  font-size: 0.82rem;
  color: var(--color-text-secondary);
  font-weight: 500;
}

.cat-subtotal-val {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-primary-dark);
}

.cat-add-form {
  padding: 1rem 1.4rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
}

.cat-form-grid {
  display: grid;
  grid-template-columns: 1.4fr 1.2fr 100px 150px auto;
  gap: 0.75rem;
  align-items: end;
}

.cat-field {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.cat-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--color-text-secondary);
}

.select-wrap {
  position: relative;
}

.cat-select,
.cat-input {
  width: 100%;
  height: 36px;
  padding: 0 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 7px;
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.83rem;
  font-family: inherit;
  box-sizing: border-box;
}

.cat-select {
  appearance: none;
  -webkit-appearance: none;
  padding-right: 2rem;
  cursor: pointer;
}

.cat-select:focus,
.cat-input:focus {
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

.btn-add-cat-order {
  height: 36px;
  padding: 0 1rem;
  border-radius: 7px;
  border: none;
  background: var(--color-primary);
  color: #ffffff;
  font-size: 0.83rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  white-space: nowrap;
  transition: background-color 0.15s ease;
}

.btn-add-cat-order:hover {
  background: var(--color-primary-dark);
}

.table-scroll-wrap {
  width: 100%;
  overflow-x: auto;
}

.cat-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 720px;
  font-size: 0.83rem;
}

.cat-table th {
  background: #f8fafc;
  padding: 0.75rem 0.9rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.cat-table td {
  padding: 0.75rem 0.9rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  vertical-align: middle;
}

.cat-table tbody tr:last-child td {
  border-bottom: none;
}

.col-no { width: 52px; }
.text-center { text-align: center !important; }
.text-muted { color: var(--color-text-secondary); }
.item-name { font-weight: 600; }

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-variant-numeric: tabular-nums;
}

.jenis-code {
  display: inline-block;
  padding: 0.16rem 0.45rem;
  border-radius: 5px;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-size: 0.75rem;
  font-weight: 700;
}

.status-pill {
  display: inline-flex;
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
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

.btn-delete-cat {
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
}

.btn-delete-cat:hover {
  background: #ef4444;
  color: #ffffff;
}

.btn-delete-cat svg {
  width: 15px;
  height: 15px;
}

.empty-cat-cell {
  text-align: center;
  padding: 2rem 1rem !important;
  color: var(--color-text-secondary);
}

@media (max-width: 1024px) {
  .cat-form-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .cat-field--btn {
    grid-column: 1 / -1;
  }
}

@media (max-width: 640px) {
  .cat-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>

