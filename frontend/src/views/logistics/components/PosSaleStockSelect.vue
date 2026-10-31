<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import posSalesService from '@/services/logistics/posSales.service';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: ''
  },
  warehouseId: {
    type: [Number, String],
    default: ''
  },
  warehouseName: {
    type: String,
    default: ''
  },
  saleDate: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: 'detail-input-medicine'
  },
  placeholder: {
    type: String,
    default: 'Cari nama atau kode sediaan...'
  }
});

const emit = defineEmits(['update:modelValue', 'select', 'clear']);

// Component state
const isOpen = ref(false);
const searchQuery = ref('');
const options = ref([]);
const loading = ref(false);
const hasSearched = ref(false);
const errorMessage = ref('');
const selectedStockItem = ref(null);
const highlightedIndex = ref(-1);
const activeRequestId = ref(0);

const containerRef = ref(null);
const inputRef = ref(null);
const dropdownMenuRef = ref(null);

// Floating position style for teleported dropdown
const dropdownStyle = ref({
  position: 'fixed',
  top: '0px',
  left: '0px',
  width: '0px',
  zIndex: 99999
});

let debounceTimer = null;
let currentAbortController = null;

const isDisabled = computed(() => {
  return props.disabled || !props.warehouseId;
});

const trimmedQuery = computed(() => searchQuery.value.trim());

const displayPlaceholder = computed(() => {
  if (!props.warehouseId) {
    return 'Pilih gudang terlebih dahulu';
  }
  return props.placeholder;
});

const displayInputValue = computed(() => {
  if (selectedStockItem.value) {
    return selectedStockItem.value.item_name;
  }
  return searchQuery.value;
});

const shouldShowDropdown = computed(() => {
  if (isDisabled.value) return false;
  if (!isOpen.value) return false;
  if (selectedStockItem.value) return false;
  if (trimmedQuery.value.length === 0) return false;
  return true;
});

// Update dropdown floating position relative to input container
const updateDropdownPosition = () => {
  if (!containerRef.value) return;
  const rect = containerRef.value.getBoundingClientRect();
  const dropdownMaxHeight = 280;
  const gap = 4;
  const spaceBelow = window.innerHeight - rect.bottom;
  const spaceAbove = rect.top;

  // Auto flip to above if space below is limited (< 240px) and above has more space
  const showAbove = spaceBelow < 240 && spaceAbove > spaceBelow;

  const maxHeight = showAbove
    ? Math.min(dropdownMaxHeight, Math.max(120, spaceAbove - gap - 8))
    : Math.min(dropdownMaxHeight, Math.max(120, spaceBelow - gap - 8));

  const top = showAbove
    ? rect.top - maxHeight - gap
    : rect.bottom + gap;

  dropdownStyle.value = {
    position: 'fixed',
    top: `${Math.round(top)}px`,
    left: `${Math.round(rect.left)}px`,
    width: `${Math.round(rect.width)}px`,
    maxHeight: `${Math.round(maxHeight)}px`,
    zIndex: 99999
  };
};

watch(shouldShowDropdown, (show) => {
  if (show) {
    nextTick(() => {
      updateDropdownPosition();
    });
  }
});

watch(options, () => {
  if (shouldShowDropdown.value) {
    nextTick(() => {
      updateDropdownPosition();
    });
  }
});

