<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import {
  formatPriceDigits,
  ROOM_CLASS_CODE_MAX_LENGTH,
  ROOM_CLASS_NAME_MAX_LENGTH,
  toRoomClassPayload,
  validateRoomClassForm,
} from '@/components/master-data/room-classes/room-class.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  initialName: {
    type: String,
    default: '',
  },
  saving: {
    type: Boolean,
    default: false,
  },
  /** Error per field dari server (422), sudah dinormalisasi: { name, code, price }. */
  serverErrors: {
    type: Object,
    default: () => ({}),
  },
  /** Pesan error umum non-field saat submit gagal. */
  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const formId = useId()
const form = reactive({
  name: '',
  code: '',
  price: '',
})
const clientErrors = ref({})
const dismissedServerFields = ref(new Set())

const errors = computed(() => {
  const activeServerErrors = {}
  for (const [key, value] of Object.entries(props.serverErrors || {})) {
    if (!dismissedServerFields.value.has(key) && value) {
      activeServerErrors[key] = value
    }
  }
  return { ...activeServerErrors, ...clientErrors.value }
})

function clearFieldError(field) {
  if (clientErrors.value[field]) {
    const next = { ...clientErrors.value }
    delete next[field]
    clientErrors.value = next
  }
  if (dismissedServerFields.value && !dismissedServerFields.value.has(field)) {
    const nextDismissed = new Set(dismissedServerFields.value)
    nextDismissed.add(field)
    dismissedServerFields.value = nextDismissed
  }
}

watch(
  () => [props.open, props.initialName],
  ([isOpen, newName]) => {
    clientErrors.value = {}
    dismissedServerFields.value = new Set()
    if (!isOpen) {
      form.name = ''
      form.code = ''
      form.price = ''
      return
    }
    form.name = newName ? newName.trim().replace(/\s+/g, ' ') : ''
    form.code = ''
    form.price = ''
  },
  { immediate: true },
)

watch(
  () => props.serverErrors,
  () => {
    dismissedServerFields.value = new Set()
  },
)

for (const field of ['name', 'code', 'price']) {
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

function handleConfirm() {
  if (props.saving) return
  clientErrors.value = validateRoomClassForm(form)
  if (Object.keys(clientErrors.value).length > 0) return

  const { name, code, price } = toRoomClassPayload(form)
  emit('submit', { name, code, price })
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Tambah Kelas Baru"
    size="sm"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-4" novalidate @submit.prevent="handleConfirm">
      <p class="text-sm text-fg-muted">
        Data kelas &ldquo;<strong class="text-fg">{{ initialName }}</strong>&rdquo; belum tersedia.
        Lengkapi detail berikut untuk menambahkan sebagai data kelas baru.
      </p>

      <BaseAlert v-if="errorMessage" tone="danger">
        {{ errorMessage }}
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
          placeholder="Masukkan nama kelas"
          :maxlength="ROOM_CLASS_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

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
        hint="Tarif kelas per hari dalam Rupiah (boleh 0)."
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
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="emit('close')">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ saving ? 'Menyimpan…' : 'Tambah Kelas' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
