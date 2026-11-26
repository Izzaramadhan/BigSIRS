<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import OccupationFilters from '@/components/master-data/occupations/OccupationFilters.vue'
import OccupationFormModal from '@/components/master-data/occupations/OccupationFormModal.vue'
import OccupationTable from '@/components/master-data/occupations/OccupationTable.vue'
import { getSafeOccupationErrorMessage } from '@/components/master-data/occupations/occupation.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useOccupations } from '@/composables/useOccupations'

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
} = useOccupations()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingOccupation = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(occupation = null) {
  editingOccupation.value = occupation
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingOccupation.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingOccupation.value)
  const fallback = isEdit ? 'Data pekerjaan gagal diperbarui.' : 'Data pekerjaan gagal ditambahkan.'
  const result = isEdit
    ? await update(editingOccupation.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeOccupationErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit ? 'Data pekerjaan berhasil diperbarui.' : 'Data pekerjaan berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingOccupation = ref(null)

const deleteMessage = computed(() => {
  if (!deletingOccupation.value) return ''
  return `Apakah Anda yakin ingin menghapus pekerjaan “${deletingOccupation.value.name}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingOccupation.value = null
}

async function handleDelete() {
  if (saving.value || !deletingOccupation.value) return
  const result = await remove(deletingOccupation.value.id)
  deletingOccupation.value = null

  if (result.ok) {
    toast.success('Data pekerjaan berhasil dihapus.')
  } else {
    toast.error(getSafeOccupationErrorMessage(result.error, 'Data pekerjaan gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeOccupationErrorMessage({ message: loadError.value }, 'Data pekerjaan gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Pekerjaan"
      description="Kelola data dasar pekerjaan."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Pekerjaan']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Pekerjaan
        </BaseButton>
      </template>
    </BasePageHeader>

    <OccupationFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data pekerjaan gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <OccupationTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingOccupation = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Pekerjaan tidak ditemukan"
            :description="`Tidak ada pekerjaan yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data pekerjaan"
            description="Tambahkan pekerjaan pertama untuk mulai mengelola data."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Pekerjaan</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </OccupationTable>

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

    <OccupationFormModal
      :open="formOpen"
      :occupation="editingOccupation"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingOccupation)"
      title="Hapus Pekerjaan"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
