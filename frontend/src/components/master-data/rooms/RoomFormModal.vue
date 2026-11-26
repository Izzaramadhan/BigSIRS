<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import AddRoomClassDialog from '@/components/master-data/rooms/AddRoomClassDialog.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { useToast } from '@/composables/core/useToast'
import { useRoomClassOptions } from '@/composables/useRoomClassOptions'
import { useWardOptions } from '@/composables/useWardOptions'
import { roomClassesService } from '@/services/master-data/roomClasses.service'
import { normalizeApiError } from '@/utils/http-error'
import {
  createRoomForm,
  getSafeRoomErrorMessage,
  ROOM_CODE_MAX_LENGTH,
  ROOM_NAME_MAX_LENGTH,
  toRoomPayload,
  validateRoomForm,
} from './room.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  room: {
    type: Object,
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  /** Error per field dari server (422), sudah dinormalisasi: { field: 'pesan' }. */
  serverErrors: {
    type: Object,
    default: () => ({}),
  },
  /** Error umum non-field saat submit gagal. */
  generalError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const toast = useToast()
const formId = useId()
const form = reactive(createRoomForm())
const clientErrors = ref({})

const {
  options: wardOptions,
  loading: loadingWards,
  error: wardError,
  load: loadWards,
} = useWardOptions()

const {
  options: roomClassOptions,
  loading: loadingRoomClasses,
  error: roomClassError,
  load: loadRoomClasses,
  ensureOption: ensureRoomClassOption,
} = useRoomClassOptions()

const addRoomClassDialog = reactive({
  open: false,
  initialName: '',
  saving: false,
  serverErrors: {},
  errorMessage: '',
})

const isEdit = computed(() => Boolean(props.room))
const errors = computed(() => {
  const merged = { ...props.serverErrors, ...clientErrors.value }
  if (!merged.room_class_id && merged.class_id) {
    merged.room_class_id = merged.class_id
  }
  return merged
})
const canSubmit = computed(
  () => !loadingWards.value && !wardError.value && wardOptions.value.length > 0,
)

function currentSavedClass() {
  return props.room?.room_class ?? props.room?.class ?? null
}

watch(
  [() => props.open, () => props.room],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createRoomForm(props.room))
    clientErrors.value = {}
    addRoomClassDialog.open = false
    addRoomClassDialog.serverErrors = {}
    addRoomClassDialog.errorMessage = ''
    loadWards()
    loadRoomClasses('', currentSavedClass())
  },
  { immediate: true },
)

for (const field of ['ward_id', 'code', 'name', 'room_class_id']) {
  watch(
    () => form[field],
    () => {
      if (clientErrors.value[field]) {
        const next = { ...clientErrors.value }
        delete next[field]
        clientErrors.value = next
      }
    },
  )
}

function handleSearchRoomClass(query) {
  loadRoomClasses(query, currentSavedClass())
}

function handleCreateRoomClass(name) {
  addRoomClassDialog.initialName = name
  addRoomClassDialog.serverErrors = {}
  addRoomClassDialog.errorMessage = ''
  addRoomClassDialog.open = true
}

function handleCloseAddRoomClass() {
  if (addRoomClassDialog.saving) return
  addRoomClassDialog.open = false
  addRoomClassDialog.serverErrors = {}
  addRoomClassDialog.errorMessage = ''
}

async function handleSubmitAddRoomClass(payload) {
  if (addRoomClassDialog.saving) return
  addRoomClassDialog.saving = true
  addRoomClassDialog.serverErrors = {}
  addRoomClassDialog.errorMessage = ''
  try {
    const res = await roomClassesService.createRoomClass(payload)
    const newClass = res?.data ?? res
    if (newClass?.id) {
      ensureRoomClassOption(newClass)
      form.room_class_id = newClass.id
      addRoomClassDialog.open = false
      toast.success('Data kelas berhasil ditambahkan.')
    }
  } catch (err) {
    const normalized = normalizeApiError(err, 'Gagal menambahkan data kelas.')
    addRoomClassDialog.serverErrors = normalized.fieldErrors
    const safeMessage = getSafeRoomErrorMessage(normalized, 'Gagal menambahkan data kelas.')
    if (Object.keys(normalized.fieldErrors).length === 0) {
      addRoomClassDialog.errorMessage = safeMessage
    }
    toast.error(safeMessage)
  } finally {
    addRoomClassDialog.saving = false
  }
}

function handleSubmit() {
  if (props.saving || !canSubmit.value) return
  clientErrors.value = validateRoomForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toRoomPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Kamar' : 'Tambah Kamar'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField v-slot="field" label="Nama Bangsal" required :error="errors.ward_id">
        <div
          v-if="loadingWards"
          class="flex h-10 items-center gap-2 rounded-lg border border-line bg-canvas px-3.5 text-sm text-fg-muted"
        >
          <LoaderCircle class="size-4 animate-spin" aria-hidden="true" />
          Memuat data bangsal…
        </div>

        <BaseAlert v-else-if="wardError" tone="danger">
          {{ wardError }}
          <template #action>
            <BaseButton variant="ghost" size="sm" @click="loadWards">Coba lagi</BaseButton>
          </template>
        </BaseAlert>

        <BaseAlert v-else-if="wardOptions.length === 0" tone="warning">
          Data Bangsal belum tersedia. Silakan tambahkan data pada menu Data Gedung, Data Lantai,
          dan Data Bangsal terlebih dahulu.
        </BaseAlert>

        <SearchableSelect
          v-else
          :id="field.id"
          v-model="form.ward_id"
          :options="wardOptions"
          placeholder="Pilih bangsal"
          search-placeholder="Cari bangsal, lantai, atau gedung…"
          empty-text="Tidak ada data bangsal ditemukan."
          :is-invalid="field.invalid"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Kode Kamar"
        required
        :error="errors.code"
        :hint="`Maksimal ${ROOM_CODE_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.code"
          placeholder="Contoh: KMR-001"
          :maxlength="ROOM_CODE_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Nama Kamar"
        required
        :error="errors.name"
        :hint="`Maksimal ${ROOM_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Kamar Melati 1"
          :maxlength="ROOM_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField v-slot="field" label="Kelas" required :error="errors.room_class_id">
        <BaseAlert v-if="roomClassError" tone="danger">
          {{ roomClassError }}
          <template #action>
            <BaseButton variant="ghost" size="sm" @click="loadRoomClasses('', currentSavedClass())">
              Coba lagi
            </BaseButton>
          </template>
        </BaseAlert>

        <SearchableSelect
          v-else
          :id="field.id"
          v-model="form.room_class_id"
          :options="roomClassOptions"
          placeholder="Pilih atau cari kelas"
          search-placeholder="Cari kelas…"
          empty-text="Data kelas tidak ditemukan."
          :loading="loadingRoomClasses"
          :is-invalid="field.invalid"
          :disabled="saving"
          server-search
          clearable
          creatable
          create-prompt='Tambah kelas “[name]”'
          @search="handleSearchRoomClass"
          @create="handleCreateRoomClass"
        />
      </BaseField>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, mis. kamar perawatan maternal"
          :disabled="saving"
        />
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving" :disabled="!canSubmit">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>

  <AddRoomClassDialog
    :open="addRoomClassDialog.open"
    :initial-name="addRoomClassDialog.initialName"
    :saving="addRoomClassDialog.saving"
    :server-errors="addRoomClassDialog.serverErrors"
    :error-message="addRoomClassDialog.errorMessage"
    @close="handleCloseAddRoomClass"
    @submit="handleSubmitAddRoomClass"
  />
</template>
