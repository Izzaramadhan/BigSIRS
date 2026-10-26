<script setup>
import { ref, reactive, watch, computed } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import AddRoomClassDialog from './AddRoomClassDialog.vue'
import { wardsService } from '@/services/master-data/wards.service'
import { roomClassesService } from '@/services/master-data/roomClasses.service'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  room: {
    type: Object,
    default: null,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'submit', 'toast'])

const form = reactive({
  ward_id: '',
  code: '',
  name: '',
  room_class_id: '',
  description: '',
})

const clientErrors = reactive({})

const wardOptions = ref([])
const loadingWards = ref(false)
const wardLoadError = ref(null)

const roomClassOptions = ref([])
const loadingRoomClasses = ref(false)
const roomClassLoadError = ref(null)

const addRoomClassDialog = reactive({
  isOpen: false,
  initialName: '',
  isSubmitting: false,
  errorMessage: '',
})

const isSubmitDisabled = computed(() => {
  return loadingWards.value || wardOptions.value.length === 0
})

const loadWards = async () => {
  loadingWards.value = true
  wardLoadError.value = null
  try {
    const response = await wardsService.getWards({
      per_page: -1,
      sort: 'name',
      direction: 'asc',
    })
    const dataList = response.data || []
    const items = Array.isArray(dataList) ? dataList : dataList.data || []
    wardOptions.value = items.map((w) => {
      let displayName = w.ward_display_name || w.display_name
      if (!displayName) {
        const floor = w.floor
        if (floor) {
          if (floor.building?.name) {
            displayName = `${w.name} (${floor.name} - ${floor.building.name})`
          } else {
            displayName = `${w.name} (${floor.name})`
          }
        } else {
          displayName = w.name
        }
      }
      return {
        id: w.id,
        name: displayName,
        label: displayName,
        value: w.id,
      }
    })
  } catch (err) {
    wardLoadError.value = 'Gagal memuat data bangsal. Silakan coba kembali.'
    wardOptions.value = []
  } finally {
    loadingWards.value = false
  }
}

const loadRoomClasses = async (searchQuery = '') => {
  loadingRoomClasses.value = true
  roomClassLoadError.value = null
  try {
    const response = await roomClassesService.getRoomClassOptions({ search: searchQuery })
    const items = response.data || []
    roomClassOptions.value = items.map((c) => ({
      id: c.id,
      name: c.name,
      code: c.code,
      label: c.name,
      value: c.id,
    }))

    // Preserve selected class if not present in options list
    const currentClassId = form.room_class_id
    if (currentClassId && !roomClassOptions.value.some((c) => String(c.id) === String(currentClassId))) {
      const currentClassObj = props.room?.room_class || props.room?.class
      if (currentClassObj && String(currentClassObj.id) === String(currentClassId)) {
        roomClassOptions.value.unshift({
          id: currentClassObj.id,
          name: currentClassObj.name,
          code: currentClassObj.code,
          label: currentClassObj.name,
          value: currentClassObj.id,
        })
      }
    }
  } catch (err) {
    roomClassLoadError.value = 'Gagal memuat data kelas. Silakan coba kembali.'
    roomClassOptions.value = []
  } finally {
    loadingRoomClasses.value = false
  }
}

const handleSearchRoomClass = async (query) => {
  await loadRoomClasses(query)
}

const handleCreateRoomClass = (name) => {
  addRoomClassDialog.initialName = name
  addRoomClassDialog.errorMessage = ''
  addRoomClassDialog.isOpen = true
}

const handleCloseAddRoomClass = () => {
  if (addRoomClassDialog.isSubmitting) return
  addRoomClassDialog.isOpen = false
  addRoomClassDialog.errorMessage = ''
}

