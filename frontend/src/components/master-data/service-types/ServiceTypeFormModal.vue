<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import {
  createServiceTypeForm,
  SERVICE_TYPE_NAME_MAX_LENGTH,
  toServiceTypePayload,
  validateServiceTypeForm,
} from './service-type.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  serviceType: {
    type: Object,
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
  /** Error per field dari server (422), sudah dinormalisasi: { name: 'pesan' }. */
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
const form = reactive(createServiceTypeForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.serviceType))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

watch(
  [() => props.open, () => props.serviceType],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createServiceTypeForm(props.serviceType))
    clientErrors.value = {}
  },
  { immediate: true },
)

watch(
  () => form.name,
  () => {
    if (clientErrors.value.name) {
      const next = { ...clientErrors.value }
      delete next.name
      clientErrors.value = next
    }
  },
)

function handleSubmit() {
  if (props.saving) return
  clientErrors.value = validateServiceTypeForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toServiceTypePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Jenis Layanan' : 'Tambah Jenis Layanan'"
    size="sm"
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
        label="Nama Jenis Layanan"
        required
        :error="errors.name"
        :hint="`Maksimal ${SERVICE_TYPE_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Masukkan nama jenis layanan"
          :maxlength="SERVICE_TYPE_NAME_MAX_LENGTH"
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
