<script setup>
import { ref, onMounted } from 'vue';
import { useLaboratoryGroups } from '@/composables/useLaboratoryGroups';
import { laboratoryCategoriesService } from '@/services/master-data/laboratoryCategories.service';
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service';
import LaboratoryGroupTable from '@/components/master-data/laboratory-groups/LaboratoryGroupTable.vue';
import LaboratoryGroupFilters from '@/components/master-data/laboratory-groups/LaboratoryGroupFilters.vue';
import LaboratoryGroupFormModal from '@/components/master-data/laboratory-groups/LaboratoryGroupFormModal.vue';
import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import AppToast from '@/components/common/AppToast.vue';

const {
  items,
  pagination,
  filters,
  sort,
  loading,
  submitting,
  error,
  fetchLaboratoryGroups,
  createLaboratoryGroup,
  updateLaboratoryGroup,
  deleteLaboratoryGroup,
  setPage,
  setSort
} = useLaboratoryGroups();

const categories = ref([]);
const availableItems = ref([]);
const isLoadingCategories = ref(false);
const isLoadingItems = ref(false);

const formModalOpen = ref(false);
const deleteDialogOpen = ref(false);
const selectedGroup = ref(null);
const groupToDelete = ref(null);
const formErrors = ref({});

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

const loadLookupData = async () => {
  isLoadingCategories.value = true;
  isLoadingItems.value = true;
  try {
    const fetchCats = typeof laboratoryCategoriesService.getLaboratoryCategories === 'function'
      ? laboratoryCategoriesService.getLaboratoryCategories({ per_page: 100 })
      : (typeof laboratoryCategoriesService.list === 'function'
        ? laboratoryCategoriesService.list({ per_page: 100 })
        : (typeof laboratoryCategoriesService.all === 'function' ? laboratoryCategoriesService.all() : Promise.resolve({ data: [] })));

    const fetchItems = typeof laboratoryItemsService.list === 'function'
      ? laboratoryItemsService.list({ per_page: 500 })
      : (typeof laboratoryItemsService.all === 'function'
        ? laboratoryItemsService.all()
        : Promise.resolve({ data: [] }));

    const [catRes, itemsRes] = await Promise.all([fetchCats, fetchItems]);
    categories.value = catRes?.data || catRes || [];
    availableItems.value = itemsRes?.data || itemsRes || [];
  } catch (err) {
    console.error('Gagal memuat data lookup kategori/item lab:', err);
  } finally {
    isLoadingCategories.value = false;
    isLoadingItems.value = false;
  }
};

const openAddModal = () => {
  selectedGroup.value = null;
  formErrors.value = {};
  formModalOpen.value = true;
};

const openEditModal = (item) => {
  selectedGroup.value = { ...item };
  formErrors.value = {};
  formModalOpen.value = true;
};

const openDeleteDialog = (item) => {
  groupToDelete.value = item;
  deleteDialogOpen.value = true;
};

const closeDeleteDialog = () => {
  if (submitting.value) return;
  deleteDialogOpen.value = false;
  groupToDelete.value = null;
};

const handleFilter = ({ key, value }) => {
  filters[key] = value;
  pagination.current_page = 1;
  fetchLaboratoryGroups();
};

const handleFormSubmit = async (payload) => {
  formErrors.value = {};

  const isEditing = Boolean(selectedGroup.value);
  let result;

  if (isEditing) {
    result = await updateLaboratoryGroup(selectedGroup.value.id, payload);
  } else {
    result = await createLaboratoryGroup(payload);
  }

  if (result.success) {
    formModalOpen.value = false;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: isEditing
        ? 'Grup lab berhasil diperbarui.'
        : 'Grup lab berhasil ditambahkan.'
    });
    fetchLaboratoryGroups();
  } else {
    if (result.error?.response?.status === 422) {
      formErrors.value = result.error.response.data.errors || {};
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: 'Silakan periksa kembali data yang dimasukkan.'
      });
    } else {
      const msg = result.error?.response?.data?.message || 'Terjadi kesalahan sistem. Silakan coba lagi.';
      formErrors.value = { general: msg };
      showToast({
        type: 'error',
        title: isEditing ? 'Gagal Memperbarui' : 'Gagal Menambahkan',
        message: msg
      });
    }
  }
};

