<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import RoomClassFilters from '@/components/master-data/room-classes/RoomClassFilters.vue'
import RoomClassFormModal from '@/components/master-data/room-classes/RoomClassFormModal.vue'
import RoomClassTable from '@/components/master-data/room-classes/RoomClassTable.vue'
import { getSafeRoomClassErrorMessage } from '@/components/master-data/room-classes/room-class.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useRoomClasses } from '@/composables/useRoomClasses'

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
} = useRoomClasses()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingRoomClass = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(roomClass = null) {
  editingRoomClass.value = roomClass
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingRoomClass.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingRoomClass.value)
  const fallback = isEdit ? 'Data kelas gagal diperbarui.' : 'Data kelas gagal ditambahkan.'
  const result = isEdit ? await update(editingRoomClass.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeRoomClassErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data kelas berhasil diperbarui.' : 'Data kelas berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingRoomClass = ref(null)

const deleteMessage = computed(() => {
  if (!deletingRoomClass.value) return ''
  return `Apakah Anda yakin ingin menghapus kelas “${deletingRoomClass.value.name}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingRoomClass.value = null
}

async function handleDelete() {
  if (saving.value || !deletingRoomClass.value) return
  const result = await remove(deletingRoomClass.value.id)
  deletingRoomClass.value = null

  if (result.ok) {
    toast.success('Data kelas berhasil dihapus.')
  } else {
    toast.error(getSafeRoomClassErrorMessage(result.error, 'Data kelas gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeRoomClassErrorMessage({ message: loadError.value }, 'Data kelas gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Kelas"
      description="Kelola data kelas perawatan, tarif harian, serta kode integrasi BPJS Aplicare dan SIRS."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Data Kelas']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Kelas
        </BaseButton>
      </template>
    </BasePageHeader>

    <RoomClassFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data kelas gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <RoomClassTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingRoomClass = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Kelas tidak ditemukan"
            :description="`Tidak ada kelas yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data kelas"
            description="Tambahkan kelas pertama untuk mulai mengelola data kelas."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Kelas</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </RoomClassTable>

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

    <RoomClassFormModal
      :open="formOpen"
      :room-class="editingRoomClass"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingRoomClass)"
      title="Hapus Kelas"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
