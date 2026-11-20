<script setup>
import { ref, watch, computed } from 'vue';
import patientService from '@/services/patient.service';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  patient: {
    type: Object,
    default: () => null
  },
  guarantorOptions: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save', 'saved']);

const loading = ref(false);
const submitting = ref(false);
const errorMessage = ref('');

const formData = ref({
  id: null,
  medical_record_number: '',
  nik: '',
  name: '',
  birth_place: '',
  birth_date: '',
  gender: 'L',
  phone: '',
  address: '',
  postal_code: '',
  guarantor_id: '',
  insurance_number: '',
  hak_kelas: '',
  has_allergy: 'tidak',
  allergy_notes: '',
  is_fast_track: 'tidak',
  emergency_contact: {
    name: '',
    relationship: '',
    phone: '',
    address: ''
  }
});

const resetForm = () => {
  formData.value = {
    id: null,
    medical_record_number: '',
    nik: '',
    name: '',
    birth_place: '',
    birth_date: '',
    gender: 'L',
    phone: '',
    address: '',
    postal_code: '',
    guarantor_id: '',
    insurance_number: '',
    hak_kelas: '',
    has_allergy: 'tidak',
    allergy_notes: '',
    is_fast_track: 'tidak',
    emergency_contact: {
      name: '',
      relationship: '',
      phone: '',
      address: ''
    }
  };
  errorMessage.value = '';
};

watch(
  () => props.isOpen,
  async (open) => {
    if (!open || !props.patient) {
      resetForm();
      return;
    }

    const p = props.patient;
    formData.value.id = p.id;
    formData.value.medical_record_number = p.medical_record_number || p.no_rm || '';
    formData.value.nik = p.nik || p.no_ktp || '';
    formData.value.name = p.name || p.nama || '';
    formData.value.birth_place = p.birth_place || p.tempat_lahir || '';
    formData.value.birth_date = p.birth_date || p.tgl_lahir || '';
    formData.value.gender = p.gender || 'L';
    formData.value.phone = p.phone || p.no_hp || '';
    formData.value.address = p.address || p.alamat || '';
    formData.value.postal_code = p.postal_code || p.kode_pos || '';
    formData.value.guarantor_id = p.guarantor?.id || p.id_jenis_asuransi || 2;
    formData.value.insurance_number = p.no_bpjs || '';
    formData.value.hak_kelas = p.hak_kelas || '';
    formData.value.has_allergy = p.alergi === 'ada' ? 'ada' : 'tidak';
    formData.value.allergy_notes = p.ket_alergi || '';
    formData.value.is_fast_track = p.fast_track === 'ya' ? 'ya' : 'tidak';

    // Fetch full patient data from backend if available
    try {
      loading.value = true;
      const res = await patientService.getPatient(p.id);
      const detail = res.data?.data;
      if (detail) {
        formData.value.name = detail.name || formData.value.name;
        formData.value.nik = detail.nik || formData.value.nik;
        formData.value.birth_place = detail.birth_place || formData.value.birth_place;
        formData.value.birth_date = detail.birth_date || formData.value.birth_date;
        formData.value.gender = detail.gender || formData.value.gender;
        formData.value.phone = detail.phone || formData.value.phone;
        formData.value.address = detail.address || formData.value.address;
        formData.value.postal_code = detail.postal_code || formData.value.postal_code;
        formData.value.guarantor_id = detail.guarantor?.id || formData.value.guarantor_id;
        formData.value.insurance_number = detail.insurance_number || detail.no_bpjs || formData.value.insurance_number;
        formData.value.hak_kelas = detail.hak_kelas || formData.value.hak_kelas;
        formData.value.has_allergy = detail.allergies?.status === 'ada' || detail.has_allergy === 'ada' ? 'ada' : 'tidak';
        formData.value.allergy_notes = detail.allergies?.notes || detail.allergy_notes || '';
        formData.value.is_fast_track = detail.is_fast_track === 'ya' || detail.fast_track === 'ya' ? 'ya' : 'tidak';

        if (detail.emergency_contact) {
          formData.value.emergency_contact = {
            name: detail.emergency_contact.name || '',
            relationship: detail.emergency_contact.relationship || '',
            phone: detail.emergency_contact.phone || '',
            address: detail.emergency_contact.address || ''
          };
        }
      }
    } catch {
      // Keep initial mapped values
    } finally {
      loading.value = false;
    }
  },
  { immediate: true }
);

