<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import WardFilters from '@/components/master-data/wards/WardFilters.vue'
import WardFormModal from '@/components/master-data/wards/WardFormModal.vue'
import WardTable from '@/components/master-data/wards/WardTable.vue'
import {
  EMPTY,
  formatWardFloor,
  getSafeWardErrorMessage,
} from '@/components/master-data/wards/ward.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useWards } from '@/composables/useWards'

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
} = useWards()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingWard = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(ward = null) {
  editingWard.value = ward
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingWard.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingWard.value)
  const fallback = isEdit ? 'Data bangsal gagal diperbarui.' : 'Data bangsal gagal ditambahkan.'
  const result = isEdit ? await update(editingWard.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeWardErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data bangsal berhasil diperbarui.' : 'Data bangsal berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingWard = ref(null)

const deleteMessage = computed(() => {
  if (!deletingWard.value) return ''
  const formattedFloor = formatWardFloor(deletingWard.value)
  const floorLabel = formattedFloor === EMPTY ? 'Lantai' : formattedFloor
  return `Apakah Anda yakin ingin menghapus bangsal “${deletingWard.value.name}” dari “${floorLabel}”?`
})

async function handleDelete() {
  if (saving.value || !deletingWard.value) return
  const result = await remove(deletingWard.value.id)
  deletingWard.value = null

  if (result.ok) {
    toast.success('Data bangsal berhasil dihapus.')
  } else {
    toast.error(getSafeWardErrorMessage(result.error, 'Data bangsal gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeWardErrorMessage({ message: loadError.value }, 'Data bangsal gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Bangsal"
      description="Kelola data bangsal berdasarkan lantai dan gedung untuk mendukung pengaturan ruangan serta tempat tidur rumah sakit."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Data Bangsal']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Bangsal
        </BaseButton>
      </template>
    </BasePageHeader>

    <WardFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data bangsal gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <WardTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingWard = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Bangsal tidak ditemukan"
            :description="`Tidak ada bangsal yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data bangsal"
            description="Tambahkan bangsal pertama untuk mulai mengelola data bangsal."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Bangsal</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </WardTable>

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

    <WardFormModal
      :open="formOpen"
      :ward="editingWard"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingWard)"
      title="Hapus Bangsal"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="deletingWard = null"
      @confirm="handleDelete"
    />
  </div>
</template>
