<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import goodsIssuesService from '@/services/logistics/goodsIssues.service';

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
  issueDate: {
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

const dropdownRef = ref(null);
const inputRef = ref(null);

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

// Single input value display
const displayInputValue = computed(() => {
  if (selectedStockItem.value) {
    return selectedStockItem.value.item_name;
  }
  return searchQuery.value;
});

// Dropdown rendering conditions:
// Dropdown is ONLY shown if:
// 1. Not disabled
// 2. Dropdown is open
// 3. No medicine is currently selected
// 4. Query length >= 1 (1 char shows hint, >= 2 chars shows loading/results/empty/error)
const shouldShowDropdown = computed(() => {
  if (isDisabled.value) return false;
  if (!isOpen.value) return false;
  if (selectedStockItem.value) return false;
  if (trimmedQuery.value.length === 0) return false;
  return true;
});

// Map backend stock response into standard item
const mapStock = (it) => {
  if (!it) return null;
  const stockId = it.stock_id ?? it.id;
  const itemId = it.item_id ?? it.medicine_id ?? it.medicineId;
  const itemCode = it.item_code ?? it.medicine_code ?? it.medicineCode ?? '-';
  const itemName = it.item_name ?? it.medicine_name ?? it.medicineName ?? 'Sediaan Obat';
  const unitId = it.unit_id ?? it.medicine_unit_id ?? null;
  const unitName = it.unit_name ?? it.unit ?? 'Unit';
  const batchNumber = it.batch_number ?? it.batchNumber ?? '-';
  const expiryDate = it.expiry_date ?? it.expired_date ?? it.expiredDate ?? '-';
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
    expiredDate: expiryDate,
    available_stock: availableStock,
    availableStock,
    selling_price: sellingPrice,
    sellingPrice,
    price: sellingPrice,
    unit_price: sellingPrice,
    unitPrice: sellingPrice,
    warehouse_id: warehouseId,
    warehouseId,
    warehouse_name: warehouseName,
    warehouseName
  };
};

// Execute search with latest query and warehouseId
const executeSearch = async (query) => {
  if (!props.warehouseId || query.length < 2) {
    loading.value = false;
    return;
  }

  // Abort any prior in-flight request
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
      issue_date: props.issueDate || undefined,
      per_page: 20
    };

    let res;
    try {
      res = await goodsIssuesService.getAvailableStocks(
        props.warehouseId,
        params,
        { signal: currentAbortController.signal }
      );
    } catch (err) {
      if (err.name === 'CanceledError' || err.name === 'AbortError') return;
      res = await goodsIssuesService.getWarehouseStocks(
        { warehouse_id: props.warehouseId, ...params },
        { signal: currentAbortController.signal }
      );
    }

    // Ignore if a newer request has been initiated
    if (reqId !== activeRequestId.value) return;

    const rawList = Array.isArray(res?.data)
      ? res.data
      : (Array.isArray(res) ? res : (res?.data?.data || []));

    const mapped = rawList.map(mapStock).filter(Boolean);
    options.value = mapped;
    hasSearched.value = true;
    highlightedIndex.value = mapped.length > 0 ? 0 : -1;
  } catch (err) {
    if (err.name === 'CanceledError' || err.name === 'AbortError') return;
    if (reqId !== activeRequestId.value) return;

    errorMessage.value = 'Gagal memuat sediaan. Silakan coba lagi.';
    options.value = [];
    hasSearched.value = true;
  } finally {
    if (reqId === activeRequestId.value) {
      loading.value = false;
    }
  }
};

// Handle input typing
const handleInput = (e) => {
  if (isDisabled.value) return;

  // If a stock was previously selected, cancel previous selection immediately
  if (selectedStockItem.value) {
    selectedStockItem.value = null;
    emit('update:modelValue', '');
    emit('clear');
  }

  const q = e.target.value;
  searchQuery.value = q;
  const trimmed = q.trim();

  // Clear pending debounce timer
  if (debounceTimer) {
    clearTimeout(debounceTimer);
    debounceTimer = null;
  }

  // 1. Query empty: close dropdown, clear state, abort active request
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

  // 2. Query only 1 character: do not request, show hint
  if (trimmed.length === 1) {
    if (currentAbortController) {
      currentAbortController.abort();
      currentAbortController = null;
    }
    options.value = [];
    hasSearched.value = false;
    errorMessage.value = '';
    loading.value = false;
    isOpen.value = true;
    return;
  }

  // 3. Query >= 2 characters: open dropdown, trigger debounce
  isOpen.value = true;
  loading.value = true;
  errorMessage.value = '';

  debounceTimer = setTimeout(() => {
    executeSearch(trimmed);
  }, 350);
};

