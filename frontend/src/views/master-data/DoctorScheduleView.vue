<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import DoctorScheduleFilters from '@/components/master-data/doctor-schedules/DoctorScheduleFilters.vue'
import DoctorScheduleFormModal from '@/components/master-data/doctor-schedules/DoctorScheduleFormModal.vue'
import DoctorScheduleRecapTable from '@/components/master-data/doctor-schedules/DoctorScheduleRecapTable.vue'
import DoctorScheduleTable from '@/components/master-data/doctor-schedules/DoctorScheduleTable.vue'
import {
  formatScheduleSummary,
  getSafeDoctorScheduleErrorMessage,
} from '@/components/master-data/doctor-schedules/doctor-schedule.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useDoctorSchedules } from '@/composables/useDoctorSchedules'

const {
  items,
  meta,
  filters,
  loading,
  loadError,
  saving,
  recapItems,
  recapLoading,
  recapError,
  fetchSchedules,
  fetchRecap,
  refreshAll,
  setPage,
  setFilters,
  create,
  update,
  remove,
} = useDoctorSchedules()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingSchedule = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(schedule = null) {
  editingSchedule.value = schedule
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingSchedule.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingSchedule.value)
  const fallback = isEdit
    ? 'Jadwal dokter gagal diperbarui.'
    : 'Jadwal dokter gagal ditambahkan.'
  const result = isEdit
    ? await update(editingSchedule.value.id, payload)
    : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeDoctorScheduleErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(
    isEdit ? 'Jadwal dokter berhasil diperbarui.' : 'Jadwal dokter berhasil ditambahkan.',
  )
  if (!result.recapOk) {
    toast.error('Data rekap jadwal gagal dimuat ulang. Silakan klik Coba Lagi pada rekap.')
  }
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingSchedule = ref(null)

const deleteMessage = computed(() => {
  if (!deletingSchedule.value) return ''
  return `Apakah Anda yakin ingin menghapus jadwal “${formatScheduleSummary(deletingSchedule.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingSchedule.value = null
}

async function handleDelete() {
  if (saving.value || !deletingSchedule.value) return
  const result = await remove(deletingSchedule.value.id)
  deletingSchedule.value = null

  if (!result.ok) {
    toast.error(getSafeDoctorScheduleErrorMessage(result.error, 'Jadwal dokter gagal dihapus.'))
    return
  }

  toast.success('Jadwal dokter berhasil dihapus.')
  if (!result.recapOk) {
    toast.error('Data rekap jadwal gagal dimuat ulang. Silakan klik Coba Lagi pada rekap.')
  }
}

onMounted(refreshAll)
</script>

<template>
  <div class="space-y-6">
    <BasePageHeader
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Dokter', 'Jadwal Dokter']"
      title="Data Jadwal Dokter"
      description="Manajemen jadwal operasional dokter berdasarkan poliklinik."
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <Plus class="size-4" aria-hidden="true" />
          Tambah Jadwal Dokter
        </BaseButton>
      </template>
    </BasePageHeader>

    <DoctorScheduleFilters
      :search="filters.search"
      :loading="loading || recapLoading"
      @update:search="setFilters({ search: $event })"
      @refresh="refreshAll"
    />

    <DoctorScheduleTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingSchedule = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat jadwal dokter"
          :description="loadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchSchedules">Muat Ulang</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Jadwal dokter tidak ditemukan"
          :description="`Tidak ada jadwal yang cocok dengan pencarian “${filters.search}”.`"
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
          title="Belum ada data jadwal dokter"
          description="Tambahkan jadwal praktik dokter pertama untuk mulai mengatur operasional poliklinik."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <Plus class="size-4" aria-hidden="true" />
              Tambah Jadwal Dokter
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </DoctorScheduleTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <DoctorScheduleRecapTable
      :schedules="recapItems"
      :loading="recapLoading"
      :error="recapError"
      @retry="fetchRecap"
    />

    <DoctorScheduleFormModal
      :open="formOpen"
      :schedule="editingSchedule"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingSchedule)"
      title="Hapus Jadwal Dokter"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
