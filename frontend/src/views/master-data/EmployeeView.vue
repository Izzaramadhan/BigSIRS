<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Data Pegawai</h1>
        <div class="breadcrumb">
          <span>Data Pegawai</span>
          <span class="separator">/</span>
          <span class="active">Pegawai</span>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 16px; height: 16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Pegawai
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
            <label>entri</label>
          </div>



          <div class="filter-group">
            <select v-model="filters.position_id" class="form-select" @change="onFilterChange">
              <option value="">Semua Jabatan</option>
              <option v-for="pos in lookupPositions" :key="pos.id" :value="pos.id">
                {{ pos.name }}
              </option>
            </select>
          </div>
          

          <div class="filter-group search-filter">
            <div class="search-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="search-icon">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input 
                v-model="filters.search" 
                type="text" 
                class="search-input" 
                placeholder="Cari NIK / Nama..."
                @keyup.enter="onFilterChange"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="table-wrapper">
          <div class="table-container">
            <table class="data-table">
            <thead>
              <tr>
                <th width="50">No</th>
                <th>NIP</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th width="100" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-8">
                  <div class="loading-state">
                    <div class="spinner"></div>
                    <p>Memuat data pegawai...</p>
                  </div>
                </td>
              </tr>
              <tr v-else-if="employees.length === 0">
                <td colspan="5" class="text-center py-8">
                  <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                      <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="3" y1="9" x2="21" y2="9"></line>
                      <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    <p class="empty-text">Data pegawai tidak ditemukan</p>
                    <p class="empty-subtext" v-if="filters.search || filters.position_id">
                      Tidak ada hasil yang cocok dengan kriteria pencarian Anda.
                    </p>
                    <button class="btn-outline mt-3" @click="onFilterChange" v-if="filters.search || filters.position_id">
                      Reset Filter
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-else v-for="(employee, index) in employees" :key="employee.id">
                <td>{{ getSerialNumber(index) }}</td>
                <td>{{ employee.code || '-' }}</td>
                <td>{{ employee.name }}</td>
                <td>{{ employee.position ? employee.position.name : '-' }}</td>
                <td class="text-center">
                  <div class="action-buttons">
                    <button class="btn-action" title="Detail" @click="openEditModal(employee)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                      </svg>
                    </button>
                    <button class="btn-action" title="Edit" @click="openEditModal(employee)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                      </svg>
                    </button>
                    <button class="btn-action text-danger" title="Hapus" @click="confirmDelete(employee)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px;">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>

        <footer class="employee-table-footer" v-if="meta">
          <p class="employee-table-footer__summary">
            {{ summaryText }}
          </p>
          <nav class="employee-pagination" aria-label="Navigasi halaman data pegawai" v-if="meta.total > 0">
            <button
              type="button"
              class="btn-page btn-page--wide"
              :disabled="meta.current_page === 1"
              :aria-disabled="meta.current_page === 1"
              @click="changePage(meta.current_page - 1)"
            >Sebelumnya</button>
            <template v-for="page in visiblePages" :key="typeof page === 'string' ? page : `page-${page}`">
              <span v-if="typeof page === 'string'" class="page-ellipsis" aria-hidden="true">…</span>
              <button
                v-else
                type="button"
                class="btn-page"
                :class="{ active: meta.current_page === page }"
                :aria-current="meta.current_page === page ? 'page' : undefined"
                :aria-label="`Halaman ${page}`"
                @click="changePage(page)"
              >{{ page }}</button>
            </template>
            <button
              type="button"
              class="btn-page btn-page--wide"
              :disabled="meta.current_page === meta.last_page"
              :aria-disabled="meta.current_page === meta.last_page"
              @click="changePage(meta.current_page + 1)"
            >Selanjutnya</button>
          </nav>
        </footer>
    </div>

    <!-- Modals -->
    <EmployeeFormModal 
      :is-open="isModalOpen"
      :employee="selectedEmployee"
      @close="closeModal"
      @saved="onSaved"
    />

    <!-- Delete Confirmation -->
    <div v-if="isDeleteModalOpen" class="modal-backdrop">
      <div class="modal-content modal-sm">
        <div class="modal-header border-0 pb-0">
          <h3 class="modal-title text-danger">Konfirmasi Hapus</h3>
          <button class="btn-close" @click="closeDeleteModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body text-center pt-2 pb-4">
          <div class="warning-icon mb-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <p>Apakah Anda yakin ingin menghapus data pegawai <strong>{{ selectedEmployee?.name }}</strong>?</p>
          <p class="text-sm text-muted mt-1">Data yang sudah dihapus tidak dapat dikembalikan.</p>
        </div>
        <div class="modal-footer flex-center bg-gray-50 border-0 rounded-b-lg">
          <button class="btn-outline" @click="closeDeleteModal" :disabled="isDeleting">Batal</button>
          <button class="btn-danger" @click="executeDelete" :disabled="isDeleting">
            <span v-if="isDeleting" class="spinner"></span>
            <span v-else>Ya, Hapus</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useEmployees } from '../../composables/useEmployees';
import EmployeeFormModal from '../../components/master-data/EmployeeFormModal.vue';
import employeeService from '../../services/employee.service';

const { 
  employees, 
  loading, 
  error, 
  meta, 
  fetchEmployees, 
  deleteEmployee 
} = useEmployees();

