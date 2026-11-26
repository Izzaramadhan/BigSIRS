<script setup>
import { computed, onMounted, ref } from 'vue'
import { CircleAlert, Inbox, Plus, SearchX } from 'lucide-vue-next'
import EmployeeFormModal from '@/components/master-data/EmployeeFormModal.vue'
import EmployeeFilters from '@/components/master-data/employees/EmployeeFilters.vue'
import EmployeeTable from '@/components/master-data/employees/EmployeeTable.vue'
import {
  getSafeEmployeeErrorMessage,
  resolveEmployeeDisplayName,
} from '@/components/master-data/employees/employee.helpers'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseConfirmDialog from '@/components/ui/BaseConfirmDialog.vue'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'
import BasePageHeader from '@/components/ui/BasePageHeader.vue'
import BasePagination from '@/components/ui/BasePagination.vue'
import { useToast } from '@/composables/core/useToast'
import { useEmployees } from '@/composables/useEmployees'

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
} = useEmployees()
const toast = useToast()

// ── Form tambah / edit ────────────────────────────────────────────────
const formOpen = ref(false)
const editingEmployee = ref(null)
const formErrors = ref({})
const formGeneralError = ref('')

function openForm(employee = null) {
  editingEmployee.value = employee
  formErrors.value = {}
  formGeneralError.value = ''
  formOpen.value = true
}

function closeForm() {
  if (saving.value) return
  formOpen.value = false
  editingEmployee.value = null
  formErrors.value = {}
  formGeneralError.value = ''
}

async function handleSubmit(payload) {
  if (saving.value) return
  formErrors.value = {}
  formGeneralError.value = ''

  const isEdit = Boolean(editingEmployee.value)
  const fallback = isEdit ? 'Data pegawai gagal diperbarui.' : 'Data pegawai gagal ditambahkan.'
  const result = isEdit ? await update(editingEmployee.value.id, payload) : await create(payload)

  if (!result.ok) {
    const safeMessage = getSafeEmployeeErrorMessage(result.error, fallback)
    formErrors.value = result.error.fieldErrors
    if (!result.error.isValidation && Object.keys(result.error.fieldErrors).length === 0) {
      formGeneralError.value = safeMessage
    }
    toast.error(safeMessage)
    return
  }

  closeForm()
  toast.success(isEdit ? 'Data pegawai berhasil diperbarui.' : 'Data pegawai berhasil ditambahkan.')
}

// ── Hapus ─────────────────────────────────────────────────────────────
const deletingEmployee = ref(null)

const deleteMessage = computed(() => {
  if (!deletingEmployee.value) return ''
  return `Apakah Anda yakin ingin menghapus data pegawai “${resolveEmployeeDisplayName(deletingEmployee.value)}”?`
})

function closeDeleteDialog() {
  if (saving.value) return
  deletingEmployee.value = null
}

async function handleDelete() {
  if (saving.value || !deletingEmployee.value) return
  const result = await remove(deletingEmployee.value.id)
  deletingEmployee.value = null

  if (result.ok) {
    toast.success('Data pegawai berhasil dihapus.')
  } else {
    toast.error(getSafeEmployeeErrorMessage(result.error, 'Data pegawai gagal dihapus.'))
  }
}

onMounted(fetchItems)
</script>

<template>
  <div class="space-y-6">
    <BasePageHeader
      :breadcrumbs="['Dashboard', 'Master Data', 'Data Pegawai', 'Pegawai']"
      title="Pegawai"
      description="Kelola data pegawai, unit kerja, jabatan, dan informasi profil lainnya."
    >
      <template #actions>
        <BaseButton @click="openForm()">
          <Plus class="size-4" aria-hidden="true" />
          Tambah Pegawai
        </BaseButton>
      </template>
    </BasePageHeader>

    <EmployeeFilters
      :search="filters.search"
      :loading="loading"
      @update:search="setFilters({ search: $event })"
      @refresh="fetchItems"
    />

    <EmployeeTable
      :items="items"
      :loading="loading"
      :offset="(meta.page - 1) * meta.perPage"
      @edit="openForm($event)"
      @delete="deletingEmployee = $event"
    >
      <template #empty>
        <BaseEmptyState
          v-if="loadError"
          :icon="CircleAlert"
          tone="danger"
          title="Gagal memuat data pegawai"
          :description="loadError"
        >
          <template #action>
            <BaseButton variant="secondary" @click="fetchItems">Muat Ulang</BaseButton>
          </template>
        </BaseEmptyState>

        <BaseEmptyState
          v-else-if="filters.search"
          :icon="SearchX"
          title="Pegawai tidak ditemukan"
          :description="`Tidak ada pegawai yang cocok dengan pencarian “${filters.search}”.`"
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
          title="Belum ada data pegawai"
          description="Tambahkan data pegawai pertama untuk mulai mengelola profil kepegawaian."
        >
          <template #action>
            <BaseButton @click="openForm()">
              <Plus class="size-4" aria-hidden="true" />
              Tambah Pegawai
            </BaseButton>
          </template>
        </BaseEmptyState>
      </template>
    </EmployeeTable>

    <BasePagination
      :page="meta.page"
      :last-page="meta.lastPage"
      :total="meta.total"
      :from="meta.from"
      :to="meta.to"
      :disabled="loading"
      @change="setPage"
    />

    <EmployeeFormModal
      :open="formOpen"
      :employee="editingEmployee"
      :saving="saving"
      :server-errors="formErrors"
      :general-error="formGeneralError"
      @close="closeForm"
      @submit="handleSubmit"
    />

    <BaseConfirmDialog
      :open="Boolean(deletingEmployee)"
      title="Hapus Data Pegawai"
      :message="deleteMessage"
      confirm-text="Ya, Hapus"
      :loading="saving"
      @close="closeDeleteDialog"
      @confirm="handleDelete"
    />
  </div>
</template>
