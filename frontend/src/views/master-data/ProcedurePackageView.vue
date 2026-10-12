<script setup>
import { ref, onMounted } from 'vue';
import { useProcedurePackages } from '@/composables/useProcedurePackages';
import ProcedurePackageTable from '@/components/master-data/procedure-packages/ProcedurePackageTable.vue';
import ProcedurePackageFormModal from '@/components/master-data/procedure-packages/ProcedurePackageFormModal.vue';
import ProcedurePackageFilters from '@/components/master-data/procedure-packages/ProcedurePackageFilters.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  packages,
  pagination,
  loading,
  error,
  fetchPackages,
  getPackage,
  createPackage,
  updatePackage,
  deletePackage
} = useProcedurePackages();

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
const modalMode = ref('create');
const selectedPackageId = ref(null);

const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);
const isDeleting = ref(false);

const filters = ref({
  search: ''
});

const sortConfig = ref({
  column: 'created_at',
  direction: 'desc'
});

const loadData = () => {
  fetchPackages({
    search: filters.value.search,
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
  modalMode.value = 'create';
  selectedPackageId.value = null;
  isModalOpen.value = true;
};

const openEditModal = (pkg) => {
  modalMode.value = 'edit';
  selectedPackageId.value = pkg.id;
  isModalOpen.value = true;
};

const handleSaved = () => {
  isModalOpen.value = false;
  selectedPackageId.value = null;
  showToast({ 
    title: 'Sukses', 
    message: modalMode.value === 'edit' ? 'Paket tindakan berhasil diperbarui.' : 'Paket tindakan berhasil ditambahkan.', 
    type: 'success' 
  });
  loadData();
};

const openDeleteDialog = (item) => {
  itemToDelete.value = item;
  isDeleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (isDeleting.value) return;
  isDeleteDialogOpen.value = false;
  itemToDelete.value = null;
};

const confirmDelete = async () => {
  if (!itemToDelete.value || isDeleting.value) return;
  
  isDeleting.value = true;
  try {
    await deletePackage(itemToDelete.value.id);
    
    isDeleteDialogOpen.value = false;
    itemToDelete.value = null;
    
    showToast({ title: 'Sukses', message: 'Paket tindakan berhasil dihapus.', type: 'success' });
    
    // Adjust pagination if needed
    if (packages.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page -= 1;
    }
    
    loadData();
  } catch (err) {
    if (err.response?.status === 409) {
      showToast({ title: 'Gagal', message: err.response.data.message || 'Paket Tindakan tidak dapat dihapus karena sudah digunakan pada transaksi.', type: 'error' });
    } else {
      showToast({ title: 'Gagal', message: 'Gagal menghapus paket tindakan.', type: 'error' });
    }
  } finally {
    isDeleting.value = false;
  }
};

const handlePageChange = (page) => {
  pagination.current_page = page;
  loadData();
};

const breadcrumbs = ref([
  { label: 'Master Data', active: false },
  { label: 'Tindakan', active: false },
  { label: 'Paket Tindakan', active: true }
]);

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="page-container">
    <MasterDataPageHeader 
      title="Paket Tindakan" 
      subtitle="Daftar Paket Tindakan"
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <button type="button" class="btn btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" class="mr-2">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Paket Tindakan
        </button>
      </template>
    </MasterDataPageHeader>

    <ProcedurePackageFilters 
      :filters="filters"
      :loading="loading"
      @filter="handleFilter"
      @refresh="loadData"
    />

    <template v-if="error && !packages.length">
      <MasterDataEmptyState 
        icon="AlertCircle"
        title="Gagal Memuat Data"
        :description="error"
        actionLabel="Coba Lagi"
        @action="loadData"
      />
    </template>

    <template v-else-if="!loading && packages.length === 0 && !filters.search">
      <MasterDataEmptyState 
        icon="Package"
        title="Belum Ada Paket Tindakan"
        description="Mulai tambahkan paket tindakan baru ke dalam sistem."
        actionLabel="Tambah Paket Tindakan"
        @action="openAddModal"
      />
    </template>

    <template v-else-if="!loading && packages.length === 0 && filters.search">
      <MasterDataEmptyState 
        icon="SearchX"
        title="Pencarian Tidak Ditemukan"
        :description="`Tidak ada paket tindakan yang cocok dengan kata kunci '${filters.search}'.`"
        actionLabel="Bersihkan Pencarian"
        @action="handleFilter({ key: 'search', value: '' })"
      />
    </template>

    <template v-else>
      <ProcedurePackageTable 
        :items="packages"
        :loading="loading"
        :sort-config="sortConfig"
        :pagination="pagination"
        @sort="handleSort"
        @edit="openEditModal"
        @delete="openDeleteDialog"
      />

      <div class="mt-4">
        <MasterDataPagination 
          :pagination="pagination"
          :loading="loading"
          :item-count="packages.length"
          @page-change="handlePageChange"
        />
      </div>
    </template>

    <ProcedurePackageFormModal
      v-model="isModalOpen"
      :mode="modalMode"
      :packageId="selectedPackageId"
      @saved="handleSaved"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteDialogOpen"
      title="Hapus Paket Tindakan"
      :item-name="itemToDelete?.name || ''"
      warning-message="Data yang telah dihapus tidak dapat digunakan kembali."
      :is-submitting="isDeleting"
      @close="closeDeleteDialog"
      @confirm="confirmDelete"
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
  gap: 1rem;
}
</style>