const isBpjsOrInsurance = computed(() => {
  const selected = (props.guarantorOptions || []).find((g) => String(g.id) === String(formData.value.guarantor_id));
  if (!selected) return false;
  const name = String(selected.name || selected.jenis_asuransi || '').toUpperCase();
  const type = String(selected.type || '').toUpperCase();
  return type === 'BPJS' || type === 'PRIVATE' || name.includes('BPJS') || name.includes('JKN') || name.includes('ASURANSI');
});

const isCheckingBpjs = ref(false);
const bpjsCheckError = ref('');

const handleCheckBpjs = () => {
  const card = formData.value.insurance_number ? formData.value.insurance_number.trim() : '';
  if (!card || card.length < 8) {
    bpjsCheckError.value = 'No.Kartu Tidak Sesuai';
    return;
  }
  bpjsCheckError.value = '';
  if (!formData.value.hak_kelas) {
    formData.value.hak_kelas = 'Kelas 1';
  }
};

const handleSave = async () => {
  errorMessage.value = '';

  if (!formData.value.name || !formData.value.name.trim()) {
    errorMessage.value = 'Nama lengkap pasien wajib diisi.';
    return;
  }

  if (!formData.value.phone || !formData.value.phone.trim()) {
    errorMessage.value = 'Nomor telepon/HP pasien wajib diisi.';
    return;
  }

  if (!formData.value.address || !formData.value.address.trim()) {
    errorMessage.value = 'Alamat lengkap pasien wajib diisi.';
    return;
  }

  const payload = {
    name: formData.value.name.trim(),
    phone: formData.value.phone.trim(),
    address: formData.value.address.trim(),
    postal_code: formData.value.postal_code ? formData.value.postal_code.trim() : null,
    guarantor_id: Number(formData.value.guarantor_id) || 2,
    insurance_number: formData.value.insurance_number ? formData.value.insurance_number.trim() : null,
    hak_kelas: isBpjsOrInsurance.value ? (formData.value.hak_kelas ? formData.value.hak_kelas.trim() : null) : null,
    has_allergy: formData.value.has_allergy,
    allergy_notes: formData.value.has_allergy === 'ada' ? formData.value.allergy_notes : null,
    is_fast_track: formData.value.is_fast_track,
    emergency_contact: {
      name: formData.value.emergency_contact.name ? formData.value.emergency_contact.name.trim() : null,
      relationship: formData.value.emergency_contact.relationship ? formData.value.emergency_contact.relationship.trim() : null,
      phone: formData.value.emergency_contact.phone ? formData.value.emergency_contact.phone.trim() : null,
      address: formData.value.emergency_contact.address ? formData.value.emergency_contact.address.trim() : null
    }
  };

  submitting.value = true;
  try {
    const res = await patientService.updatePatient(formData.value.id, payload);
    const updated = res.data?.data || { id: formData.value.id, ...payload };
    emit('save', { id: formData.value.id, payload });
    emit('saved', updated);
    emit('close');
  } catch (err) {
    errorMessage.value =
      err.response?.data?.message || err.message || 'Terjadi kesalahan saat menyimpan perubahan data pasien.';
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <div v-if="isOpen && patient" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-lg" role="dialog" aria-modal="true" aria-labelledby="modal-edit-patient-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left-title">
          <div class="header-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
          </div>
          <div>
            <h3 id="modal-edit-patient-title">Edit Data Pasien</h3>
            <p class="header-sub">
              No. RM: <strong class="font-mono text-primary">{{ formData.medical_record_number }}</strong>
              <span v-if="formData.nik"> &bull; NIK: <span class="font-mono">{{ formData.nik }}</span></span>
            </p>
          </div>
        </div>

        <button
          type="button"
          class="btn-close"
          :disabled="submitting"
          aria-label="Tutup modal"
          @click="emit('close')"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <div v-if="errorMessage" class="alert-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <span>Memuat data pasien dari database...</span>
        </div>

        <form v-else autocomplete="off" class="patient-edit-form" @submit.prevent="handleSave">
          <!-- Section 1: Identitas Pasien -->
          <div class="form-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span>Identitas Pasien</span>
            </h4>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label" for="edit-patient-rm">
                  Nomor Rekam Medis (RM) <span class="badge-lock">Terkunci</span>
                </label>
                <input
                  id="edit-patient-rm"
                  :value="formData.medical_record_number"
                  type="text"
                  class="form-input disabled-input font-mono"
                  disabled
                  aria-label="Nomor Rekam Medis terkunci"
                />
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-nik">
                  NIK Pasien
                </label>
                <input
                  id="edit-patient-nik"
                  :value="formData.nik || '—'"
                  type="text"
                  class="form-input disabled-input font-mono"
                  disabled
                  aria-label="NIK Pasien"
                />
              </div>

              <div class="form-group full-width">
                <label class="form-label" for="edit-patient-name">
                  Nama Lengkap Pasien <span class="text-danger">*</span>
                </label>
                <input
                  id="edit-patient-name"
                  v-model="formData.name"
                  type="text"
                  class="form-input"
                  placeholder="Masukkan nama lengkap pasien..."
                  required
                />
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-phone">
                  Nomor Telepon / HP <span class="text-danger">*</span>
                </label>
                <input
                  id="edit-patient-phone"
                  v-model="formData.phone"
                  type="tel"
                  class="form-input font-mono"
                  placeholder="Contoh: 08123456789"
                  required
                />
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-postcode">
                  Kode Pos
                </label>
                <input
                  id="edit-patient-postcode"
                  v-model="formData.postal_code"
                  type="text"
                  class="form-input font-mono"
                  placeholder="Kode pos..."
                />
              </div>

              <div class="form-group full-width">
                <label class="form-label" for="edit-patient-address">
                  Alamat Lengkap <span class="text-danger">*</span>
                </label>
                <textarea
                  id="edit-patient-address"
                  v-model="formData.address"
                  class="form-textarea"
                  rows="2"
                  placeholder="Alamat domisili pasien..."
                  required
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Section 2: Jaminan & Status Klinis -->
          <div class="form-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
              <span>Jaminan & Informasi Klinis</span>
            </h4>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label" for="edit-patient-guarantor">
                  Jenis Penjamin / Asuransi <span class="text-danger">*</span>
                </label>
                <select
                  id="edit-patient-guarantor"
                  v-model="formData.guarantor_id"
                  class="form-select"
                  required
                >
                  <option
                    v-for="g in guarantorOptions"
                    :key="g.id"
                    :value="g.id"
                  >
                    {{ g.name || g.jenis_asuransi }}
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-insurance-no">
                  No. Jaminan <span v-if="isBpjsOrInsurance" class="text-danger">*</span>
                </label>
                <div class="input-action-group">
                  <input
                    id="edit-patient-insurance-no"
                    v-model="formData.insurance_number"
                    type="text"
                    class="form-input font-mono"
                    :placeholder="isBpjsOrInsurance ? 'No jaminan' : 'Tidak diperlukan untuk pasien Umum'"
                    @input="bpjsCheckError = ''"
                  />
                  <button
                    v-if="isBpjsOrInsurance"
                    type="button"
                    class="btn-action-cek"
                    :disabled="isCheckingBpjs"
                    @click="handleCheckBpjs"
                    title="Klik untuk mengecek kartu dan mengisi hak kelas"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span>Cek</span>
                  </button>
                </div>
                <div v-if="bpjsCheckError" class="bpjs-warning-box">
                  <span>{{ bpjsCheckError }}</span>
                </div>
              </div>

              <!-- Hak Kelas (Khusus Penjamin Non-Umum/Gratis) -->
              <div v-if="isBpjsOrInsurance" class="form-group">
                <label class="form-label" for="edit-patient-hak-kelas">
                  Hak Kelas <span class="text-danger">*</span>
                </label>
                <input
                  id="edit-patient-hak-kelas"
                  v-model="formData.hak_kelas"
                  type="text"
                  class="form-input"
                  placeholder="Hak Kelas"
                  autocomplete="off"
                />
                <span class="field-hint-text text-danger-hint">*) Klik tombol Cek untuk mengisi hak kelas</span>
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-allergy">
                  Riwayat Alergi Obat / Makanan
                </label>
                <select
                  id="edit-patient-allergy"
                  v-model="formData.has_allergy"
                  class="form-select"
                >
                  <option value="tidak">Tidak Ada Alergi</option>
                  <option value="ada">Ada Alergi</option>
                </select>
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-patient-fasttrack">
                  Prioritas Pelayanan (Fast Track)
                </label>
                <select
                  id="edit-patient-fasttrack"
                  v-model="formData.is_fast_track"
                  class="form-select"
                >
                  <option value="tidak">Tidak (Reguler)</option>
                  <option value="ya">Ya (Fast Track / Lansia / Darurat)</option>
                </select>
              </div>

              <div v-if="formData.has_allergy === 'ada'" class="form-group full-width">
                <label class="form-label" for="edit-patient-allergy-notes">
                  Catatan Alergi Pasien <span class="text-danger">*</span>
                </label>
                <input
                  id="edit-patient-allergy-notes"
                  v-model="formData.allergy_notes"
                  type="text"
                  class="form-input"
                  placeholder="Sebutkan jenis alergi (contoh: Amoxicillin, Paracetamol)..."
                  required
                />
              </div>
            </div>
          </div>

          <!-- Section 3: Kontak Darurat / Penanggung Jawab -->
          <div class="form-section">
            <h4 class="section-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              <span>Kontak Darurat / Penanggung Jawab</span>
            </h4>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label" for="edit-emergency-name">
                  Nama Penanggung Jawab
                </label>
                <input
                  id="edit-emergency-name"
                  v-model="formData.emergency_contact.name"
                  type="text"
                  class="form-input"
                  placeholder="Nama keluarga / wali..."
                />
              </div>

              <div class="form-group">
                <label class="form-label" for="edit-emergency-relation">
                  Hubungan Keluarga
                </label>
                <input
                  id="edit-emergency-relation"
                  v-model="formData.emergency_contact.relationship"
                  type="text"
                  class="form-input"
                  placeholder="Orang Tua / Pasangan / Anak / Saudara..."
                />
              </div>

              <div class="form-group full-width">
                <label class="form-label" for="edit-emergency-phone">
                  Nomor HP Penanggung Jawab
                </label>
                <input
                  id="edit-emergency-phone"
                  v-model="formData.emergency_contact.phone"
                  type="tel"
                  class="form-input font-mono"
                  placeholder="Nomor HP keluarga..."
                />
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button
          type="button"
          class="btn-cancel"
          :disabled="submitting"
          @click="emit('close')"
        >
          Batal
        </button>

        <button
          type="button"
          class="btn-primary"
          :disabled="submitting || loading"
          @click="handleSave"
        >
          <span v-if="submitting" class="btn-spinner"></span>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>{{ submitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1.25rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalScale 0.18s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.97);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-lg {
  max-width: 780px;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.header-left-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.header-icon-box {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.header-icon-box svg {
  width: 20px;
  height: 20px;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.header-sub {
  margin: 0.2rem 0 0;
  font-size: 0.825rem;
  color: var(--color-text-secondary);
}

.btn-close {
  background: transparent;
  border: none;
  color: var(--color-text-muted);
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-close:hover:not(:disabled) {
  background: #e2e8f0;
  color: var(--color-text-navy);
}

.modal-body {
  padding: 1.4rem 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.35rem;
}

.alert-error {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  font-size: 0.85rem;
}

.alert-error svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  gap: 0.75rem;
  color: var(--color-text-secondary);
  font-size: 0.875rem;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.form-section {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding-bottom: 1.15rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.form-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.section-title {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--color-text-navy);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-title svg {
  width: 16px;
  height: 16px;
  color: var(--color-primary);
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.9rem;
}

.full-width {
  grid-column: 1 / -1;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.badge-lock {
  font-size: 0.7rem;
  font-weight: 500;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  background: #f1f5f9;
  color: #64748b;
}

.form-input,
.form-select,
.form-textarea {
  height: 38px;
  padding: 0 0.8rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.85rem;
  font-family: inherit;
  transition: all 0.15s ease;
}

.form-textarea {
  height: auto;
  padding: 0.6rem 0.8rem;
  resize: vertical;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.input-action-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.input-action-group .form-input {
  flex: 1;
}

.btn-action-cek {
  height: 38px;
  padding: 0 1.1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  background: #e05260;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(224, 82, 96, 0.25);
  transition: all 0.15s ease;
  box-sizing: border-box;
}

.btn-action-cek svg {
  width: 14px;
  height: 14px;
}

.btn-action-cek:hover:not(:disabled) {
  background: #c53b49;
  box-shadow: 0 4px 10px rgba(224, 82, 96, 0.35);
}

.btn-action-cek:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.bpjs-warning-box {
  margin-top: 0.45rem;
  padding: 0.5rem 0.75rem;
  background: #fee2e2;
  border: 1px solid #fca5a5;
  border-radius: 6px;
  color: #dc2626;
  font-size: 0.82rem;
  font-weight: 600;
}

.text-danger-hint {
  font-size: 0.78rem;
  color: #dc2626;
  font-style: italic;
  margin-top: 0.35rem;
  display: block;
}

.disabled-input {
  background: #f8fafc;
  color: #64748b;
  cursor: not-allowed;
  border-color: #e2e8f0;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.text-danger {
  color: var(--color-danger);
}

.text-primary {
  color: var(--color-primary);
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  background: #f8fafc;
}

.btn-cancel {
  height: 38px;
  padding: 0 1.25rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  color: var(--color-text-navy);
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel:hover:not(:disabled) {
  background: #f1f5f9;
}

.btn-primary {
  height: 38px;
  padding: 0 1.35rem;
  border-radius: 6px;
  border: none;
  background: var(--color-primary);
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-primary:disabled,
.btn-cancel:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@media (max-width: 640px) {
  .form-grid-2 {
    grid-template-columns: 1fr;
  }
}
</style>
