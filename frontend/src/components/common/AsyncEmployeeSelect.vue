<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import LookupService from '@/services/lookup.service'

const props = defineProps({
  modelValue: {
    type: [Array, String, Number],
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Cari pegawai...'
  },
  error: {
    type: Boolean,
    default: false
  },
  multiple: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchQuery = ref('')
const options = ref([])
const loading = ref(false)
const selectedOptions = ref([])

const dropdownRef = ref(null)

const fetchOptions = async (query = '') => {
  loading.value = true
  try {
    const response = await LookupService.getEmployees({
      search: query,
      is_active: true,
      per_page: 50
    })
    options.value = response.data ? (Array.isArray(response.data) ? response.data : (response.data.data || response.data)) : (response.data || response)
  } catch (err) {
    console.error('Failed to fetch employees', err)
  } finally {
    loading.value = false
  }
}

let debounceTimer = null
const handleSearch = (e) => {
  searchQuery.value = e.target.value
  isOpen.value = true
  
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchOptions(searchQuery.value)
  }, 300)
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value && options.value.length === 0) {
    fetchOptions()
  }
}

const selectOption = (option) => {
  if (props.multiple) {
    const index = selectedOptions.value.findIndex(item => item.id === option.id)
    if (index === -1) {
      selectedOptions.value.push(option)
    } else {
      selectedOptions.value.splice(index, 1)
    }
    const ids = selectedOptions.value.map(item => item.id)
    emit('update:modelValue', ids)
    emit('change', selectedOptions.value)
  } else {
    selectedOptions.value = [option]
    emit('update:modelValue', option.id)
    emit('change', option)
    isOpen.value = false
  }
  searchQuery.value = ''
}

const removeOption = (index) => {
  selectedOptions.value.splice(index, 1)
  const ids = selectedOptions.value.map(item => item.id)
  emit('update:modelValue', ids)
  emit('change', selectedOptions.value)
}

const isSelected = (option) => {
  return selectedOptions.value.some(item => item.id === option.id)
}

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  clearTimeout(debounceTimer)
})

watch(() => props.modelValue, async (newVal) => {
  if (!newVal || (Array.isArray(newVal) && newVal.length === 0)) {
    selectedOptions.value = []
    return
  }
  
  // Basic hydration logic if options not fully loaded
  // Real app might need a specific fetch-by-ids endpoint
  if (options.value.length === 0) {
    await fetchOptions()
  }

  if (props.multiple) {
    const selected = []
    newVal.forEach(id => {
      const opt = options.value.find(o => o.id === id)
      // Just a mock object if we don't find it in current page
      if (opt) selected.push(opt)
    })
    // If some were not found in current list, we might miss displaying their names correctly here
    // In complete implementation, we fetch /lookups/employees?ids[]=...
    // For now we will rely on initial form hydration to pass full objects if needed, or assume they are in first 50.
    selectedOptions.value = selected
  } else {
    const opt = options.value.find(o => o.id === newVal)
    if (opt) selectedOptions.value = [opt]
  }
}, { immediate: true })

// Expose setOptions to allow parent to set initial selected data (for Edit form)
const setInitialOptions = (initialData) => {
  if (Array.isArray(initialData)) {
    selectedOptions.value = initialData
  } else if (initialData) {
    selectedOptions.value = [initialData]
  }
}

defineExpose({ setInitialOptions })
</script>

<template>
  <div class="custom-select" ref="dropdownRef" :class="{ 'has-error': error }">
    <!-- Selected Items Container (Multiple) -->
    <div 
      v-if="multiple"
      class="selected-tags" 
      @click="toggleDropdown"
    >
      <span v-if="selectedOptions.length === 0" class="placeholder">
        {{ placeholder }}
      </span>
      <span 
        v-for="(item, index) in selectedOptions" 
        :key="item.id"
        class="tag"
        @click.stop
      >
        {{ item.name }} &mdash; {{ item.profession }}
        <button class="tag-remove" type="button" @click="removeOption(index)">×</button>
      </span>
    </div>

    <!-- Single Select Box -->
    <div 
      v-else
      class="select-box"
      @click="toggleDropdown"
    >
      <span v-if="selectedOptions.length === 0" class="placeholder">
        {{ placeholder }}
      </span>
      <span v-else class="selected-text">
        {{ selectedOptions[0].name }} &mdash; {{ selectedOptions[0].profession }}
      </span>
      <svg class="chevron" :class="{ 'chevron-up': isOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 12 15 18 9"></polyline>
      </svg>
    </div>

    <!-- Dropdown Menu -->
    <div v-show="isOpen" class="dropdown-menu">
      <div class="search-container">
        <input 
          type="text" 
          class="search-input" 
          v-model="searchQuery"
          @input="handleSearch"
          placeholder="Ketik untuk mencari..."
          @click.stop
        >
      </div>

      <div class="options-list">
        <div v-if="loading" class="loading-state">
          Memuat...
        </div>
        <div v-else-if="options.length === 0" class="empty-state">
          Tidak ada data
        </div>
        <template v-else>
          <div 
            v-for="option in options" 
            :key="option.id"
            class="option-item"
            :class="{ 'selected': isSelected(option) }"
            @click.stop="selectOption(option)"
          >
            <div class="option-content">
              <span class="option-name">{{ option.code }} &mdash; {{ option.name }} &mdash; {{ option.profession }}</span>
            </div>
            <div v-if="isSelected(option) && multiple" class="check-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-select {
  position: relative;
  width: 100%;
}

.select-box, .selected-tags {
  min-height: 42px;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: #ffffff;
  cursor: pointer;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.selected-tags {
  flex-wrap: wrap;
  gap: 0.5rem;
}

.has-error .select-box, .has-error .selected-tags {
  border-color: var(--color-danger);
}

.placeholder {
  color: var(--color-text-secondary);
  font-size: 0.9rem;
}

.selected-text {
  color: var(--color-text-navy);
  font-size: 0.9rem;
  font-weight: 500;
  flex: 1;
}

.tag {
  background: var(--color-primary-light);
  color: var(--color-primary);
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.tag-remove {
  background: none;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  font-size: 1.1rem;
  line-height: 1;
  padding: 0;
  display: flex;
  align-items: center;
}

.tag-remove:hover {
  color: var(--color-danger);
}

.chevron {
  width: 16px;
  height: 16px;
  color: var(--color-text-secondary);
  transition: transform 0.2s;
  margin-left: auto;
}

.chevron-up {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  right: 0;
  background: #ffffff;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  z-index: 50;
  overflow: hidden;
}

.search-container {
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font-size: 0.9rem;
  outline: none;
}

.search-input:focus {
  border-color: var(--color-primary);
}

.options-list {
  max-height: 240px;
  overflow-y: auto;
}

.loading-state, .empty-state {
  padding: 1rem;
  text-align: center;
  color: var(--color-text-secondary);
  font-size: 0.9rem;
}

.option-item {
  padding: 0.75rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: background 0.2s;
  border-bottom: 1px solid var(--color-border-soft);
}

.option-item:last-child {
  border-bottom: none;
}

.option-item:hover {
  background: #f8fafc;
}

.option-item.selected {
  background: var(--color-primary-light);
}

.option-content {
  display: flex;
  flex-direction: column;
}

.option-name {
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.9rem;
}

.option-sub {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  margin-top: 2px;
}

.check-icon {
  color: var(--color-primary);
  width: 18px;
  height: 18px;
}
</style>
