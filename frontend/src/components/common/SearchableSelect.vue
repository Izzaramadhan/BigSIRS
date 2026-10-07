<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

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
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const isOpen = ref(false);
const dropdownRef = ref(null);

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  const q = searchQuery.value.toLowerCase();
  return props.options.filter(opt => 
    opt.name?.toLowerCase().includes(q) || 
    opt.code?.toLowerCase().includes(q) ||
    opt.description?.toLowerCase().includes(q)
  );
});

const selectedItem = computed(() => {
  return props.options.find(o => o.id === props.modelValue || o.value === props.modelValue);
});

const getDisplayName = (opt) => {
  if (opt.label) return opt.label;
  if (opt.code && (opt.name || opt.description)) {
    return `${opt.code} — ${opt.name || opt.description}`;
  }
  return opt.name || opt.description || opt.code || 'Unknown';
};

const selectOption = (opt) => {
  const id = opt.value !== undefined ? opt.value : opt.id;
  emit('update:modelValue', id);
  isOpen.value = false;
  searchQuery.value = '';
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
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
    <div class="selected-box" @click="isOpen = !isOpen">
      <div v-if="loading && !selectedItem" class="placeholder">Memuat...</div>
      <div v-else-if="!selectedItem" class="placeholder">{{ placeholder }}</div>
      <div v-else class="selected-text">{{ getDisplayName(selectedItem) }}</div>
      <div class="caret">▼</div>
    </div>
    
    <div v-if="isOpen" class="dropdown-menu">
      <div class="search-box">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Cari..." 
          class="form-control form-control-sm"
          @click.stop
        />
      </div>
      <div class="options-list">
        <div v-if="loading" class="no-options">Memuat data...</div>
        <div v-else-if="error" class="no-options text-danger">{{ error }}</div>
        <div v-else-if="filteredOptions.length === 0" class="no-options">Tidak ada data ditemukan.</div>
        <div 
          v-for="opt in filteredOptions" 
          :key="opt.value !== undefined ? opt.value : opt.id" 
          class="option-item"
          :class="{ 'is-selected': props.modelValue === (opt.value !== undefined ? opt.value : opt.id) }"
          @click.stop="selectOption(opt)"
        >
          {{ getDisplayName(opt) }}
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
  padding: 0.4rem 2rem 0.4rem 0.75rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  position: relative;
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
}

.caret {
  position: absolute;
  right: 0.75rem;
  font-size: 0.7rem;
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
  max-height: 250px;
  display: flex;
  flex-direction: column;
}

.search-box {
  padding: 0.5rem;
  border-bottom: 1px solid #cbd5e1;
}

.options-list {
  overflow-y: auto;
  padding: 0.25rem 0;
}

.option-item {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.9rem;
}

.option-item:hover {
  background: #f1f5f9;
}

.option-item.is-selected {
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 500;
}

.no-options {
  padding: 0.5rem 0.75rem;
  color: #64748b;
  font-size: 0.9rem;
  text-align: center;
}

.form-control {
  width: 100%;
  padding: 0.4rem 0.6rem;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.9rem;
  box-sizing: border-box;
}
</style>
