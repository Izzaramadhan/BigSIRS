<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    required: true
  },
  placeholder: {
    type: String,
    default: 'Pilih...'
  }
});

const emit = defineEmits(['update:modelValue']);

const searchQuery = ref('');
const isOpen = ref(false);
const dropdownRef = ref(null);

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  const q = searchQuery.value.toLowerCase();
  return props.options.filter(opt => (opt.name || '').toLowerCase().includes(q));
});

const selectedItems = computed(() => {
  return props.modelValue.map(id => props.options.find(o => o.id === id)).filter(Boolean);
});

const toggleSelect = (id) => {
  const newValue = [...props.modelValue];
  const index = newValue.indexOf(id);
  if (index === -1) {
    newValue.push(id);
  } else {
    newValue.splice(index, 1);
  }
  emit('update:modelValue', newValue);
};

const removeItem = (id) => {
  const newValue = props.modelValue.filter(v => v !== id);
  emit('update:modelValue', newValue);
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
  <div class="searchable-multi-select" ref="dropdownRef">
    <div class="selected-tags" @click="isOpen = !isOpen">
      <div v-if="selectedItems.length === 0" class="placeholder">{{ placeholder }}</div>
      <div v-for="item in selectedItems" :key="item.id" class="tag">
        {{ item.name }}
        <span class="remove-tag" @click.stop="removeItem(item.id)">&times;</span>
      </div>
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
        <div v-if="filteredOptions.length === 0" class="no-options">Tidak ada data ditemukan.</div>
        <div 
          v-for="opt in filteredOptions" 
          :key="opt.id" 
          class="option-item"
          @click.stop="toggleSelect(opt.id)"
        >
          <input type="checkbox" :checked="props.modelValue.includes(opt.id)" readonly />
          <span class="ml-2">{{ opt.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.searchable-multi-select {
  position: relative;
  width: 100%;
}

.selected-tags {
  min-height: 38px;
  padding: 0.3rem 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: #fff;
  cursor: pointer;
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  align-items: center;
}

.placeholder {
  color: #94a3b8;
  font-size: 0.95rem;
  padding-left: 0.25rem;
}

.tag {
  background: #e0f2fe;
  color: #0369a1;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.remove-tag {
  cursor: pointer;
  font-weight: bold;
  font-size: 1rem;
  line-height: 1;
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
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

.option-item:hover {
  background: #f1f5f9;
}

.ml-2 {
  margin-left: 0.5rem;
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
}
</style>
