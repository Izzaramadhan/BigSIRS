<script setup>
import { ref, reactive, watch, computed } from 'vue'
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import { wardsService } from '@/services/master-data/wards.service'

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

const emit = defineEmits(['close', 'submit'])

const form = reactive({
  ward_id: '',
  code: '',
  name: '',
  description: '',
})

const wardOptions = ref([])
const loadingWards = ref(false)
const wardLoadError = ref(null)

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

const populateForm = (data) => {
  form.ward_id = data.ward_id || data.ward?.id || ''
  form.code = data.code || ''
  form.name = data.name || ''
  form.description = data.description || ''
}

const resetForm = () => {
  form.ward_id = ''
  form.code = ''
  form.name = ''
  form.description = ''
}

watch(
  () => props.isOpen,
  async (isOpen) => {
    if (!isOpen) {
      resetForm()
      return
    }

    await loadWards()

    if (props.room) {
      populateForm(props.room)
    } else {
      resetForm()
    }
  },
  { immediate: true },
)

const handleSubmit = () => {
  if (isSubmitDisabled.value) return

  const payload = {
    ward_id: form.ward_id ? Number(form.ward_id) : '',
    code: form.code.trim(),
    name: form.name.trim(),
    class_id: null,
    description: form.description ? form.description.trim() : null,
  }
  emit('submit', payload)
}

const fieldError = (field) => props.errors?.[field]?.[0] || null
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
        />
        <div v-if="fieldError('ward_id')" class="invalid-feedback">
          {{ fieldError('ward_id') }}
        </div>
      </div>

      <!-- Field 2: Kode -->
      <div class="form-group">
        <label for="room-code" class="form-label required">Kode</label>
        <input
          id="room-code"
          v-model="form.code"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('code') }"
          placeholder="Masukkan kode kamar"
          required
        />
        <div v-if="fieldError('code')" class="invalid-feedback">
          {{ fieldError('code') }}
        </div>
      </div>

      <!-- Field 3: Nama Kamar -->
      <div class="form-group">
        <label for="room-name" class="form-label required">Nama Kamar</label>
        <input
          id="room-name"
          v-model="form.name"
          type="text"
          class="form-control"
          :class="{ 'is-invalid': fieldError('name') }"
          placeholder="Masukkan nama kamar"
          required
        />
        <div v-if="fieldError('name')" class="invalid-feedback">
          {{ fieldError('name') }}
        </div>
      </div>

      <!-- Field 4: Kelas (Disabled with helper text as per specification) -->
      <div class="form-group">
        <label for="room-class" class="form-label">Kelas</label>
        <input
          id="room-class"
          type="text"
          class="form-control"
          disabled
          placeholder="Master data Kelas belum tersedia dan akan diintegrasikan pada pengembangan berikutnya."
        />
        <span class="field-hint">
          Master data Kelas belum tersedia dan akan diintegrasikan pada pengembangan berikutnya.
        </span>
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
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">
          {{ fieldError('description') }}
        </div>
      </div>
    </div>
  </MasterDataFormModal>
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

.field-hint {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
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
