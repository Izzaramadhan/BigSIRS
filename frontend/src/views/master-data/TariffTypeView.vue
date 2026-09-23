<script setup>
import { ref, onMounted, watch } from 'vue';
import { useTariffTypes } from '@/composables/useTariffTypes';
import TariffTypeTable from '@/components/master-data/tariff-types/TariffTypeTable.vue';
import TariffTypeFormModal from '@/components/master-data/tariff-types/TariffTypeFormModal.vue';

const {
  tariffTypes,
  totalItems,
  currentPage,
  perPage,
  loading,
  error,
  fetchTariffTypes,
  createTariffType,
  updateTariffType,
  updateTariffTypeStatus,
  deleteTariffType
} = useTariffTypes();

const searchQuery = ref('');
const isModalOpen = ref(false);
const editingItem = ref(null);
const modalErrors = ref({});
const isSaving = ref(false);

const sortField = ref('created_at');
const sortDir = ref('desc');

const searchTimeout = ref(null);

const handleSearch = () => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  searchTimeout.value = setTimeout(() => {
    currentPage.value = 1;
    loadData();
  }, 400);
};

const handleSort = ({ column, direction }) => {
  sortField.value = column;
  sortDir.value = direction;
  loadData();
};

const loadData = () => {
  fetchTariffTypes({
    search: searchQuery.value,
    sort_by: sortField.value,
    sort_dir: sortDir.value
  });
};

const openAddModal = () => {
  editingItem.value = null;
  modalErrors.value = {};
  isModalOpen.value = true;
};

const openEditModal = (item) => {
  editingItem.value = { ...item };
  modalErrors.value = {};
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  editingItem.value = null;
  modalErrors.value = {};
};

const handleSave = async (data) => {
  isSaving.value = true;
  modalErrors.value = {};
  try {
    if (editingItem.value) {
      await updateTariffType(editingItem.value.id, data);
    } else {
      await createTariffType(data);
    }
    closeModal();
    loadData();
  } catch (err) {
    if (err.response?.status === 422) {
      modalErrors.value = err.response.data.errors || {};
    } else {
      modalErrors.value = { general: err.response?.data?.message || err.message || 'Terjadi kesalahan' };
    }
  } finally {
    isSaving.value = false;
  }
};

const handleToggleStatus = async (item) => {
  const newStatus = !item.is_active;
  const actionText = newStatus ? 'mengaktifkan' : 'menonaktifkan';
  if (confirm(`Apakah Anda yakin ingin ${actionText} jenis tarif "${item.name}"?`)) {
    try {
      await updateTariffTypeStatus(item.id, newStatus);
    } catch (err) {
      console.error(err);
      alert('Gagal merubah status.');
    }
  }
};

const handleDelete = async (item) => {
  if (confirm(`Apakah Anda yakin ingin menghapus jenis tarif "${item.name}"?`)) {
    try {
      await deleteTariffType(item.id);
      loadData();
    } catch (err) {
      console.error(err);
      alert(error.value || 'Gagal menghapus data.');
    }
  }
};

const changePage = (page) => {
  currentPage.value = page;
  loadData();
};

const changePerPage = () => {
  currentPage.value = 1;
  loadData();
};

onMounted(() => {
  loadData();
});

watch(searchQuery, (newVal) => {
  handleSearch(newVal);
});
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Jenis Tarif</h1>
        <p class="page-subtitle">Kelola master data jenis tarif dan komposisi komponennya</p>
      </div>
      <button class="btn btn-primary" @click="openAddModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Tambah Jenis Tarif
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
          placeholder="Cari nama atau kode..." 
        />
      </div>

      <div class="filter-actions">
        <div class="per-page-select">
          <label>Tampilkan:</label>
          <select v-model="perPage" class="form-control" @change="changePerPage">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </div>
      </div>
    </div>

    <TariffTypeTable 
      :items="tariffTypes" 
      :loading="loading"
      :sort-config="{ column: sortField, direction: sortDir }"
      :pagination="{ current_page: currentPage, per_page: perPage, total: totalItems }"
      @sort="handleSort"
      @edit="openEditModal" 
      @delete="handleDelete" 
      @toggle-status="handleToggleStatus"
    />

    <div class="pagination-bar" v-if="Math.ceil(totalItems / perPage) > 1">
      <div class="pagination-info">
        Menampilkan {{ (currentPage - 1) * perPage + 1 }} - 
        {{ Math.min(currentPage * perPage, totalItems) }} 
        dari {{ totalItems }} data
      </div>
      <div class="pagination-controls">
        <button 
          class="btn-page" 
          :disabled="currentPage === 1"
          @click="changePage(currentPage - 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        
        <button 
          v-for="page in Math.ceil(totalItems / perPage)" 
          :key="page"
          class="btn-page"
          :class="{ active: page === currentPage }"
          @click="changePage(page)"
        >
          {{ page }}
        </button>

        <button 
          class="btn-page" 
          :disabled="currentPage === Math.ceil(totalItems / perPage)"
          @click="changePage(currentPage + 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <TariffTypeFormModal
      :isOpen="isModalOpen"
      :editData="editingItem"
      :loading="isSaving"
      :errors="modalErrors"
      @close="closeModal"
      @save="handleSave"
    />
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
</style>
