<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import DoctorDetailModal from '@/components/master-data/doctors/DoctorDetailModal.vue'
import DoctorFilters from '@/components/master-data/doctors/DoctorFilters.vue'
import DoctorFormModal from '@/components/master-data/doctors/DoctorFormModal.vue'
import DoctorTable from '@/components/master-data/doctors/DoctorTable.vue'
import {
  formatDoctorName,
  getSafeDoctorErrorMessage,
} from '@/components/master-data/doctors/doctor.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useDoctors } from '@/composables/useDoctors'
import doctorService from '@/services/master-data/doctors.service'
import { normalizeApiError } from '@/utils/http-error'

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
} = useDoctors()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingDoctor = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(doctor = null) {
  editingDoctor.value = doctor
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingDoctor.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingDoctor.value)
  const fallback = isEdit ? 'Data dokter gagal diperbarui.' : 'Data dokter gagal ditambahkan.'
  const result = isEdit ? await update(editingDoctor.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeDoctorErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data dokter berhasil diperbarui.' : 'Data dokter berhasil ditambahkan.')
}

// ── Detail ────────────────────────────────────────────────────────────
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const doctorDetail = ref(null)

async function openDetail(row) {
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  doctorDetail.value = null

  try {
    const detail = await doctorService.getDoctor(row.id)
    if (!detail?.id) {
      detailError.value = 'Detail dokter tidak valid.'
      return
    }
    doctorDetail.value = detail
  } catch (error) {
    const safeMessage = getSafeDoctorErrorMessage(
      normalizeApiError(error, 'Gagal memuat detail dokter.'),
      'Gagal memuat detail dokter.',
    )
    detailError.value = safeMessage
    toast.error(safeMessage)
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
}

function openEditFromDetail() {
  if (!doctorDetail.value) return
  const target = doctorDetail.value
  detailOpen.value = false
  openForm(target)
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingDoctor = ref(null)

const deleteMessage = computed(() => {
  if (!deletingDoctor.value) return ''
  return `Apakah Anda yakin ingin menghapus data dokter “${formatDoctorName(deletingDoctor.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingDoctor.value = null
}

async function handleDelete() {
  if (saving.value || !deletingDoctor.value) return
  const result = await remove(deletingDoctor.value.id)
  deletingDoctor.value = null

  if (result.ok) {
    toast.success('Data dokter berhasil dihapus.')
  } else {
    toast.error(getSafeDoctorErrorMessage(result.error, 'Data dokter gagal dihapus.'))
  }
}

onMounted(fetchItems)
</script>

<template>
  <div class="space-y-6">
    <BasePageHeader
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Dokter', 'Dokter']"
      title="Dokter"
      description="Kelola data dokter, spesialisasi, dan informasi profesional lainnya."
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <Plus class="size-4" aria-hidden="true" />
          Tambah Dokter
        </BaseButton>
      </template>
    </BasePageHeader>

    <DoctorFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <DoctorTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @view="openDetail"
      @edit="openForm($event)"
      @delete="deletingDoctor = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data dokter"
          :description="loadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Muat Ulang</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Dokter tidak ditemukan"
          :description="`Tidak ada dokter yang cocok dengan pencarian “${filters.search}”.`"
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
          title="Belum ada data dokter"
          description="Tambahkan data dokter pertama untuk mulai mengelola profil tenaga medis."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <Plus class="size-4" aria-hidden="true" />
              Tambah Dokter
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </DoctorTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <DoctorFormModal
      :open="formOpen"
      :doctor="editingDoctor"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <DoctorDetailModal
      :open="detailOpen"
      :loading="detailLoading"
      :detail="doctorDetail"
      :error-message="detailError"
      @close="closeDetail"
      @edit="openEditFromDetail"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingDoctor)"
      title="Hapus Data Dokter"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
