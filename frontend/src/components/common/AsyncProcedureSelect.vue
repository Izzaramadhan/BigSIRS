<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import MedicalProcedureService from '@/services/procedure';

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
  initialProcedure: {
    type: Object,
    default: null
  },
  placeholder: {
    type: String,
    default: 'Cari Tindakan...'
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
const currentPage = ref(1);
const lastPage = ref(1);

const selectedItem = ref(props.initialProcedure);

watch(() => props.initialProcedure, (newVal) => {
  if (newVal && (!selectedItem.value || selectedItem.value.id !== newVal.id)) {
    selectedItem.value = newVal;
  }
});

const getDisplayName = (opt) => {
  if (!opt) return '';
  return `${opt.code || ''} — ${opt.name || 'Unknown'}`;
};



const fetchOptions = async (page = 1, append = false) => {
  if (loading.value) return;
  loading.value = true;
  error.value = null;

  try {
    const { data } = await MedicalProcedureService.getProcedures({
      page,
      per_page: 15,
      search: searchQuery.value,
      is_visible: true
    });
    
    if (append) {
      options.value = [...options.value, ...data.data];
    } else {
      options.value = data.data;
    }
    
    currentPage.value = data.meta.current_page;
    lastPage.value = data.meta.last_page;
  } catch (err) {
    error.value = 'Gagal memuat Master Tindakan';
    console.error(err);
  } finally {
    loading.value = false;
  }
};

const debouncedSearch = debounce(() => {
  fetchOptions(1, false);
}, 400);

const handleSearchInput = () => {
  debouncedSearch();
};

const onScroll = (e) => {
  const { scrollTop, scrollHeight, clientHeight } = e.target;
  if (scrollTop + clientHeight >= scrollHeight - 10) {
    if (currentPage.value < lastPage.value && !loading.value) {
      fetchOptions(currentPage.value + 1, true);
    }
  }
};

const selectOption = (opt) => {
  selectedItem.value = opt;
  emit('update:modelValue', opt.id);
  emit('change', opt);
  isOpen.value = false;
  searchQuery.value = '';
};

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    if (options.value.length === 0 && !searchQuery.value) {
      fetchOptions(1, false);
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
    <div class="selected-box" @click="toggleDropdown">
      <div v-if="!selectedItem" class="placeholder">{{ placeholder }}</div>
      <div v-else class="selected-text">
        <span v-if="selectedItem.is_visible === false" class="badge-inactive">Tidak Aktif</span>
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
          placeholder="Cari nama, kode..." 
          class="form-control form-control-sm"
          @input="handleSearchInput"
          @click.stop
        />
      </div>
      <div class="options-list" @scroll="onScroll">
        <div v-if="loading && options.length === 0" class="status-msg">Memuat data...</div>
        <div v-else-if="error" class="status-msg text-red-500">{{ error }}</div>
        <div v-else-if="options.length === 0" class="status-msg">Tidak ada Tindakan ditemukan</div>
        
        <template v-else>
          <div 
            v-for="opt in options" 
            :key="opt.id" 
            class="option-item"
            :class="{ 'is-selected': props.modelValue === opt.id }"
            @click.stop="selectOption(opt)"
          >
            <div class="opt-main">{{ getDisplayName(opt) }}</div>
            <div class="opt-sub" v-if="opt.category || (opt.tariffs && opt.tariffs.length)">
              {{ opt.category ? opt.category.name : 'Tanpa Kategori' }} &bull; 
              <span v-for="(t, i) in opt.tariffs" :key="t.id">
                {{ t.tariff_type?.name }} (Rp{{ (Number(t.total_amount) || 0).toLocaleString('id-ID') }}){{ i < opt.tariffs.length - 1 ? ', ' : '' }}
              </span>
            </div>
          </div>
          <div v-if="loading && options.length > 0" class="status-msg text-sm">Memuat lebih banyak...</div>
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
  min-height: 34px;
  padding: 0.3rem 2rem 0.3rem 0.6rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  position: relative;
}

.placeholder {
  color: #94a3b8;
  font-size: 0.85rem;
}

.selected-text {
  font-size: 0.85rem;
  color: #0f172a;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.badge-inactive {
  background: #fee2e2;
  color: #991b1b;
  font-size: 0.7rem;
  padding: 0.1rem 0.3rem;
  border-radius: 4px;
  font-weight: 500;
}

.caret {
  position: absolute;
  right: 0.6rem;
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
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
  background: #f1f5f9;
}

.option-item.is-selected {
  background: #e0f2fe;
}

.opt-main {
  font-size: 0.85rem;
  color: #0f172a;
  font-weight: 500;
}

.opt-sub {
  font-size: 0.75rem;
  color: #64748b;
  margin-top: 0.15rem;
}

.status-msg {
  padding: 0.5rem 0.75rem;
  color: #64748b;
  font-size: 0.85rem;
  text-align: center;
}
.text-red-500 {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.4rem 0.6rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.85rem;
  box-sizing: border-box;
  outline: none;
}
.form-control:focus {
  border-color: var(--color-primary, #3b82f6);
}
</style>
