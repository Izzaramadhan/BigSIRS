<script setup>
import { onMounted, ref, watch } from 'vue';
import { useProcedureCategories } from '@/composables/useProcedureCategories';
import ProcedureCategoryTable from '@/components/master-data/procedure-categories/ProcedureCategoryTable.vue';
import ProcedureCategoryFormModal from '@/components/master-data/procedure-categories/ProcedureCategoryFormModal.vue';

const {
  items,
  pagination,
  filters,
  sort,
  loading,
  error,
  fetchProcedureCategories,
  setPage,
  setSort,
  toggleStatus,
  archiveProcedureCategory
} = useProcedureCategories();

const showModal = ref(false);
const selectedCategory = ref(null);
const searchInput = ref('');

let searchTimeout = null;
const handleSearch = (value) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    filters.search = value;
    setPage(1);
  }, 400);
};

watch(searchInput, (newVal) => {
  handleSearch(newVal);
});

const handleAdd = () => {
  selectedCategory.value = null;
  showModal.value = true;
};

const handleEdit = (category) => {
  selectedCategory.value = category;
  showModal.value = true;
};

const handleSaved = () => {
  fetchProcedureCategories();
};

const handleDelete = async (category) => {
  if (confirm(`Apakah Anda yakin ingin menghapus kategori tindakan "${category.name}"?`)) {
    const res = await archiveProcedureCategory(category.id);
    if (res.success) {
      fetchProcedureCategories();
    } else {
      const msg = res.error?.response?.data?.message || 'Gagal menghapus data.';
      alert(msg);
    }
  }
};

const handleToggleStatus = async (category) => {
  const newStatus = !category.is_active;
  const actionText = newStatus ? 'mengaktifkan' : 'menonaktifkan';
  if (confirm(`Apakah Anda yakin ingin ${actionText} kategori tindakan "${category.name}"?`)) {
    const res = await toggleStatus(category.id, newStatus);
    if (res.success) {
      fetchProcedureCategories();
    } else {
      alert('Gagal merubah status.');
    }
  }
};

onMounted(() => {
  fetchProcedureCategories();
});
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Kategori Tindakan</h1>
        <p class="page-subtitle">Kelola master data kategori tindakan</p>
      </div>
      <button class="btn btn-primary" @click="handleAdd">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Tambah Kategori
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
          v-model="searchInput" 
          class="form-control" 
          placeholder="Cari nama atau deskripsi..." 
        />
      </div>

      <div class="filter-actions">
        <select v-model="filters.is_active" class="form-control" @change="setPage(1)">
          <option :value="null">Semua Status</option>
          <option :value="true">Aktif</option>
          <option :value="false">Nonaktif</option>
        </select>
      </div>
    </div>

    <ProcedureCategoryTable 
      :items="items"
      :loading="loading"
      :sort-config="sort"
      :pagination="pagination"
      @sort="setSort"
      @edit="handleEdit"
      @delete="handleDelete"
      @toggle-status="handleToggleStatus"
    />

    <div class="pagination-bar" v-if="pagination.last_page > 1">
      <div class="pagination-info">
        Menampilkan {{ (pagination.current_page - 1) * pagination.per_page + 1 }} - 
        {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} 
        dari {{ pagination.total }} data
      </div>
      <div class="pagination-controls">
        <button 
          class="btn-page" 
          :disabled="pagination.current_page === 1"
          @click="setPage(pagination.current_page - 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        
        <button 
          v-for="page in pagination.last_page" 
          :key="page"
          class="btn-page"
          :class="{ active: page === pagination.current_page }"
          @click="setPage(page)"
        >
          {{ page }}
        </button>

        <button 
          class="btn-page" 
          :disabled="pagination.current_page === pagination.last_page"
          @click="setPage(pagination.current_page + 1)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

    <ProcedureCategoryFormModal 
      :is-open="showModal"
      :category="selectedCategory"
      @close="showModal = false"
      @saved="handleSaved"
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
