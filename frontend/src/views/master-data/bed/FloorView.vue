<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import FloorFilters from '@/components/master-data/floors/FloorFilters.vue'
import FloorFormModal from '@/components/master-data/floors/FloorFormModal.vue'
import FloorTable from '@/components/master-data/floors/FloorTable.vue'
import { getSafeFloorErrorMessage } from '@/components/master-data/floors/floor.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useFloors } from '@/composables/useFloors'

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
} = useFloors()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingFloor = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(floor = null) {
  editingFloor.value = floor
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingFloor.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingFloor.value)
  const fallback = isEdit ? 'Data lantai gagal diperbarui.' : 'Data lantai gagal ditambahkan.'
  const result = isEdit ? await update(editingFloor.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeFloorErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data lantai berhasil diperbarui.' : 'Data lantai berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingFloor = ref(null)

const deleteMessage = computed(() =>
  deletingFloor.value
    ? `Apakah Anda yakin ingin menghapus lantai “${deletingFloor.value.name}” dari “${deletingFloor.value.building?.name?.trim() || 'Gedung'}”?`
    : '',
)

async function handleDelete() {
  if (saving.value || !deletingFloor.value) return
  const result = await remove(deletingFloor.value.id)
  deletingFloor.value = null

  if (result.ok) {
    toast.success('Data lantai berhasil dihapus.')
  } else {
    toast.error(getSafeFloorErrorMessage(result.error, 'Data lantai gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeFloorErrorMessage({ message: loadError.value }, 'Data lantai gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Lantai"
      description="Kelola data lantai berdasarkan gedung untuk mendukung pengaturan ruangan dan tempat tidur rumah sakit."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Data Lantai']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Lantai
        </BaseButton>
      </template>
    </BasePageHeader>

    <FloorFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data lantai gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <FloorTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingFloor = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Lantai tidak ditemukan"
            :description="`Tidak ada lantai yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data lantai"
            description="Tambahkan lantai pertama untuk mulai mengelola data lantai."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Lantai</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </FloorTable>

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

    <FloorFormModal
      :open="formOpen"
      :floor="editingFloor"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingFloor)"
      title="Hapus Lantai"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="deletingFloor = null"
      @confirm="handleDelete"
    />
  </div>
</template>
