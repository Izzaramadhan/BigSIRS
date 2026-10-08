<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRadiologyGroups } from '@/composables/master-data/radiology/useRadiologyGroups'
import RadiologyGroupModal from './components/RadiologyGroupModal.vue'

const {
  items,
  loading,
  error,
  currentPage,
  perPage,
  totalItems,
  fetchItems,
  deleteItem,
  toggleStatus
} = useRadiologyGroups()

const searchQuery = ref('')
const showModal = ref(false)
const selectedItem = ref(null)

const handleSearch = () => {
  currentPage.value = 1
  fetchItems({ search: searchQuery.value })
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchItems({ search: searchQuery.value })
}

const handleAdd = () => {
  selectedItem.value = null
  showModal.value = true
}

const handleEdit = (item) => {
  selectedItem.value = item
  showModal.value = true
}

const handleSaved = () => {
  fetchItems({ search: searchQuery.value })
}

const handleDelete = async (item) => {
  if (confirm(`Anda yakin ingin menghapus group "${item.name}"?`)) {
    try {
      await deleteItem(item.id)
      fetchItems({ search: searchQuery.value })
    } catch {
      // Error handled by composable
    }
  }
}

const handleToggleStatus = async (item) => {
  try {
    await toggleStatus(item.id, item.is_active)
    fetchItems({ search: searchQuery.value })
  } catch {
    // Error handled by composable
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

watch(perPage, () => {
  currentPage.value = 1
  fetchItems({ search: searchQuery.value })
})

onMounted(() => {
  fetchItems()
})
</script>

<template>
  <div class="page-container">
    <div class="breadcrumb">
      <span>Home</span>
      <span class="separator">•</span>
      <span>Master Data</span>
      <span class="separator">•</span>
      <span>Data Radiologi</span>
      <span class="separator">•</span>
      <span class="current">Data Grup Radiologi</span>
    </div>

    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <svg xmlns="http://www.w3.org/2000/svg" class="title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
          </svg>
          Group Rad
        </h1>
        <p class="page-subtitle">Manajemen Master Data Grup Radiologi</p>
      </div>
    </div>

    <div class="card">
      <div class="filters-container">
        <div class="filter-actions">
          <button class="btn-primary" @click="handleAdd">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Group Rad
          </button>
          
          <select v-model="perPage" @change="handlePageChange(1)" class="form-select filter-select">
            <option value="10">10 data</option>
            <option value="25">25 data</option>
            <option value="50">50 data</option>
            <option value="100">100 data</option>
          </select>
        </div>

        <div class="search-box">
          <input 
            type="text" 
            v-model="searchQuery" 
            @keyup.enter="handleSearch"
            placeholder="Cari nama / loinc..." 
            class="search-input"
          >
        </div>
      </div>

      <div v-if="error && !items.length" class="error-state">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p>{{ error }}</p>
        <button class="btn-outline" @click="handleSearch">Coba Lagi</button>
      </div>

      <div class="table-wrapper">
        <div class="table-container">
          <table class="data-table">
            <thead>
              <tr>
                <th class="col-no text-center">No</th>
                <th class="col-name">Nama Group</th>
                <th class="col-name">Tipe</th>
                <th class="col-name">Kategori</th>
                <th class="col-name">Kelompok Pemeriksaan</th>
                <th class="col-name">Harga</th>
                <th class="col-name">Harga Interpretasi</th>
                <th class="col-status text-center">Status</th>
                <th class="col-actions text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <template v-if="loading">
                <tr v-for="i in perPage" :key="`skeleton-${i}`">
                  <td><div class="skeleton-box skeleton-small mx-auto"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td><div class="skeleton-box skeleton-large"></div></td>
                  <td><div class="skeleton-box skeleton-medium"></div></td>
                  <td><div class="skeleton-box skeleton-medium"></div></td>
                  <td><div class="skeleton-box skeleton-medium mx-auto"></div></td>
                  <td>
                    <div class="action-buttons">
                      <div class="skeleton-box skeleton-circle"></div>
                      <div class="skeleton-box skeleton-circle"></div>
                    </div>
                  </td>
                </tr>
              </template>
              
              <template v-else-if="items.length > 0">
                <tr v-for="(item, index) in items" :key="item.id">
                  <td class="text-center">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                  <td class="font-medium text-navy">{{ item.name }}</td>
                  <td>{{ item.type?.name || '-' }}</td>
                  <td>{{ item.category?.name || '-' }}</td>
                  <td>
                    <div v-if="item.item_groups && item.item_groups.length > 0">
                      <span v-for="(ig, i) in item.item_groups" :key="ig.id">
                        {{ ig.name }}<span v-if="i < item.item_groups.length - 1">, </span>
                      </span>
                    </div>
                    <span v-else>-</span>
                  </td>
                  <td>{{ formatCurrency(item.price) }}</td>
                  <td>{{ formatCurrency(item.interpretation_price) }}</td>
                  <td class="text-center">
                    <button 
                      @click="handleToggleStatus(item)"
                      class="status-badge"
                      :class="item.is_active ? 'status-active' : 'status-inactive'"
                    >
                      {{ item.is_active ? 'AKTIF' : 'NONAKTIF' }}
                    </button>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-action edit" title="Edit" @click="handleEdit(item)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                        </svg>
                      </button>
                      <button class="btn-action delete" title="Hapus" @click="handleDelete(item)">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </template>

              <tr v-else>
                <td colspan="9" class="text-center" style="padding: 3rem;">
                  <span class="text-muted">Data group radiologi belum tersedia.</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="pagination-container" v-if="totalItems > 0">
        <div class="pagination-info">
          Menampilkan {{ ((currentPage - 1) * perPage) + 1 }} sampai {{ Math.min(currentPage * perPage, totalItems) }} dari {{ totalItems }} data
        </div>
        
        <div class="pagination-controls">
          <button 
            class="page-btn" 
            :disabled="currentPage === 1"
            @click="handlePageChange(currentPage - 1)"
          >
            &lt;
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in Math.ceil(totalItems / perPage)" 
              :key="page"
              class="page-btn page-number"
              :class="{ active: currentPage === page }"
              @click="handlePageChange(page)"
              v-show="Math.abs(page - currentPage) <= 2 || page === 1 || page === Math.ceil(totalItems / perPage)"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            class="page-btn" 
            :disabled="currentPage === Math.ceil(totalItems / perPage)"
            @click="handlePageChange(currentPage + 1)"
          >
            &gt;
          </button>
        </div>
      </div>
    </div>

    <RadiologyGroupModal
      :show="showModal"
      :item="selectedItem"
      @close="showModal = false"
      @saved="handleSaved"
    />
  </div>
</template>

<style scoped>
.page-container { padding: 1.5rem; background-color: var(--color-page-bg); min-height: 100%; }
.breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--color-text-secondary); margin-bottom: 1.5rem; }
.breadcrumb .current { color: var(--color-primary); font-weight: 500; }
.breadcrumb .separator { color: #cbd5e1; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.page-title { margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--color-text-navy); display: flex; align-items: center; gap: 0.75rem; }
.title-icon { width: 24px; height: 24px; color: var(--color-primary); }
.page-subtitle { margin: 0.25rem 0 0 0; font-size: 0.9rem; color: var(--color-text-secondary); }
.card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.03); border: 1px solid var(--color-border-soft); padding: 1.5rem; }
.btn-primary { background: var(--color-primary); color: #ffffff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; }
.btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
.btn-primary svg { width: 18px; height: 18px; }
.btn-outline { background: transparent; color: var(--color-primary); border: 1px solid var(--color-primary); padding: 0.5rem 1rem; border-radius: 6px; font-weight: 500; font-size: 0.9rem; cursor: pointer; }
.btn-outline:hover { background: var(--color-primary-light); }
.error-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem; background: #fef2f2; border-radius: 8px; color: #991b1b; text-align: center; }
.error-state svg { width: 48px; height: 48px; color: #ef4444; margin-bottom: 1rem; }
.error-state p { margin: 0 0 1rem 0; font-weight: 500; }
.pagination-container { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.pagination-info { font-size: 0.85rem; color: var(--color-text-secondary); }
.pagination-controls { display: flex; align-items: center; gap: 0.25rem; }
.page-numbers { display: flex; gap: 0.25rem; }
.page-btn { background: #ffffff; border: 1px solid var(--color-border-soft); color: var(--color-text-navy); padding: 0.4rem 0.75rem; border-radius: 4px; font-size: 0.85rem; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.page-btn:hover:not(:disabled) { background: var(--color-page-bg); }
.page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
.page-number { min-width: 32px; text-align: center; }
.page-number.active { background: #1d4ed8; color: #ffffff; border-color: #1d4ed8; }
.filters-container { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; justify-content: space-between; align-items: center; }
.search-box { position: relative; flex: 1; min-width: 250px; max-width: 200px; margin-left: auto; }
.search-input { width: 100%; padding: 0.4rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.85rem; transition: all 0.2s; box-sizing: border-box; }
.search-input:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light); }
.filter-actions { display: flex; gap: 0.75rem; align-items: center; order: -1; }
.form-select.filter-select { padding: 0.4rem 2rem 0.4rem 0.75rem; border: 1px solid var(--color-border-soft); border-radius: 4px; font-size: 0.85rem; background-color: white; min-width: 70px; }
.form-select.filter-select:focus { outline: none; border-color: var(--color-primary); box-shadow: 0 0 0 2px var(--color-primary-light); }
.table-wrapper { width: 100%; }
.table-container { width: 100%; overflow-x: auto; border: 1px solid var(--color-border-soft); background: #ffffff; -webkit-overflow-scrolling: touch; border-radius: 0; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; min-width: 800px; }
.data-table th { background: #f9fafb; padding: 0.75rem 1rem; text-align: left; font-weight: 700; color: #374151; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; white-space: nowrap; }
.data-table th:last-child { border-right: none; }
.data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; color: #4b5563; vertical-align: middle; }
.data-table td:last-child { border-right: none; }
.data-table tbody tr:nth-child(even) { background-color: #f9fafb; }
.data-table tbody tr:hover { background: #f3f4f6; }
.col-no { width: 48px; }
.col-name { min-width: 150px; }
.col-status { width: 100px; }
.col-actions { width: 100px; }
.font-medium { font-weight: 500; }
.text-navy { color: var(--color-text-navy); }
.text-muted { color: #64748b; }
.text-center { text-align: center; }
.mx-auto { margin-left: auto; margin-right: auto; }
.action-buttons { display: flex; gap: 0.25rem; justify-content: center; }
.btn-action { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: none; background: transparent; border-radius: 4px; color: #ffffff; cursor: pointer; transition: all 0.2s; }
.btn-action svg { width: 14px; height: 14px; }
.btn-action.edit { background: #0ea5e9; }
.btn-action.edit:hover { background: #0284c7; }
.btn-action.delete { background: #ef4444; }
.btn-action.delete:hover { background: #dc2626; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; border: none; cursor: pointer; }
.status-active { background-color: #dcfce7; color: #166534; }
.status-inactive { background-color: #fee2e2; color: #991b1b; }
.skeleton-box { height: 16px; background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: loading 1.5s infinite; border-radius: 4px; }
.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-large { width: 150px; }
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; }
@keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
</style>
