<script setup>
import { ref, onMounted } from 'vue';
import { useProcedureUserMappings } from '@/composables/useProcedureUserMappings';
import ProcedureUserMappingTable from '@/components/master-data/procedure-user-mappings/ProcedureUserMappingTable.vue';
import ProcedureUserMappingFilters from '@/components/master-data/procedure-user-mappings/ProcedureUserMappingFilters.vue';
import ProcedureUserMappingFormModal from '@/components/master-data/procedure-user-mappings/ProcedureUserMappingFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  mappings,
  pagination,
  loading,
  error,
  fetchMappings,
  deleteMapping
} = useProcedureUserMappings();

const isFormModalOpen = ref(false);
const isDeleteDialogOpen = ref(false);
const selectedMapping = ref(null);
const selectedMappingId = ref(null);
const isDeleting = ref(false);

const filters = ref({
  search: ''
});

const sortConfig = ref({
  column: 'created_at',
  direction: 'desc'
});

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

const breadcrumbs = ref([
  { label: 'Master Data', active: false },
  { label: 'Tindakan', active: false },
  { label: 'Mapping Tindakan User', active: true }
]);

const loadData = () => {
  fetchMappings({
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

const handlePageChange = (page) => {
  pagination.current_page = page;
  loadData();
};

const openAddModal = () => {
  selectedMappingId.value = null;
  selectedMapping.value = null;
  isFormModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedMappingId.value = item.id;
  selectedMapping.value = item;
  isFormModalOpen.value = true;
};

const handleSaved = (mode) => {
  isFormModalOpen.value = false;
  selectedMappingId.value = null;
  selectedMapping.value = null;
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: mode === 'edit'
      ? 'Mapping tindakan user berhasil diperbarui.'
      : 'Mapping tindakan user berhasil ditambahkan.'
  });
  loadData();
};

const openDeleteDialog = (item) => {
  selectedMapping.value = item;
  isDeleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (isDeleting.value) return;
  isDeleteDialogOpen.value = false;
  selectedMapping.value = null;
};

const confirmDelete = async () => {
  if (!selectedMapping.value || isDeleting.value) return;

  isDeleting.value = true;
  try {
    const result = await deleteMapping(selectedMapping.value.id);
    if (result.success) {
      isDeleteDialogOpen.value = false;
      selectedMapping.value = null;

      showToast({
        type: 'success',
        title: 'Berhasil',
        message: 'Mapping tindakan user berhasil dihapus.'
      });

      // Adjust pagination if deleting the only record on later page
      if (mappings.value.length === 1 && pagination.current_page > 1) {
        pagination.current_page -= 1;
      }
      loadData();
    } else {
      showToast({
        type: 'error',
        title: 'Gagal Menghapus',
        message: result.error?.response?.data?.message || 'Gagal menghapus mapping tindakan user.'
      });
    }
  } catch (err) {
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: err.response?.data?.message || 'Gagal menghapus mapping tindakan user.'
    });
  } finally {
    isDeleting.value = false;
  }
};

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="page-container">
    <MasterDataPageHeader
      title="Mapping Tindakan User"
      subtitle="Kelola pemetaan tindakan dengan pegawai (medis/paramedis)"
      :breadcrumbs="breadcrumbs"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Mapping
        </button>
      </template>
    </MasterDataPageHeader>

    <div class="page-content">
      <ProcedureUserMappingFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="loadData"
      />

      <MasterDataErrorState 
        v-if="error && !mappings.length" 
        :error="error" 
        @retry="loadData" 
      />

      <template v-else>
        <ProcedureUserMappingTable
          :items="mappings"
          :loading="loading"
          :sort-config="sortConfig"
          :pagination="pagination"
          :search-keyword="filters.search"
          @sort="handleSort"
          @edit="openEditModal"
          @delete="openDeleteDialog"
          @add="openAddModal"
        />

        <div v-if="pagination.total > 0">
          <MasterDataPagination
            :pagination="pagination"
            :loading="loading"
            :item-count="mappings.length"
            @page-change="handlePageChange"
          />
        </div>
      </template>
    </div>

    <!-- Modals -->
    <ProcedureUserMappingFormModal
      v-model="isFormModalOpen"
      :id="selectedMappingId"
      :item="selectedMapping"
      @saved="handleSaved"
    />

    <MasterDataDeleteDialog
      :is-open="isDeleteDialogOpen"
      title="Hapus Mapping Tindakan User"
      :item-name="selectedMapping ? `mapping tindakan &quot;${selectedMapping.procedure?.name}&quot; dari user &quot;${selectedMapping.employees?.map(e => e.name).join(', ') || 'User'}&quot;` : ''"
      warning-message="Penghapusan hanya melepaskan hubungan user dengan tindakan dan tidak menghapus data user maupun data tindakan."
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
  gap: 24px;
}

.page-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