const handleSubmitAddRoomClass = async (name) => {
  addRoomClassDialog.isSubmitting = true
  addRoomClassDialog.errorMessage = ''
  try {
    const res = await roomClassesService.createRoomClass({ name })
    const newClass = res?.data ?? res

    if (newClass?.id) {
      const exists = roomClassOptions.value.some((c) => String(c.id) === String(newClass.id))
      if (!exists) {
        roomClassOptions.value.unshift({
          id: newClass.id,
          name: newClass.name,
          code: newClass.code,
          label: newClass.name,
          value: newClass.id,
        })
      }
      form.room_class_id = newClass.id
      clearFieldError('room_class_id')
      addRoomClassDialog.isOpen = false

      emit('toast', {
        type: 'success',
        title: 'Berhasil',
        message: 'Data kelas berhasil ditambahkan.',
      })
    }
  } catch (err) {
    const msg =
      err.response?.data?.errors?.name?.[0] ||
      err.response?.data?.message ||
      'Gagal menambahkan data kelas.'
    addRoomClassDialog.errorMessage = msg
  } finally {
    addRoomClassDialog.isSubmitting = false
  }
}

const populateForm = (data) => {
  form.ward_id = data.ward_id || data.ward?.id || ''
  form.code = data.code || ''
  form.name = data.name || ''
  const classVal = data.room_class_id || data.class_id || data.room_class?.id || data.class?.id || ''
  form.room_class_id = classVal ? Number(classVal) : ''
  form.description = data.description || ''

  // Ensure room class is in options
  const classObj = data.room_class || data.class
  if (classObj && form.room_class_id) {
    const exists = roomClassOptions.value.some((c) => String(c.id) === String(form.room_class_id))
    if (!exists) {
      roomClassOptions.value.unshift({
        id: classObj.id,
        name: classObj.name,
        code: classObj.code,
        label: classObj.name,
        value: classObj.id,
      })
    }
  }
}

const resetForm = () => {
  form.ward_id = ''
  form.code = ''
  form.name = ''
  form.room_class_id = ''
  form.description = ''
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k])
  addRoomClassDialog.isOpen = false
  addRoomClassDialog.errorMessage = ''
}

