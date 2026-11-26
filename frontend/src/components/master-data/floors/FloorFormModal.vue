<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { useBuildingOptions } from '@/composables/useBuildingOptions'
import {
  createFloorForm,
  FLOOR_NAME_MAX_LENGTH,
  toFloorPayload,
  validateFloorForm,
} from './floor.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  floor: {
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
const form = reactive(createFloorForm())
const clientErrors = ref({})
const {
  options: buildingOptions,
  loading: loadingBuildings,
  error: buildingError,
  load: loadBuildings,
} = useBuildingOptions()

const isEdit = computed(() => Boolean(props.floor))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))
const canSubmit = computed(
  () => !loadingBuildings.value && !buildingError.value && buildingOptions.value.length > 0,
)

watch(
  [() => props.open, () => props.floor],
  ([isOpen]) => {
    if (!isOpen) return
    Object.assign(form, createFloorForm(props.floor))
    clientErrors.value = {}
    loadBuildings()
  },
  { immediate: true },
)

watch(
  () => form.building_id,
  () => {
    if (clientErrors.value.building_id) {
      const next = { ...clientErrors.value }
      delete next.building_id
      clientErrors.value = next
    }
  },
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
  if (props.saving || !canSubmit.value) return
  clientErrors.value = validateFloorForm(form)
  if (Object.keys(clientErrors.value).length > 0) return
  emit('submit', toFloorPayload(form))
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Lantai' : 'Tambah Lantai'"
    :persistent="saving"
    @close="emit('close')"
  >
    <form :id="formId" class="flex flex-col gap-5" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseField v-slot="field" label="Nama Gedung" required :error="errors.building_id">
        <div
          v-if="loadingBuildings"
          class="flex h-10 items-center gap-2 rounded-lg border border-line bg-canvas px-3.5 text-sm text-fg-muted"
        >
          <LoaderCircle class="size-4 animate-spin" aria-hidden="true" />
          Memuat data gedung…
        </div>

        <BaseAlert v-else-if="buildingError" tone="danger">
          {{ buildingError }}
          <template #action>
            <BaseButton variant="ghost" size="sm" @click="loadBuildings">Coba lagi</BaseButton>
          </template>
        </BaseAlert>

        <BaseAlert v-else-if="buildingOptions.length === 0" tone="warning">
          Data Gedung belum tersedia. Silakan tambahkan data pada menu Data Gedung terlebih dahulu.
        </BaseAlert>

        <SearchableSelect
          v-else
          :id="field.id"
          v-model="form.building_id"
          :options="buildingOptions"
          placeholder="Pilih gedung"
          search-placeholder="Cari nama gedung…"
          empty-text="Tidak ada data gedung ditemukan."
          :is-invalid="field.invalid"
          :disabled="saving"
        />
      </BaseField>

      <BaseField
        v-slot="field"
        label="Nama Lantai"
        required
        :error="errors.name"
        :hint="`Maksimal ${FLOOR_NAME_MAX_LENGTH} karakter.`"
      >
        <BaseInput
          v-bind="field"
          v-model="form.name"
          placeholder="Contoh: Lantai 1"
          :maxlength="FLOOR_NAME_MAX_LENGTH"
          :disabled="saving"
        />
      </BaseField>

      <BaseField v-slot="field" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          placeholder="Opsional, mis. lantai poliklinik rawat jalan"
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
</template>
