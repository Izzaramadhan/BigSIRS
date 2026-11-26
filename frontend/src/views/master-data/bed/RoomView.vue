<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import RoomFilters from '@/components/master-data/rooms/RoomFilters.vue'
import RoomFormModal from '@/components/master-data/rooms/RoomFormModal.vue'
import RoomTable from '@/components/master-data/rooms/RoomTable.vue'
import {
  EMPTY,
  formatRoomWard,
  getSafeRoomErrorMessage,
} from '@/components/master-data/rooms/room.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useRooms } from '@/composables/useRooms'

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
} = useRooms()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingRoom = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(room = null) {
  editingRoom.value = room
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingRoom.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingRoom.value)
  const fallback = isEdit ? 'Data kamar gagal diperbarui.' : 'Data kamar gagal ditambahkan.'
  const result = isEdit ? await update(editingRoom.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeRoomErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data kamar berhasil diperbarui.' : 'Data kamar berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingRoom = ref(null)

const deleteMessage = computed(() => {
  if (!deletingRoom.value) return ''
  const formattedWard = formatRoomWard(deletingRoom.value)
  const wardLabel = formattedWard === EMPTY ? 'Bangsal' : formattedWard
  return `Apakah Anda yakin ingin menghapus kamar “${deletingRoom.value.name}” dari “${wardLabel}”?`
})

async function handleDelete() {
  if (saving.value || !deletingRoom.value) return
  const result = await remove(deletingRoom.value.id)
  deletingRoom.value = null

  if (result.ok) {
    toast.success('Data kamar berhasil dihapus.')
  } else {
    toast.error(getSafeRoomErrorMessage(result.error, 'Data kamar gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeRoomErrorMessage({ message: loadError.value }, 'Data kamar gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Kamar"
      description="Kelola data kamar berdasarkan bangsal, lantai, gedung, dan kelas perawatan rumah sakit."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Data Kamar']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Kamar
        </BaseButton>
      </template>
    </BasePageHeader>

    <RoomFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data kamar gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <RoomTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingRoom = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Kamar tidak ditemukan"
            :description="`Tidak ada kamar yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data kamar"
            description="Tambahkan kamar pertama untuk mulai mengelola data kamar."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Kamar</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </RoomTable>

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

    <RoomFormModal
      :open="formOpen"
      :room="editingRoom"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingRoom)"
      title="Hapus Kamar"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="deletingRoom = null"
      @confirm="handleDelete"
    />
  </div>
</template>
