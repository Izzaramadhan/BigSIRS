<script setup>
import { computed } from 'vue';
import { maskNik } from '@/utils/patientFormatters';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  admission: {
    type: Object,
    default: () => null
  },
  documentType: {
    type: String,
    default: 'No. Antrean'
  }
});

const emit = defineEmits(['close', 'confirm-print']);

const documentIcon = computed(() => {
  switch (props.documentType) {
    case 'No. Antrean':
      return 'ticket';
    case 'Tracer Rekam Medis':
      return 'file-text';
    case 'Label Barcode Pasien':
      return 'barcode';
    case 'Formulir General Consent':
      return 'file-check';
    default:
      return 'printer';
  }
});
</script>

<template>
  <div v-if="isOpen && admission" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-md" role="dialog" aria-modal="true" aria-labelledby="modal-print-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-icon-title">
          <div class="print-icon-circle">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 6 2 18 2 18 9"></polyline>
              <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
              <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
          </div>
          <div>
            <h3 id="modal-print-title">Pratinjau Dokumen Cetak</h3>
            <p class="header-sub">{{ documentType }} &bull; BigSIRS SIMRS</p>
          </div>
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

      <!-- Modal Body (Paper-like preview card) -->
      <div class="modal-body">
        <div class="paper-preview-container">
          <div class="hospital-letterhead">
            <strong>RS SISFOMEDIKA UTAMA</strong>
            <small>Jl. Kesehatan No. 1, Yogyakarta &bull; Telp: (0274) 555-123</small>
            <div class="letterhead-divider"></div>
          </div>

          <h4 class="doc-preview-heading">{{ documentType.toUpperCase() }}</h4>

          <!-- Preview Content for No. Antrean -->
          <div v-if="documentType === 'No. Antrean'" class="queue-ticket-view">
            <div class="ticket-queue-number">{{ admission.queue_number }}</div>
            <div class="ticket-poly">{{ admission.polyclinic }}</div>
            <div class="ticket-doctor">{{ admission.doctor }}</div>
            <div class="ticket-meta">
              <span>RM: {{ admission.medical_record_number }}</span>
              <span>{{ admission.admission_time }}</span>
            </div>
            <div class="ticket-footer">Harap menunggu nomor antrean Anda dipanggil di ruang tunggu.</div>
          </div>

          <!-- Preview Content for Tracer Rekam Medis -->
          <div v-else-if="documentType === 'Tracer Rekam Medis'" class="tracer-view">
            <div class="tracer-grid">
              <div class="tracer-item"><span>No. RM:</span> <strong>{{ admission.medical_record_number }}</strong></div>
              <div class="tracer-item"><span>Nama Pasien:</span> <strong>{{ admission.patient_name }}</strong></div>
              <div class="tracer-item"><span>NIK:</span> <span>{{ maskNik(admission.nik) }}</span></div>
              <div class="tracer-item"><span>Tujuan Poli:</span> <span>{{ admission.polyclinic }}</span></div>
              <div class="tracer-item"><span>DPJP:</span> <span>{{ admission.doctor }}</span></div>
              <div class="tracer-item"><span>Penjamin:</span> <span>{{ admission.guarantor_type }} ({{ admission.guarantor_detail }})</span></div>
            </div>
            <div class="tracer-barcode-placeholder">
              <div class="barcode-lines">||||| ||||||| |||||| |||||||| |||||</div>
              <span>*{{ admission.medical_record_number }}*</span>
            </div>
          </div>

          <!-- Preview Content for Label Barcode -->
          <div v-else-if="documentType === 'Label Barcode Pasien'" class="barcode-view">
            <div class="barcode-box">
              <div class="barcode-patient-name">{{ admission.patient_name }}</div>
              <div class="barcode-patient-sub">{{ admission.medical_record_number }} &bull; {{ admission.gender === 'L' ? 'LK' : 'PR' }} ({{ admission.age }} th)</div>
              <div class="barcode-graphic">|||||||||||||||||||||||||||||||||||||||||||||||</div>
              <div class="barcode-id">{{ admission.medical_record_number }}</div>
            </div>
          </div>

          <!-- Preview Content for General Consent -->
          <div v-else class="consent-view">
            <p class="consent-text">
              Formulir Persetujuan Umum Rawat Jalan (General Consent for Outpatient Care) telah terverifikasi secara elektronik untuk pasien <strong>{{ admission.patient_name }}</strong> (No. RM: {{ admission.medical_record_number }}).
            </p>
            <div class="consent-status-box">
              <span>Status Persetujuan:</span>
              <strong class="text-green">{{ admission.general_consent }}</strong>
            </div>
          </div>
        </div>

        <div class="print-simulation-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <span>Fitur cetak ini dalam mode simulasi frontend (tanpa koneksi perangkat printer fisik).</span>
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
          @click="emit('confirm-print', { documentType, admission })"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          <span>Simulasi Cetak</span>
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-md {
  max-width: 560px;
}

