<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import { activityTypeService } from '@/services/activityType.service'
import { normalizeApiError } from '@/utils/http-error'
import {
  ACTIVITY_TYPE_NAME_MAX_LENGTH,
  createActivityTypeForm,
  ensureSavedParentOptions,
  getSafeActivityTypeErrorMessage,
  toActivityTypePayload,
  validateActivityTypeForm,
} from './activity-type.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  activityType: {
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

const formId = useId()
const form = reactive(createActivityTypeForm())
const clientErrors = ref({})

const rawParentOptions = ref([])
const loadingParents = ref(false)
const lookupError = ref('')
let latestLookupRequest = 0

const isEdit = computed(() => Boolean(props.activityType?.id))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))
const parentOptions = computed(() =>
  ensureSavedParentOptions(rawParentOptions.value, props.activityType),
)

async function loadParentOptions(excludeId = null) {
  const requestId = ++latestLookupRequest
  loadingParents.value = true
  lookupError.value = ''

  try {
    const params = excludeId ? { exclude_id: excludeId } : {}
    const response = await activityTypeService.lookup(params)
    if (requestId !== latestLookupRequest) return
    rawParentOptions.value = Array.isArray(response?.data) ? response.data : []
  } catch (err) {
    if (requestId !== latestLookupRequest) return
    rawParentOptions.value = []
    lookupError.value = getSafeActivityTypeErrorMessage(
      normalizeApiError(err, 'Daftar induk jenis kegiatan gagal dimuat.'),
      'Daftar induk jenis kegiatan gagal dimuat.',
    )
  } finally {
    if (requestId === latestLookupRequest) {
      loadingParents.value = false
    }
  }
}

watch(
  [() => props.open, () => props.activityType],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createActivityTypeForm(props.activityType))
    clientErrors.value = {}
    loadParentOptions(props.activityType?.id ?? null)
  },
  { immediate: true },
)

for (const field of ['name', 'parent_id']) {
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

function handleSubmit() {
  if (props.saving) return
  clientErrors.value = validateActivityTypeForm(form, props.activityType?.id ?? null)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toActivityTypePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Jenis Kegiatan' : 'Tambah Jenis Kegiatan'"
    size="md"
    :persistent="saving"
    @close="requestClose"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseAlert v-else-if="lookupError" tone="warning">
        {{ lookupError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        id="name"
        label="Nama Jenis Kegiatan"
        required
        :error="errors.name"
        :hint="`Maksimal ${ACTIVITY_TYPE_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          autocomplete="off"
          placeholder="Masukkan nama Jenis Kegiatan"
          :maxlength="ACTIVITY_TYPE_NAME_MAX_LENGTH"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        id="parent_id"
        label="Induk"
        :error="errors.parent_id"
        :hint="loadingParents ? 'Memuat daftar induk...' : 'Opsional, pilih jika merupakan sub-kegiatan.'"
      >
        <select
          :id="field.id"
          v-model="form.parent_id"
          :disabled="saving || loadingParents"
          :aria-invalid="field.invalid || undefined"
          :aria-describedby="field['aria-describedby']"
          :class="fieldControlClass({ invalid: field.invalid })"
        >
          <option :value="null">Tidak memiliki induk</option>
          <option v-for="option in parentOptions" :key="option.id" :value="option.id">
            {{ option.name }}
          </option>
        </select>
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
