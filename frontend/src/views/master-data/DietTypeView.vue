<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import DietTypeFilters from '@/components/master-data/nutrition-care/DietTypeFilters.vue'
import DietTypeFormModal from '@/components/master-data/nutrition-care/DietTypeFormModal.vue'
import DietTypeTable from '@/components/master-data/nutrition-care/DietTypeTable.vue'
import {
  formatDietTypeSummary,
  getSafeDietTypeErrorMessage,
} from '@/components/master-data/nutrition-care/diet-type.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useDietTypes } from '@/composables/useDietTypes'

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
} = useDietTypes()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingDietType = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(dietType = null) {
  editingDietType.value = dietType
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingDietType.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingDietType.value?.id)
  const fallback = isEdit
    ? 'Data asuhan gizi gagal diperbarui.'
    : 'Data asuhan gizi gagal ditambahkan.'
  const result = isEdit
    ? await update(editingDietType.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeDietTypeErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit
      ? 'Data asuhan gizi berhasil diperbarui.'
      : 'Data asuhan gizi berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingDietType = ref(null)

const deleteMessage = computed(() => {
  if (!deletingDietType.value) return ''
  return `Apakah Anda yakin ingin menghapus asuhan gizi “${formatDietTypeSummary(deletingDietType.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingDietType.value = null
}

async function handleDelete() {
  if (saving.value || !deletingDietType.value) return
  const result = await remove(deletingDietType.value.id)
  deletingDietType.value = null

  if (result.ok) {
    toast.success('Data asuhan gizi berhasil dihapus.')
    return
  }

  toast.error(getSafeDietTypeErrorMessage(result.error, 'Data asuhan gizi gagal dihapus.'))
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeDietTypeErrorMessage({ message: loadError.value }, 'Data asuhan gizi gagal dimuat.')
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Asuhan Gizi"
      description="Kelola data dasar Asuhan Gizi."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Asuhan Gizi']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Asuhan Gizi
        </BaseButton>
      </template>
    </BasePageHeader>

    <DietTypeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <DietTypeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingDietType = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data asuhan gizi"
          :description="safeLoadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Coba Lagi</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Asuhan gizi tidak ditemukan"
          :description="`Tidak ada asuhan gizi yang cocok dengan kata kunci “${filters.search}”.`"
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
          title="Belum ada data asuhan gizi"
          description="Tambahkan data asuhan gizi pertama untuk melengkapi referensi layanan gizi."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <template #icon><Plus class="size-4" aria-hidden="true" /></template>
              Tambah Asuhan Gizi
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </DietTypeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <DietTypeFormModal
      :open="formOpen"
      :diet-type="editingDietType"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingDietType)"
      title="Hapus Asuhan Gizi?"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
