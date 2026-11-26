<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import {
  createPositionForm,
  POSITION_DESCRIPTION_MAX_LENGTH,
  POSITION_NAME_MAX_LENGTH,
  toPositionPayload,
  validatePositionForm,
} from './position.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  position: {
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
const form = reactive(createPositionForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.position))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.position],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createPositionForm(props.position))
    clientErrors.value = {}
  },
  { immediate: true },
)

for (const field of ['name', 'description']) {
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
  clientErrors.value = validatePositionForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toPositionPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Jabatan' : 'Tambah Jabatan'"
    size="sm"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        label="Nama Jabatan"
        required
        :error="errors.name"
        :hint="`Maksimal ${POSITION_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Misal: Administrator"
          :maxlength="POSITION_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Deskripsi"
        :error="errors.description"
        :hint="`Opsional, maksimal ${POSITION_DESCRIPTION_MAX_LENGTH} karakter.`"
      >
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Misal: Deskripsi singkat tentang jabatan ini..."
          :maxlength="POSITION_DESCRIPTION_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
