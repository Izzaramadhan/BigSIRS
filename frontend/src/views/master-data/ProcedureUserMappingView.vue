<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useProcedureUserMappings } from '@/composables/useProcedureUserMappings'
import ProcedureUserMappingTable from '@/components/master-data/procedure-user-mappings/ProcedureUserMappingTable.vue'
import ProcedureUserMappingFormModal from '@/components/master-data/procedure-user-mappings/ProcedureUserMappingFormModal.vue'

const {
  mappings,
  loading,
  error,
  meta,
  fetchMappings,
  deleteMapping
} = useProcedureUserMappings()

const searchQuery = ref('')
const sortField = ref('created_at')
const sortDir = ref('desc')

const showFormModal = ref(false)
const showDeleteConfirm = ref(false)
const selectedMapping = ref(null)
const selectedMappingId = ref(null)

const visiblePages = computed(() => {
  if (!meta.value || !meta.value.last_page) return []
  const current = meta.value.current_page
  const last = meta.value.last_page
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

const searchTimeout = ref(null)

const loadData = () => {
  fetchMappings({
    search: searchQuery.value,
    sort_by: sortField.value,
    sort_dir: sortDir.value,
    page: meta.value.current_page,
    per_page: meta.value.per_page
  })
}

const handleSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  searchTimeout.value = setTimeout(() => {
    meta.value.current_page = 1
    loadData()
  }, 400)
}

const handleSort = ({ column, direction }) => {
  sortField.value = column
  sortDir.value = direction
  loadData()
}

const changePage = (page) => {
  meta.value.current_page = page
  loadData()
}

const changePerPage = () => {
  meta.value.current_page = 1
  loadData()
}

const handleAdd = () => {
  selectedMappingId.value = null
  showFormModal.value = true
}

const handleEdit = (item) => {
  selectedMappingId.value = item.id
  showFormModal.value = true
}

const handleSaved = () => {
  showFormModal.value = false
  loadData()
}

const handleDeleteClick = (item) => {
  selectedMapping.value = item
  showDeleteConfirm.value = true
}

const confirmDelete = async () => {
  if (selectedMapping.value) {
    try {
      await deleteMapping(selectedMapping.value.id)
      showDeleteConfirm.value = false
      selectedMapping.value = null
      loadData()
    } catch {
      // error handled in composable
    }
  }
}

onMounted(() => {
  loadData()
})

watch(searchQuery, () => {
  handleSearch()
})
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Mapping Tindakan User</h1>
        <p class="page-subtitle">Kelola pemetaan tindakan dengan pegawai (medis/paramedis)</p>
      </div>
      <button class="btn btn-primary" @click="handleAdd">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Tambah Mapping
      </button>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <div class="filter-bar">
      <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input 
          type="text" 
          v-model="searchQuery" 
          class="form-control" 
          placeholder="Cari tindakan atau pegawai..." 
        />
      </div>

      <div class="filter-actions">
        <div class="per-page-select">
          <label>Tampilkan:</label>
          <select v-model="meta.per_page" class="form-control" @change="changePerPage">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <ProcedureUserMappingTable 
      :items="mappings" 
      :loading="loading"
      :sort-config="{ column: sortField, direction: sortDir }"
      @sort="handleSort"
      @edit="handleEdit" 
      @delete="handleDeleteClick" 
    />

    <div class="pagination-bar" v-if="meta.last_page > 1">
      <div class="pagination-info">
        Menampilkan {{ (meta.current_page - 1) * meta.per_page + 1 }} - 
        {{ Math.min(meta.current_page * meta.per_page, meta.total) }} 
        dari {{ meta.total }} data
      </div>
      <div class="pagination-controls">
        <button 
          class="btn-page" 
          :disabled="meta.current_page === 1"
          @click="changePage(meta.current_page - 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        
        <button 
          v-for="(page, index) in visiblePages" 
          :key="index"
          class="btn-page"
          :class="{ active: page === meta.current_page }"
          @click="page !== '...' ? changePage(page) : null"
          :disabled="page === '...'"
        >
          {{ page }}
        </button>

        <button 
          class="btn-page" 
          :disabled="meta.current_page === meta.last_page"
          @click="changePage(meta.current_page + 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <ProcedureUserMappingFormModal
      v-if="showFormModal"
      :id="selectedMappingId"
      @close="showFormModal = false"
      @saved="handleSaved"
    />

    <div v-if="showDeleteConfirm" class="modal-backdrop">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h3 class="modal-title">Konfirmasi Hapus</h3>
            <button class="close-btn" @click="showDeleteConfirm = false">×</button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus mapping untuk tindakan <strong>{{ selectedMapping?.procedure?.name }}</strong>?</p>
            <p class="text-sm text-secondary">
              Pegawai yang terkait dengan tindakan ini tidak akan lagi dapat menggunakannya.
            </p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="showDeleteConfirm = false" :disabled="loading">
              Batal
            </button>
            <button class="btn btn-danger" @click="confirmDelete" :disabled="loading">
              {{ loading ? 'Menghapus...' : 'Ya, Hapus Mapping' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: var(--color-text-secondary);
  margin: 0;
  font-size: 0.9rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
}

.alert-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
}

.filter-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  background: #fff;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
}

.search-box {
  position: relative;
  width: 300px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.search-box .form-control {
  padding-left: 2.25rem;
}

.filter-actions {
  display: flex;
  gap: 1rem;
}

.per-page-select {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--color-text-secondary);
  font-size: 0.9rem;
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
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover {
  background: var(--color-primary-dark);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}

.btn-secondary {
  background: transparent;
  border-color: var(--color-border);
  color: var(--color-text-navy);
}

.btn-secondary:hover {
  background: var(--color-bg-subtle);
}

.btn-danger {
  background: var(--color-danger);
  color: #fff;
}

.btn-danger:hover {
  background: #dc2626;
}

.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  gap: 0.25rem;
}

.btn-page {
  min-width: 32px;
  height: 32px;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--color-border-soft);
  background: #fff;
  border-radius: 6px;
  font-size: 0.85rem;
  color: var(--color-text-navy);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled):not(.active) {
  background: #f1f5f9;
}

.btn-page.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f8fafc;
}

.btn-page svg {
  width: 16px;
  height: 16px;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease;
}

.modal-dialog {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.3s ease;
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.modal-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: color 0.2s;
}

.close-btn:hover {
  color: var(--color-danger);
}

.modal-body {
  padding: 1.5rem;
  color: var(--color-text-navy);
}

.text-sm {
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.text-secondary {
  color: var(--color-text-secondary);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
