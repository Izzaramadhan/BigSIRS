<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import PolyclinicFilters from '@/components/master-data/polyclinics/PolyclinicFilters.vue'
import PolyclinicFormModal from '@/components/master-data/polyclinics/PolyclinicFormModal.vue'
import PolyclinicTable from '@/components/master-data/polyclinics/PolyclinicTable.vue'
import {
  formatPolyclinicSummary,
  getSafePolyclinicErrorMessage,
} from '@/components/master-data/polyclinics/polyclinic.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { usePolyclinics } from '@/composables/usePolyclinics'

const {
  items,
  meta,
  filters,
  loading,
  loadError,
  saving,
  serviceTypes,
  fetchPolyclinics,
  fetchServiceTypes,
  create,
  update,
  remove,
  setPage,
  setFilters,
  resetFilters,
} = usePolyclinics()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingPolyclinic = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(polyclinic = null) {
  editingPolyclinic.value = polyclinic
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingPolyclinic.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingPolyclinic.value)
  const fallback = isEdit
    ? 'Data Poliklinik gagal diperbarui.'
    : 'Data Poliklinik gagal ditambahkan.'
  const result = isEdit
    ? await update(editingPolyclinic.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    let safeMessage = getSafePolyclinicErrorMessage(result.error, fallback)
    if (result.error.status === 409 && !result.error.message) {
      safeMessage = 'Terdapat poliklinik turunan, periksa konfigurasi induk.'
    }
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit ? 'Data Poliklinik berhasil diperbarui.' : 'Data Poliklinik berhasil ditambahkan.',
  )
}

// ── Arsip / Hapus ─────────────────────────────────────────────────────
const archivingPolyclinic = ref(null)

const archiveMessage = computed(() => {
  if (!archivingPolyclinic.value) return ''
  return `Apakah Anda yakin ingin mengarsipkan poliklinik “${formatPolyclinicSummary(archivingPolyclinic.value)}”? Data ini tidak akan tampil pada daftar aktif.`
})

function closeArchiveDialog() {
  if (saving.value) return
  archivingPolyclinic.value = null
}

async function handleArchive() {
  if (saving.value || !archivingPolyclinic.value) return
  const result = await remove(archivingPolyclinic.value.id)
  archivingPolyclinic.value = null

  if (result.ok) {
    toast.success('Data Poliklinik berhasil diarsipkan.')
    return
  }

  let fallback = 'Data Poliklinik gagal diarsipkan. Silakan coba lagi.'
  if (result.error.status === 409) {
    fallback = 'Poliklinik tidak dapat diarsipkan karena masih memiliki relasi aktif.'
  }
  toast.error(getSafePolyclinicErrorMessage(result.error, fallback))
}

const hasActiveFilter = computed(
  () => Boolean(filters.search) || (filters.is_active !== null && filters.is_active !== undefined),
)

onMounted(() => {
  fetchPolyclinics()
  fetchServiceTypes()
})
</script>

<template>
  <div class="space-y-6">
    <BasePageHeader
      :breadcrumbs="['Dashboard', 'Master Data', 'Poliklinik']"
      title="Poliklinik"
      description="Kelola data poliklinik dan kode integrasi pelayanan."
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <Plus class="size-4" aria-hidden="true" />
          Tambah Poliklinik
        </BaseButton>
      </template>
    </BasePageHeader>

    <PolyclinicFilters
      :search="filters.search"
      :is-active="filters.is_active"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @update:is-active="setFilters({ is_active: $event })"
      @refresh="fetchPolyclinics"
    />

    <PolyclinicTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="archivingPolyclinic = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data poliklinik"
          :description="loadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchPolyclinics">Muat Ulang</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="hasActiveFilter"
          :icon="SearchX"
          title="Poliklinik tidak ditemukan"
          :description="
            filters.search
              ? `Tidak ada poliklinik yang cocok dengan pencarian “${filters.search}”.`
              : 'Tidak ada poliklinik yang cocok dengan filter status yang dipilih.'
          "
        >
          <template #action>
            <BaseButton variant="secondary" @click="resetFilters">Reset Filter</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else
          :icon="Inbox"
          title="Belum ada data poliklinik"
          description="Tambahkan data poliklinik pertama untuk mulai mengatur layanan rawat jalan dan unit terkait."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <Plus class="size-4" aria-hidden="true" />
              Tambah Poliklinik
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </PolyclinicTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <PolyclinicFormModal
      :open="formOpen"
      :polyclinic="editingPolyclinic"
      :service-types="serviceTypes"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(archivingPolyclinic)"
      title="Arsipkan Poliklinik?"
      :message="archiveMessage"
      confirm-text="Ya, Arsipkan"
      :loading="saving"
      @close="closeArchiveDialog"
      @confirm="handleArchive"
    />
  </div>
</template>
