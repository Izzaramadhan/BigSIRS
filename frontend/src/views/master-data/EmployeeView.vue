<script setup>
import { ref, onMounted } from 'vue';
import { useEmployees } from '@/composables/useEmployees';
import EmployeeTable from '@/components/master-data/employees/EmployeeTable.vue';
import EmployeeFilters from '@/components/master-data/employees/EmployeeFilters.vue';
import EmployeeFormModal from '@/components/master-data/EmployeeFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  items,
  pagination,
  filters,
  sort,
  loading,
  error,
  fetchEmployees,
  deleteEmployee,
  setPage,
  setSort
} = useEmployees();

const isEditOpen = ref(false);
const deleteDialogOpen = ref(false);
const formMode = ref('create');
const selectedEmployee = ref(null);
const detailLoading = ref(false);

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

const openCreateEmployee = () => {
  formMode.value = 'create';
  selectedEmployee.value = null;
  isEditOpen.value = true;
};

const openEditEmployee = (row) => {
  selectedEmployee.value = row;
  formMode.value = 'edit';
  isEditOpen.value = true;
};

const closeEmployeeForm = () => {
  isEditOpen.value = false;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchEmployees();
};

const handleEmployeeSaved = () => {
  isEditOpen.value = false;
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: formMode.value === 'edit' ? 'Data pegawai berhasil diperbarui.' : 'Data pegawai berhasil ditambahkan.'
  });
  fetchEmployees();
};

const openDeleteDialog = (item) => {
  selectedEmployee.value = item;
  deleteDialogOpen.value = true;
};

const handleDeleteConfirm = async () => {
  detailLoading.value = true;
  const result = await deleteEmployee(selectedEmployee.value.id);
  detailLoading.value = false;

  if (result.success) {
    deleteDialogOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Data pegawai berhasil dihapus.'
    });
    // Adjust pagination if needed
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }
    fetchEmployees();
  } else {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: result.error?.response?.data?.message || 'Terjadi kesalahan saat menghapus data pegawai.'
    });
    deleteDialogOpen.value = false;
  }
};

onMounted(() => {
  fetchEmployees();
});
</script>

<template>
  <div class="page-container">
    <AppToast 
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header -->
    <MasterDataPageHeader 
      title="Pegawai"
      subtitle="Kelola data pegawai, unit kerja, jabatan, dan informasi profil lainnya."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Pegawai', active: false },
        { label: 'Pegawai', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openCreateEmployee">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Pegawai
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <EmployeeFilters 
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchEmployees"
      />
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="fetchEmployees" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && items.length === 0" 
          :is-search="!!filters.search"
        >
          <template #action v-if="!filters.search">
            <button class="btn-primary" @click="openCreateEmployee">Tambah Pegawai</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <EmployeeTable 
            :items="items"
            :pagination="pagination"
            :sort="sort"
            :loading="loading"
            @sort="setSort"
            @edit="openEditEmployee"
            @delete="openDeleteDialog"
          />
          
          <MasterDataPagination 
            :pagination="pagination"
            :loading="loading"
            :item-count="items.length"
            @page-change="setPage"
          />
        </template>
      </template>
    </div>

    <!-- Modals -->
    <EmployeeFormModal 
      :is-open="isEditOpen"
      :employee="selectedEmployee"
      @close="closeEmployeeForm"
      @saved="handleEmployeeSaved"
    />
    
    <MasterDataDeleteDialog 
      :is-open="deleteDialogOpen"
      title="Hapus Pegawai?"
      :item-name="selectedEmployee ? selectedEmployee.name : ''"
      warning-message="Data pegawai yang dihapus mungkin tidak dapat dikembalikan. Lanjutkan?"
      :is-submitting="detailLoading"
      @close="deleteDialogOpen = false"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>
