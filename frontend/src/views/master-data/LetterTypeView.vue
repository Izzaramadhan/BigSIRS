<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Surat-Surat</h1>
        <div class="breadcrumb">
          <span>Data Dasar</span>
          <span class="separator">/</span>
          <span class="active">Surat-Surat</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-sm">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Surat
        </button>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <!-- Error Alert -->
        <div v-if="error" class="alert alert-danger mb-4 flex-between">
          <div class="flex-align-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <span>{{ error }}</span>
          </div>
          <button class="btn-action text-danger" @click="fetchData">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
          </button>
        </div>

        <!-- Filters -->
        <div class="filters-container">
          <div class="filter-group items-per-page">
            <label>Tampilkan</label>
            <select v-model="filters.per_page" class="form-select" @change="onFilterChange">
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <label>data</label>
          </div>
          
          <div class="filter-group search-filter">

            <div class="search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input 
                type="text" 
                v-model="searchInput" 
                class="form-input search-input" 
                placeholder="Cari nama, deskripsi, atau resource..."
                @keyup.enter="applySearch"
              >
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && letterTypes.length === 0" class="empty-state">
          <div class="empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="3" y1="9" x2="21" y2="9"></line>
              <line x1="9" y1="21" x2="9" y2="9"></line>
            </svg>
          </div>
          <h3>Data tidak ditemukan</h3>
          <p class="text-muted">Belum ada data surat atau tidak ada yang cocok dengan pencarian.</p>
          <button v-if="searchInput" class="btn btn-secondary mt-3" @click="resetFilters">
            Reset Pencarian
          </button>
        </div>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-name">Nama</th>
                    <th class="col-desc">Deskripsi</th>
                    <th class="col-resource">Resource Legacy</th>
                    <th class="col-actions">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td><div class="skeleton-box skeleton-small"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-large"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-circle"></div></td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in letterTypes" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="col-desc text-muted">{{ item.description || '-' }}</td>
                      <td class="col-resource text-muted">
                        <span v-if="item.legacy_resource" class="code-text">{{ item.legacy_resource }}</span>
                        <span v-else>-</span>
                      </td>
                      <td class="col-actions actions-cell">
                        <div class="action-buttons">
                          <button type="button" class="btn-action edit" @click="openEditModal(item)" aria-label="Edit" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                          </button>

                          <button type="button" class="btn-action delete" @click="confirmDelete(item)" aria-label="Hapus" title="Hapus">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <polyline points="3 6 5 6 21 6"></polyline>
                              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                              <line x1="10" y1="11" x2="10" y2="17"></line>
                              <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                          </button>

                          <button v-if="item.legacy_resource" type="button" class="btn-action view-legacy" @click="viewLegacy(item)" aria-label="Lihat Format Lama" title="Lihat Format Lama">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                              <line x1="8" y1="6" x2="21" y2="6"></line>
                              <line x1="8" y1="12" x2="21" y2="12"></line>
                              <line x1="8" y1="18" x2="21" y2="18"></line>
                              <line x1="3" y1="6" x2="3.01" y2="6"></line>
                              <line x1="3" y1="12" x2="3.01" y2="12"></line>
                              <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                          </button>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Pagination -->
          <div class="pagination-container" v-if="meta && meta.last_page > 1">
            <div class="pagination-info">
              Menampilkan {{ meta.from || 0 }} - {{ meta.to || 0 }} dari {{ meta.total || 0 }} data
            </div>
            <div class="pagination-controls">
              <button 
                class="btn-page" 
                :disabled="meta.current_page === 1"
                @click="changePage(meta.current_page - 1)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
              </button>
              
              <template v-for="page in visiblePages" :key="page">
                <span v-if="page === '...'" class="page-ellipsis">...</span>
                <button 
                  v-else
                  class="btn-page" 
                  :class="{ active: page === meta.current_page }"
                  @click="changePage(page)"
                >
                  {{ page }}
                </button>
              </template>
              
              <button 
                class="btn-page" 
                :disabled="meta.current_page === meta.last_page"
                @click="changePage(meta.current_page + 1)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- Form Modal -->
    <LetterTypeFormModal
      :is-open="isModalOpen"
      :letter-type-data="selectedItem"
      :loading="formLoading"
      :errors="formErrors"
      @close="closeModal"
      @submit="handleFormSubmit"
    />

    <!-- Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal-content confirm-modal">
        <div class="confirm-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
        </div>
        <h3 class="confirm-title">Hapus Surat</h3>
        <p class="confirm-text">
          Apakah Anda yakin ingin menghapus <strong>{{ itemToDelete?.name }}</strong>? 
          <br>
          <span class="text-xs text-red-500 mt-2 block">Catatan: Jika surat ini sudah digunakan pada transaksi pelayanan, sebaiknya nonaktifkan saja statusnya.</span>
        </p>
        <div class="confirm-actions">
          <button class="btn btn-secondary" @click="closeDeleteModal" :disabled="deleteLoading">Batal</button>
          <button class="btn btn-danger" @click="executeDelete" :disabled="deleteLoading">
            <span v-if="deleteLoading" class="spinner"></span>
            Hapus
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useLetterTypes } from '../../composables/useLetterTypes';
import LetterTypeFormModal from '../../components/master-data/LetterTypeFormModal.vue';