// Handle focus
const handleFocus = () => {
  if (isDisabled.value) return;
  // If already selected, do not reopen dropdown
  if (selectedStockItem.value) return;

  // If query is empty, do not open dropdown
  if (trimmedQuery.value.length === 0) return;

  // If query is 1 char, open to show hint
  if (trimmedQuery.value.length === 1) {
    isOpen.value = true;
    return;
  }

  // If query >= 2 chars, open and re-fetch if not searched
  isOpen.value = true;
  if (!hasSearched.value && !loading.value) {
    executeSearch(trimmedQuery.value);
  }
};

// Toggle dropdown click
const handleBoxClick = () => {
  if (isDisabled.value) return;
  inputRef.value?.focus();
};

// Keyboard navigation
const handleKeyDown = (e) => {
  if (isDisabled.value) return;

  if (e.key === 'ArrowDown') {
    e.preventDefault();
    if (!isOpen.value && trimmedQuery.value.length >= 2) {
      isOpen.value = true;
      if (!hasSearched.value) executeSearch(trimmedQuery.value);
      return;
    }
    if (options.value.length > 0) {
      highlightedIndex.value = (highlightedIndex.value + 1) % options.value.length;
    }
  } else if (e.key === 'ArrowUp') {
    e.preventDefault();
    if (!isOpen.value && trimmedQuery.value.length >= 2) {
      isOpen.value = true;
      return;
    }
    if (options.value.length > 0) {
      highlightedIndex.value = (highlightedIndex.value - 1 + options.value.length) % options.value.length;
    }
  } else if (e.key === 'Enter') {
    if (isOpen.value && highlightedIndex.value >= 0 && options.value[highlightedIndex.value]) {
      e.preventDefault();
      handleSelect(options.value[highlightedIndex.value]);
    }
  } else if (e.key === 'Escape') {
    isOpen.value = false;
  }
};

// Select Option
const handleSelect = (stockItem) => {
  selectedStockItem.value = stockItem;
  searchQuery.value = '';
  options.value = [];
  hasSearched.value = false;
  isOpen.value = false;
  highlightedIndex.value = -1;
  emit('update:modelValue', stockItem.stock_id);
  emit('select', stockItem);
};

// Native select change (for accessibility and testing)
const handleNativeChange = (val) => {
  if (!val) {
    handleClear();
    return;
  }
  const match = options.value.find((o) => String(o.stock_id) === String(val));
  if (match) {
    handleSelect(match);
  } else {
    emit('update:modelValue', Number(val) || val);
  }
};

// Clear Selection
const handleClear = (e) => {
  if (e) e.stopPropagation();
  selectedStockItem.value = null;
  searchQuery.value = '';
  options.value = [];
  hasSearched.value = false;
  isOpen.value = false;
  highlightedIndex.value = -1;
  if (currentAbortController) {
    currentAbortController.abort();
    currentAbortController = null;
  }
  emit('update:modelValue', '');
  emit('clear');
  inputRef.value?.focus();
};

// Retry fetch on error
const handleRetry = () => {
  if (trimmedQuery.value.length >= 2) {
    executeSearch(trimmedQuery.value);
  }
};

// Click outside handler
const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

// Watch Warehouse Change -> Reset all search and selection states completely
watch(
  () => props.warehouseId,
  (newWh, oldWh) => {
    if (newWh !== oldWh) {
      selectedStockItem.value = null;
      searchQuery.value = '';
      options.value = [];
      hasSearched.value = false;
      errorMessage.value = '';
      isOpen.value = false;

      if (debounceTimer) {
        clearTimeout(debounceTimer);
        debounceTimer = null;
      }
      if (currentAbortController) {
        currentAbortController.abort();
        currentAbortController = null;
      }

      if (oldWh !== undefined && props.modelValue) {
        emit('update:modelValue', '');
        emit('clear');
      }
      // Note: Do not auto-fetch with empty query!
    }
  },
  { immediate: true }
);

// Watch external modelValue updates
watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) {
      selectedStockItem.value = null;
    } else if (!selectedStockItem.value && options.value.length > 0) {
      const match = options.value.find((o) => String(o.stock_id) === String(newVal));
      if (match) selectedStockItem.value = match;
    }
  }
);

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (debounceTimer) clearTimeout(debounceTimer);
  if (currentAbortController) currentAbortController.abort();
});
</script>

