<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRegencies } from '@/composables/useRegencies'
import RegencyFormModal from '@/components/master-data/regencies/RegencyFormModal.vue'
import AppToast from '@/components/common/AppToast.vue'

import MasterDataPageHeader from '@/components/master-data/shared/MasterDataPageHeader.vue';
import MasterDataPagination from '@/components/master-data/shared/MasterDataPagination.vue';
import MasterDataEmptyState from '@/components/master-data/shared/MasterDataEmptyState.vue';
import MasterDataErrorState from '@/components/master-data/shared/MasterDataErrorState.vue';
import MasterDataDeleteDialog from '@/components/master-data/shared/MasterDataDeleteDialog.vue';
import MasterDataSearchInput from '@/components/master-data/shared/MasterDataSearchInput.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

const {
  regencies,
  meta,
  loading,
  error,
  fetchRegencies,
  deleteRegency
} = useRegencies()

const toast = ref({
  show: false,
  type: 'success',
  title: '',
  message: ''
})

const showToast = ({ type = 'success', title, message }) => {
  toast.value = { show: true, type, title, message }
}

const closeToast = () => {
  toast.value.show = false
}

const searchQuery = ref('')
const perPage = ref(10)
const currentPage = ref(1)

const isFormModalOpen = ref(false)
const selectedRegency = ref(null)

const isDeleteModalOpen = ref(false)
const regencyToDelete = ref(null)
const deleteError = ref(null)

const handleSearch = () => {
  currentPage.value = 1
  loadData()
}

const handlePageChange = (page) => {
  if (page < 1 || page > meta.value.last_page) return
  currentPage.value = page
  loadData()
}

const loadData = async () => {
  try {
    await fetchRegencies({
      page: currentPage.value,
      per_page: perPage.value,
      search: searchQuery.value
    })
  } catch (err) {
    console.error('Failed to load regencies:', err)
  }
}

const handleRefresh = () => {
  loadData();
}

const openAddModal = () => {
  selectedRegency.value = null
  isFormModalOpen.value = true
}

const openEditModal = (regency) => {
  selectedRegency.value = { ...regency }
  isFormModalOpen.value = true
}

const onFormSuccess = () => {
  const isEditing = Boolean(selectedRegency.value)
  isFormModalOpen.value = false
  loadData()
  showToast({
    type: 'success',
    title: 'Berhasil',
    message: isEditing ? 'Data kabupaten berhasil diperbarui.' : 'Data kabupaten berhasil ditambahkan.'
  })
}

const confirmDelete = (regency) => {
  regencyToDelete.value = regency
  deleteError.value = null
  isDeleteModalOpen.value = true
}

const handleDelete = async () => {
  if (!regencyToDelete.value) return
  
  try {
    await deleteRegency(regencyToDelete.value.id)
    isDeleteModalOpen.value = false
    loadData()
    showToast({ type: 'success', title: 'Berhasil', message: 'Data kabupaten berhasil dihapus.' })
  } catch (err) {
    showToast({ 
      type: 'error', 
      title: 'Gagal Menghapus', 
      message: err.response?.data?.message || 'Data kabupaten gagal dihapus.' 
    })
    isDeleteModalOpen.value = false
  }
}

let searchTimeout
watch(searchQuery, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleSearch()
  }, 300)
})

onMounted(() => {
  loadData()
})

const getRowNumber = (index) => {
  return (currentPage.value - 1) * perPage.value + index + 1;
};
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

    <MasterDataPageHeader 
      title="Kabupaten/Kota"
      subtitle="Kelola data dasar kabupaten atau kota."
      :breadcrumbs="[
        { label: 'Dashboard', active: false },
        { label: 'Data Dasar', active: false },
        { label: 'Kabupaten/Kota', active: true }
      ]"
    >
      <template #actions>
        <button type="button" class="btn-primary" @click="openAddModal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Tambah Kabupaten
        </button>
      </template>
    </MasterDataPageHeader>

    <!-- Content -->
    <div class="page-content">
      <!-- Filters -->
      <div class="filters-container">
        <MasterDataSearchInput 
          v-model="searchQuery" 
          placeholder="Cari kode atau nama kabupaten..." 
        />
        <div class="filters-actions">
          <button type="button" class="btn-icon" @click="handleRefresh" title="Refresh Data" :disabled="loading">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spin': loading }">
              <polyline points="23 4 23 10 17 10"></polyline>
              <polyline points="1 20 1 14 7 14"></polyline>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
          </button>
        </div>
      </div>
      
      <MasterDataErrorState 
        v-if="error" 
        :error="error" 
        @retry="loadData" 
      />
      
      <template v-else>
        <MasterDataEmptyState 
          v-if="!loading && regencies.length === 0" 
          :is-search="!!searchQuery"
        >
          <template #action v-if="!searchQuery">
            <button class="btn-primary" @click="openAddModal">Tambah Kabupaten</button>
          </template>
        </MasterDataEmptyState>
        
        <template v-else>
          <div class="table-wrapper">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th class="col-no">No</th>
                    <th class="col-code">Kode</th>
                    <th class="col-name">Kabupaten/Kota</th>
                    <th class="col-province">Provinsi</th>
                    <th class="col-actions text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <template v-if="loading">
                    <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
                      <td><div class="skeleton-box skeleton-small"></div></td>
                      <td><div class="skeleton-box skeleton-small"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-medium"></div></td>
                      <td><div class="skeleton-box skeleton-circle"></div></td>
                    </tr>
                  </template>

                  <template v-else>
                    <tr v-for="(item, index) in regencies" :key="item.id">
                      <td class="col-no text-muted">{{ getRowNumber(index) }}</td>
                      <td class="col-code">{{ item.code || '-' }}</td>
                      <td class="col-name font-medium text-navy">{{ item.name }}</td>
                      <td class="col-province">{{ item.province_name }}</td>
                      <td class="actions-cell">
                        <MasterDataActionButtons 
                          :show-toggle="false"
                          @edit="openEditModal(item)"
                          @delete="confirmDelete(item)"
                        />
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>
          
          <MasterDataPagination 
            v-if="meta"
            :pagination="{ current_page: currentPage, last_page: meta.last_page || 1, per_page: perPage, total: meta.total || 0, from: meta.from, to: meta.to }"
            :loading="loading"
            :item-count="regencies.length"
            @page-change="handlePageChange"
          />
        </template>
      </template>
    </div>
    
    <RegencyFormModal 
      :is-open="isFormModalOpen"
      :regency="selectedRegency"
      @close="isFormModalOpen = false"
      @success="onFormSuccess"
    />

    <!-- Delete Dialog -->
    <MasterDataDeleteDialog 
      :is-open="isDeleteModalOpen"
      title="Hapus Kabupaten/Kota?"
      :item-name="regencyToDelete?.name || ''"
      warning-message="Tindakan ini tidak dapat dibatalkan."
      :is-submitting="loading"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />
  </div>
</template>

<style scoped>
.page-container {
  max-width: 100%;
  position: relative;
}

.filters-container {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  justify-content: space-between;
  align-items: center;
}

.filters-actions {
  display: flex;
  gap: 0.5rem;
}

.table-wrapper {
  width: 100%;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft);
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
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 1rem;
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

.col-no { width: 48px; }
.col-code { width: 100px; }
.col-name { min-width: 180px; }
.col-province { min-width: 150px; }
.col-actions { width: 110px; }

.font-medium {
  font-weight: 500;
}

.text-navy {
  color: var(--color-text-navy);
}

.text-muted {
  color: #cbd5e1;
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
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; margin: 0 auto; }

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
