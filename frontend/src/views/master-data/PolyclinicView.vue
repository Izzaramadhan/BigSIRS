<script setup>
import { ref, onMounted } from 'vue';
import { usePolyclinics } from '@/composables/usePolyclinics';
import PolyclinicTable from '@/components/master-data/polyclinics/PolyclinicTable.vue';
import PolyclinicFilters from '@/components/master-data/polyclinics/PolyclinicFilters.vue';
import PolyclinicFormModal from '@/components/master-data/polyclinics/PolyclinicFormModal.vue';
import PolyclinicDeleteDialog from '@/components/master-data/polyclinics/PolyclinicDeleteDialog.vue';
import PolyclinicEmptyState from '@/components/master-data/polyclinics/PolyclinicEmptyState.vue';

const {
  items,
  pagination,
  filters,
  sort,
  loading,
  submitting,
  error,
  serviceTypes,
  fetchPolyclinics,
  fetchServiceTypes,
  createPolyclinic,
  updatePolyclinic,
  toggleStatus,
  archivePolyclinic,
  setPage,
  setSort
} = usePolyclinics();

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedPolyclinic = ref(null);
const formErrors = ref({});
const notification = ref(null);

const showNotification = (message, type = 'success') => {
  notification.value = { message, type };
  setTimeout(() => {
    notification.value = null;
  }, 3000);
};

const openAddModal = () => {
  selectedPolyclinic.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedPolyclinic.value = item;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  selectedPolyclinic.value = item;
  deleteDialogOpen.value = true;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchPolyclinics();
};

const handleResetFilters = () => {
  filters.search = '';
  filters.is_active = null;
  filters.service_type = null;
  pagination.current_page = 1;
  fetchPolyclinics();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};
  
  let result;
  if (selectedPolyclinic.value) {
    result = await updatePolyclinic(selectedPolyclinic.value.id, payload);
  } else {
    result = await createPolyclinic(payload);
  }
  
  if (result.success) {
    formModalOpen.value = false;
    showNotification(`Poliklinik berhasil ${selectedPolyclinic.value ? 'diperbarui' : 'ditambahkan'}.`);
    fetchPolyclinics();
  } else {
    if (result.error.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
    } else if (result.error.response?.status === 409) {
      formErrors.value = { general: 'Terdapat poliklinik turunan, periksa konfigurasi induk.' };
    } else {
      formErrors.value = { general: 'Terjadi kesalahan sistem. Silakan coba lagi.' };
    }
  }
};

const handleToggleStatus = async (item) => {
  const result = await toggleStatus(item.id, !item.is_active);
  if (result.success) {
    showNotification('Status Poliklinik berhasil diubah.');
    fetchPolyclinics();
  } else {
    showNotification('Gagal mengubah status Poliklinik.', 'error');
  }
};

const handleDeleteConfirm = async () => {
  const result = await archivePolyclinic(selectedPolyclinic.value.id);
  
  if (result.success) {
    deleteDialogOpen.value = false;
    showNotification('Poliklinik berhasil diarsipkan.');
    
    // Adjust pagination if needed
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    fetchPolyclinics();
  } else {
    if (result.error.response?.status === 409) {
      showNotification('Poliklinik tidak dapat diarsipkan karena masih memiliki poliklinik turunan.', 'error');
    } else {
      showNotification('Terjadi kesalahan saat mengarsipkan data.', 'error');
    }
    deleteDialogOpen.value = false;
  }
};

onMounted(() => {
  fetchPolyclinics();
  fetchServiceTypes();
});
</script>

