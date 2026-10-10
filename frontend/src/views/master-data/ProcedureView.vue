<script setup>
import { ref, onMounted } from 'vue';
import { useProcedures } from '@/composables/useProcedures';
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
  error,
  fetchProcedures,
  getProcedure,
  createProcedure,
  updateProcedure,
  updateProcedureVisibility,
  deleteProcedure
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
  is_visible: ''
});

const sortConfig = ref({
  column: 'created_at',
  direction: 'desc'
});

const loadData = () => {
  fetchProcedures({
    search: filters.value.search,
    is_visible: filters.value.is_visible,
    sort_by: sortConfig.value.column,
    sort_dir: sortConfig.value.direction
  });
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
        @filter="handleFilter" 
        @refresh="loadData" 
      />
      
      <MasterDataEmptyState 
        v-if="!loading && procedures.length === 0" 
        :is-search="!!filters.search || filters.is_visible !== ''"
        title="Tidak ada data Tindakan"
        message="Belum ada data tindakan yang tersedia atau tidak ada hasil pencarian yang cocok."
      >
        <template #action v-if="!filters.search && filters.is_visible === ''">
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
