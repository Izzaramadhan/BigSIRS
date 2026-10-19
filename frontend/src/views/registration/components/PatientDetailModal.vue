<script setup>
import { 
  formatDateWithAge, 
  formatDateIndo, 
  maskNik, 
  formatGender 
} from '@/utils/patientFormatters';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  patient: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'select']);
</script>

<template>
  <div v-if="isOpen && patient" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-lg" role="dialog" aria-modal="true" aria-labelledby="patient-detail-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-info">
          <div class="header-badges">
            <span class="rm-tag">{{ patient.medical_record_number }}</span>
            <span 
              class="status-pill" 
              :class="patient.is_active ? 'pill-active' : 'pill-inactive'"
            >
              {{ patient.is_active ? 'Pasien Aktif' : 'Pasien Tidak Aktif' }}
            </span>
          </div>
          <h3 id="patient-detail-title">{{ patient.name }}</h3>
          <p class="header-sub">NIK: {{ maskNik(patient.nik) }} &bull; Terdaftar sejak {{ formatDateIndo(patient.registered_at) }}</p>
        </div>

        <button 
          type="button" 
          class="btn-close" 
          @click="emit('close')" 
          aria-label="Tutup modal"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <!-- Section 1: Identitas Pribadi -->
        <div class="detail-card-section">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Identitas Pribadi Pasien</span>
          </h4>

          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Nomor Rekam Medis</span>
              <span class="info-val font-bold font-primary">{{ patient.medical_record_number }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Nomor Induk Kependudukan (NIK)</span>
              <span class="info-val font-mono">{{ maskNik(patient.nik) }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Nama Lengkap Pasien</span>
              <span class="info-val font-semibold">{{ patient.name }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Jenis Kelamin</span>
              <span class="info-val">{{ formatGender(patient.gender) }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Tanggal Lahir & Usia</span>
              <span class="info-val">{{ formatDateWithAge(patient.birth_date) }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Golongan Darah</span>
              <span class="info-val badge-blood">{{ patient.blood_type || '—' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Agama</span>
              <span class="info-val">{{ patient.religion || '—' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Status Perkawinan</span>
              <span class="info-val">{{ patient.marital_status || '—' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Pendidikan Terakhir</span>
              <span class="info-val">{{ patient.education || '—' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Pekerjaan</span>
              <span class="info-val">{{ patient.occupation || '—' }}</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Kontak & Alamat -->
        <div class="detail-card-section">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
              <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span>Alamat Domisili & Kontak</span>
          </h4>

          <div class="info-grid">
            <div class="info-item full-width">
              <span class="info-label">Alamat Lengkap</span>
              <span class="info-val">{{ patient.address }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Nomor Telepon / WhatsApp</span>
              <span class="info-val font-mono">{{ patient.phone }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">Kontak Darurat (Keluarga)</span>
              <span class="info-val">
                {{ patient.emergency_contact?.name || '—' }} 
                <small v-if="patient.emergency_contact?.phone" class="text-muted">({{ patient.emergency_contact.phone }})</small>
              </span>
            </div>
          </div>
        </div>

        <!-- Section 3: Asuransi & Rekam Medis Tambahan -->
        <div class="detail-card-section">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="5" width="20" height="14" rx="2"></rect>
              <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
            <span>Penjamin & Catatan Klinis</span>
          </h4>

          <div class="info-grid">
            <div class="info-item">
              <span class="info-label">Penjamin Biaya</span>
              <span class="info-val badge-guarantor">{{ patient.guarantor || 'Umum / Pribadi' }}</span>
            </div>

            <div class="info-item">
              <span class="info-label">No. Kartu / Polis Asuransi</span>
              <span class="info-val font-mono">{{ patient.insurance_number || '—' }}</span>
            </div>

            <div class="info-item full-width">
              <span class="info-label">Riwayat Alergi Pasien</span>
              <span class="info-val" :class="patient.allergies && patient.allergies !== 'Tidak ada alergi' ? 'text-danger font-semibold' : ''">
                {{ patient.allergies || 'Tidak ada catatan alergi' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button 
          type="button" 
          class="btn-cancel" 
          @click="emit('close')"
        >
          Tutup
        </button>

        <button 
          type="button" 
          class="btn-primary" 
          @click="emit('select', patient)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Pilih Pasien Ini</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-lg {
  max-width: 780px;
}

.header-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.header-badges {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.rm-tag {
  background: var(--color-primary);
  color: #ffffff;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.status-pill {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
}

.pill-active {
  background: #dcfce7;
  color: #166534;
}

.pill-inactive {
  background: #f1f5f9;
  color: #64748b;
}

.header-sub {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.detail-card-section {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 1rem 1.25rem;
  margin-bottom: 1.25rem;
}

.detail-card-section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0 0 0.85rem 0;
  font-size: 0.925rem;
  font-weight: 700;
  color: var(--color-text-navy);
  border-bottom: 1px dashed var(--color-border-soft);
  padding-bottom: 0.5rem;
}

.section-title svg {
  width: 16px;
  height: 16px;
  color: var(--color-primary);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.info-val {
  font-size: 0.9rem;
  color: var(--color-text-navy);
  line-height: 1.4;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-primary {
  color: var(--color-primary);
}

.badge-blood {
  display: inline-block;
  background: #fee2e2;
  color: #991b1b;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 4px;
  font-size: 0.8rem;
  width: fit-content;
}

.badge-guarantor {
  display: inline-block;
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
  font-size: 0.825rem;
  width: fit-content;
}

@media (max-width: 640px) {
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
