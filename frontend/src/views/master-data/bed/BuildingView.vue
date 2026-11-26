<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import BuildingFilters from '@/components/master-data/buildings/BuildingFilters.vue'
import BuildingFormModal from '@/components/master-data/buildings/BuildingFormModal.vue'
import BuildingTable from '@/components/master-data/buildings/BuildingTable.vue'
import { getSafeBuildingErrorMessage } from '@/components/master-data/buildings/building.helpers'
import { useBuildings } from '@/composables/useBuildings'
import { useToast } from '@/composables/core/useToast'

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
} = useBuildings()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingBuilding = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(building = null) {
  editingBuilding.value = building
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingBuilding.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingBuilding.value)
  const fallback = isEdit
    ? 'Data gedung gagal diperbarui.'
    : 'Data gedung gagal ditambahkan.'
  const result = isEdit ? await update(editingBuilding.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeBuildingErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data gedung berhasil diperbarui.' : 'Data gedung berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingBuilding = ref(null)

const deleteMessage = computed(() =>
  deletingBuilding.value
    ? `Apakah Anda yakin ingin menghapus gedung “${deletingBuilding.value.name}”?`
    : '',
)

async function handleDelete() {
  if (saving.value || !deletingBuilding.value) return
  const result = await remove(deletingBuilding.value.id)
  deletingBuilding.value = null

  if (result.ok) {
    toast.success('Data gedung berhasil dihapus.')
  } else {
    toast.error(getSafeBuildingErrorMessage(result.error, 'Data gedung gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeBuildingErrorMessage({ message: loadError.value }, 'Data gedung gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Gedung"
      description="Kelola data gedung yang digunakan dalam pengaturan ruangan dan tempat tidur rumah sakit."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed', 'Data Gedung']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Gedung
        </BaseButton>
      </template>
    </BasePageHeader>

    <BuildingFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data gedung gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <BuildingTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingBuilding = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Gedung tidak ditemukan"
            :description="`Tidak ada gedung yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data gedung"
            description="Tambahkan gedung pertama untuk mulai mengelola data gedung."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Gedung</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </BuildingTable>

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

    <BuildingFormModal
      :open="formOpen"
      :building="editingBuilding"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingBuilding)"
      title="Hapus Gedung"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="deletingBuilding = null"
      @confirm="handleDelete"
    />
  </div>
</template>
