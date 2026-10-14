<script setup>
import { ref, computed, onMounted } from 'vue';
import { useProcedures } from '@/composables/useProcedures';
import { procedureCategoryService } from '@/services/procedureCategory';
import tariffTypeService from '@/services/tariffType';
import ProcedureTable from '@/components/master-data/procedures/ProcedureTable.vue';
import ProcedureFormModal from '@/components/master-data/procedures/ProcedureFormModal.vue';
import ProcedureFilters from '@/components/master-data/procedures/ProcedureFilters.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  procedures,
  pagination,
  loading,
  exporting,
  error,
  fetchProcedures,
  getProcedure,
  createProcedure,
  updateProcedure,
  updateProcedureVisibility,
  deleteProcedure,
  exportProcedures
} = useProcedures();

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
});

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message };
};

const closeToast = () => {
  toast.value.show = false;
};

const isModalOpen = ref(false);
const editingItem = ref(null);
const modalErrors = ref({});
const isSubmitting = ref(false);

const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

const filters = ref({
  search: '',
  category_id: '',
  tariff_type_id: '',
  is_visible: ''
});

const categories = ref([]);
const categoriesLoading = ref(false);
const categoriesError = ref(null);

const tariffTypes = ref([]);
const tariffTypesLoading = ref(false);
const tariffTypesError = ref(null);

const sortConfig = ref({
  column: 'created_at',
  direction: 'desc'
});

const hasActiveFilter = computed(() => {
  return Boolean(
    filters.value.search || 
    filters.value.category_id || 
    filters.value.tariff_type_id || 
    (filters.value.is_visible !== '' && filters.value.is_visible !== null && filters.value.is_visible !== undefined)
  );
});

const loadCategories = async () => {
  categoriesLoading.value = true;
  categoriesError.value = null;
  try {
    const res = await procedureCategoryService.getProcedureCategories({ all: true, per_page: 500, sort: 'name', direction: 'asc' });
    const rawList = res.data?.data || res.data || [];
    const uniqueMap = new Map();
    for (const item of rawList) {
      if (item && item.id && !uniqueMap.has(item.id)) {
        uniqueMap.set(item.id, {
          value: item.id,
          label: item.name || `Kategori #${item.id}`
        });
      }
    }
    categories.value = Array.from(uniqueMap.values()).sort((a, b) => a.label.localeCompare(b.label, 'id'));
  } catch (err) {
    console.error('Gagal memuat kategori tindakan:', err);
    categoriesError.value = 'Gagal memuat kategori tindakan.';
    showToast({ title: 'Peringatan', message: 'Gagal memuat data referensi kategori tindakan.', type: 'warning' });
  } finally {
    categoriesLoading.value = false;
  }
};

const loadTariffTypes = async () => {
  tariffTypesLoading.value = true;
  tariffTypesError.value = null;
  try {
    const res = await tariffTypeService.getTariffTypes({ all: true, per_page: 500, sort_by: 'name', sort_dir: 'asc' });
    const rawList = res.data?.data || res.data || [];
    const uniqueMap = new Map();
    for (const item of rawList) {
      if (item && item.id && !uniqueMap.has(item.id)) {
        uniqueMap.set(item.id, {
          value: item.id,
          label: item.name || `Jenis Tarif #${item.id}`
        });
      }
    }
    tariffTypes.value = Array.from(uniqueMap.values()).sort((a, b) => a.label.localeCompare(b.label, 'id'));
  } catch (err) {
    console.error('Gagal memuat jenis tarif:', err);
    tariffTypesError.value = 'Gagal memuat jenis tarif.';
    showToast({ title: 'Peringatan', message: 'Gagal memuat data referensi jenis tarif.', type: 'warning' });
  } finally {
    tariffTypesLoading.value = false;
  }
};

const loadData = () => {
  const params = {
    sort_by: sortConfig.value.column,
    sort_dir: sortConfig.value.direction
  };

  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.category_id) params.category_id = filters.value.category_id;
  if (filters.value.tariff_type_id) params.tariff_type_id = filters.value.tariff_type_id;
  if (filters.value.is_visible !== '' && filters.value.is_visible !== null && filters.value.is_visible !== undefined) {
    params.is_visible = filters.value.is_visible;
  }

  fetchProcedures(params);
};

const handleRefresh = async () => {
  await Promise.all([
    loadCategories(),
    loadTariffTypes(),
    loadData()
  ]);
};

const handleFilter = ({ key, value }) => {
  filters.value[key] = value;
  pagination.current_page = 1;
  loadData();
};

const handleSort = ({ column, direction }) => {
  sortConfig.value = { column, direction };
  loadData();
};

const handleExport = async () => {
  const params = {
    sort_by: sortConfig.value.column,
    sort_dir: sortConfig.value.direction
  };

  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.category_id) params.category_id = filters.value.category_id;
  if (filters.value.tariff_type_id) params.tariff_type_id = filters.value.tariff_type_id;
  if (filters.value.is_visible !== '' && filters.value.is_visible !== null && filters.value.is_visible !== undefined) {
    params.is_visible = filters.value.is_visible;
  }

  const result = await exportProcedures(params);
  if (result.empty) {
    showToast({ title: 'Informasi', message: result.message || 'Tidak ada data tindakan yang dapat diunduh.', type: 'info' });
  } else if (result.success) {
    showToast({ title: 'Sukses', message: 'Data tindakan berhasil diunduh.', type: 'success' });
  } else {
    showToast({ title: 'Error', message: result.message || 'Data tindakan gagal diunduh. Silakan coba lagi.', type: 'error' });
  }
};