const {
  letterTypes,
  loading,
  error,
  meta,
  fetchLetterTypes,
  createLetterType,
  updateLetterType,
  deleteLetterType
} = useLetterTypes();

// Filter & Pagination State
const filters = reactive({
  page: 1,
  per_page: 10,
  search: '',
  is_active: ''
});

const searchInput = ref('');

// Modal State
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const selectedItem = ref(null);
const itemToDelete = ref(null);
const formLoading = ref(false);
const deleteLoading = ref(false);
const formErrors = ref({});

// Fetch Data
const fetchData = async () => {
  await fetchLetterTypes(filters);
};

onMounted(() => {
  fetchData();
});

// Search & Filter Handlers
const applySearch = () => {
  filters.search = searchInput.value;
  filters.page = 1;
  fetchData();
};

const onFilterChange = () => {
  filters.page = 1;
  fetchData();
};

const resetFilters = () => {
  searchInput.value = '';
  filters.search = '';
  filters.is_active = '';
  filters.page = 1;
  fetchData();
};

const changePage = (page) => {
  if (page < 1 || (meta.value && page > meta.value.last_page)) return;
  filters.page = page;
  fetchData();
};

// Computed Helpers
const getRowNumber = (index) => {
  if (!meta.value) return index + 1;
  return (meta.value.current_page - 1) * meta.value.per_page + index + 1;
};

const visiblePages = computed(() => {
  if (!meta.value) return [];
  const current = meta.value.current_page;
  const last = meta.value.last_page;
  
  if (last <= 7) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }
  
  if (current <= 4) {
    return [1, 2, 3, 4, 5, '...', last];
  }
  
  if (current >= last - 3) {
    return [1, '...', last - 4, last - 3, last - 2, last - 1, last];
  }
  
  return [1, '...', current - 1, current, current + 1, '...', last];
});

// Modal Handlers
const openAddModal = () => {
  selectedItem.value = null;
  formErrors.value = {};
  isModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedItem.value = { ...item };
  formErrors.value = {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  setTimeout(() => {
    selectedItem.value = null;
    formErrors.value = {};
  }, 200);
};

const handleFormSubmit = async (formData) => {
  formLoading.value = true;
  formErrors.value = {};
  
  try {
    let success = false;
    
    if (selectedItem.value) {
      success = await updateLetterType(selectedItem.value.id, formData);
    } else {
      success = await createLetterType(formData);
    }
    
    if (success) {
      closeModal();
      fetchData();
    }
  } catch (err) {
    if (err) {
      formErrors.value = err;
    }
  } finally {
    formLoading.value = false;
  }
};

// Delete Handlers
const confirmDelete = (item) => {
  itemToDelete.value = item;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
  setTimeout(() => {
    itemToDelete.value = null;
  }, 200);
};

const executeDelete = async () => {
  if (!itemToDelete.value) return;
  
  deleteLoading.value = true;
  const success = await deleteLetterType(itemToDelete.value.id);
  deleteLoading.value = false;
  
  if (success) {
    closeDeleteModal();
    // Jika ini item terakhir di halaman dan bukan halaman pertama, mundur 1 halaman
    if (letterTypes.value.length === 1 && filters.page > 1) {
      filters.page--;
    }
    fetchData();
  }
};

const viewLegacy = (item) => {
  if (item.legacy_resource) {
    // Membuka resource lama di tab baru (URL disesuaikan dengan environment jika ada)
    const baseUrl = import.meta.env.VITE_LEGACY_BASE_URL || '/simrs_lama/';
    window.open(`${baseUrl}${item.legacy_resource}`, '_blank');
  }
};
</script>

<style scoped>
/* Page Layout */
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 0.25rem 0;
}

