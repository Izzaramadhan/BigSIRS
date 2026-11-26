<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import {
  createRoomClassForm,
  formatPriceDigits,
  ROOM_CLASS_APLICARE_CODE_MAX_LENGTH,
  ROOM_CLASS_CODE_MAX_LENGTH,
  ROOM_CLASS_DESCRIPTION_MAX_LENGTH,
  ROOM_CLASS_NAME_MAX_LENGTH,
  ROOM_CLASS_SIRS_CODE_MAX_LENGTH,
  toRoomClassPayload,
  validateRoomClassForm,
} from './room-class.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  roomClass: {
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
const form = reactive(createRoomClassForm())
const clientErrors = ref({})

const isEdit = computed(() => Boolean(props.roomClass))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

function clearFieldError(field) {
  if (!clientErrors.value[field]) return
  const next = { ...clientErrors.value }
  delete next[field]
  clientErrors.value = next
}

watch(
  [() => props.open, () => props.roomClass],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createRoomClassForm(props.roomClass))
    clientErrors.value = {}
  },
  { immediate: true },
)

for (const field of ['name', 'code', 'price', 'description', 'aplicare_code', 'sirs_code']) {
  watch(
    () => form[field],
    () => clearFieldError(field),
  )
}

function handlePriceInput(event) {
  const formatted = formatPriceDigits(event.target.value)
  form.price = formatted
  event.target.value = formatted
}

function handleSubmit() {
  if (props.saving) return
  clientErrors.value = validateRoomClassForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toRoomClassPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Kelas' : 'Tambah Kelas'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField
        v-slot="field"
        label="Nama Kelas"
        required
        :error="errors.name"
        :hint="`Maksimal ${ROOM_CLASS_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Kelas 1, VIP, ICU"
          :maxlength="ROOM_CLASS_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField
          v-slot="field"
          label="Kode Kelas"
          required
          :error="errors.code"
          :hint="`Maksimal ${ROOM_CLASS_CODE_MAX_LENGTH} karakter.`"
        >
          <BaseInput
            v-bind="field"
            v-model="form.code"
            placeholder="Contoh: KLS-1, VIP"
            :maxlength="ROOM_CLASS_CODE_MAX_LENGTH"
            :disabled="saving"
          />
        </BaseField>

        <BaseField
          v-slot="field"
          label="Harga Kelas"
          required
          :error="errors.price"
          hint="Tarif kelas per hari dalam Rupiah."
        >
          <div
            class="flex items-stretch overflow-hidden rounded-lg border bg-surface transition-colors focus-within:ring-3"
            :class="[
              field.invalid
                ? 'border-danger focus-within:ring-danger/15'
                : 'border-line-strong focus-within:border-primary focus-within:ring-primary/15',
              saving ? 'cursor-not-allowed bg-canvas opacity-70' : '',
            ]"
          >
            <span
              class="flex items-center border-r border-line bg-canvas px-3 text-sm font-medium text-fg-muted select-none"
              aria-hidden="true"
            >
              Rp
            </span>
            <input
              :id="field.id"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              :value="form.price"
              placeholder="0"
              :disabled="saving"
              class="min-w-0 flex-1 bg-transparent px-3.5 py-2.5 font-sans text-sm text-fg placeholder:text-fg-muted focus:outline-none disabled:cursor-not-allowed"
              @input="handlePriceInput"
            />
          </div>
        </BaseField>
      </div>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, deskripsi kelas atau fasilitas ruangan"
          :maxlength="ROOM_CLASS_DESCRIPTION_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField
          v-slot="field"
          label="Kode Aplicare"
          :error="errors.aplicare_code"
          hint="Kosongkan jika tidak perlu bridging ke sistem BPJS Kesehatan."
        >
          <BaseInput
            v-bind="field"
            v-model="form.aplicare_code"
            placeholder="Contoh: KL1"
            :maxlength="ROOM_CLASS_APLICARE_CODE_MAX_LENGTH"
            :disabled="saving"
          />
        </BaseField>

        <BaseField
          v-slot="field"
          label="Kode SIRS"
          :error="errors.sirs_code"
          hint="Kosongkan jika tidak perlu pelaporan ke sistem SIRS."
        >
          <BaseInput
            v-bind="field"
            v-model="form.sirs_code"
            placeholder="Contoh: VIP"
            :maxlength="ROOM_CLASS_SIRS_CODE_MAX_LENGTH"
            :disabled="saving"
          />
        </BaseField>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
