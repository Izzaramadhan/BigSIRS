<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import LetterTypeFormModal from '@/components/master-data/LetterTypeFormModal.vue'
import LetterTypeFilters from '@/components/master-data/letter-types/LetterTypeFilters.vue'
import LetterTypeTable from '@/components/master-data/letter-types/LetterTypeTable.vue'
import {
  formatLetterTypeSummary,
  getSafeLetterTypeErrorMessage,
  resolveLegacyResourceUrl,
} from '@/components/master-data/letter-types/letter-type.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useLetterTypes } from '@/composables/useLetterTypes'

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
} = useLetterTypes()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingLetterType = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(letterType = null) {
  editingLetterType.value = letterType
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingLetterType.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingLetterType.value?.id)
  const fallback = isEdit ? 'Data surat gagal diperbarui.' : 'Data surat gagal ditambahkan.'
  const result = isEdit
    ? await update(editingLetterType.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeLetterTypeErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit ? 'Data surat berhasil diperbarui.' : 'Data surat berhasil ditambahkan.',
  )
}

// ── Preview Format Lama (terproteksi skema aman) ───────────────────────
function handlePreviewLegacy(letterType) {
  const safeUrl = resolveLegacyResourceUrl(
    letterType?.legacy_resource,
    import.meta.env.VITE_LEGACY_BASE_URL,
  )
  if (!safeUrl) return
  window.open(safeUrl, '_blank', 'noopener,noreferrer')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingLetterType = ref(null)

const deleteMessage = computed(() => {
  if (!deletingLetterType.value) return ''
  return `Apakah Anda yakin ingin menghapus surat “${formatLetterTypeSummary(deletingLetterType.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingLetterType.value = null
}

async function handleDelete() {
  if (saving.value || !deletingLetterType.value) return
  const result = await remove(deletingLetterType.value.id)
  deletingLetterType.value = null

  if (result.ok) {
    toast.success('Data surat berhasil dihapus.')
    return
  }

  toast.error(getSafeLetterTypeErrorMessage(result.error, 'Data surat gagal dihapus.'))
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeLetterTypeErrorMessage({ message: loadError.value }, 'Data surat gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Surat-Surat"
      description="Kelola data dasar Surat-Surat."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Surat-Surat']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Surat
        </BaseButton>
      </template>
    </BasePageHeader>

    <LetterTypeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <LetterTypeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @preview-legacy="handlePreviewLegacy"
      @edit="openForm($event)"
      @delete="deletingLetterType = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data surat-surat"
          :description="safeLoadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Coba Lagi</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Data surat tidak ditemukan"
          :description="`Tidak ada surat yang cocok dengan kata kunci “${filters.search}”.`"
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
          title="Belum ada data surat"
          description="Tambahkan data jenis surat pertama untuk melengkapi daftar master surat."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <template #icon><Plus class="size-4" aria-hidden="true" /></template>
              Tambah Surat
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </LetterTypeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <LetterTypeFormModal
      :open="formOpen"
      :letter-type="editingLetterType"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingLetterType)"
      title="Hapus Surat?"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