const filters = reactive({
  search: '',
  position_id: '',
  per_page: 10,
  page: 1
});

let searchTimeout = null;

const isModalOpen = ref(false);
const selectedEmployee = ref(null);
const isDeleteModalOpen = ref(false);
const isDeleting = ref(false);
const lookupPositions = ref([]);

const fetchData = () => {
  fetchEmployees(filters);
};

const loadPositions = async () => {
  try {
    const response = await employeeService.getPositions({ is_active: 1 });
    lookupPositions.value = response.data || [];
  } catch (err) {
    console.error("Failed to load positions for filter", err);
  }
};

const onFilterChange = () => {
  filters.page = 1;
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchData();
  }, 300);
};

const changePage = (page) => {
  if (page < 1 || page > meta.value.last_page) return;
  filters.page = page;
  fetchData();
};

const getSerialNumber = (index) => {
  if (!meta.value) return index + 1;
  return (meta.value.current_page - 1) * meta.value.per_page + index + 1;
};

const startItem = computed(() => {
  if (!meta.value || !meta.value.total) return 0;
  return (meta.value.current_page - 1) * meta.value.per_page + 1;
});

const endItem = computed(() => {
  if (!meta.value || !meta.value.total) return 0;
  return Math.min(meta.value.current_page * meta.value.per_page, meta.value.total);
});

const summaryText = computed(() => {
  if (!meta.value || meta.value.total === 0) {
    return 'Menampilkan 0 dari 0 pegawai';
  }
  return `Menampilkan ${startItem.value}–${endItem.value} dari ${meta.value.total} pegawai`;
});

const visiblePages = computed(() => {
  if (!meta.value || meta.value.last_page <= 1) return [];
  const current = meta.value.current_page;
  const last = meta.value.last_page;
  
  if (last <= 5) {
    return Array.from({ length: last }, (_, i) => i + 1);
  }

  const range = [1];

  if (current <= 3) {
    range.push(2, 3);
    if (last > 4) {
      range.push('ellipsis-end');
    }
    if (last > 3) {
      range.push(last);
    }
  } else if (current >= last - 2) {
    range.push('ellipsis-start');
    range.push(last - 2, last - 1, last);
  } else {
    range.push('ellipsis-start');
    range.push(current - 1, current, current + 1);
    range.push('ellipsis-end');
    range.push(last);
  }

  const unique = [];
  for (const page of range) {
    if (!unique.includes(page)) {
      unique.push(page);
    }
  }
  return unique;
});

const openAddModal = () => {
  selectedEmployee.value = null;
  isModalOpen.value = true;
};

const openEditModal = (employee) => {
  selectedEmployee.value = { ...employee };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedEmployee.value = null;
};

const onSaved = () => {
  fetchData();
};

const confirmDelete = (employee) => {
  selectedEmployee.value = employee;
  isDeleteModalOpen.value = true;
};

const closeDeleteModal = () => {
  isDeleteModalOpen.value = false;
  selectedEmployee.value = null;
};

const executeDelete = async () => {
  if (!selectedEmployee.value) return;
  
  isDeleting.value = true;
  const success = await deleteEmployee(selectedEmployee.value.id);
  isDeleting.value = false;
  
  if (success) {
    closeDeleteModal();
    fetchData();
  }
};


onMounted(() => {
  loadPositions();
  fetchData();
});
</script>

<style scoped>
/* Page Layout */
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card {
  overflow: hidden;
}

.card-body {
  padding: 1.25rem;
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
  padding: 0;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 0;
  border: none;
  border-top: 1px solid var(--color-border-soft);
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
.employee-table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-height: 0;
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.employee-table-footer__summary {
  margin: 0;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
}

.employee-pagination {
  display: flex;
  align-items: center;
}

.btn-page, .page-ellipsis {
  min-width: 36px;
  height: 36px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #cbd5e1;
  background: #ffffff;
  font-size: 0.875rem;
  box-sizing: border-box;
  margin-left: -1px;
}

.btn-page:first-child {
  margin-left: 0;
  border-top-left-radius: 6px;
  border-bottom-left-radius: 6px;
}

.btn-page:last-child {
  border-top-right-radius: 6px;
  border-bottom-right-radius: 6px;
}

.btn-page {
  padding: 0 0.5rem;
  color: var(--color-text-secondary);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.btn-page--wide {
  padding: 0 0.75rem;
}

.btn-page:hover:not(:disabled):not(.active) {
  background: #f8fafc;
  color: var(--color-text-navy);
  z-index: 1;
}

.btn-page:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
  z-index: 2;
}

.btn-page.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #ffffff;
  z-index: 1;
}

.btn-page:disabled {
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.page-ellipsis {
  color: #94a3b8;
  user-select: none;
  background: #f8fafc;
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

@media (max-width: 640px) {
  .employee-table-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .employee-pagination {
    justify-content: center;
    overflow-x: auto;
    padding-bottom: 0.125rem;
  }
}
</style>

<style scoped>
.btn-close svg {
  width: 24px;
  height: 24px;
}
.btn-action svg {
  width: 18px;
  height: 18px;
}
.empty-icon {
  width: 48px;
  height: 48px;
  color: #94a3b8;
  margin-bottom: 1rem;
}
</style>
