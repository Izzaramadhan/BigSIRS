<script setup>
import { ref } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  admission: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close', 'confirm']);

const cancelReason = ref('Permintaan Pasien / Keluarga');
const customReason = ref('');

const handleConfirm = () => {
  const finalReason = cancelReason.value === 'Lainnya' 
    ? (customReason.value || 'Alasan Lainnya') 
    : cancelReason.value;

  emit('confirm', {
    id: props.admission.id,
    reason: finalReason
  });
};
</script>

<template>
  <div v-if="isOpen && admission" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-sm" role="dialog" aria-modal="true" aria-labelledby="modal-cancel-title">
      <div class="modal-header">
        <div class="cancel-header-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <div>
          <h3 id="modal-cancel-title" class="cancel-title">Batalkan Admisi Pasien?</h3>
          <p class="cancel-subtitle">Tindakan ini akan membatalkan status antrean pelayanan.</p>
        </div>
      </div>

      <div class="modal-body">
        <div class="patient-summary-box">
          <div class="summary-row">
            <span class="label">Pasien:</span>
            <strong>{{ admission.patient_name }}</strong>
          </div>
          <div class="summary-row">
            <span class="label">No. Antrean:</span>
            <span class="queue-text font-bold">{{ admission.queue_number }}</span>
          </div>
          <div class="summary-row">
            <span class="label">No. Rekam Medis:</span>
            <span class="font-mono">{{ admission.medical_record_number }}</span>
          </div>
          <div class="summary-row">
            <span class="label">Poliklinik Tujuan:</span>
            <span>{{ admission.polyclinic }}</span>
          </div>
        </div>

        <div class="form-group cancel-reason-group">
          <label class="form-label" for="cancel-reason-select">Alasan Pembatalan Admisi</label>
          <select id="cancel-reason-select" v-model="cancelReason" class="form-select">
            <option value="Permintaan Pasien / Keluarga">Permintaan Pasien / Keluarga</option>
            <option value="Salah Pilih Poliklinik / Dokter">Salah Pilih Poliklinik / Dokter</option>
            <option value="Pasien Tidak Hadir / Batal Periksa">Pasien Tidak Hadir / Batal Periksa</option>
            <option value="Kendala Administrasi Penjamin">Kendala Administrasi Penjamin</option>
            <option value="Lainnya">Alasan Lainnya...</option>
          </select>
        </div>

        <div v-if="cancelReason === 'Lainnya'" class="form-group">
          <input autocomplete="off" 
            v-model="customReason" 
            type="text" 
            class="form-input" 
            placeholder="Tuliskan alasan pembatalan..." 
          />
        </div>

        <p class="warning-text">
          Data pendaftaran admisi tidak akan dihapus dari sistem, namun statusnya akan diubah menjadi <strong>Dibatalkan</strong>.
        </p>
      </div>

      <div class="modal-footer">
        <button 
          type="button" 
          class="btn-cancel" 
          @click="emit('close')"
        >
          Batal
        </button>

        <button 
          type="button" 
          class="btn-danger" 
          @click="handleConfirm"
        >
          Konfirmasi Pembatalan
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-sm {
  max-width: 480px;
}

.cancel-header-icon {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: var(--color-danger-bg);
  color: var(--color-danger);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-right: 0.75rem;
}

.cancel-header-icon svg {
  width: 20px;
  height: 20px;
}

.modal-header {
  display: flex;
  align-items: center;
  padding: 1.25rem 1.5rem;
}

.cancel-title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.cancel-subtitle {
  margin: 0.15rem 0 0 0;
  font-size: 0.8rem;
  color: var(--color-text-secondary);
}

.patient-summary-box {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-radius: 8px;
  padding: 0.85rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.85rem;
  margin-bottom: 1rem;
}

.summary-row {
  display: flex;
  justify-content: space-between;
}

.summary-row .label {
  color: var(--color-text-secondary);
}

.queue-text {
  color: var(--color-primary-dark);
}

.cancel-reason-group {
  margin-bottom: 0.75rem;
}

.warning-text {
  font-size: 0.775rem;
  color: #64748b;
  line-height: 1.4;
  margin: 0.75rem 0 0 0;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>
