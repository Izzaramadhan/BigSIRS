<script setup>
import { maskNik, formatGender } from '@/utils/patientFormatters';

defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  admission: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'edit', 'print']);
</script>

<template>
  <div v-if="isOpen && admission" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-lg" role="dialog" aria-modal="true" aria-labelledby="modal-detail-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-info">
          <div class="header-badges">
            <span class="queue-pill">{{ admission.queue_number }}</span>
            <span class="rm-pill">{{ admission.medical_record_number }}</span>
            <span 
              class="status-pill"
              :class="{
                'pill-waiting': admission.status === 'Menunggu',
                'pill-progress': admission.status === 'Sedang Diperiksa',
                'pill-done': admission.status === 'Selesai',
                'pill-void': admission.status === 'Dibatalkan'
              }"
            >
              {{ admission.status }}
            </span>
          </div>
          <h3 id="modal-detail-title" class="patient-title">{{ admission.patient_name }}</h3>
          <p class="header-sub">Waktu Pendaftaran: {{ admission.admission_time }} &bull; Tanggal: {{ admission.admission_date }}</p>
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
        <!-- Section 1: Data Pasien & Identitas -->
        <div class="detail-section-card">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Identitas Pasien</span>
          </h4>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Nama Lengkap Pasien</span>
              <span class="detail-val font-semibold">{{ admission.patient_name }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Nomor Rekam Medis (RM)</span>
              <span class="detail-val font-mono font-bold text-primary">{{ admission.medical_record_number }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">NIK Pasien</span>
              <span class="detail-val font-mono">{{ maskNik(admission.nik) }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Jenis Kelamin & Usia</span>
              <span class="detail-val">{{ formatGender(admission.gender) }} &bull; {{ admission.age }} Tahun</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Tanggal Lahir</span>
              <span class="detail-val">{{ admission.birth_date }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Nomor Telepon</span>
              <span class="detail-val">{{ admission.phone || '—' }}</span>
            </div>
          </div>
        </div>

        <!-- Section 2: Informasi Pelayanan & Dokter -->
        <div class="detail-section-card">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Tujuan Pelayanan Medis</span>
          </h4>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Poliklinik Tujuan</span>
              <span class="detail-val font-semibold">{{ admission.polyclinic }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Dokter Penanggung Jawab (DPJP)</span>
              <span class="detail-val">{{ admission.doctor }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Nomor Antrean Pelayanan</span>
              <span class="detail-val font-mono font-bold">{{ admission.queue_number }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Status Pelayanan Saat Ini</span>
              <span class="detail-val font-medium">{{ admission.status }}</span>
            </div>

            <div class="detail-item full-width">
              <span class="detail-label">Catatan / Keluhan Pendaftaran</span>
              <span class="detail-val note-text">{{ admission.notes || '—' }}</span>
            </div>
          </div>
        </div>

        <!-- Section 3: Penjamin Biaya & General Consent -->
        <div class="detail-section-card">
          <h4 class="section-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="5" width="20" height="14" rx="2"></rect>
              <line x1="2" y1="10" x2="22" y2="10"></line>
            </svg>
            <span>Penjamin & Persetujuan Umum (General Consent)</span>
          </h4>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-label">Jenis Penjamin</span>
              <span class="detail-val font-semibold">{{ admission.guarantor_type }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Detail / Sub Penjamin</span>
              <span class="detail-val">{{ admission.guarantor_detail }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Status General Consent</span>
              <span class="detail-val font-semibold" :class="admission.general_consent === 'Sudah TTD' ? 'text-green' : 'text-amber'">
                {{ admission.general_consent }}
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-label">Kontak Darurat Keluarga</span>
              <span class="detail-val">{{ admission.emergency_contact?.name || '—' }} ({{ admission.emergency_contact?.phone || '—' }})</span>
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
          v-if="admission.status !== 'Dibatalkan'"
          type="button" 
          class="btn-outline" 
          @click="emit('edit', admission)"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
          <span>Edit Admisi</span>
        </button>

        <button 
          type="button" 
          class="btn-primary" 
          @click="emit('print', { item: admission, documentType: 'No. Antrean' })"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Cetak Dokumen</span>
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
  margin-bottom: 0.2rem;
}

.queue-pill {
  background: var(--color-primary);
  color: #ffffff;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.rm-pill {
  background: #f1f5f9;
  color: var(--color-text-navy);
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  border: 1px solid var(--color-border-soft);
}

.status-pill {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.55rem;
  border-radius: 9999px;
}

.pill-waiting {
  background: #fef3c7;
  color: #b45309;
}
.pill-progress {
  background: #e0f2fe;
  color: #0369a1;
}
.pill-done {
  background: #dcfce7;
  color: #166534;
}
.pill-void {
  background: #fee2e2;
  color: #991b1b;
}

.patient-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.header-sub {
  margin: 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.detail-section-card {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  padding: 1.15rem 1.25rem;
  margin-bottom: 1.25rem;
}

.detail-section-card:last-child {
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
  padding-bottom: 0.45rem;
}

.section-title svg {
  width: 16px;
  height: 16px;
  color: var(--color-primary);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.85rem 1.5rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-label {
  font-size: 0.725rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-val {
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.note-text {
  background: #ffffff;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft);
  font-size: 0.85rem;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.text-primary {
  color: var(--color-primary);
}

.text-green {
  color: #15803d;
}

.text-amber {
  color: #b45309;
}

@media (max-width: 640px) {
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
