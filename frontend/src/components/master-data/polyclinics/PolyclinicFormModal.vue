<script setup>
import { computed, reactive, ref, useId, watch } from 'vue'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import lookupService from '@/services/lookup.service'
import { polyclinicService } from '@/services/polyclinic'
import { normalizeApiError } from '@/utils/http-error'
import {
  createPolyclinicForm,
  ensureSavedWarehouseOptions,
  getSafePolyclinicErrorMessage,
  POLYCLINIC_BPJS_CODE_MAX_LENGTH,
  POLYCLINIC_CODE_MAX_LENGTH,
  POLYCLINIC_DESCRIPTION_MAX_LENGTH,
  POLYCLINIC_NAME_MAX_LENGTH,
  toPolyclinicPayload,
  validatePolyclinicForm,
} from './polyclinic.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  polyclinic: {
    type: Object,
    default: null,
  },
  serviceTypes: {
    type: Array,
    default: () => [],
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
  /** Error umum non-field saat submit gagal (mis. 409/500). */
  generalError: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'submit'])

const formId = useId()
const form = reactive(createPolyclinicForm())
const clientErrors = ref({})

const rawWarehouses = ref([])
const polyclinicDetail = ref(null)
const loadingDetail = ref(false)
const loadingWarehouses = ref(false)
const lookupError = ref('')

let latestOpenRequest = 0

const isEdit = computed(() => Boolean(props.polyclinic?.id))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))
const warehouseOptions = computed(() =>
  ensureSavedWarehouseOptions(rawWarehouses.value, polyclinicDetail.value || props.polyclinic),
)

async function loadModalData(targetPolyclinic) {
  const requestId = ++latestOpenRequest
  lookupError.value = ''

  let detailPromise = Promise.resolve(null)
  if (targetPolyclinic?.id) {
    loadingDetail.value = true
    detailPromise = polyclinicService.getPolyclinic(targetPolyclinic.id)
  }

  const needsWarehouses = rawWarehouses.value.length === 0
  let warehousesPromise = Promise.resolve(null)
  if (needsWarehouses) {
    loadingWarehouses.value = true
    warehousesPromise = lookupService.getWarehouses()
  }

  const [detailResult, warehouseResult] = await Promise.allSettled([
    detailPromise,
    warehousesPromise,
  ])

  if (requestId !== latestOpenRequest) return

  loadingDetail.value = false
  loadingWarehouses.value = false

  if (targetPolyclinic?.id) {
    if (detailResult.status === 'fulfilled') {
      const resolved =
        detailResult.value?.data?.data ?? detailResult.value?.data ?? targetPolyclinic
      polyclinicDetail.value = resolved
      Object.assign(form, createPolyclinicForm(resolved))
    } else {
      polyclinicDetail.value = targetPolyclinic
      lookupError.value = getSafePolyclinicErrorMessage(
        normalizeApiError(detailResult.reason, 'Detail terbaru poliklinik gagal dimuat.'),
        'Detail terbaru poliklinik gagal dimuat.',
      )
    }
  }

  if (needsWarehouses) {
    if (warehouseResult.status === 'fulfilled') {
      rawWarehouses.value = Array.isArray(warehouseResult.value) ? warehouseResult.value : []
    } else {
      lookupError.value =
        lookupError.value ||
        getSafePolyclinicErrorMessage(
          normalizeApiError(warehouseResult.reason, 'Daftar gudang gagal dimuat.'),
          'Daftar gudang gagal dimuat.',
        )
    }
  }
}

watch(
  [() => props.open, () => props.polyclinic],
  ([isOpen, polyclinic]) => {
    if (!isOpen) return
    clientErrors.value = {}
    polyclinicDetail.value = polyclinic
    Object.assign(form, createPolyclinicForm(polyclinic))
    loadModalData(polyclinic)
  },
  { immediate: true },
)

function clearFieldError(field) {
  if (!clientErrors.value[field]) return
  const next = { ...clientErrors.value }
  delete next[field]
  clientErrors.value = next
}