.header-icon-title {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.print-icon-circle {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
}

.print-icon-circle svg {
  width: 20px;
  height: 20px;
}

.header-sub {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.paper-preview-container {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  margin-bottom: 1rem;
}

.hospital-letterhead {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.hospital-letterhead strong {
  font-size: 0.95rem;
  color: var(--color-text-navy);
}

.letterhead-divider {
  height: 2px;
  background: #17243d;
  margin: 0.6rem 0 1rem 0;
}

.doc-preview-heading {
  text-align: center;
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 1.25rem 0;
  letter-spacing: 0.05em;
}

/* Ticket View */
.queue-ticket-view {
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
}

.ticket-queue-number {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 2.75rem;
  font-weight: 800;
  color: var(--color-primary-dark);
  line-height: 1;
  margin-bottom: 0.5rem;
}

.ticket-poly {
  font-weight: 700;
  font-size: 1rem;
  color: var(--color-text-navy);
}

.ticket-doctor {
  font-size: 0.85rem;
  color: var(--color-text-secondary);
}

.ticket-meta {
  display: flex;
  justify-content: space-between;
  width: 100%;
  max-width: 280px;
  padding-top: 0.75rem;
  margin-top: 0.75rem;
  border-top: 1px dashed #cbd5e1;
  font-size: 0.8rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.ticket-footer {
  margin-top: 0.75rem;
  font-size: 0.725rem;
  color: #94a3b8;
  font-style: italic;
}

/* Tracer View */
.tracer-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.45rem;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.tracer-item {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px dotted #e2e8f0;
  padding-bottom: 0.25rem;
}

.tracer-barcode-placeholder {
  text-align: center;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 6px;
}

.barcode-lines {
  font-family: monospace;
  font-size: 1.25rem;
  letter-spacing: 0.15em;
}

/* Barcode View */
.barcode-box {
  border: 1px dashed #94a3b8;
  padding: 0.85rem;
  text-align: center;
  border-radius: 6px;
  background: #f8fafc;
}

.barcode-patient-name {
  font-weight: 700;
  font-size: 0.95rem;
}

.barcode-patient-sub {
  font-size: 0.8rem;
  color: var(--color-text-secondary);
  margin-bottom: 0.5rem;
}

.barcode-graphic {
  font-family: monospace;
  font-size: 1.15rem;
  letter-spacing: 0.1em;
}

.barcode-id {
  font-size: 0.75rem;
  font-family: monospace;
  margin-top: 0.2rem;
}

/* Consent View */
.consent-text {
  font-size: 0.85rem;
  color: var(--color-text-navy);
  line-height: 1.5;
  margin-bottom: 1rem;
}

.consent-status-box {
  display: flex;
  justify-content: space-between;
  background: #f8fafc;
  padding: 0.75rem;
  border-radius: 6px;
  font-size: 0.85rem;
}

.text-green {
  color: #15803d;
}

.print-simulation-note {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
  font-size: 0.775rem;
  padding: 0.6rem 0.85rem;
  border-radius: 6px;
}

.print-simulation-note svg {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}
</style>