// Map backend stock response into standard item
const mapStock = (it) => {
  if (!it) return null;
  const stockId = it.stock_id ?? it.id;
  const itemId = it.item_id ?? it.medicine_id ?? it.medicineId;
  const medicineId = itemId;
  const itemCode = it.item_code ?? it.medicine_code ?? it.medicineCode ?? '-';
  const itemName = it.item_name ?? it.medicine_name ?? it.medicineName ?? 'Sediaan Obat';
  const unitId = it.unit_id ?? it.medicine_unit_id ?? null;
  const unitName = it.unit_name ?? it.unit ?? 'Unit';
  const batchNumber = it.batch_number ?? it.batchNumber ?? '-';
  const expiryDate = it.expiry_date ?? it.expired_date ?? it.expiredDate ?? '-';
  const expiredDate = expiryDate;
  const availableStock = Number(it.available_stock ?? it.availableStock ?? it.quantity ?? 0);
  const sellingPrice = Number(it.selling_price ?? it.price ?? it.unit_price ?? it.unitPrice ?? 0);
  const warehouseId = it.warehouse_id ?? it.warehouseId ?? props.warehouseId;
  const warehouseName = it.warehouse_name ?? it.warehouseName ?? props.warehouseName;

  return {
    id: stockId,
    stock_id: stockId,
    stockId,
    item_id: itemId,
    itemId,
    medicine_id: itemId,
    medicineId: itemId,
    item_code: itemCode,
    itemCode,
    medicine_code: itemCode,
    medicineCode: itemCode,
    item_name: itemName,
    itemName,
    name: itemName,
    unit_id: unitId,
    unitId,
    unit_name: unitName,
    unitName,
    unit: unitName,
    batch_number: batchNumber,
    batchNumber,
    expiry_date: expiryDate,
    expiryDate,
    expired_date: expiryDate,
    expiredDate,
    available_stock: availableStock,
    availableStock,
    selling_price: sellingPrice,
    sellingPrice,
    price: sellingPrice,
    unitPrice: sellingPrice,
    unit_price: sellingPrice,
    warehouse_id: warehouseId,
    warehouseId,
    warehouse_name: warehouseName,
    warehouseName
  };
};

const isCancelError = (err) => {
  return (
    axios.isCancel(err) ||
    err?.name === 'CanceledError' ||
    err?.name === 'AbortError' ||
    err?.code === 'ERR_CANCELED' ||
    err?.message === 'canceled'
  );
};

// Execute search with latest query and warehouseId
const executeSearch = async (query) => {
  if (!props.warehouseId || query.length < 2) {
    loading.value = false;
    return;
  }

  if (currentAbortController) {
    currentAbortController.abort();
    currentAbortController = null;
  }
  currentAbortController = new AbortController();

  activeRequestId.value += 1;
  const reqId = activeRequestId.value;

  loading.value = true;
  errorMessage.value = '';

  try {
    const params = {
      search: query,
      issue_date: props.saleDate || undefined,
      per_page: 20
    };

    let res;
    try {
      res = await posSalesService.getAvailableStocks(
        props.warehouseId,
        params,
        { signal: currentAbortController.signal }
      );
    } catch (err) {
      if (isCancelError(err)) return;
      // Dual-path fallback: try getWarehouseStocks
      res = await posSalesService.getWarehouseStocks(
        { warehouse_id: props.warehouseId, ...params },
        { signal: currentAbortController.signal }
      );
    }

    if (reqId !== activeRequestId.value) return;

    const rawList = Array.isArray(res?.data)
      ? res.data
      : (Array.isArray(res) ? res : (res?.data?.data || []));

    const mapped = rawList.map(mapStock).filter(Boolean);
    options.value = mapped;
    hasSearched.value = true;
    highlightedIndex.value = mapped.length > 0 ? 0 : -1;
    nextTick(() => updateDropdownPosition());
  } catch (err) {
    if (isCancelError(err)) return;
    if (reqId !== activeRequestId.value) return;

    errorMessage.value = 'Gagal memuat sediaan. Silakan coba lagi.';
    options.value = [];
    hasSearched.value = true;
    nextTick(() => updateDropdownPosition());
  } finally {
    if (reqId === activeRequestId.value) {
      loading.value = false;
    }
  }
};

