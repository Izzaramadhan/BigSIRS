<script setup>
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

const emit = defineEmits(['close']);
</script>

<template>
  <div v-if="isOpen && bill" class="modal-backdrop" @click.self="emit('close')">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="cancel-title">
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
            <h3 id="cancel-title" class="title">Catatan Pembatalan Tagihan</h3>
            <p class="subtitle font-mono">{{ bill.invoice_number }} &bull; {{ bill.visit_number }}</p>
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
          <h4 class="patient-name">{{ bill.patient_name }}</h4>
          <span class="font-mono text-muted text-xs">{{ bill.medical_record_number }} &bull; {{ bill.polyclinic }}</span>
        </div>

        <div class="reason-box">
          <div class="reason-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
            <span class="reason-title">Alasan Pembatalan Tagihan:</span>
          </div>
          <p class="reason-text">
            {{ bill.cancellation_reason || 'Pemeriksaan dibatalkan atau dialihkan atas konfirmasi poliklinik / keluarga pasien.' }}
          </p>
        </div>

        <div class="note-box">
          Tagihan berstatus dibatalkan tidak diperhitungkan ke dalam total pendapatan shift kasir hari ini.
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
  max-width: 500px;
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
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.patient-name {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0f172a;
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

.reason-head {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: #be123c;
}

.reason-head svg {
  width: 16px;
  height: 16px;
}

.reason-title {
  font-size: 0.775rem;
  font-weight: 700;
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
}

.btn-secondary:hover {
  background: #f1f5f9;
  color: #0f172a;
}
</style>
