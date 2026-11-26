<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import ActivityTypeFilters from '@/components/master-data/activity-type/ActivityTypeFilters.vue'
import ActivityTypeFormModal from '@/components/master-data/activity-type/ActivityTypeFormModal.vue'
import ActivityTypeTable from '@/components/master-data/activity-type/ActivityTypeTable.vue'
import {
  formatActivityTypeSummary,
  getSafeActivityTypeErrorMessage,
} from '@/components/master-data/activity-type/activity-type.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useActivityTypes } from '@/composables/useActivityTypes'

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
} = useActivityTypes()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingActivityType = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(activityType = null) {
  editingActivityType.value = activityType
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingActivityType.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingActivityType.value?.id)
  const fallback = isEdit
    ? 'Data jenis kegiatan gagal diperbarui.'
    : 'Data jenis kegiatan gagal ditambahkan.'
  const result = isEdit
    ? await update(editingActivityType.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeActivityTypeErrorMessage(result.error, fallback)
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
      ? 'Data jenis kegiatan berhasil diperbarui.'
      : 'Data jenis kegiatan berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingActivityType = ref(null)

const deleteMessage = computed(() => {
  if (!deletingActivityType.value) return ''
  return `Apakah Anda yakin ingin menghapus jenis kegiatan “${formatActivityTypeSummary(deletingActivityType.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingActivityType.value = null
}

async function handleDelete() {
  if (saving.value || !deletingActivityType.value) return
  const result = await remove(deletingActivityType.value.id)
  deletingActivityType.value = null

  if (result.ok) {
    toast.success('Data jenis kegiatan berhasil dihapus.')
    return
  }

  toast.error(getSafeActivityTypeErrorMessage(result.error, 'Data jenis kegiatan gagal dihapus.'))
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeActivityTypeErrorMessage({ message: loadError.value }, 'Data jenis kegiatan gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Jenis Kegiatan"
      description="Kelola data dasar Jenis Kegiatan."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Jenis Kegiatan']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Jenis Kegiatan
        </BaseButton>
      </template>
    </BasePageHeader>

    <ActivityTypeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <ActivityTypeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingActivityType = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data jenis kegiatan"
          :description="safeLoadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Coba Lagi</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Jenis kegiatan tidak ditemukan"
          :description="`Tidak ada jenis kegiatan yang cocok dengan kata kunci “${filters.search}”.`"
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
          title="Belum ada data jenis kegiatan"
          description="Tambahkan data jenis kegiatan pertama untuk melengkapi pengelompokan kegiatan layanan."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <template #icon><Plus class="size-4" aria-hidden="true" /></template>
              Tambah Jenis Kegiatan
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </ActivityTypeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <ActivityTypeFormModal
      :open="formOpen"
      :activity-type="editingActivityType"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingActivityType)"
      title="Hapus Jenis Kegiatan?"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