const handleInput = (e) => {
  if (isDisabled.value) return;

  if (selectedStockItem.value) {
    selectedStockItem.value = null;
    emit('update:modelValue', '');
    emit('clear');
  }

  const q = e.target.value;
  searchQuery.value = q;
  const trimmed = q.trim();

  if (debounceTimer) {
    clearTimeout(debounceTimer);
    debounceTimer = null;
  }

  if (trimmed.length === 0) {
    if (currentAbortController) {
      currentAbortController.abort();
      currentAbortController = null;
    }
    options.value = [];
    hasSearched.value = false;
    errorMessage.value = '';
    loading.value = false;
    isOpen.value = false;
    return;
  }

  isOpen.value = true;
  nextTick(() => updateDropdownPosition());

  if (trimmed.length < 2) {
    if (currentAbortController) {
      currentAbortController.abort();
      currentAbortController = null;
    }
    options.value = [];
    hasSearched.value = false;
    errorMessage.value = '';
    loading.value = false;
    return;
  }

  loading.value = true;
  debounceTimer = setTimeout(() => {
    executeSearch(trimmed);
  }, 300);
};

const handleFocus = () => {
  if (isDisabled.value) return;
  if (!selectedStockItem.value && trimmedQuery.value.length >= 1) {
    isOpen.value = true;
    nextTick(() => updateDropdownPosition());
  }
};

const handleBoxClick = () => {
  if (isDisabled.value) return;
  if (inputRef.value) {
    inputRef.value.focus();
  }
};

const handleSelect = (item) => {
  selectedStockItem.value = item;
  searchQuery.value = item.item_name;
  isOpen.value = false;
  hasSearched.value = false;
  options.value = [];
  errorMessage.value = '';

  emit('update:modelValue', item.stock_id);
  emit('select', item);
};

const handleClear = () => {
  if (isDisabled.value) return;
  selectedStockItem.value = null;
  searchQuery.value = '';
  options.value = [];
  hasSearched.value = false;
  errorMessage.value = '';
  loading.value = false;
  isOpen.value = false;
  highlightedIndex.value = -1;

  if (currentAbortController) {
    currentAbortController.abort();
    currentAbortController = null;
  }

  emit('update:modelValue', '');
  emit('clear');

  if (inputRef.value) {
    inputRef.value.focus();
  }
};

const handleKeyDown = (e) => {
  if (!isOpen.value || selectedStockItem.value) {
    if (e.key === 'ArrowDown' && !selectedStockItem.value && trimmedQuery.value.length >= 2) {
      isOpen.value = true;
      nextTick(() => updateDropdownPosition());
      e.preventDefault();
    }
    return;
  }

  if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (options.value.length > 0) {
      highlightedIndex.value = (highlightedIndex.value + 1) % options.value.length;
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (options.value.length > 0) {
      highlightedIndex.value = (highlightedIndex.value - 1 + options.value.length) % options.value.length;
    }
  } else if (e.key === 'Enter') {
    if (highlightedIndex.value >= 0 && highlightedIndex.value < options.value.length) {
      e.preventDefault();
      handleSelect(options.value[highlightedIndex.value]);
    }
  } else if (e.key === 'Escape') {
    e.preventDefault();
    isOpen.value = false;
  }
};

const handleClickOutside = (event) => {
  const clickedInContainer = containerRef.value && containerRef.value.contains(event.target);
  const clickedInDropdown = dropdownMenuRef.value && dropdownMenuRef.value.contains(event.target);

  if (!clickedInContainer && !clickedInDropdown) {
    isOpen.value = false;
  }
};

const retrySearch = () => {
  errorMessage.value = '';
  if (trimmedQuery.value.length >= 2) {
    executeSearch(trimmedQuery.value);
  }
};

const formatRupiah = (val) => {
  if (val == null) return 'Rp 0';
  return 'Rp ' + Number(val).toLocaleString('id-ID');
};

watch(
  () => props.warehouseId,
  (newWh, oldWh) => {
    if (newWh !== oldWh) {
      handleClear();
    }
  }
);

watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal && selectedStockItem.value) {
      selectedStockItem.value = null;
      searchQuery.value = '';
    }
  }
);

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('scroll', updateDropdownPosition, true);
  window.addEventListener('resize', updateDropdownPosition);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('scroll', updateDropdownPosition, true);
  window.removeEventListener('resize', updateDropdownPosition);
  if (debounceTimer) clearTimeout(debounceTimer);
  if (currentAbortController) currentAbortController.abort();
});
</script>

