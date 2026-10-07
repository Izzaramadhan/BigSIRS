<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import lookupService from '@/services/lookup.service';

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  initialVillage: {
    type: Object,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Cari Kelurahan, Kecamatan...'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const searchQuery = ref('');
const isOpen = ref(false);
const dropdownRef = ref(null);
const searchInputRef = ref(null);

const options = ref([]);
const loading = ref(false);
const error = ref(null);

const selectedItem = ref(props.initialVillage);

watch(() => props.initialVillage, (newVal) => {
  if (newVal && (!selectedItem.value || selectedItem.value.id !== newVal.id)) {
    selectedItem.value = newVal;
  }
});

const getDisplayName = (opt) => {
  if (!opt) return '';
  const parts = [];
  if (opt.name) parts.push(opt.name);
  if (opt.district_name) parts.push(opt.district_name);
  if (opt.regency_name) parts.push(opt.regency_name);
  
  if (parts.length > 0) return parts.join(', ');
  return opt.code || 'Unknown';
};

const fetchOptions = async () => {
  if (loading.value) return;
  loading.value = true;
  error.value = null;

  try {
    const res = await lookupService.getVillages({
      search: searchQuery.value
    });
    
    options.value = res || [];
  } catch (err) {
    error.value = 'Gagal memuat wilayah';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = debounce(() => {
  fetchOptions();
}, 400);

const handleSearchInput = () => {
  debouncedSearch();
};

const selectOption = (opt) => {
  selectedItem.value = opt;
  emit('update:modelValue', opt.id);
  emit('change', opt);
  isOpen.value = false;
  searchQuery.value = '';
};

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    if (options.value.length === 0 && !searchQuery.value) {
      fetchOptions();
    }
    setTimeout(() => {
      if (searchInputRef.value) searchInputRef.value.focus();
    }, 100);
  } else {
    searchQuery.value = '';
  }
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
    searchQuery.value = '';
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div class="searchable-select" ref="dropdownRef">
    <div class="selected-box" :class="{ disabled: props.disabled }" @click="toggleDropdown">
      <div v-if="!selectedItem" class="placeholder">{{ placeholder }}</div>
      <div v-else class="selected-text">
        {{ getDisplayName(selectedItem) }}
      </div>
      <div class="caret">▼</div>
    </div>
    
    <div v-if="isOpen" class="dropdown-menu">
      <div class="search-box">
        <input 
          ref="searchInputRef"
          type="text" 
          v-model="searchQuery" 
          placeholder="Cari nama kelurahan/kecamatan..." 
          class="form-control form-control-sm"
          @input="handleSearchInput"
          @click.stop
        />
      </div>
      <div class="options-list">
        <div v-if="loading" class="status-msg">Memuat data...</div>
        <div v-else-if="error" class="status-msg text-red-500">{{ error }}</div>
        <div v-else-if="options.length === 0" class="status-msg">Tidak ada wilayah ditemukan</div>
        
        <template v-else>
          <div 
            v-for="opt in options" 
            :key="opt.id" 
            class="option-item"
            :class="{ 'is-selected': props.modelValue === opt.id }"
            @click.stop="selectOption(opt)"
          >
            <div class="opt-main">{{ opt.name }}</div>
            <div class="opt-sub">{{ opt.district_name || '' }}{{ opt.regency_name ? ', ' + opt.regency_name : '' }}</div>
          </div>
        </template>
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
  padding: 0.4rem 2rem 0.4rem 0.875rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  position: relative;
  transition: all 0.2s;
}

.selected-box:hover:not(.disabled) {
  border-color: #94a3b8;
}

.selected-box.disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
  opacity: 0.8;
}

.placeholder {
  color: #94a3b8;
  font-size: 0.875rem;
}

.selected-text {
  font-size: 0.875rem;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.caret {
  position: absolute;
  right: 0.875rem;
  font-size: 0.6rem;
  color: #64748b;
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
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 50;
  display: flex;
  flex-direction: column;
}

.search-box {
  padding: 0.5rem;
  border-bottom: 1px solid #cbd5e1;
}

.options-list {
  max-height: 250px;
  overflow-y: auto;
  padding: 0.25rem 0;
}

.option-item {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
}
.option-item:last-child {
  border-bottom: none;
}

.option-item:hover {
  background: #f8fafc;
}

.option-item.is-selected {
  background: #e0f2fe;
}

.opt-main {
  font-size: 0.875rem;
  color: #0f172a;
  font-weight: 500;
}

.opt-sub {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.status-msg {
  padding: 0.75rem;
  color: #64748b;
  font-size: 0.875rem;
  text-align: center;
}
.text-red-500 {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.875rem;
  box-sizing: border-box;
  outline: none;
}
.form-control:focus {
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.1);
}
</style>