<template>
  <div ref="dropdownRef" class="stock-searchable-select">
    <!-- Accessible hidden select element for automation and tests -->
    <select
      :id="id"
      :value="modelValue"
      class="sr-only"
      tabindex="-1"
      aria-hidden="true"
      :disabled="isDisabled"
      @change="(e) => handleNativeChange(e.target.value)"
    >
      <option value="">{{ displayPlaceholder }}</option>
      <option
        v-for="opt in options"
        :key="opt.stock_id"
        :value="opt.stock_id"
      >
        {{ opt.item_name }} (Stok: {{ opt.available_stock }} {{ opt.unit_name }})
      </option>
    </select>

    <!-- Single Main Search Box (Combobox) -->
    <div
      class="search-input-wrapper select-box"
      :class="{
        'is-disabled': isDisabled,
        'is-focused': isOpen && !selectedStockItem,
        'is-selected': Boolean(selectedStockItem),
        'is-invalid': Boolean(error)
      }"
      @click="handleBoxClick"
    >
      <!-- Left Search Icon -->
      <span class="input-icon-left" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </span>

      <!-- The Single Search Input -->
      <input
        ref="inputRef"
        type="text"
        class="main-search-input"
        :value="displayInputValue"
        :placeholder="displayPlaceholder"
        :disabled="isDisabled"
        autocomplete="off"
        @input="handleInput"
        @focus="handleFocus"
        @keydown="handleKeyDown"
      />

      <!-- Right Action Icons: Clear, Spinner, Chevron -->
      <div class="input-actions-right">
        <!-- Clear Button (when stock selected or query typed) -->
        <button
          v-if="(selectedStockItem || searchQuery) && !isDisabled"
          type="button"
          class="clear-btn"
          title="Hapus sediaan terpilih"
          aria-label="Hapus sediaan terpilih"
          @click.stop="handleClear"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>

        <!-- Loading Spinner -->
        <span v-if="loading" class="spin-icon-small" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"></circle>
          </svg>
        </span>

        <!-- Chevron Down / Up Icon -->
        <span
          v-else
          class="chevron-icon"
          :class="{ 'is-flipped': shouldShowDropdown }"
          aria-hidden="true"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"></polyline>
          </svg>
        </span>
      </div>
    </div>

    <!-- Floating Dropdown Popover (Conditional: only when typing with valid state) -->
    <div v-if="shouldShowDropdown" class="dropdown-popover">
      <div class="dropdown-results">
        <!-- 1. Hint State: Query is only 1 character -->
        <div v-if="trimmedQuery.length === 1" class="dropdown-state hint-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="hint-icon">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span class="text-secondary font-medium">Ketik minimal 2 karakter</span>
        </div>

        <!-- 2. Loading State -->
        <div v-else-if="loading && options.length === 0" class="dropdown-state loading-state">
          <svg class="spin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10" stroke-width="3" stroke-dasharray="32" stroke-linecap="round"></circle>
          </svg>
          <span>Mencari sediaan...</span>
        </div>

        <!-- 3. Error State -->
        <div v-else-if="errorMessage" class="dropdown-state error-state">
          <span class="text-danger font-medium">{{ errorMessage }}</span>
          <button type="button" class="btn-retry" @click.stop="handleRetry">
            Coba Lagi
          </button>
        </div>

        <!-- 4. Query Not Found in Warehouse -->
        <div v-else-if="hasSearched && options.length === 0" class="dropdown-state empty-state">
          <span class="text-secondary font-medium">Sediaan tidak ditemukan pada gudang ini</span>
        </div>

        <!-- 5. Options List -->
        <ul v-else-if="options.length > 0" class="options-list">
          <li
            v-for="(opt, idx) in options"
            :key="opt.stock_id"
            class="option-item"
            :class="{
              'is-active': String(opt.stock_id) === String(modelValue),
              'is-highlighted': highlightedIndex === idx
            }"
            @click="handleSelect(opt)"
          >
            <div class="option-header">
              <span class="med-name font-semibold text-navy">{{ opt.item_name }}</span>
            </div>
            <div class="option-meta text-xs text-muted">
              <span>Kode: <strong>{{ opt.item_code }}</strong></span>
              <span class="meta-dot">•</span>
              <span>Stok: <strong class="text-teal-700">{{ opt.available_stock }} {{ opt.unit_name }}</strong></span>
              <span class="meta-dot">•</span>
              <span>Batch: <strong>{{ opt.batch_number }}</strong></span>
              <template v-if="opt.expiry_date && opt.expiry_date !== '-'">
                <span class="meta-dot">•</span>
                <span>Exp: <strong>{{ opt.expiry_date }}</strong></span>
              </template>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stock-searchable-select {
  position: relative;
  width: 100%;
}

