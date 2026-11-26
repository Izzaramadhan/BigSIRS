<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import PositionFilters from '@/components/master-data/positions/PositionFilters.vue'
import PositionFormModal from '@/components/master-data/positions/PositionFormModal.vue'
import PositionTable from '@/components/master-data/positions/PositionTable.vue'
import { getSafePositionErrorMessage } from '@/components/master-data/positions/position.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { usePositions } from '@/composables/usePositions'

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
} = usePositions()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingPosition = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(position = null) {
  editingPosition.value = position
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingPosition.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingPosition.value)
  const fallback = isEdit ? 'Data jabatan gagal diperbarui.' : 'Data jabatan gagal ditambahkan.'
  const result = isEdit ? await update(editingPosition.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafePositionErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data jabatan berhasil diperbarui.' : 'Data jabatan berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingPosition = ref(null)

const deleteMessage = computed(() => {
  if (!deletingPosition.value) return ''
  return `Apakah Anda yakin ingin menghapus jabatan “${deletingPosition.value.name}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingPosition.value = null
}

async function handleDelete() {
  if (saving.value || !deletingPosition.value) return
  const result = await remove(deletingPosition.value.id)
  deletingPosition.value = null

  if (result.ok) {
    toast.success('Data jabatan berhasil dihapus.')
  } else {
    toast.error(getSafePositionErrorMessage(result.error, 'Data jabatan gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafePositionErrorMessage({ message: loadError.value }, 'Data jabatan gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Jabatan"
      description="Kelola data dasar tingkat jabatan pegawai."
      :breadcrumbs="['Dashboard', 'Data Pegawai', 'Jabatan']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Jabatan
        </BaseButton>
      </template>
    </BasePageHeader>

    <PositionFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data jabatan gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <PositionTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingPosition = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Jabatan tidak ditemukan"
            :description="`Tidak ada jabatan yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data jabatan"
            description="Tambahkan jabatan pertama untuk mulai mengelola data."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Jabatan</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </PositionTable>

      <BasePagination
        :page="meta.page"
        :last-page="meta.lastPage"
        :total="meta.total"
        :from="meta.from"
        :to="meta.to"
        :disabled="loading"
        @change="setPage"
      />
    </template>

    <PositionFormModal
      :open="formOpen"
      :position="editingPosition"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingPosition)"
      title="Hapus Jabatan"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