const handleDeleteConfirm = async () => {
  if (!groupToDelete.value) return;

  const result = await deleteLaboratoryGroup(groupToDelete.value.id);

  if (result.success) {
    deleteDialogOpen.value = false;
    groupToDelete.value = null;
    showToast({
      type: 'success',
      title: 'Berhasil',
      message: 'Grup lab berhasil dihapus.'
    });

    // Handle pagination if last item on page deleted
    if (items.value.length === 1 && pagination.current_page > 1) {
      pagination.current_page--;
    }

    fetchLaboratoryGroups();
  } else {
    const errorMsg = result.error?.response?.data?.message || 'Grup lab gagal dihapus. Silakan coba lagi.';
    showToast({
      type: 'error',
      title: 'Gagal Menghapus',
      message: errorMsg
    });
  }
};

onMounted(() => {
  fetchLaboratoryGroups();
  loadLookupData();
});
</script>

<template>
  <div class="page-container">
    <!-- App Toast -->
    <AppToast
      :show="toast.show"
      :type="toast.type"
      :title="toast.title"
      :message="toast.message"
      @close="closeToast"
    />

    <!-- Header -->
    <MasterDataPageHeader
      title="Grup Lab"
      subtitle="Kelola master data grup laboratorium dan item di dalamnya."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Master Data', active: false },
        { label: 'Data Lab', active: false },
        { label: 'Grup Lab', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Grup Lab
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content Card / Section -->
    <div class="page-content">
      <LaboratoryGroupFilters
        :filters="filters"
        :loading="loading"
        @filter="handleFilter"
        @refresh="fetchLaboratoryGroups"
      />

      <MasterDataErrorState
        v-if="error"
        :error="error"
        @retry="fetchLaboratoryGroups"
      />

      <template v-else>
        <MasterDataEmptyState
          v-if="!loading && items.length === 0"
          :is-search="!!filters.search || filters.is_active !== null"
        >
          <template
            #action
            v-if="!filters.search && filters.is_active === null"
          >
            <button class="btn-primary" @click="openAddModal">
              Tambah Grup Lab
            </button>
          </template>
        </MasterDataEmptyState>

        <template v-else>
          <LaboratoryGroupTable
            :items="items"
            :pagination="pagination"
            :sort="sort"
            :loading="loading"
            @sort="setSort"
            @edit="openEditModal"
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

    <!-- Modal Form Tambah/Edit -->
    <LaboratoryGroupFormModal
      :is-open="formModalOpen"
      :group="selectedGroup"
      :categories="categories"
      :available-items="availableItems"
      :is-loading-categories="isLoadingCategories"
      :is-loading-items="isLoadingItems"
      :is-submitting="submitting"
      :errors="formErrors"
      @close="formModalOpen = false"
      @submit="handleFormSubmit"
    />

    <!-- Dialog Konfirmasi Hapus -->
    <MasterDataDeleteDialog
      :is-open="deleteDialogOpen"
      title="Hapus Grup Lab?"
      :item-name="groupToDelete?.name || ''"
      warning-message="Apakah Anda yakin ingin menghapus grup lab ini? Tindakan ini tidak dapat dibatalkan."
      :is-submitting="submitting"
      @close="closeDeleteDialog"
      @confirm="handleDeleteConfirm"
    />
  </div>
</template>

<style scoped>
.page-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-content {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background-color: var(--color-primary, #0f766e);
  color: #ffffff;
  border: none;
  border-radius: 6px;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s, transform 0.1s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-primary:hover {
  background-color: #0d9488;
}

.btn-primary:active {
  transform: translateY(1px);
}

.btn-primary svg {
  width: 18px;
  height: 18px;
}
</style>
