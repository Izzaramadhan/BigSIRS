<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  initialNik: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'verified']);

const nik = ref('');
const patientName = ref('');
const isVerifying = ref(false);
const verificationResult = ref(null);
const kycError = ref('');

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      nik.value = props.initialNik || '';
      patientName.value = '';
      verificationResult.value = null;
      kycError.value = '';
    }
  }
);

const handleVerify = () => {
  const cleanNik = nik.value.trim();
  if (!/^\d{16}$/.test(cleanNik)) {
    kycError.value = 'NIK harus terdiri dari 16 digit angka untuk verifikasi SatuSehat KYC.';
    return;
  }

  kycError.value = '';
  isVerifying.value = true;

  setTimeout(() => {
    isVerifying.value = false;
    verificationResult.value = {
      ihs_number: `P${cleanNik.slice(-9)}`,
      nik: cleanNik,
      name: patientName.value.trim() || 'Pasien Terverifikasi KYC',
      verified_at: new Date().toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit'
      }) + ' WIB'
    };
    emit('verified', verificationResult.value);
  }, 350);
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="emit('close')">
      <div class="modal-content modal-sm" role="dialog" aria-modal="true" aria-labelledby="kyc-modal-title">
        <div class="modal-header">
          <div class="kyc-title-group">
            <div class="kyc-icon-badge" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                <polyline points="9 12 11 14 15 10"></polyline>
              </svg>
            </div>
            <div>
              <h3 id="kyc-modal-title">Verifikasi SatuSehat KYC</h3>
              <p>Simulasi verifikasi identitas pasien (Frontend Demo)</p>
            </div>
          </div>

          <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <div class="modal-body">
          <form autocomplete="off" class="kyc-form" @submit.prevent="handleVerify">
            <div class="form-group">
              <label class="form-label" for="kyc-nik">
                NIK (16 Digit) <span class="required-mark">*</span>
              </label>
              <input
                id="kyc-nik"
                v-model="nik"
                type="text"
                maxlength="16"
                class="form-input font-mono"
                :class="{ 'has-error': Boolean(kycError) }"
                placeholder="Contoh: 3471011205860001"
                autocomplete="off"
              />
              <span v-if="kycError" class="error-message">{{ kycError }}</span>
            </div>

            <div class="form-group">
              <label class="form-label" for="kyc-name">Nama Lengkap Sesuai KTP (Opsional)</label>
              <input
                id="kyc-name"
                v-model="patientName"
                type="text"
                class="form-input"
                placeholder="Nama lengkap pasien"
                autocomplete="off"
              />
            </div>

            <div v-if="verificationResult" class="kyc-success-box">
              <div class="kyc-success-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <strong>Terverifikasi SatuSehat KYC</strong>
              </div>
              <div class="kyc-meta-grid">
                <span>No. IHS:</span>
                <strong class="font-mono">{{ verificationResult.ihs_number }}</strong>
                <span>NIK:</span>
                <span class="font-mono">{{ verificationResult.nik }}</span>
                <span>Waktu:</span>
                <span>{{ verificationResult.verified_at }}</span>
              </div>
            </div>
          </form>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel kyc-btn-cancel" @click="emit('close')">
            Tutup
          </button>
          <button
            type="button"
            class="btn-primary kyc-btn-verify"
            :disabled="isVerifying"
            @click="handleVerify"
          >
            {{ isVerifying ? 'Memverifikasi...' : 'Verifikasi KYC' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.kyc-title-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.kyc-icon-badge {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: var(--color-primary-light);
  color: var(--color-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.kyc-icon-badge svg {
  width: 19px;
  height: 19px;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.kyc-form {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.kyc-success-box {
  margin-top: 0.5rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 0.85rem;
  color: #166534;
}

.kyc-success-header {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
}

.kyc-success-header svg {
  width: 16px;
  height: 16px;
}

.kyc-meta-grid {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 0.25rem 0.75rem;
  font-size: 0.8rem;
}

.kyc-btn-cancel,
.kyc-btn-verify {
  height: 38px;
  font-family: inherit;
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
</style>

