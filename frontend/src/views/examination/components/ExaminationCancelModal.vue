<script setup>
import { calculateAge } from '@/mocks/examinations';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  patient: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="isOpen && patient" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="cancel-modal-title">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="icon-rose">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
          </div>
          <div>
            <h3 id="cancel-modal-title" class="title">Detail Pembatalan / No Show</h3>
            <p class="subtitle">Informasi pembatalan antrean pemeriksaan poliklinik</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="patient-box">
          <div class="patient-top">
            <span class="queue-chip">{{ patient.queue_number }}</span>
            <div>
              <h4 class="patient-name">{{ patient.patient_name }}</h4>
              <span class="text-muted font-mono text-xs">{{ patient.medical_record_number }} &bull; NIK: {{ patient.nik }}</span>
            </div>
          </div>

          <div class="details-grid">
            <div class="detail-item">
              <span class="label">Poliklinik Tujuan:</span>
              <span class="val font-semibold">{{ patient.polyclinic }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Dokter Pemeriksa:</span>
              <span class="val">{{ patient.doctor }}</span>
            </div>
            <div class="detail-item">
              <span class="label">Waktu Admisi:</span>
              <span class="val font-mono">{{ patient.admission_time }} WIB</span>
            </div>
            <div class="detail-item">
              <span class="label">Penjamin:</span>
              <span class="val">{{ patient.guarantor_type }}</span>
            </div>
          </div>
        </div>

        <!-- Cancellation Reason Box -->
        <div class="reason-box">
          <div class="reason-header">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span class="reason-title">Alasan Pembatalan / Status Berkas:</span>
          </div>
          <p class="reason-text">
            {{ patient.cancellation_reason || 'Pasien batal / rujukan luar RS jejaring atau tidak hadir setelah 3x pemanggilan antrean.' }}
          </p>
        </div>

        <div class="note-box">
          Status pembatalan ini tercatat di riwayat simulasi rawat jalan dan tidak mengubah data rekam medis pasien di database pusat.
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Tutup
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
}

.modal-card {
  width: 100%;
  max-width: 520px;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  animation: modalScale 0.2s ease-out;
}

@keyframes modalScale {
  from {
    opacity: 0;
    transform: scale(0.96);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.modal-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  border-bottom: 1px solid #e2e8f0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.icon-rose {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #ffe4e6;
  color: #e11d48;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-rose svg {
  width: 22px;
  height: 22px;
}

.title {
  margin: 0 0 0.2rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.subtitle {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
}

.btn-close:hover {
  color: #0f172a;
  background: #f1f5f9;
}

.btn-close svg {
  width: 20px;
  height: 20px;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.patient-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.patient-top {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 0.65rem;
  border-bottom: 1px dashed #cbd5e1;
}

.queue-chip {
  padding: 0.25rem 0.6rem;
  background: #fee2e2;
  color: #b91c1c;
  font-family: monospace;
  font-size: 0.95rem;
  font-weight: 700;
  border-radius: 6px;
}

.patient-name {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.4rem 1rem;
  font-size: 0.8rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.label {
  color: #64748b;
  font-size: 0.72rem;
}

.val {
  color: #1e293b;
}

.reason-box {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.reason-header {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #be123c;
}

.reason-header svg {
  width: 16px;
  height: 16px;
}

.reason-title {
  font-size: 0.775rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.reason-text {
  margin: 0;
  font-size: 0.85rem;
  color: #9f1239;
  font-weight: 600;
  line-height: 1.4;
}

.note-box {
  font-size: 0.775rem;
  color: #64748b;
  line-height: 1.4;
}

.modal-footer {
  padding: 0.85rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  height: 36px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}
</style>