.breadcrumb {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.breadcrumb .separator {
  margin: 0 0.5rem;
  color: #cbd5e1;
}

.breadcrumb .active {
  color: var(--color-primary);
  font-weight: 500;
}

/* Helpers */
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.flex-align-center {
  display: flex;
  align-items: center;
}

.mt-3 { margin-top: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }

.icon-sm {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.5rem;
}

/* Alert */
.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.alert-danger {
  background-color: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-icon {
  width: 1.25rem;
  height: 1.25rem;
  margin-right: 0.5rem;
  flex-shrink: 0;
}

/* Filters */
.filters-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.25rem;
  padding: 0.5rem 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.items-per-page {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.search-filter {
  display: flex;
  gap: 0.75rem;
  flex: 1;
  justify-content: flex-end;
}

.form-select {
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background-color: #fff;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.5rem center;
  background-repeat: no-repeat;
  background-size: 1.5em 1.5em;
  min-width: 140px;
}

.form-select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.1);
}

.search-box {
  position: relative;
  max-width: 300px;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1rem;
  height: 1rem;
  color: #94a3b8;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.75rem 0.5rem 2.25rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.1);
}

/* Table */
.table-wrapper {
  margin: 0 -1rem;
  padding: 0 1rem;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  min-width: 600px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.6rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.data-table td {
  padding: 0.4rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no { width: 60px; text-align: center; }
.col-name { min-width: 200px; }
.col-desc { min-width: 250px; }
.col-resource { min-width: 150px; }
.col-status { width: 120px; text-align: center; }
.col-actions { width: 100px; text-align: center; }

.font-medium {
  font-weight: 500;
}

.text-navy {
  color: var(--color-text-navy);
}

.text-muted {
  color: #64748b;
}

.code-text {
  font-family: monospace;
  background: #f1f5f9;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
  font-size: 0.8rem;
  color: #334155;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-align: center;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #f1f5f9;
  color: #475569;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: center;
}

.btn-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.edit:hover { background: #e0f2fe; color: #0284c7; }
.btn-action.delete:hover { background: #fef2f2; color: #ef4444; }
.btn-action.view-legacy:hover { background: #dbeafe; color: #2563eb; }

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
  gap: 1rem;
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
  min-width: 2rem;
  height: 2rem;
  padding: 0 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  color: var(--color-text-secondary);
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page svg {
  width: 1rem;
  height: 1rem;
}

.btn-page:hover:not(:disabled):not(.active) {
  background: #f8fafc;
  color: var(--color-text-navy);
}

.btn-page.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #ffffff;
}

.btn-page:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.page-ellipsis {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  width: 2rem;
  color: #94a3b8;
}

/* Empty State */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 1rem;
  text-align: center;
}

.empty-icon {
  width: 64px;
  height: 64px;
  background: #f1f5f9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  margin-bottom: 1rem;
}

.empty-icon svg {
  width: 32px;
  height: 32px;
}

.empty-state h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.empty-state p {
  margin: 0;
  max-width: 400px;
}

/* Confirm Modal */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.confirm-modal {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
}

.confirm-icon {
  width: 48px;
  height: 48px;
  background: #fef2f2;
  color: #ef4444;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.confirm-icon svg {
  width: 24px;
  height: 24px;
}

.confirm-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 0.5rem 0;
}

.confirm-text {
  color: var(--color-text-secondary);
  font-size: 0.875rem;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.text-xs { font-size: 0.75rem; }
.text-red-500 { color: #ef4444; }
.block { display: block; }

.confirm-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

/* Button Component */
.btn {
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-secondary {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  color: #0f172a;
}

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #ffffff;
  animation: spin 0.8s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Skeleton Loading */
.skeleton-box {
  height: 16px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
}

.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-large { width: 140px; }
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; margin: 0 auto; }

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 768px) {
  .search-box {
    max-width: 100%;
  }
}
</style>
