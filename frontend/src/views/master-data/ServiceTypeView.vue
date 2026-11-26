<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import ServiceTypeFilters from '@/components/master-data/service-types/ServiceTypeFilters.vue'
import ServiceTypeFormModal from '@/components/master-data/service-types/ServiceTypeFormModal.vue'
import ServiceTypeTable from '@/components/master-data/service-types/ServiceTypeTable.vue'
import { getSafeServiceTypeErrorMessage } from '@/components/master-data/service-types/service-type.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useServiceTypes } from '@/composables/useServiceTypes'

const {
  items,
  meta,
  filters,
  loading,
  loadError,
  saving,
  fetchItems,
  setPage,
  setFilters,
  create,
  update,
  remove,
} = useServiceTypes()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingServiceType = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(serviceType = null) {
  editingServiceType.value = serviceType
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingServiceType.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingServiceType.value)
  const fallback = isEdit
    ? 'Data jenis layanan gagal diperbarui.'
    : 'Data jenis layanan gagal ditambahkan.'
  const result = isEdit
    ? await update(editingServiceType.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeServiceTypeErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit
      ? 'Data jenis layanan berhasil diperbarui.'
      : 'Data jenis layanan berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingServiceType = ref(null)

const deleteMessage = computed(() => {
  if (!deletingServiceType.value) return ''
  return `Apakah Anda yakin ingin menghapus jenis layanan “${deletingServiceType.value.name}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingServiceType.value = null
}

async function handleDelete() {
  if (saving.value || !deletingServiceType.value) return
  const result = await remove(deletingServiceType.value.id)
  deletingServiceType.value = null

  if (result.ok) {
    toast.success('Data jenis layanan berhasil dihapus.')
    return
  }

  toast.error(
    getSafeServiceTypeErrorMessage(result.error, 'Data jenis layanan gagal dihapus.'),
  )
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeServiceTypeErrorMessage({ message: loadError.value }, 'Data jenis layanan gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Jenis Layanan"
      description="Kelola data dasar jenis layanan."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Jenis Layanan']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Jenis Layanan
        </BaseButton>
      </template>
    </BasePageHeader>

    <ServiceTypeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <ServiceTypeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingServiceType = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data jenis layanan"
          :description="safeLoadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Coba Lagi</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Jenis layanan tidak ditemukan"
          :description="`Tidak ada jenis layanan yang cocok dengan kata kunci “${filters.search}”.`"
        >
          <template #action>
            <BaseButton variant="secondary" @click="setFilters({ search: '' })">
              Reset Pencarian
            </BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else
          :icon="Inbox"
          title="Belum ada data jenis layanan"
          description="Tambahkan jenis layanan pertama untuk melengkapi data referensi."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <template #icon><Plus class="size-4" aria-hidden="true" /></template>
              Tambah Jenis Layanan
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </ServiceTypeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <ServiceTypeFormModal
      :open="formOpen"
      :service-type="editingServiceType"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingServiceType)"
      title="Hapus Jenis Layanan?"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