/* 1. Single Main Input Wrapper */
.search-input-wrapper {
  display: flex;
  align-items: center;
  position: relative;
  width: 100%;
  height: 44px;
  min-height: 44px;
  padding: 0 0.85rem;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-sizing: border-box;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  cursor: text;
}

.search-input-wrapper:hover:not(.is-disabled) {
  border-color: var(--color-primary, #087f78);
}

.search-input-wrapper.is-focused {
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.15);
}

.search-input-wrapper.is-selected {
  border-color: #94a3b8;
  background-color: #f8fafc;
}

.search-input-wrapper.is-disabled {
  background: #f8fafc;
  border-color: #e2e8f0;
  cursor: not-allowed;
  opacity: 0.85;
}

.search-input-wrapper.is-invalid {
  border-color: #ef4444;
  background-color: #fef2f2;
}

/* 2. Left Icon */
.input-icon-left {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  margin-right: 0.65rem;
  color: #94a3b8;
  flex-shrink: 0;
  pointer-events: none;
}

.input-icon-left svg {
  width: 16px;
  height: 16px;
}

/* 3. Main Input Element */
.main-search-input {
  flex: 1;
  width: 100%;
  height: 100%;
  padding: 0;
  border: none;
  outline: none;
  background: transparent;
  font-size: 0.875rem;
  color: #1e293b;
  font-family: inherit;
  box-sizing: border-box;
}

.main-search-input::placeholder {
  color: #94a3b8;
  font-size: 0.875rem;
}

.main-search-input:disabled {
  cursor: not-allowed;
  color: #94a3b8;
}

/* 4. Right Action Icons */
.input-actions-right {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin-left: 0.5rem;
  flex-shrink: 0;
}

.clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  padding: 0;
  background: #f1f5f9;
  color: #64748b;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.15s;
}

.clear-btn:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.clear-btn svg {
  width: 12px;
  height: 12px;
}

.chevron-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: transform 0.2s ease;
}

.chevron-icon svg {
  width: 16px;
  height: 16px;
}

.chevron-icon.is-flipped {
  transform: rotate(180deg);
}

.spin-icon-small {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  color: var(--color-primary, #087f78);
  animation: spin 1s linear infinite;
}

.spin-icon-small svg {
  width: 16px;
  height: 16px;
}

/* 5. Floating Dropdown Popover */
.dropdown-popover {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  width: 100%;
  min-width: 100%;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
  z-index: 9999;
  overflow: hidden;
  box-sizing: border-box;
  animation: fadeInDown 0.12s ease-out;
}

@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-results {
  max-height: 260px;
  overflow-y: auto;
}

/* Dropdown States */
.dropdown-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem 0.85rem;
  gap: 0.5rem;
  text-align: center;
  font-size: 0.825rem;
}

.dropdown-state.hint-state {
  flex-direction: row;
  padding: 0.85rem;
  gap: 0.45rem;
  color: #64748b;
}

.hint-icon {
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.dropdown-state.loading-state {
  flex-direction: row;
  color: var(--color-primary, #087f78);
  font-weight: 500;
  padding: 0.85rem;
}

.spin-icon {
  width: 18px;
  height: 18px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.dropdown-state.error-state {
  color: #ef4444;
}

.btn-retry {
  margin-top: 0.25rem;
  padding: 0.3rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  border-radius: 4px;
  background: transparent;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-retry:hover {
  background: var(--color-primary, #087f78);
  color: #ffffff;
}

/* Options List */
.options-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.option-item {
  padding: 0.65rem 0.85rem;
  border-bottom: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background-color 0.12s ease;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.option-item:last-child {
  border-bottom: none;
}

.option-item:hover,
.option-item.is-highlighted {
  background-color: #f0fdfa;
}

.option-item.is-active {
  background-color: #e6fffa;
  border-left: 3px solid var(--color-primary, #087f78);
}

.option-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.med-name {
  font-size: 0.875rem;
  line-height: 1.25;
}

.option-meta {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem;
  color: #64748b;
  font-size: 0.78rem;
  line-height: 1.2;
}

.meta-dot {
  color: #cbd5e1;
}

.text-teal-700 {
  color: #0f766e;
}

/* Accessibility */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
