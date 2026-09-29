<script setup>
import { onMounted, ref, computed } from 'vue'
import { useIcd9Cms } from '@/composables/useIcd9Cms'
import Icd9CmTable from '@/components/master-data/icd9-cms/Icd9CmTable.vue'
import Icd9CmFormModal from '@/components/master-data/icd9-cms/Icd9CmFormModal.vue'

const {
  codes,
  loading,
  pagination,
  filters,
  fetchCodes,
  createCode,
  updateCode,
  updateStatus,
  deleteCode
} = useIcd9Cms()

const isModalOpen = ref(false)
const selectedCode = ref(null)
const searchTimeout = ref(null)

const visiblePages = computed(() => {
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage
  const delta = 2
  const range = []
  const rangeWithDots = []
  let l

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      range.push(i)
    }
  }

  for (let i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1)
      } else if (i - l !== 1) {
        rangeWithDots.push('...')
      }
    }
    rangeWithDots.push(i)
    l = i
  }

  return rangeWithDots
})

onMounted(() => {
  fetchCodes()
})

const handleSearch = (e) => {
  if (searchTimeout.value) clearTimeout(searchTimeout.value)
  searchTimeout.value = setTimeout(() => {
    filters.value.search = e.target.value
    fetchCodes(1)
  }, 500)
}

const handleFilterChange = () => {
  fetchCodes(1)
}

const handleAdd = () => {
  selectedCode.value = null
  isModalOpen.value = true
}

const handleEdit = (code) => {
  selectedCode.value = code
  isModalOpen.value = true
}

const handleSubmit = async (formData) => {
  const success = selectedCode.value
    ? await updateCode(selectedCode.value.id, formData)
    : await createCode(formData)

  if (success) {
    isModalOpen.value = false
  }
}

const handlePageChange = (page) => {
  fetchCodes(page)
}
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">ICD-9-CM</h1>
        <p class="page-subtitle">Kelola referensi kode prosedur medis ICD-9-CM.</p>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="handleAdd">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon">
            <path d="M12 5v14M5 12h14" />
          </svg>
          Tambah ICD-9-CM
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="filters">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input 
              type="text" 
              class="form-control" 
              placeholder="Cari kode atau nama prosedur..."
              :value="filters.search"
              @input="handleSearch"
            >
          </div>
          <select class="form-control" v-model="filters.is_active" @change="handleFilterChange">
            <option :value="null">Semua Status</option>
            <option :value="true">Aktif</option>
            <option :value="false">Nonaktif</option>
          </select>
        </div>
      </div>

      <div class="card-body p-0">
        <div v-if="loading && codes.length === 0" class="loading-state">
          <div class="spinner"></div>
          <p>Memuat data ICD-9-CM...</p>
        </div>
        
        <Icd9CmTable 
          v-else
          :items="codes"
          @edit="handleEdit"
          @toggle-status="(id, status) => updateStatus(id, status)"
          @delete="deleteCode"
        />

        <div class="pagination-bar" v-if="pagination.lastPage > 1">
          <div class="pagination-info">
            Menampilkan halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} 
            (Total {{ pagination.total }} data)
          </div>
          <div class="pagination-controls">
            <button 
              class="btn-page" 
              :disabled="pagination.currentPage === 1"
              @click="handlePageChange(pagination.currentPage - 1)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            
            <button 
              v-for="(page, index) in visiblePages" 
              :key="index"
              class="btn-page"
              :class="{ active: page === pagination.currentPage }"
              @click="page !== '...' ? handlePageChange(page) : null"
              :disabled="page === '...'"
            >
              {{ page }}
            </button>
            
            <button 
              class="btn-page" 
              :disabled="pagination.currentPage === pagination.lastPage"
              @click="handlePageChange(pagination.currentPage + 1)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <Icd9CmFormModal 
      v-if="isModalOpen"
      :item="selectedCode"
      @close="isModalOpen = false"
      @submit="handleSubmit"
    />
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  color: var(--color-text-secondary);
  margin: 0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn-primary {
  background-color: var(--color-primary);
  color: white;
}

.btn-primary:hover {
  background-color: var(--color-primary-dark);
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border: 1px solid var(--color-border);
}

.card-header {
  padding: 1.25rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.filters {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-box {
  position: relative;
  flex: 1;
  max-width: 400px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1.25rem;
  height: 1.25rem;
  color: var(--color-text-secondary);
}

.search-box .form-control {
  padding-left: 2.5rem;
  width: 100%;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.9rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.1);
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-top: 1px solid var(--color-border-soft);
  background: white;
}

.pagination-info {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.btn-page {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: white;
  color: var(--color-text-navy);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page svg {
  width: 16px;
  height: 16px;
}

.btn-page:hover:not(:disabled) {
  background: var(--color-page-bg);
  border-color: var(--color-border);
}

.btn-page.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: var(--color-page-bg);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: var(--color-text-secondary);
}

.spinner {
  width: 2rem;
  height: 2rem;
  border: 3px solid var(--color-border-soft);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
