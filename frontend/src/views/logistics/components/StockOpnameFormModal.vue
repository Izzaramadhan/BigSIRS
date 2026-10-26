<script setup>
import { ref, reactive, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  opnameTypes: {
    type: Array,
    default: () => ['Audit Rutin', 'Opname Parsial', 'Audit Akhir Bulan', 'Audit Khusus']
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const errors = ref({});

const getNowLocalDateTime = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset();
  const local = new Date(now.getTime() - offset * 60000);
  return local.toISOString().slice(0, 16);
};

const generateNextOpnameNumber = () => {
  const now = new Date();
  const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
  const randomSeq = Math.floor(1000 + Math.random() * 9000);
  return `SO-${yearMonth}-${randomSeq}`;
};

const formData = reactive({
  opnameNumber: generateNextOpnameNumber(),
  startDateTime: getNowLocalDateTime(),
  warehouse: props.warehouses[0] || 'Gudang Utama Farmasi',
  locationDetail: 'Rak A1 - Rak C4',
  type: 'Audit Rutin',
  officerName: 'Apt. Budi Santoso, S.Farm',
  totalItems: 150,
  notes: ''
});

watch(
  () => props.isOpen,
  (val) => {
    if (val) {
      formData.opnameNumber = generateNextOpnameNumber();
      formData.startDateTime = getNowLocalDateTime();
      formData.warehouse = props.warehouses[0] || 'Gudang Utama Farmasi';
      formData.locationDetail = 'Rak A1 - Rak C4';
      formData.type = 'Audit Rutin';
      formData.officerName = 'Apt. Budi Santoso, S.Farm';
      formData.totalItems = 150;
      formData.notes = '';
      errors.value = {};
    }
  },
  { immediate: true }
);

const validate = () => {
  const errs = {};
  if (!formData.warehouse) {
    errs.warehouse = 'Gudang/Lokasi wajib dipilih.';
  }
  if (!formData.officerName || !formData.officerName.trim()) {
    errs.officerName = 'Petugas/Penanggung Jawab wajib diisi.';
  }
  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSaveDraft = () => {
  if (!validate()) return;
  emit('submit', { ...formData, isDraft: true });
};

const handleStartOpname = () => {
  if (!validate()) return;
  emit('submit', { ...formData, isDraft: false });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-md">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h3>Mulai Sesi Stok Opname</h3>
            <p>Siapkan dokumen dan parameter audit fisik persediaan farmasi.</p>
          </div>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <form autocomplete="off" id="stock-opname-form" @submit.prevent="handleStartOpname">
            <!-- Row 1: Nomor Stok Opname & Tanggal Waktu Mulai -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-so-number">
                  Nomor Stok Opname
                  <span class="badge-auto">Otomatis</span>
                </label>
                <div class="input-readonly-wrap">
                  <span class="input-icon">#</span>
                  <input autocomplete="off"
                    id="form-so-number"
                    v-model="formData.opnameNumber"
                    type="text"
                    class="form-input readonly-input font-bold"
                    readonly
                  />
                </div>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-so-datetime">
                  Tanggal & Waktu Mulai <span class="required">*</span>
                </label>
                <input autocomplete="off"
                  id="form-so-datetime"
                  v-model="formData.startDateTime"
                  type="datetime-local"
                  class="form-input"
                  required
                />
              </div>
            </div>

            <!-- Row 2: Gudang/Lokasi & Rincian Lokasi -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-so-warehouse">
                  Gudang / Lokasi <span class="required">*</span>
                </label>
                <select
                  id="form-so-warehouse"
                  v-model="formData.warehouse"
                  class="form-select"
                  :class="{ 'has-error': errors.warehouse }"
                  required
                >
                  <option value="" disabled>Pilih Gudang / Depo Farmasi</option>
                  <option v-for="wh in warehouses" :key="wh" :value="wh">
                    {{ wh }}
                  </option>
                </select>
                <span v-if="errors.warehouse" class="error-msg">{{ errors.warehouse }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-so-location-detail">
                  Detail Rak / Zona
                </label>
                <input autocomplete="off"
                  id="form-so-location-detail"
                  v-model="formData.locationDetail"
                  type="text"
                  class="form-input"
                  placeholder="Contoh: Rak A1 - Rak C4"
                />
              </div>
            </div>

            <!-- Row 3: Jenis Stok Opname -->
            <div class="form-group">
              <label class="form-label">
                Jenis Stok Opname <span class="required">*</span>
              </label>
              <div class="type-pill-group">
                <label
                  v-for="t in opnameTypes"
                  :key="t"
                  class="type-pill-label"
                  :class="{ 'pill-selected': formData.type === t }"
                >
                  <input
                    v-model="formData.type"
                    type="radio"
                    name="opname-type"
                    :value="t"
                    class="sr-only"
                  />
                  <span>{{ t }}</span>
                </label>
              </div>
            </div>

            <!-- Row 4: Petugas PJ -->
            <div class="form-group">
              <label class="form-label" for="form-so-officer">
                Petugas / Penanggung Jawab <span class="required">*</span>
              </label>
              <input autocomplete="off"
                id="form-so-officer"
                v-model="formData.officerName"
                type="text"
                class="form-input"
                :class="{ 'has-error': errors.officerName }"
                placeholder="Nama lengkap petugas atau apoteker penanggung jawab"
                required
              />
              <span v-if="errors.officerName" class="error-msg">{{ errors.officerName }}</span>
            </div>

            <!-- Row 5: Catatan -->
            <div class="form-group">
              <label class="form-label" for="form-so-notes">
                Catatan / Ruang Lingkup Audit
              </label>
              <textarea
                id="form-so-notes"
                v-model="formData.notes"
                rows="3"
                class="form-textarea"
                placeholder="Tuliskan catatan khusus atau instruksi audit untuk petugas lapangan..."
              ></textarea>
            </div>
          </form>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button
            type="button"
            class="btn-cancel"
            :disabled="isSubmitting"
            @click="$emit('close')"
          >
            Batal
          </button>

          <div class="footer-actions-right">
            <button
              type="button"
              class="btn-outline"
              :disabled="isSubmitting"
              @click="handleSaveDraft"
            >
              Simpan Draft
            </button>

            <button
              type="button"
              class="btn-primary"
              :disabled="isSubmitting"
              @click="handleStartOpname"
            >
              <span v-if="isSubmitting" class="spinner"></span>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
              </svg>
              <span>Mulai Stok Opname</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(2px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-content {
  background: #ffffff;
  border-radius: 14px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: modalEnter 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

@keyframes modalEnter {
  from {
    opacity: 0;
    transform: scale(0.97) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.modal-header h3 {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.modal-header p {
  font-size: 0.8rem;
  color: var(--color-text-secondary, #64748b);
  margin: 0.25rem 0 0 0;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.btn-close:hover {
  background: #f1f5f9;
  color: #334155;
}

.btn-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.flex-1 {
  flex: 1;
}

.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
  margin-bottom: 0.4rem;
}

.required {
  color: #ef4444;
}

.badge-auto {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.1rem 0.4rem;
  background: #e0f2fe;
  color: #0369a1;
  border-radius: 4px;
}

.input-readonly-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 0.75rem;
  color: #94a3b8;
  font-weight: 700;
}

.readonly-input {
  background: #f8fafc !important;
  color: var(--color-primary, #087f78) !important;
  padding-left: 1.6rem;
  cursor: default;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.55rem 0.75rem;
  font-size: 0.85rem;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 6px;
  background: #ffffff;
  color: var(--color-text-navy, #17243d);
  outline: none;
  transition: all 0.15s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: var(--color-primary, #087f78);
  box-shadow: 0 0 0 3px rgba(8, 127, 120, 0.12);
}

.has-error {
  border-color: #ef4444 !important;
}

.error-msg {
  display: block;
  font-size: 0.74rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

/* Type Pill Group */
.type-pill-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.type-pill-label {
  padding: 0.4rem 0.85rem;
  font-size: 0.78rem;
  font-weight: 600;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  border-radius: 9999px;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.type-pill-label:hover {
  background: #f8fafc;
  border-color: #94a3b8;
}

.pill-selected {
  background: var(--color-primary-light, #dff7f4) !important;
  border-color: var(--color-primary, #087f78) !important;
  color: var(--color-primary, #087f78) !important;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  background: #f8fafc;
}

.footer-actions-right {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.btn-cancel,
.btn-outline,
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.1rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-cancel {
  background: transparent;
  border: 1px solid transparent;
  color: #64748b;
}

.btn-cancel:hover:not(:disabled) {
  background: #e2e8f0;
  color: #1e293b;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  color: var(--color-text-navy, #17243d);
}

.btn-outline:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.btn-primary {
  background: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #066761;
}

.btn-primary svg {
  width: 13px;
  height: 13px;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.4);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  .modal-footer {
    flex-direction: column-reverse;
    gap: 0.75rem;
  }
  .footer-actions-right {
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
