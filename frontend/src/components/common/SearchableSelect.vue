<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Pilih...'
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari...'
  },
  emptyText: {
    type: String,
    default: 'Tidak ada data ditemukan.'
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  clearable: {
    type: Boolean,
    default: false
  },
  isInvalid: {
    type: Boolean,
    default: false
  },
  serverSearch: {
    type: Boolean,
    default: false
  },
  id: {
    type: String,
    default: ''
  },
  creatable: {
    type: Boolean,
    default: false
  },
  createPrompt: {
    type: [String, Function],
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'search', 'create']);

const searchQuery = ref('');
const isOpen = ref(false);
const dropdownRef = ref(null);
const searchInputRef = ref(null);

let debounceTimeout = null;

const filteredOptions = computed(() => {
  if (props.serverSearch) {
    return props.options;
  }
  if (!searchQuery.value) return props.options;
  const q = searchQuery.value.toLowerCase();
  return props.options.filter((opt) =>
    opt.name?.toLowerCase().includes(q) ||
    opt.code?.toLowerCase().includes(q) ||
    opt.label?.toLowerCase().includes(q) ||
    opt.description?.toLowerCase().includes(q)
  );
});

const selectedItem = computed(() => {
  if (props.modelValue === '' || props.modelValue === null || props.modelValue === undefined) {
    return null;
  }
  return props.options.find((o) =>
    String(o.id) === String(props.modelValue) ||
    String(o.value) === String(props.modelValue)
  );
});

const getDisplayName = (opt) => {
  if (!opt) return '';
  if (opt.label) return opt.label;
  if (opt.code && (opt.name || opt.description)) {
    return `${opt.code} — ${opt.name || opt.description}`;
  }
  return opt.name || opt.description || opt.code || 'Unknown';
};

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    setTimeout(() => {
      if (searchInputRef.value) {
        searchInputRef.value.focus();
      }
    }, 50);
  }
};

const selectOption = (opt) => {
  const id = opt.value !== undefined ? opt.value : opt.id;
  emit('update:modelValue', id);
  isOpen.value = false;
  searchQuery.value = '';
};

const clearSelection = (e) => {
  e.stopPropagation();
  emit('update:modelValue', '');
  searchQuery.value = '';
  if (props.serverSearch) {
    emit('search', '');
  }
};

const handleSearchInput = (e) => {
  const val = e.target.value;
  searchQuery.value = val;
  if (props.serverSearch) {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
      emit('search', val);
    }, 300);
  }
};

const onNativeSelectChange = (e) => {
  emit('update:modelValue', e.target.value);
};

const hasExactMatch = computed(() => {
  const q = searchQuery.value.trim().replace(/\s+/g, ' ').toLowerCase();
  if (!q) return true;
  return props.options.some((opt) => {
    const name = (opt.name || opt.label || '').trim().replace(/\s+/g, ' ').toLowerCase();
    return name === q;
  });
});

const handleCreateClick = () => {
  const text = searchQuery.value.trim().replace(/\s+/g, ' ');
  emit('create', text);
  isOpen.value = false;
};

const createPromptText = computed(() => {
  const q = searchQuery.value.trim();
  if (typeof props.createPrompt === 'function') {
    return props.createPrompt(q);
  }
  if (props.createPrompt) {
    return props.createPrompt.replace('{name}', q).replace('[name]', q).replace('[nama]', q);
  }
  return `Tambahkan “${q}” sebagai generik baru`;
});

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

watch(
  () => props.modelValue,
  () => {
    // When modelValue updates, if empty, reset search
    if (props.modelValue === '' || props.modelValue === null) {
      searchQuery.value = '';
    }
  }
);

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (debounceTimeout) clearTimeout(debounceTimeout);
});
</script>

