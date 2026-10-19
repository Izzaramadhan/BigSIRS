<script setup>
import { formatRupiah } from '@/utils/billingFormatters';

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
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="verify-modal-title">
      <!-- Modal Header -->
      <div class="modal-header">
        <div class="header-left">
          <div class="header-icon icon-amber">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="9 11 12 14 22 4"></polyline>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
          </div>
          <div>
            <h3 id="verify-modal-title" class="modal-title">Verifikasi Dokumen Klaim & Tagihan</h3>
            <p class="modal-subtitle">{{ bill.invoice_number }} &bull; {{ bill.visit_number }}</p>
          </div>
        </div>

        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <div class="verify-summary-box">
          <div class="patient-line">
            <h4 class="patient-name">{{ bill.patient_name }}</h4>
            <span class="rm-badge font-mono">{{ bill.medical_record_number }}</span>
          </div>

          <div class="verify-grid">
            <div class="grid-item">
              <span class="item-label">Poliklinik & DPJP:</span>
              <span class="item-val">{{ bill.polyclinic }} &bull; {{ bill.doctor }}</span>
            </div>
            <div class="grid-item">
              <span class="item-label">Jenis Penjamin:</span>
              <span class="item-val font-semibold">{{ bill.guarantor_type }} ({{ bill.guarantor_detail }})</span>
            </div>
            <div class="grid-item">
              <span class="item-label">Total Tagihan:</span>
              <span class="item-val font-mono font-bold">{{ formatRupiah(bill.total_amount) }}</span>
            </div>
            <div class="grid-item">
              <span class="item-label">Status SEP / Klaim:</span>
              <span class="item-val text-teal font-semibold">{{ bill.sep_status }}</span>
            </div>
          </div>
        </div>

        <!-- Verification Checklist -->
        <div class="checklist-box">
          <h5 class="checklist-title">Checklist Validasi Kelengkapan Berkas</h5>
          <label class="check-item">
            <input type="checkbox" checked class="form-checkbox" disabled />
            <span>Kesesuaian Diagnosis ICD-10 & Resume Medis Elektronik (RME)</span>
          </label>
          <label class="check-item">
            <input type="checkbox" checked class="form-checkbox" disabled />
            <span>Kesesuaian Rincian Tindakan Poliklinik & Tarif Ina-CBG</span>
          </label>
          <label class="check-item">
            <input type="checkbox" checked class="form-checkbox" disabled />
            <span>Dokumen SEP & Surat Rujukan Faskes 1 Terverifikasi</span>
          </label>
        </div>

        <!-- Notice -->
        <div class="simulation-alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
          </svg>
          <div class="alert-text">
            <strong>Mode Simulasi Verifikasi:</strong> Verifikasi ini akan mengubah status tagihan menjadi <em>Siap Dibayar</em> di antarmuka kasir tanpa melakukan request bridging BPJS E-Klaim nyata.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn-secondary" @click="emit('close')">
          Batal
        </button>
        <button type="button" class="btn-primary" @click="emit('confirm', bill.id)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>Konfirmasi Verifikasi Simulasi</span>
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
  max-width: 560px;
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
  gap: 0.85rem;
  align-items: center;
}

.header-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-amber {
  background: #fef3c7;
  color: #d97706;
}

.icon-amber svg {
  width: 20px;
  height: 20px;
}

.modal-title {
  margin: 0 0 0.2rem 0;
  font-size: 1.15rem;
  font-weight: 700;
  color: #0f172a;
}

.modal-subtitle {
  margin: 0;
  font-size: 0.8rem;
  color: #64748b;
  font-family: monospace;
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

.verify-summary-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.patient-line {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px dashed #cbd5e1;
  padding-bottom: 0.5rem;
}

.patient-name {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
}

.rm-badge {
  font-size: 0.8rem;
  color: #475569;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  padding: 0.15rem 0.5rem;
  border-radius: 4px;
}

.verify-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem 1rem;
  font-size: 0.8rem;
}

.grid-item {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.item-label {
  color: #64748b;
  font-size: 0.72rem;
}

.item-val {
  color: #1e293b;
}

.text-teal {
  color: var(--color-primary, #087f78);
}

.checklist-box {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.checklist-title {
  margin: 0 0 0.25rem 0;
  font-size: 0.8rem;
  font-weight: 700;
  color: #334155;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.8rem;
  color: #334155;
  cursor: default;
}

.form-checkbox {
  width: 16px;
  height: 16px;
  accent-color: var(--color-primary, #087f78);
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
</style>