<template>
  <div class="page-container">
    <!-- Notification Toast -->
    <div v-if="notification" class="notification-toast" :class="`toast-${notification.type}`">
      {{ notification.message }}
    </div>

    <!-- Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="breadcrumbs">
          <span>Dashboard</span>
          <span class="separator">/</span>
          <span>Master Data</span>
          <span class="separator">/</span>
          <span class="current">Poliklinik</span>
        </div>
        <h1 class="page-title">Poliklinik</h1>
        <p class="page-subtitle">Kelola data poliklinik dan kode integrasi pelayanan.</p>
      </div>
      
      <div class="header-actions">
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Poliklinik
        </button>
      </div>
    </div>

    <!-- Content -->
    <div class="page-content">
      <PolyclinicFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @reset="handleResetFilters"
        @refresh="fetchPolyclinics"
      />
      
      <div v-if="error" class="error-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <p>{{ error }}</p>
        <button class="btn-outline" @click="fetchPolyclinics">Coba Lagi</button>
      </div>
      
      <template v-else>
        <PolyclinicEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search || filters.is_active !== null || !!filters.service_type"
        >
          <template #action v-if="!filters.search && filters.is_active === null && !filters.service_type">
            <button class="btn-primary" @click="openAddModal">Tambah Poliklinik</button>
          </template>
        </PolyclinicEmptyState>
        
        <template v-else>
          <PolyclinicTable 
            :items="items"
            :pagination="pagination"
            :sort="sort"
            :loading="loading"
            @sort="setSort"
            @edit="openEditModal"
            @toggle-status="handleToggleStatus"
            @delete="openDeleteDialog"
          />
          
          <!-- Pagination -->
          <div class="pagination-container" v-if="pagination.last_page > 1">
            <div class="pagination-info">
              Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + (items.length > 0 ? 1 : 0) }} 
              sampai {{ (pagination.current_page - 1) * pagination.per_page + items.length }} 
              dari {{ pagination.total }} entri
            </div>
            
            <div class="pagination-controls">
              <button 
                class="page-btn" 
                :disabled="pagination.current_page === 1 || loading"
                @click="setPage(pagination.current_page - 1)"
              >
                Sebelumnya
              </button>
              
              <div class="page-numbers">
                <button 
                  v-for="p in pagination.last_page" 
                  :key="p"
                  class="page-btn page-number"
                  :class="{ 'active': p === pagination.current_page }"
                  @click="setPage(p)"
                  :disabled="loading"
                  v-show="p === 1 || p === pagination.last_page || Math.abs(p - pagination.current_page) <= 1"
                >
                  {{ p }}
                </button>
              </div>
              
              <button 
                class="page-btn" 
                :disabled="pagination.current_page === pagination.last_page || loading"
                @click="setPage(pagination.current_page + 1)"
              >
                Selanjutnya
              </button>
            </div>
          </div>
        </template>
      </template>
    </div>

    <!-- Modals -->
    <PolyclinicFormModal 
      :is-open="formModalOpen"
      :polyclinic="selectedPolyclinic"
      :service-types="serviceTypes"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />
    
    <PolyclinicDeleteDialog 
      :is-open="deleteDialogOpen"
      :polyclinic="selectedPolyclinic"
      :is-submitting="submitting"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.page-container {
  max-width: 100%;
  position: relative;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-bottom: 0.75rem;
}

.separator {
  color: var(--color-border-soft);
}

.current {
  color: var(--color-primary);
  font-weight: 500;
}

.page-title {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.page-subtitle {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-text-secondary);
}

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}

.btn-outline {
  background: transparent;
  color: var(--color-primary);
  border: 1px solid var(--color-primary);
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
}

.btn-outline:hover {
  background: var(--color-primary-light);
}

.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  background: #fef2f2;
  border-radius: 8px;
  color: #991b1b;
  text-align: center;
}

.error-state svg {
  width: 48px;
  height: 48px;
  color: #ef4444;
  margin-bottom: 1rem;
}

.error-state p {
  margin: 0 0 1rem 0;
  font-weight: 500;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

.page-btn {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  padding: 0.4rem 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  background: var(--color-page-bg);
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-number {
  min-width: 32px;
  text-align: center;
}

.page-number.active {
  background: var(--color-primary);
  color: #ffffff;
  border-color: var(--color-primary);
}

.notification-toast {
  position: fixed;
  top: 1rem;
  right: 1rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: #ffffff;
  font-weight: 500;
  font-size: 0.9rem;
  z-index: 1000;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  animation: slideIn 0.3s ease-out forwards;
}

.toast-success {
  background-color: #10b981;
}

.toast-error {
  background-color: #ef4444;
}

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

@media (max-width: 640px) {
  .page-header {
    flex-direction: column;
  }
  
  .pagination-container {
    flex-direction: column;
    align-items: center;
  }
}
</style>