<template>
  <div class="searchable-select" ref="dropdownRef">
    <!-- Accessible / test compatible select element -->
    <select
      v-if="id"
      :id="id"
      :value="modelValue"
      class="sr-only"
      tabindex="-1"
      aria-hidden="true"
      :disabled="disabled"
      @change="onNativeSelectChange"
    >
      <option value="">{{ placeholder }}</option>
      <option
        v-for="opt in options"
        :key="opt.value !== undefined ? opt.value : opt.id"
        :value="String(opt.value !== undefined ? opt.value : opt.id)"
      >
        {{ getDisplayName(opt) }}
      </option>
    </select>

    <!-- Visual Combobox Box -->
    <div
      class="selected-box"
      :class="{
        'is-invalid': isInvalid,
        'is-disabled': disabled,
        'is-open': isOpen
      }"
      @click="toggleDropdown"
    >
      <div v-if="loading && !selectedItem" class="placeholder">Memuat...</div>
      <div v-else-if="!selectedItem" class="placeholder">{{ placeholder }}</div>
      <div v-else class="selected-text" :title="getDisplayName(selectedItem)">
        {{ getDisplayName(selectedItem) }}
      </div>

      <div class="box-actions">
        <button
          v-if="clearable && selectedItem && !disabled"
          type="button"
          class="clear-btn"
          title="Hapus pilihan"
          @click="clearSelection"
        >
          ×
        </button>
        <div class="caret">▼</div>
      </div>
    </div>

    <!-- Dropdown Menu -->
    <div v-if="isOpen" class="dropdown-menu">
      <div class="search-box">
        <input autocomplete="off"
          ref="searchInputRef"
          type="text"
          :value="searchQuery"
          :placeholder="searchPlaceholder"
          class="form-control form-control-sm"
          @input="handleSearchInput"
          @click.stop
        />
      </div>
      <div class="options-list">
        <div v-if="loading" class="no-options">Memuat data...</div>
        <div v-else-if="error" class="no-options text-danger">{{ error }}</div>
        <div v-else-if="filteredOptions.length === 0" class="no-options">
          {{ emptyText }}
        </div>
        <div
          v-for="opt in filteredOptions"
          :key="opt.value !== undefined ? opt.value : opt.id"
          class="option-item"
          :class="{
            'is-selected':
              String(props.modelValue) ===
              String(opt.value !== undefined ? opt.value : opt.id)
          }"
          @click.stop="selectOption(opt)"
        >
          {{ getDisplayName(opt) }}
        </div>

        <!-- Create New Option -->
        <div
          v-if="creatable && searchQuery.trim() && !hasExactMatch && !loading"
          class="create-option-item"
          @click.stop="handleCreateClick"
        >
          <span class="create-plus">+</span>
          {{ createPromptText }}
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.searchable-select {
  position: relative;
  width: 100%;
}

.selected-box {
  min-height: 38px;
  padding: 0.45rem 2.25rem 0.45rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  position: relative;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.selected-box:hover:not(.is-disabled) {
  border-color: #94a3b8;
}

.selected-box.is-open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.selected-box.is-invalid {
  border-color: #ef4444;
}

.selected-box.is-invalid.is-open {
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.selected-box.is-disabled {
  background-color: #f8fafc;
  cursor: not-allowed;
  opacity: 0.7;
}

.placeholder {
  color: #94a3b8;
  font-size: 0.95rem;
}

.selected-text {
  font-size: 0.95rem;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding-right: 0.5rem;
}

.box-actions {
  position: absolute;
  right: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.clear-btn {
  background: none;
  border: none;
  color: #94a3b8;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.clear-btn:hover {
  color: #475569;
}

.caret {
  font-size: 0.7rem;
  color: #64748b;
  pointer-events: none;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 4px;
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  z-index: 1050;
  max-height: 260px;
  display: flex;
  flex-direction: column;
}

.search-box {
  padding: 0.5rem;
  border-bottom: 1px solid #cbd5e1;
  background: #f8fafc;
}

.options-list {
  overflow-y: auto;
  max-height: 200px;
  padding: 0.25rem 0;
}

.option-item {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #1e293b;
  transition: background-color 0.1s ease;
}

.option-item:hover {
  background: #f1f5f9;
}

.option-item.is-selected {
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 500;
}

.create-option-item {
  padding: 0.625rem 0.75rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: var(--color-primary, #0f766e);
  background-color: #f0fdfa;
  border-top: 1px dashed #99f6e4;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.create-option-item:hover {
  background-color: #ccfbf1;
  color: #115e59;
}

.create-plus {
  font-size: 1.15rem;
  font-weight: 700;
  line-height: 1;
}

.no-options {
  padding: 0.75rem;
  color: #64748b;
  font-size: 0.9rem;
  text-align: center;
}

.text-danger {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.4rem 0.6rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.9rem;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
