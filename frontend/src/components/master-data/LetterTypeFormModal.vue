<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import {
  createLetterTypeForm,
  LETTER_TYPE_DESCRIPTION_MAX_LENGTH,
  LETTER_TYPE_NAME_MAX_LENGTH,
  LETTER_TYPE_RESOURCE_MAX_LENGTH,
  toLetterTypePayload,
  validateLetterTypeForm,
} from './letter-types/letter-type.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  letterType: {
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
  /** Error umum non-field saat submit gagal (mis. 403/404/5xx). */
  generalError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const formId = useId()
const form = reactive(createLetterTypeForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.letterType?.id))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.letterType],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createLetterTypeForm(props.letterType))
    clientErrors.value = {}
  },
  { immediate: true },
)

for (const field of ['name', 'description', 'legacy_resource']) {
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
  clientErrors.value = validateLetterTypeForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toLetterTypePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Surat' : 'Tambah Surat'"
    size="md"
    :persistent="saving"
    @close="requestClose"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        id="name"
        label="Nama Surat"
        required
        :error="errors.name"
        :hint="`Maksimal ${LETTER_TYPE_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          autocomplete="off"
          placeholder="Masukkan nama surat"
          :maxlength="LETTER_TYPE_NAME_MAX_LENGTH"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        id="description"
        label="Deskripsi"
        :error="errors.description"
        :hint="`Opsional, maksimal ${LETTER_TYPE_DESCRIPTION_MAX_LENGTH} karakter.`"
      >
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          :rows="3"
          placeholder="Masukkan deskripsi (opsional)"
          :maxlength="LETTER_TYPE_DESCRIPTION_MAX_LENGTH"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        id="legacy_resource"
        label="Resource"
        :error="errors.legacy_resource"
        :hint="`Opsional, path atau URL format lama (maksimal ${LETTER_TYPE_RESOURCE_MAX_LENGTH} karakter).`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.legacy_resource"
          autocomplete="off"
          placeholder="Masukkan resource (opsional)"
          :maxlength="LETTER_TYPE_RESOURCE_MAX_LENGTH"
        />
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
