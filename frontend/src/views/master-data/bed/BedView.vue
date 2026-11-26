<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import BedFilters from '@/components/master-data/beds/BedFilters.vue'
import BedFormModal from '@/components/master-data/beds/BedFormModal.vue'
import BedTable from '@/components/master-data/beds/BedTable.vue'
import { formatBedRoom, getSafeBedErrorMessage } from '@/components/master-data/beds/bed.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useBeds } from '@/composables/useBeds'

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
} = useBeds()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingBed = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(bed = null) {
  editingBed.value = bed
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingBed.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingBed.value)
  const fallback = isEdit ? 'Data bed gagal diperbarui.' : 'Data bed gagal ditambahkan.'
  const result = isEdit ? await update(editingBed.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeBedErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data bed berhasil diperbarui.' : 'Data bed berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingBed = ref(null)

const deleteMessage = computed(() =>
  deletingBed.value
    ? `Apakah Anda yakin ingin menghapus “${deletingBed.value.name}” dari “${formatBedRoom(deletingBed.value)}”?`
    : '',
)

async function handleDelete() {
  if (saving.value || !deletingBed.value) return
  const result = await remove(deletingBed.value.id)
  deletingBed.value = null

  if (result.ok) {
    toast.success('Data bed berhasil dihapus.')
  } else {
    toast.error(getSafeBedErrorMessage(result.error, 'Data bed gagal dihapus.'))
  }
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeBedErrorMessage({ message: loadError.value }, 'Data bed gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Data Bed"
      description="Kelola data tempat tidur berdasarkan kamar, bangsal, dan kelas perawatan rumah sakit."
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Bed']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Bed
        </BaseButton>
      </template>
    </BasePageHeader>

    <BedFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <div v-if="safeLoadError" class="rounded-xl border border-line bg-surface">
      <BaseEmptyState
        :icon="CircleAlert"
        tone="danger"
        title="Data bed gagal dimuat"
        :description="safeLoadError"
      >
        <template #action>
          <BaseButton variant="secondary" @click="fetchItems">Coba lagi</BaseButton>
        </template>
      </BaseEmptyState>
    </div>

    <template v-else>
      <BedTable
        :items="items"
        :loading="loading"
        :offset="(meta.page - 1) * meta.perPage"
        @edit="openForm"
        @delete="deletingBed = $event"
      >
        <template #empty>
          <BaseEmptyState
            v-if="filters.search"
            :icon="SearchX"
            title="Bed tidak ditemukan"
            :description="`Tidak ada bed yang cocok dengan “${filters.search}”.`"
          />
          <BaseEmptyState
            v-else
            :icon="Inbox"
            title="Belum ada data bed"
            description="Tambahkan bed pertama untuk mulai mengelola tempat tidur."
          >
            <template #action>
              <BaseButton @click="openForm()">Tambah Bed</BaseButton>
            </template>
          </BaseEmptyState>
        </template>
      </BedTable>

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

    <BedFormModal
      :open="formOpen"
      :bed="editingBed"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingBed)"
      title="Hapus Bed"
      :message="deleteMessage"
      confirm-text="Hapus"
      :loading="saving"
      @close="deletingBed = null"
      @confirm="handleDelete"
    />
  </div>
</template>
