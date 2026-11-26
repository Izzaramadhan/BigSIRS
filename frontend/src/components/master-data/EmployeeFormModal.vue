<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, useId, watch } from 'vue'
import { LoaderCircle, Trash2, Upload, X } from 'lucide-vue-next'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import BaseTextarea from '@/components/ui/BaseTextarea.vue'
import { fieldControlClass } from '@/components/ui/fieldControlClass'
import employeeService from '@/services/employee.service'
import lookupService from '@/services/lookup.service'
import { normalizeApiError } from '@/utils/http-error'
import {
  BLOOD_TYPE_OPTIONS,
  createEmployeeForm,
  EMPLOYEE_CODE_MAX_LENGTH,
  EMPLOYEE_NAME_MAX_LENGTH,
  EMPLOYEE_NATIONAL_ID_MAX_LENGTH,
  EMPLOYEE_PHONE_MAX_LENGTH,
  EMPLOYEE_POSTAL_CODE_MAX_LENGTH,
  getSafeEmployeeErrorMessage,
  MARITAL_STATUS_OPTIONS,
  NATIONALITY_OPTIONS,
  RELIGION_OPTIONS,
  toEmployeePayload,
  validateEmployeeForm,
  validateSignatureFile,
} from './employees/employee.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  /** null = mode tambah, object = mode edit. */
  employee: {
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
const form = reactive(createEmployeeForm())
const clientErrors = ref({})

const loadingDetail = ref(false)
const detailError = ref('')
const loadingLookups = ref(false)
const lookupError = ref('')

const positionOptions = ref([])
const educationOptions = ref([])
const occupationOptions = ref([])
const provinceOptions = ref([])
const regencyOptions = ref([])
const districtOptions = ref([])
const villageOptions = ref([])

const loadingRegencies = ref(false)
const loadingDistricts = ref(false)
const loadingVillages = ref(false)

const fileInputRef = ref(null)
const newPreviewUrl = ref('')
const objectPreviewUrl = ref('')
let isHydrating = false
let hydrationToken = 0

const isEdit = computed(() => Boolean(props.employee))
const errors = computed(() => ({ ...props.serverErrors, ...clientErrors.value }))

const activePreviewUrl = computed(() => {
  if (newPreviewUrl.value) return newPreviewUrl.value
  if (!form.remove_signature && form.signature_url) return form.signature_url
  return ''
})

function revokeObjectPreview() {
  if (objectPreviewUrl.value && typeof URL !== 'undefined' && typeof URL.revokeObjectURL === 'function') {
    URL.revokeObjectURL(objectPreviewUrl.value)
  }
  objectPreviewUrl.value = ''
}

function resetPreviewState() {
  revokeObjectPreview()
  newPreviewUrl.value = ''
  if (fileInputRef.value) fileInputRef.value.value = ''
}

function toArray(payload) {
  if (Array.isArray(payload)) return payload
  return Array.isArray(payload?.data) ? payload.data : []
}

async function ensureStaticLookups() {
  if (
    positionOptions.value.length > 0 &&
    educationOptions.value.length > 0 &&
    occupationOptions.value.length > 0 &&
    provinceOptions.value.length > 0
  ) {
    return
  }

  loadingLookups.value = true
  lookupError.value = ''
  try {
    const [positions, educations, occupations, provinces] = await Promise.all([
      employeeService.getPositions({ is_active: 1 }),
      lookupService.getEducations(),
      lookupService.getOccupations(),
      lookupService.getProvinces(),
    ])
    positionOptions.value = toArray(positions)
    educationOptions.value = toArray(educations)
    occupationOptions.value = toArray(occupations)
    provinceOptions.value = toArray(provinces)
  } catch (error) {
    lookupError.value = getSafeEmployeeErrorMessage(
      normalizeApiError(error, 'Sebagian data referensi gagal dimuat.'),
      'Sebagian data referensi gagal dimuat.',
    )
  } finally {
    loadingLookups.value = false
  }
}

async function loadRegencies(provinceId) {
  if (!provinceId) {
    regencyOptions.value = []
    return
  }
  loadingRegencies.value = true
  try {
    regencyOptions.value = toArray(await lookupService.getRegencies({ province_id: provinceId }))
  } catch {
    regencyOptions.value = []
  } finally {
    loadingRegencies.value = false
  }
}

async function loadDistricts(regencyId) {
  if (!regencyId) {
    districtOptions.value = []
    return
  }
  loadingDistricts.value = true
  try {
    districtOptions.value = toArray(await lookupService.getDistricts({ regency_id: regencyId }))
  } catch {
    districtOptions.value = []
  } finally {
    loadingDistricts.value = false
  }
}

async function loadVillages(districtId) {
  if (!districtId) {
    villageOptions.value = []
    return
  }
  loadingVillages.value = true
  try {
    villageOptions.value = toArray(await lookupService.getVillages({ district_id: districtId }))
  } catch {
    villageOptions.value = []
  } finally {
    loadingVillages.value = false
  }
}

watch(
  () => form.province_id,
  async (newVal) => {
    if (isHydrating) return
    form.regency_id = ''
    form.district_id = ''
    form.village_id = ''
    regencyOptions.value = []
    districtOptions.value = []
    villageOptions.value = []
    await loadRegencies(newVal)
  },
)

watch(
  () => form.regency_id,
  async (newVal) => {
    if (isHydrating) return
    form.district_id = ''
    form.village_id = ''
    districtOptions.value = []
    villageOptions.value = []
    await loadDistricts(newVal)
  },
)

watch(
  () => form.district_id,
  async (newVal) => {
    if (isHydrating) return
    form.village_id = ''
    villageOptions.value = []
    await loadVillages(newVal)
  },
)

async function initializeModal(isOpen, employee) {
  const token = ++hydrationToken
  if (!isOpen) {
    resetPreviewState()
    return
  }

  clientErrors.value = {}
  detailError.value = ''
  resetPreviewState()
  regencyOptions.value = []
  districtOptions.value = []
  villageOptions.value = []

  if (!employee?.id) {
    isHydrating = true
    Object.assign(form, createEmployeeForm())
    await ensureStaticLookups()
    await nextTick()
    if (token === hydrationToken) isHydrating = false
    return
  }

  loadingDetail.value = true
  isHydrating = true
  try {
    const [, detail] = await Promise.all([
      ensureStaticLookups(),
      employeeService.getEmployee(employee.id),
    ])
    if (token !== hydrationToken || !props.open) return

    const hydrated = createEmployeeForm(detail, positionOptions.value)
    Object.assign(form, hydrated)

    await Promise.all([
      loadRegencies(hydrated.province_id),
      loadDistricts(hydrated.regency_id),
      loadVillages(hydrated.district_id),
    ])
  } catch (error) {
    if (token !== hydrationToken) return
    detailError.value = getSafeEmployeeErrorMessage(
      normalizeApiError(error, 'Detail data pegawai gagal dimuat.'),
      'Detail data pegawai gagal dimuat.',
    )
  } finally {
    if (token === hydrationToken) {
      loadingDetail.value = false
      await nextTick()
      isHydrating = false
    }
  }
}

watch(
  [() => props.open, () => props.employee],
  ([isOpen, employee]) => {
    initializeModal(isOpen, employee)
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  revokeObjectPreview()
})

function setPreviewFromFile(file) {
  revokeObjectPreview()
  if (typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function') {
    const url = URL.createObjectURL(file)
    objectPreviewUrl.value = url
    newPreviewUrl.value = url
    return
  }

  if (typeof FileReader !== 'undefined') {
    const reader = new FileReader()
    reader.onload = (event) => {
      newPreviewUrl.value = String(event.target?.result ?? '')
    }
    reader.readAsDataURL(file)
  }
}

function handleFileChange(event) {
  const file = event.target?.files?.[0]
  if (!file) return

  const fileError = validateSignatureFile(file)
  if (fileError) {
    clientErrors.value = { ...clientErrors.value, signature: fileError }
    if (event.target) event.target.value = ''
    return
  }

  const nextErrors = { ...clientErrors.value }
  delete nextErrors.signature
  clientErrors.value = nextErrors

  form.signature = file
  form.remove_signature = false
  setPreviewFromFile(file)
}

function cancelNewFile() {
  form.signature = null
  const nextErrors = { ...clientErrors.value }
  delete nextErrors.signature
  clientErrors.value = nextErrors
  resetPreviewState()
}

function removeSignature() {
  form.signature = null
  form.remove_signature = true
  resetPreviewState()
}

function undoRemoveSignature() {
  form.remove_signature = false
}

function clearFieldError(field) {
  if (!clientErrors.value[field]) return
  const next = { ...clientErrors.value }
  delete next[field]
  clientErrors.value = next
}

function handleSubmit() {
  if (props.saving || loadingDetail.value || detailError.value) return
  const validation = validateEmployeeForm(form)
  clientErrors.value = validation
  if (Object.keys(validation).length > 0) return

  emit('submit', toEmployeePayload(form))
}

function requestClose() {
  if (!props.saving) emit('close')
}
</script>

<template>
  <BaseModal
    :open="open"
    :title="isEdit ? 'Edit Data Pegawai' : 'Tambah Data Pegawai'"
    description="Lengkapi identitas pribadi, alamat dan kontak, serta informasi kepegawaian."
    size="lg"
    :persistent="saving"
    @close="requestClose"
  >
    <div v-if="loadingDetail" class="flex items-center justify-center gap-2 py-12 text-sm text-fg-muted">
      <LoaderCircle class="size-4 animate-spin text-primary" aria-hidden="true" />
      <span>Memuat detail data pegawai...</span>
    </div>

    <form v-else :id="formId" class="space-y-6" novalidate @submit.prevent="handleSubmit">
      <BaseAlert v-if="detailError" tone="danger">
        {{ detailError }}
      </BaseAlert>

      <BaseAlert v-else-if="generalError" tone="danger">
        {{ generalError }}
      </BaseAlert>

      <BaseAlert v-if="lookupError" tone="warning">
        {{ lookupError }}
      </BaseAlert>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          1. Identitas Pribadi
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField v-slot="field" label="Nomor KTP (NIK)" required :error="errors.national_id">
            <BaseInput
              v-bind="field"
              v-model="form.national_id"
              placeholder="16 digit NIK"
              :maxlength="EMPLOYEE_NATIONAL_ID_MAX_LENGTH"
              @update:model-value="clearFieldError('national_id')"
            />
          </BaseField>

          <BaseField v-slot="field" label="Nama Lengkap" required :error="errors.name">
            <BaseInput
              v-bind="field"
              v-model="form.name"
              placeholder="Nama lengkap dengan gelar"
              :maxlength="EMPLOYEE_NAME_MAX_LENGTH"
              @update:model-value="clearFieldError('name')"
            />
          </BaseField>

          <BaseField v-slot="field" label="Tempat Lahir" :error="errors.birth_place">
            <BaseInput
              v-bind="field"
              v-model="form.birth_place"
              placeholder="Contoh: Surabaya"
            />
          </BaseField>

          <BaseField v-slot="field" label="Tanggal Lahir" :error="errors.birth_date">
            <BaseInput v-bind="field" v-model="form.birth_date" type="date" />
          </BaseField>

          <BaseField v-slot="field" label="Golongan Darah" :error="errors.blood_type">
            <select
              :id="field.id"
              v-model="form.blood_type"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih --</option>
              <option v-for="item in BLOOD_TYPE_OPTIONS" :key="item" :value="item">
                {{ item === 'Unknown' ? 'Tidak Diketahui' : item }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Agama" :error="errors.religion">
            <select
              :id="field.id"
              v-model="form.religion"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Agama --</option>
              <option v-for="item in RELIGION_OPTIONS" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Status Pernikahan" :error="errors.marital_status">
            <select
              :id="field.id"
              v-model="form.marital_status"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Status --</option>
              <option v-for="item in MARITAL_STATUS_OPTIONS" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Kewarganegaraan" :error="errors.nationality">
            <select
              :id="field.id"
              v-model="form.nationality"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option v-for="item in NATIONALITY_OPTIONS" :key="item" :value="item">
                {{ item }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Alergi" :error="errors.allergies" class="sm:col-span-2">
            <BaseInput
              v-bind="field"
              v-model="form.allergies"
              placeholder="Riwayat alergi (opsional)"
            />
          </BaseField>

          <BaseField v-slot="field" label="Pendidikan Terakhir" :error="errors.education_id">
            <select
              :id="field.id"
              v-model="form.education_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Pendidikan --</option>
              <option v-for="edu in educationOptions" :key="edu.id" :value="edu.id">
                {{ edu.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Pekerjaan" :error="errors.occupation_id">
            <select
              :id="field.id"
              v-model="form.occupation_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Pekerjaan --</option>
              <option v-for="occ in occupationOptions" :key="occ.id" :value="occ.id">
                {{ occ.name }}
              </option>
            </select>
          </BaseField>
        </div>
      </section>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          2. Alamat &amp; Kontak
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField
            v-slot="field"
            label="Alamat Lengkap"
            :error="errors.address"
            class="sm:col-span-2"
          >
            <BaseTextarea
              v-bind="field"
              v-model="form.address"
              :rows="2"
              placeholder="Jalan, RT/RW, Nomor Rumah"
            />
          </BaseField>

          <BaseField v-slot="field" label="Provinsi" :error="errors.province_id">
            <select
              :id="field.id"
              v-model="form.province_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">-- Pilih Provinsi --</option>
              <option v-for="prov in provinceOptions" :key="prov.id" :value="prov.id">
                {{ prov.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Kabupaten / Kota" :error="errors.regency_id">
            <select
              :id="field.id"
              v-model="form.regency_id"
              :disabled="!form.province_id || loadingRegencies"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingRegencies ? 'Memuat kabupaten/kota...' : '-- Pilih Kab/Kota --' }}
              </option>
              <option v-for="reg in regencyOptions" :key="reg.id" :value="reg.id">
                {{ reg.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Kecamatan" :error="errors.district_id">
            <select
              :id="field.id"
              v-model="form.district_id"
              :disabled="!form.regency_id || loadingDistricts"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingDistricts ? 'Memuat kecamatan...' : '-- Pilih Kecamatan --' }}
              </option>
              <option v-for="dist in districtOptions" :key="dist.id" :value="dist.id">
                {{ dist.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Kelurahan / Desa" :error="errors.village_id">
            <select
              :id="field.id"
              v-model="form.village_id"
              :disabled="!form.district_id || loadingVillages"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
            >
              <option value="">
                {{ loadingVillages ? 'Memuat kelurahan/desa...' : '-- Pilih Kel/Desa --' }}
              </option>
              <option v-for="vil in villageOptions" :key="vil.id" :value="vil.id">
                {{ vil.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="field" label="Kode Pos" :error="errors.postal_code">
            <BaseInput
              v-bind="field"
              v-model="form.postal_code"
              placeholder="Contoh: 60111"
              :maxlength="EMPLOYEE_POSTAL_CODE_MAX_LENGTH"
              @update:model-value="clearFieldError('postal_code')"
            />
          </BaseField>

          <BaseField v-slot="field" label="No. Telepon / HP" :error="errors.phone">
            <BaseInput
              v-bind="field"
              v-model="form.phone"
              type="tel"
              placeholder="Contoh: 08123456789"
              :maxlength="EMPLOYEE_PHONE_MAX_LENGTH"
              @update:model-value="clearFieldError('phone')"
            />
          </BaseField>
        </div>
      </section>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          3. Informasi Kepegawaian
        </h3>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <BaseField
            v-slot="field"
            label="NIP (Nomor Induk Pegawai)"
            required
            :error="errors.code"
          >
            <BaseInput
              v-bind="field"
              v-model="form.code"
              placeholder="Nomor Induk Pegawai"
              :maxlength="EMPLOYEE_CODE_MAX_LENGTH"
              @update:model-value="clearFieldError('code')"
            />
          </BaseField>

          <BaseField v-slot="field" label="Jabatan" required :error="errors.position_id">
            <select
              :id="field.id"
              v-model="form.position_id"
              :disabled="loadingLookups"
              :aria-invalid="field.invalid || undefined"
              :aria-describedby="field['aria-describedby']"
              :class="fieldControlClass({ invalid: field.invalid })"
              @change="clearFieldError('position_id')"
            >
              <option value="">
                {{ loadingLookups ? 'Memuat jabatan...' : '-- Pilih Jabatan --' }}
              </option>
              <option v-for="pos in positionOptions" :key="pos.id" :value="pos.id">
                {{ pos.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="field"
            label="Tanda Tangan Digital"
            hint="Format PNG atau JPG/JPEG, maksimal 2MB."
            :error="errors.signature"
            class="sm:col-span-2"
          >
            <div class="space-y-3">
              <div
                v-if="activePreviewUrl"
                class="flex flex-col items-start gap-3 rounded-lg border border-line bg-canvas p-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <img
                  :src="activePreviewUrl"
                  alt="Preview tanda tangan"
                  class="max-h-24 max-w-full rounded border border-line bg-surface object-contain p-1"
                />

                <div class="flex flex-wrap gap-2">
                  <BaseButton
                    v-if="form.signature"
                    variant="secondary"
                    size="sm"
                    @click="cancelNewFile"
                  >
                    <X class="size-4" aria-hidden="true" />
                    Batalkan Pilihan
                  </BaseButton>
                  <BaseButton
                    v-else-if="form.signature_url"
                    variant="ghost-danger"
                    size="sm"
                    @click="removeSignature"
                  >
                    <Trash2 class="size-4" aria-hidden="true" />
                    Hapus Tanda Tangan
                  </BaseButton>
                </div>
              </div>

              <div
                v-else-if="form.remove_signature && form.signature_url"
                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-line bg-canvas px-3.5 py-2.5 text-xs text-fg-muted"
              >
                <span>Tanda tangan tersimpan akan dihapus saat data disimpan.</span>
                <BaseButton variant="secondary" size="sm" @click="undoRemoveSignature">
                  Batal Hapus
                </BaseButton>
              </div>

              <div class="flex flex-wrap items-center gap-3">
                <input
                  :id="field.id"
                  ref="fileInputRef"
                  type="file"
                  accept="image/png,image/jpeg,image/jpg"
                  class="block w-full text-sm text-fg-muted file:mr-3 file:cursor-pointer file:rounded-lg file:border file:border-line-strong file:bg-surface file:px-3.5 file:py-2 file:text-sm file:font-medium file:text-fg hover:file:bg-canvas"
                  :aria-describedby="field['aria-describedby']"
                  @change="handleFileChange"
                />
                <span
                  v-if="form.signature"
                  class="inline-flex items-center gap-1.5 text-xs text-fg-muted"
                >
                  <Upload class="size-3.5" aria-hidden="true" />
                  {{ form.signature.name }}
                </span>
              </div>
            </div>
          </BaseField>
        </div>
      </section>
    </form>

    <template #footer>
      <BaseButton variant="secondary" :disabled="saving" @click="requestClose">Batal</BaseButton>
      <BaseButton
        type="submit"
        :form="formId"
        :loading="saving"
        :disabled="saving || loadingDetail || Boolean(detailError)"
      >
        {{ isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
      </BaseButton>
    </template>
  </BaseModal>
</template>