watch(
  () => props.isOpen,
  async (isOpen) => {
    if (!isOpen) {
      resetForm()
      return
    }

    await Promise.all([loadWards(), loadRoomClasses()])

    if (props.room) {
      populateForm(props.room)
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const clearFieldError = (field) => {
  if (clientErrors[field]) {
    delete clientErrors[field]
  }
}

const fieldError = (field) => {
  if (clientErrors[field]) {
    return clientErrors[field]
  }
  return props.errors?.[field]?.[0] || null
}

const validateClient = () => {
  Object.keys(clientErrors).forEach((k) => delete clientErrors[k])
  let isValid = true

  if (!form.ward_id) {
    clientErrors.ward_id = 'Bangsal wajib dipilih.'
    isValid = false
  }

  if (!form.code || !form.code.trim()) {
    clientErrors.code = 'Kode kamar wajib diisi.'
    isValid = false
  }

  if (!form.name || !form.name.trim()) {
    clientErrors.name = 'Nama kamar wajib diisi.'
    isValid = false
  }

  if (!form.room_class_id) {
    clientErrors.room_class_id = 'Kelas wajib dipilih.'
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
  if (isSubmitDisabled.value) return
  if (!validateClient()) return

  const payload = {
    ward_id: form.ward_id ? Number(form.ward_id) : '',
    code: form.code.trim(),
    name: form.name.trim(),
    room_class_id: form.room_class_id ? Number(form.room_class_id) : '',
    class_id: form.room_class_id ? Number(form.room_class_id) : '',
    description: form.description ? form.description.trim() : null,
  }
  emit('submit', payload)
}
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="room ? 'Edit Kamar' : 'Tambah Kamar'"
    :submit-text="room ? 'Simpan Perubahan' : 'Simpan'"
    :is-submitting="isSubmitting"
    :is-submit-disabled="isSubmitDisabled"
    size="md"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <!-- General error alert -->
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <!-- Notice when no wards available -->
      <div v-if="!loadingWards && wardOptions.length === 0" class="alert-warning">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <div>
          <strong>Data Bangsal belum tersedia.</strong>
          <p class="alert-desc">
            Silakan tambahkan data pada menu <em>Data Gedung</em>, <em>Data Lantai</em>, dan <em>Data Bangsal</em> terlebih dahulu sebelum menambahkan data kamar.
          </p>
        </div>
      </div>

      <!-- Field 1: Bangsal -->
      <div class="form-group">
        <label for="room-ward-id" class="form-label required">Bangsal</label>
        <SearchableSelect
          id="room-ward-id"
          v-model="form.ward_id"
          :options="wardOptions"
          :loading="loadingWards"
          :disabled="loadingWards || wardOptions.length === 0"
          placeholder="Pilih bangsal"
          search-placeholder="Cari bangsal, lantai, atau gedung..."
          empty-text="Tidak ada data bangsal ditemukan."
          :is-invalid="!!fieldError('ward_id')"
          @update:model-value="clearFieldError('ward_id')"
        />
        <div v-if="fieldError('ward_id')" class="invalid-feedback">
          {{ fieldError('ward_id') }}
        </div>
      </div>

      <!-- Field 2: Kode -->
      <div class="form-group">
        <label for="room-code" class="form-label required">Kode</label>
        <input autocomplete="off"
          id="room-code"
          v-model="form.code"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('code') }"
          placeholder="Masukkan kode kamar"
          required
          @input="clearFieldError('code')"
        />
        <div v-if="fieldError('code')" class="invalid-feedback">
          {{ fieldError('code') }}
        </div>
      </div>

      <!-- Field 3: Nama Kamar -->
      <div class="form-group">
        <label for="room-name" class="form-label required">Nama Kamar</label>
        <input autocomplete="off"
          id="room-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama kamar"
          required
          @input="clearFieldError('name')"
        />
        <div v-if="fieldError('name')" class="invalid-feedback">
          {{ fieldError('name') }}
        </div>
      </div>

      <!-- Field 4: Kelas -->
      <div class="form-group">
        <label for="room-class-id" class="form-label required">Kelas</label>
        <SearchableSelect
          id="room-class-id"
          v-model="form.room_class_id"
          :options="roomClassOptions"
          placeholder="Pilih atau cari kelas"
          search-placeholder="Cari kelas..."
          empty-text="Data kelas tidak ditemukan"
          :loading="loadingRoomClasses"
          :error="roomClassLoadError"
          :is-invalid="!!fieldError('room_class_id')"
          :disabled="isSubmitting"
          server-search
          clearable
          creatable
          create-prompt='Tambah kelas “[name]”'
          @search="handleSearchRoomClass"
          @create="handleCreateRoomClass"
          @update:model-value="clearFieldError('room_class_id')"
        />
        <div v-if="fieldError('room_class_id')" class="invalid-feedback">
          {{ fieldError('room_class_id') }}
        </div>
      </div>

      <!-- Field 5: Deskripsi -->
      <div class="form-group">
        <label for="room-description" class="form-label">Deskripsi</label>
        <textarea
          id="room-description"
          v-model="form.description"
          class="form-control form-textarea"
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Masukkan deskripsi kamar"
          rows="3"
          @input="clearFieldError('description')"
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">
          {{ fieldError('description') }}
        </div>
      </div>
    </div>
  </MasterDataFormModal>

  <!-- Add Room Class Inline Modal Dialog -->
  <AddRoomClassDialog
    :is-open="addRoomClassDialog.isOpen"
    :initial-name="addRoomClassDialog.initialName"
    :is-submitting="addRoomClassDialog.isSubmitting"
    :error-message="addRoomClassDialog.errorMessage"
    @close="handleCloseAddRoomClass"
    @submit="handleSubmitAddRoomClass"
  />
</template>

<style scoped>
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.alert-error {
  background-color: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.alert-warning {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  border-left: 4px solid #f59e0b;
  color: #92400e;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
}

.alert-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.alert-desc {
  margin: 0.25rem 0 0 0;
  font-size: 0.825rem;
  color: #78350f;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.required::after {
  content: ' *';
  color: #ef4444;
}

.form-control {
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: #fff;
  width: 100%;
}

.form-control:disabled {
  background-color: var(--color-page-bg, #f8fafc);
  color: var(--color-text-secondary, #94a3b8);
  cursor: not-allowed;
  border-color: var(--color-border-soft, #e2e8f0);
}

.form-control:focus:not(:disabled) {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}
</style>
