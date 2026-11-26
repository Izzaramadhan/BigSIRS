<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import InsuranceTypeFilters from '@/components/master-data/insurance-types/InsuranceTypeFilters.vue'
import InsuranceTypeFormModal from '@/components/master-data/insurance-types/InsuranceTypeFormModal.vue'
import InsuranceTypeTable from '@/components/master-data/insurance-types/InsuranceTypeTable.vue'
import {
  formatInsuranceSummary,
  getSafeInsuranceErrorMessage,
} from '@/components/master-data/insurance-types/insurance-type.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useGuarantors } from '@/composables/useGuarantors'

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
} = useGuarantors()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingInsuranceType = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(insuranceType = null) {
  editingInsuranceType.value = insuranceType
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingInsuranceType.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingInsuranceType.value?.id)
  const fallback = isEdit
    ? 'Data jenis asuransi gagal diperbarui.'
    : 'Data jenis asuransi gagal ditambahkan.'
  const result = isEdit
    ? await update(editingInsuranceType.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeInsuranceErrorMessage(result.error, fallback)
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
      ? 'Data jenis asuransi berhasil diperbarui.'
      : 'Data jenis asuransi berhasil ditambahkan.',
  )
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingInsuranceType = ref(null)

const deleteMessage = computed(() => {
  if (!deletingInsuranceType.value) return ''
  return `Apakah Anda yakin ingin menghapus jenis asuransi “${formatInsuranceSummary(deletingInsuranceType.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingInsuranceType.value = null
}

async function handleDelete() {
  if (saving.value || !deletingInsuranceType.value) return
  const result = await remove(deletingInsuranceType.value.id)
  deletingInsuranceType.value = null

  if (result.ok) {
    toast.success('Data jenis asuransi berhasil dihapus.')
    return
  }

  let fallback = 'Data jenis asuransi gagal dihapus.'
  if (result.error.status === 409) {
    fallback = 'Data jenis asuransi tidak dapat dihapus karena masih digunakan.'
  }
  toast.error(getSafeInsuranceErrorMessage(result.error, fallback))
}

const safeLoadError = computed(() =>
  loadError.value
    ? getSafeInsuranceErrorMessage(
        { message: loadError.value },
        'Data jenis asuransi gagal dimuat.',
      )
    : '',
)

onMounted(fetchItems)
</script>

<template>
  <div class="flex flex-col gap-6">
    <BasePageHeader
      title="Jenis Asuransi"
      description="Kelola data dasar jenis asuransi dan penjamin."
      :breadcrumbs="['Dashboard', 'Data Dasar', 'Jenis Asuransi']"
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <template #icon><Plus class="size-4" aria-hidden="true" /></template>
          Tambah Jenis Asuransi
        </BaseButton>
      </template>
    </BasePageHeader>

    <InsuranceTypeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <InsuranceTypeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingInsuranceType = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data jenis asuransi"
          :description="safeLoadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Coba Lagi</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Jenis asuransi tidak ditemukan"
          :description="`Tidak ada jenis asuransi yang cocok dengan kata kunci “${filters.search}”.`"
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
          title="Belum ada data jenis asuransi"
          description="Tambahkan jenis asuransi pertama untuk melengkapi data penjamin pasien."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <template #icon><Plus class="size-4" aria-hidden="true" /></template>
              Tambah Jenis Asuransi
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </InsuranceTypeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <InsuranceTypeFormModal
      :open="formOpen"
      :insurance-type="editingInsuranceType"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingInsuranceType)"
      title="Hapus Jenis Asuransi?"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