<template>
  <div ref="containerRef" class="stock-select-container">
    <div
      class="stock-select-input-box"
      :class="{
        'is-disabled': isDisabled,
        'has-error': !!error,
        'is-open': isOpen && shouldShowDropdown
      }"
      @click="handleBoxClick"
    >
      <span class="search-icon-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </span>

      <input
        :id="id"
        ref="inputRef"
        type="text"
        class="stock-select-input"
        :value="displayInputValue"
        :placeholder="displayPlaceholder"
        :disabled="isDisabled"
        autocomplete="off"
        @input="handleInput"
        @focus="handleFocus"
        @keydown="handleKeyDown"
      />

      <div class="input-controls-right">
        <span v-if="loading" class="spinner-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
          </svg>
        </span>

        <button
          v-if="selectedStockItem || searchQuery"
          type="button"
          class="clear-btn"
          title="Hapus pilihan"
          tabindex="-1"
          @click.stop="handleClear"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <span class="chevron-indicator" :class="{ 'chevron-up': isOpen && shouldShowDropdown }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </span>
      </div>
    </div>

    <!-- Teleported Dropdown List -->
    <Teleport to="body">
      <div
        v-if="shouldShowDropdown"
        ref="dropdownMenuRef"
        class="stock-select-dropdown stock-select-dropdown-teleported"
        :style="dropdownStyle"
      >
        <div v-if="trimmedQuery.length === 1" class="dropdown-state-box info-hint">
          <span class="hint-text text-muted">Ketik minimal 2 karakter untuk mencari sediaan...</span>
        </div>

        <div v-else-if="loading" class="dropdown-state-box loading-box">
          <span class="spinner-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
            </svg>
          </span>
          <span class="text-muted">Mencari stok sediaan di {{ warehouseName || 'gudang' }}...</span>
        </div>

        <div v-else-if="errorMessage" class="dropdown-state-box error-box">
          <span class="text-danger">{{ errorMessage }}</span>
          <button type="button" class="btn-retry" @click="retrySearch">
            Coba Lagi
          </button>
        </div>

        <div v-else-if="options.length === 0 && hasSearched" class="dropdown-state-box empty-box">
          <div class="empty-title font-semibold text-navy">Sediaan tidak ditemukan</div>
          <div class="empty-sub text-muted">
            Tidak ada stok aktif di {{ warehouseName || 'gudang' }} yang cocok dengan "{{ searchQuery }}".
          </div>
        </div>

        <!-- Options List -->
        <ul v-else-if="options.length > 0" class="stock-options-list">
          <li
            v-for="(item, index) in options"
            :key="item.stock_id || index"
            class="stock-option-item"
            :class="{
              'is-highlighted': index === highlightedIndex
            }"
            @click="handleSelect(item)"
            @mouseenter="highlightedIndex = index"
          >
            <div class="option-row-main">
              <div class="option-name font-semibold text-navy">
                {{ item.item_name }}
              </div>
              <div class="option-stock-badge">
                <span class="stock-badge font-bold">
                  {{ item.available_stock }} {{ item.unit_name }}
                </span>
              </div>
            </div>
            <div class="option-meta-row">
              <span class="meta-tag">
                Kode: <strong>{{ item.item_code }}</strong>
              </span>
              <span class="meta-sep">•</span>
              <span class="meta-tag">
                Batch: <strong>{{ item.batch_number }}</strong>
              </span>
              <span class="meta-sep">•</span>
              <span class="meta-tag">
                Exp: <strong>{{ item.expiry_date }}</strong>
              </span>
              <span class="meta-sep">•</span>
              <span class="meta-tag text-teal font-medium">
                Harga: <strong>{{ formatRupiah(item.selling_price) }}</strong>
              </span>
            </div>
          </li>
        </ul>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.stock-select-container {
  position: relative;
  width: 100%;
}