const openAddModal = () => {
  editingItem.value = null;
  modalErrors.value = {};
  isModalOpen.value = true;
};

const openEditModal = async (item) => {
  try {
    const detail = await getProcedure(item.id);
    editingItem.value = { ...detail };
    modalErrors.value = {};
    isModalOpen.value = true;
  } catch (err) {
    console.error(err);
    showToast({ title: 'Error', message: 'Gagal mengambil rincian tindakan untuk diubah.', type: 'error' });
  }
};

const closeModal = () => {
  isModalOpen.value = false;
  editingItem.value = null;
  modalErrors.value = {};
};

const handleSave = async (data) => {
  isSubmitting.value = true;
  modalErrors.value = {};
  
  try {
    if (editingItem.value) {
      await updateProcedure(editingItem.value.id, data);
      showToast({ title: 'Sukses', message: 'Tindakan berhasil diperbarui.', type: 'success' });
    } else {
      await createProcedure(data);
      showToast({ title: 'Sukses', message: 'Tindakan berhasil ditambahkan.', type: 'success' });
    }
    closeModal();
    loadData();
  } catch (err) {
    if (err.response?.status === 422) {
      modalErrors.value = err.response.data.errors || {};
    } else {
      modalErrors.value = { general: err.response?.data?.message || err.message || 'Terjadi kesalahan' };
      showToast({ title: 'Error', message: editingItem.value ? 'Gagal memperbarui tindakan.' : 'Gagal menambahkan tindakan.', type: 'error' });
    }
  } finally {
    isSubmitting.value = false;
  }
};

const handleToggleStatus = async (item) => {
  const newStatus = !item.is_visible;
  try {
    await updateProcedureVisibility(item.id, newStatus);
    showToast({ title: 'Sukses', message: 'Status tindakan berhasil diperbarui.', type: 'success' });
    loadData();
  } catch (err) {
    console.error(err);
    showToast({ title: 'Error', message: 'Gagal memperbarui status tindakan.', type: 'error' });
  }
};

const openDeleteDialog = (item) => {
  itemToDelete.value = item;
  isDeleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  isDeleteDialogOpen.value = false;
  itemToDelete.value = null;
};

const handleDelete = async () => {
  if (!itemToDelete.value) return;
  
  isDeleting.value = true;
  try {
    await deleteProcedure(itemToDelete.value.id);
    showToast({ title: 'Sukses', message: 'Tindakan berhasil dihapus.', type: 'success' });
    
    // Adjust pagination if needed
    if (procedures.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    
    closeDeleteDialog();
    loadData();
  } catch (err) {
    console.error(err);
    showToast({ title: 'Error', message: error.value || 'Gagal menghapus tindakan.', type: 'error' });
    closeDeleteDialog();
  } finally {
    isDeleting.value = false;
  }
};

const handlePageChange = (page) => {
  if (page >= 1 && page <= pagination.last_page) {
    pagination.current_page = page;
    loadData();
  }
};

onMounted(() => {
  loadCategories();
  loadTariffTypes();
  loadData();
});
</script>

<template>
  <div class="page-container">
    <MasterDataPageHeader 
      title="Tindakan" 
      subtitle="Kelola master data tindakan dan detail tarif"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Tindakan
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="table-card">
      <ProcedureFilters 
        :filters="filters" 
        :loading="loading" 
        :exporting="exporting"
        :categories="categories"
        :categories-loading="categoriesLoading"
        :tariff-types="tariffTypes"
        :tariff-types-loading="tariffTypesLoading"
        @filter="handleFilter" 
        @refresh="handleRefresh" 
        @export="handleExport"
      />
      
      <MasterDataEmptyState 
        v-if="!loading && procedures.length === 0" 
        :is-search="hasActiveFilter"
        title="Belum ada data tindakan"
        message="Belum ada data tindakan."
        search-message="Tidak ada tindakan yang sesuai dengan filter."
      >
        <template #action v-if="!hasActiveFilter">
          <button class="btn-primary" @click="openAddModal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; margin-right: 8px; vertical-align: middle;">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Tambah Tindakan
          </button>
        </template>
      </MasterDataEmptyState>
      
      <template v-else>
        <ProcedureTable 
          :items="procedures" 
          :loading="loading"
          :sort-config="sortConfig"
          :pagination="pagination"
          @sort="handleSort"
          @edit="openEditModal" 
          @delete="openDeleteDialog" 
          @toggle-status="handleToggleStatus"
        />

        <MasterDataPagination 
          :pagination="pagination"
          :loading="loading"
          :item-count="procedures.length"
          @page-change="handlePageChange"
        />
      </template>
    </div>

    <ProcedureFormModal
      v-if="isModalOpen"
      :is-open="isModalOpen"
      :edit-data="editingItem"
      :is-submitting="isSubmitting"
      :errors="modalErrors"
      @close="closeModal"
      @submit="handleSave"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteDialogOpen"
      :is-deleting="isDeleting"
      :title="'Hapus Tindakan'"
      :item-name="itemToDelete?.name"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />

    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.table-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  padding: 1.5rem;
}
</style>