function handleSubmit() {
  if (props.saving) return
  const validation = validatePolyclinicForm(form)
  clientErrors.value = validation
  if (Object.keys(validation).length > 0) return

  emit('submit', toPolyclinicPayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Poliklinik' : 'Tambah Poliklinik'"
    description="Atur identitas poliklinik, jenis layanan, kuota, dan integrasi BPJS."
    size="md"
    :persistent="saving"
    @close="requestClose"
  >
    <form :id="formId" class="space-y-4" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseAlert v-if="lookupError" tone="warning">
        {{ lookupError }}
      </BaseAlert>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField v-slot="field" id="code" label="Kode Poliklinik" required :error="errors.code">
          <BaseInput
            v-bind="field"
            v-model="form.code"
            placeholder="Contoh: POLI-01"
            :maxlength="POLYCLINIC_CODE_MAX_LENGTH"
            class="uppercase"
            @update:model-value="clearFieldError('code')"
          />
        </BaseField>

        <BaseField v-slot="field" id="name" label="Nama Poliklinik" required :error="errors.name">
          <BaseInput
            v-bind="field"
            v-model="form.name"
            placeholder="Contoh: Poli Umum"
            :maxlength="POLYCLINIC_NAME_MAX_LENGTH"
            @update:model-value="clearFieldError('name')"
          />
        </BaseField>
      </div>

      <BaseField
        v-slot="field"
        id="service_type"
        label="Jenis Layanan"
        required
        :error="errors.service_type"
      >
        <select
          :id="field.id"
          v-model="form.service_type"
          :aria-invalid="field.invalid || undefined"
          :aria-describedby="field['aria-describedby']"
          :class="fieldControlClass({ invalid: field.invalid })"
          @change="clearFieldError('service_type')"
        >
          <option value="">-- Pilih Jenis Layanan --</option>
          <option v-for="st in serviceTypes" :key="st.value" :value="st.value">
            {{ st.label }}
          </option>
        </select>
      </BaseField>

      <BaseField v-slot="field" id="description" label="Deskripsi" :error="errors.description">
        <BaseTextarea
          v-bind="field"
          v-model="form.description"
          :rows="2"
          placeholder="Deskripsi singkat poliklinik"
          :maxlength="POLYCLINIC_DESCRIPTION_MAX_LENGTH"
          @update:model-value="clearFieldError('description')"
        />
      </BaseField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-2">
          <span class="text-sm font-medium text-fg">Pengaturan Tampilan</span>
          <div class="flex flex-col gap-2.5 pt-1">
            <label
              for="is_visible"
              class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-fg"
            >
              <input
                id="is_visible"
                v-model="form.is_visible"
                type="checkbox"
                class="peer sr-only"
              />
              <span
                aria-hidden="true"
                class="relative h-6 w-10 shrink-0 rounded-full bg-line-strong transition-colors peer-checked:bg-primary peer-focus-visible:ring-3 peer-focus-visible:ring-primary/20 after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-surface after:shadow-xs after:transition-transform peer-checked:after:translate-x-4"
              />
              <span>Tampil di Antrian</span>
            </label>

            <label
              for="is_online_visible"
              class="inline-flex cursor-pointer items-center gap-2.5 text-sm text-fg"
            >
              <input
                id="is_online_visible"
                v-model="form.is_online_visible"
                type="checkbox"
                class="peer sr-only"
              />
              <span
                aria-hidden="true"
                class="relative h-6 w-10 shrink-0 rounded-full bg-line-strong transition-colors peer-checked:bg-primary peer-focus-visible:ring-3 peer-focus-visible:ring-primary/20 after:absolute after:top-0.5 after:left-0.5 after:size-5 after:rounded-full after:bg-surface after:shadow-xs after:transition-transform peer-checked:after:translate-x-4"
              />
              <span>Tampil Online</span>
            </label>
          </div>
        </div>

        <BaseField
          v-slot="field"
          id="warehouse_id"
          label="Gudang Default"
          :error="errors.warehouse_id"
          :hint="loadingWarehouses ? 'Memuat daftar gudang...' : ''"
        >
          <select
            :id="field.id"
            v-model="form.warehouse_id"
            :disabled="loadingWarehouses || loadingDetail"
            :aria-invalid="field.invalid || undefined"
            :aria-describedby="field['aria-describedby']"
            :class="fieldControlClass({ invalid: field.invalid })"
            @change="clearFieldError('warehouse_id')"
          >
            <option value="">Pilih Gudang Default</option>
            <option v-for="wh in warehouseOptions" :key="wh.id" :value="wh.id">
              {{ wh.code ? `${wh.code} — ${wh.name}` : wh.name }}
            </option>
          </select>
        </BaseField>
      </div>

      <BaseField v-slot="field" id="bpjs_code" label="Kode BPJS" :error="errors.bpjs_code">
        <BaseInput
          v-bind="field"
          v-model="form.bpjs_code"
          placeholder="Masukkan kode BPJS"
          :maxlength="POLYCLINIC_BPJS_CODE_MAX_LENGTH"
          @update:model-value="clearFieldError('bpjs_code')"
        />
      </BaseField>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseField v-slot="field" id="quota" label="Kuota" :error="errors.quota">
          <BaseInput
            v-bind="field"
            v-model.number="form.quota"
            type="number"
            min="0"
            placeholder="0"
            @update:model-value="clearFieldError('quota')"
          />
        </BaseField>

        <BaseField v-slot="field" id="jkn_quota" label="Kuota JKN" :error="errors.jkn_quota">
          <BaseInput
            v-bind="field"
            v-model.number="form.jkn_quota"
            type="number"
            min="0"
            placeholder="0"
            @update:model-value="clearFieldError('jkn_quota')"
          />
        </BaseField>
      </div>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton type="submit" :form="formId" :loading="saving">
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
