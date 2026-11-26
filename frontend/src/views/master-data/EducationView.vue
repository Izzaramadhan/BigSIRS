<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import EducationFilters from '@/components/master-data/educations/EducationFilters.vue'
import EducationFormModal from '@/components/master-data/educations/EducationFormModal.vue'
import EducationTable from '@/components/master-data/educations/EducationTable.vue'
import { getSafeEducationErrorMessage } from '@/components/master-data/educations/education.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useEducations } from '@/composables/useEducations'

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
} = useEducations()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingEducation = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(education = null) {
  editingEducation.value = education
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingEducation.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingEducation.value)
  const fallback = isEdit
    ? 'Data pendidikan gagal diperbarui.'
    : 'Data pendidikan gagal ditambahkan.'
  const result = isEdit
    ? await update(editingEducation.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeEducationErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit ? 'Data pendidikan berhasil diperbarui.' : 'Data pendidikan berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingEducation = ref(null)

const deleteMessage = computed(() => {
  if (!deletingEducation.value) return ''
  return `Apakah Anda yakin ingin menghapus pendidikan “${deletingEducation.value.name}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingEducation.value = null
}

async function handleDelete() {
  if (saving.value || !deletingEducation.value) return
  const result = await remove(deletingEducation.value.id)
  deletingEducation.value = null

  if (result.ok) {
    toast.success('Data pendidikan berhasil dihapus.')
  } else {
    toast.error(getSafeEducationErrorMessage(result.error, 'Data pendidikan gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeEducationErrorMessage({ message: loadError.value }, 'Data pendidikan gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Pendidikan"
      description="Kelola data dasar tingkat pendidikan."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Pendidikan']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Pendidikan
        </BaseButton>
      </template>
    </BasePageHeader>

    <EducationFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data pendidikan gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <EducationTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingEducation = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Pendidikan tidak ditemukan"
            :description="`Tidak ada tingkat pendidikan yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data pendidikan"
            description="Tambahkan tingkat pendidikan pertama untuk mulai mengelola data."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Pendidikan</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </EducationTable>

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

    <EducationFormModal
      :open="formOpen"
      :education="editingEducation"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingEducation)"
      title="Hapus Pendidikan"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
