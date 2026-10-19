<script setup>
import { ref } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  bill: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'confirm']);

const notes = ref('Mohon lengkapi tanda tangan resume medis dan rincian alkes farmasi untuk verifikasi klaim tagihan.');

const handleConfirm = () => {
  emit('confirm', { billId: props.bill.id, notes: notes.value });
};
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="notif-title">
      <!-- Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="icon-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
          </div>
          <div>
            <h3 id="notif-title" class="title">Notifikasi Kelengkapan ke Poliklinik</h3>
            <p class="subtitle font-mono">{{ bill.invoice_number }} &bull; {{ bill.polyclinic }}</p>
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
        <div class="patient-line">
          <span class="patient-name font-semibold">{{ bill.patient_name }}</span>
          <span class="text-muted text-xs font-mono">{{ bill.medical_record_number }} &bull; Dokter: {{ bill.doctor }}</span>
        </div>

        <div class="form-group">
          <label class="form-label font-semibold">Pesan Notifikasi ke Petugas / Dokter Poli:</label>
          <textarea
            v-model="notes"
            rows="3"
            class="form-textarea"
            placeholder="Tuliskan catatan kelengkapan dokumen atau tindakan yang diperlukan..."
          ></textarea>
        </div>

        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi Internal:</strong> Pesan ini hanya menghasilkan feedback notifikasi lokal dan tidak mengirimkan pesan WhatsApp, SMS, maupun integrasi sistem eksternal.
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Batal
        </button>
        <button type="button" class="btn-primary" @click="handleConfirm">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
          <span>Kirim Notifikasi Simulasi</span>
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

.icon-amber {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: #fef3c7;
  color: #d97706;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-amber svg {
  width: 20px;
  height: 20px;
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

.patient-line {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.patient-name {
  color: #0f172a;
  font-size: 0.95rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-label {
  font-size: 0.8rem;
  color: #334155;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  font-size: 0.825rem;
  color: #1e293b;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-family: inherit;
  resize: vertical;
}

.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 2px rgba(8, 127, 120, 0.15);
}

.simulation-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.75rem 0.85rem;
  background: #f0fdfa;
  border: 1px solid #ccfbf1;
  border-radius: 8px;
  color: #0f766e;
  font-size: 0.775rem;
  line-height: 1.4;
}

.simulation-alert svg {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-secondary, .btn-primary {
  height: 38px;
  padding: 0 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  transition: all 0.15s ease;
}

.btn-secondary {
  background: #ffffff;
  color: #475569;
  border: 1px solid #cbd5e1;
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  color: #ffffff;
  border: none;
}

.btn-primary:hover {
  background: var(--color-primary-dark, #06635e);
}

.btn-primary svg {
  width: 16px;
  height: 16px;
}
</style>