.stock-select-input-box {
  display: flex;
  align-items: center;
  position: relative;
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  background-color: #ffffff;
  padding: 0 0.75rem;
  transition: all 0.15s ease;
  min-height: 42px;
  height: 42px;
  box-sizing: border-box;
}

.stock-select-input-box:focus-within {
  border-color: #008779;
  box-shadow: 0 0 0 3px rgba(0, 135, 121, 0.15);
}

.stock-select-input-box.is-disabled {
  background-color: #f8fafc;
  cursor: not-allowed;
  opacity: 0.75;
}

.stock-select-input-box.has-error {
  border-color: #ef4444;
}

.search-icon-left {
  display: flex;
  align-items: center;
  color: #94a3b8;
  margin-right: 0.5rem;
  flex-shrink: 0;
}

.search-icon-left svg {
  width: 18px;
  height: 18px;
}

.stock-select-input {
  flex: 1;
  border: none;
  outline: none;
  padding: 0;
  font-size: 0.875rem;
  color: #1e293b;
  background: transparent;
  min-width: 0;
  height: 100%;
}

.stock-select-input:disabled {
  cursor: not-allowed;
  color: #94a3b8;
}

.input-controls-right {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-left: 0.5rem;
  flex-shrink: 0;
}

.clear-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.clear-btn:hover {
  color: #ef4444;
  background-color: #fef2f2;
}

.clear-btn svg {
  width: 14px;
  height: 14px;
}

.chevron-indicator {
  display: flex;
  align-items: center;
  color: #94a3b8;
  transition: transform 0.2s ease;
}

.chevron-indicator svg {
  width: 16px;
  height: 16px;
}

.chevron-indicator.chevron-up {
  transform: rotate(180deg);
}

.spinner-icon {
  animation: spin 1s linear infinite;
  display: flex;
  align-items: center;
  color: #008779;
}

.spinner-icon svg {
  width: 16px;
  height: 16px;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Dropdown */
.stock-select-dropdown {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.06);
  max-height: 280px;
  overflow-y: auto;
  overflow-x: hidden;
  box-sizing: border-box;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f8fafc;
}

.stock-select-dropdown::-webkit-scrollbar {
  width: 6px;
}

.stock-select-dropdown::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 4px;
}

.stock-select-dropdown::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.stock-select-dropdown::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dropdown-state-box {
  padding: 1.25rem 1rem;
  text-align: center;
  font-size: 0.85rem;
}

.dropdown-state-box.loading-box {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
}

.dropdown-state-box.error-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.btn-retry {
  padding: 0.3rem 0.75rem;
  font-size: 0.78rem;
  font-weight: 600;
  background-color: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  color: #334155;
  cursor: pointer;
}

.btn-retry:hover {
  background-color: #e2e8f0;
}

.empty-title {
  font-size: 0.875rem;
  margin-bottom: 0.2rem;
}

.empty-sub {
  font-size: 0.78rem;
}

/* Options List */
.stock-options-list {
  list-style: none;
  margin: 0;
  padding: 0.35rem 0;
}

.stock-option-item {
  padding: 0.65rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.15s ease;
}

.stock-option-item:last-child {
  border-bottom: none;
}

.stock-option-item.is-highlighted,
.stock-option-item:hover {
  background-color: #f0fdfa;
}

.option-row-main {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.25rem;
}

.option-name {
  font-size: 0.875rem;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  min-width: 0;
}

.option-stock-badge {
  flex-shrink: 0;
}

.stock-badge {
  background-color: #e0f2f1;
  color: #008779;
  font-size: 0.75rem;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  white-space: nowrap;
  display: inline-block;
}

.option-meta-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.45rem;
  font-size: 0.75rem;
  color: #64748b;
}

.meta-sep {
  color: #cbd5e1;
}

.text-teal {
  color: #0d9488;
}

.text-navy {
  color: #0f172a;
}

.text-muted {
  color: #64748b;
}

.text-danger {
  color: #ef4444;
}

.font-medium {
  font-weight: 500;
}

.font-semibold {
  font-weight: 600;
}

.font-bold {
  font-weight: 700;
}
</style>
