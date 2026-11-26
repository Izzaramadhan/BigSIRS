<script setup>
import { computed } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import BaseAlert from '@/components/ui/BaseAlert.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import {
  EMPTY,
  extractDoctorPerson,
  formatDoctorDate,
  formatDoctorGender,
  formatDoctorSpecialization,
} from './doctor.helpers'

const props = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  detail: {
    type: Object,
    default: null,
  },
  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close', 'edit'])

const person = computed(() => extractDoctorPerson(props.detail))
const professional = computed(() => props.detail?.professional ?? props.detail ?? {})

const formatValue = (value) => {
  if (value === null || value === undefined) return EMPTY
  const text = String(value).trim()
  return text || EMPTY
}
</script>

<template>
  <BaseModal
    :open="open"
    title="Detail Dokter"
    description="Informasi lengkap biodata, kontak, dan profil profesional dokter."
    size="lg"
    @close="emit('close')"
  >
    <div v-if="loading" class="flex items-center justify-center gap-2 py-12 text-sm text-fg-muted">
      <LoaderCircle class="size-4 animate-spin text-primary" aria-hidden="true" />
      <span>Memuat detail dokter...</span>
    </div>

    <BaseAlert v-else-if="errorMessage || !detail" tone="danger">
      {{ errorMessage || 'Gagal memuat detail dokter.' }}
    </BaseAlert>

    <div v-else class="space-y-6">
      <section class="space-y-3">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          Identitas Person / Pegawai
        </h3>
        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-xs font-medium text-fg-muted">NIK / KTP</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.national_id) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Nama Lengkap</dt>
            <dd class="mt-0.5 font-medium text-fg">{{ formatValue(person.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Tempat Lahir</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.birth_place) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Tanggal Lahir</dt>
            <dd class="mt-0.5 text-fg">{{ formatDoctorDate(person.birth_date) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Jenis Kelamin</dt>
            <dd class="mt-0.5 text-fg">{{ formatDoctorGender(person.gender) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Golongan Darah</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.blood_type) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Agama</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.religion) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Status Perkawinan</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.marital_status) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kebangsaan</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.nationality) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Pendidikan</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.education?.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Pekerjaan</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.occupation?.name) }}</dd>
          </div>
          <div class="sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-medium text-fg-muted">Alergi</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.allergies) }}</dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          Alamat &amp; Kontak
        </h3>
        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
          <div class="sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-medium text-fg-muted">Alamat Lengkap</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.address) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Provinsi</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.province?.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kabupaten / Kota</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.city?.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kecamatan</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.district?.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kelurahan / Desa</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.village?.name) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kode Pos</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.postal_code) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Nomor HP / Telepon</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.phone) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Email</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.email) }}</dd>
          </div>
        </dl>
      </section>

      <section class="space-y-3">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-fg">
          Profil Profesional Dokter
        </h3>
        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <dt class="text-xs font-medium text-fg-muted">Spesialisasi</dt>
            <dd class="mt-0.5 font-medium text-fg">
              {{ formatDoctorSpecialization(detail) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Nomor STR</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(professional.str_number) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Nomor SIP</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(professional.sip_number) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Masa Berlaku SIP</dt>
            <dd class="mt-0.5 text-fg">{{ formatDoctorDate(professional.sip_valid_until) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Kode DPJS BPJS</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(professional.bpjs_dpjp_code) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-fg-muted">Nomor IHS (SatuSehat)</dt>
            <dd class="mt-0.5 text-fg">{{ formatValue(person.ihs_number) }}</dd>
          </div>
          <div v-if="detail.signature_url" class="sm:col-span-2 lg:col-span-3">
            <dt class="text-xs font-medium text-fg-muted">Tanda Tangan Digital</dt>
            <dd class="mt-1.5">
              <img
                :src="detail.signature_url"
                alt="Tanda tangan dokter"
                class="max-h-24 max-w-full rounded border border-line bg-canvas object-contain p-1.5"
              />
            </dd>
          </div>
        </dl>
      </section>
    </div>

    <template #footer>
      <BaseButton variant="secondary" @click="emit('close')">Tutup</BaseButton>
      <BaseButton v-if="!loading && detail" @click="emit('edit')">Edit Dokter</BaseButton>
    </template>
  </BaseModal>
</template>
