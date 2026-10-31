<script setup>
import { reactive, watch, ref, computed } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  item: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  apiErrors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const formData = reactive({
  warehouseName: '',
  expiration_date: '',
  selling_price: 0,
  stock: 0
});

const clientErrors = ref({});

const originalStock = computed(() => {
  if (!props.item) return 0;
  return Number(props.item.stock ?? props.item.quantity ?? 0);
});

const itemUnit = computed(() => {
  if (!props.item) return 'Unit';
  return props.item.unit?.name || props.item.unit_name || props.item.unit || 'Unit';
});

watch(
  () => props.isOpen,
  (open) => {
    if (open && props.item) {
      clientErrors.value = {};
      formData.warehouseName =
        props.item.warehouse?.name ||
        props.item.warehouse_name ||
        'Gudang Utama';

      formData.expiration_date =
        props.item.expiration_date ||
        props.item.expirationDate ||
        '';

      formData.selling_price = Number(
        props.item.selling_price ?? props.item.sellingPrice ?? 0
      );

      formData.stock = Number(
        props.item.stock ?? props.item.quantity ?? 0
      );
    }
  },
  { immediate: true }
);

const validate = () => {
  const errs = {};

  if (formData.selling_price === '' || formData.selling_price === null || isNaN(formData.selling_price)) {
    errs.selling_price = 'Harga jual wajib diisi berupa angka.';
  } else if (Number(formData.selling_price) < 0) {
    errs.selling_price = 'Harga jual tidak boleh bernilai negatif.';
  }

  if (formData.stock === '' || formData.stock === null || isNaN(formData.stock)) {
    errs.stock = 'Stok sediaan wajib diisi berupa angka.';
  } else if (Number(formData.stock) < 0) {
    errs.stock = 'Stok sediaan tidak boleh bernilai negatif.';
  }

  clientErrors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;

  emit('submit', {
    expiration_date: formData.expiration_date ? formData.expiration_date : null,
    selling_price: Number(formData.selling_price),
    stock: Number(formData.stock)
  });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-card">
        <!-- Header -->
        <div class="modal-header">
          <div class="header-title-box">
            <div class="header-icon-box">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
              </svg>
            </div>
            <div>
              <h3 class="modal-title">Edit Stok Opname</h3>
              <p v-if="item" class="modal-subtitle">
                {{ item.medicine?.name || item.medicine_name }}
                <span v-if="item.batch_number && item.batch_number !== '-'" class="batch-sub">
                  (Batch: {{ item.batch_number }})
                </span>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="btn-close"
            :disabled="isSubmitting"
            aria-label="Tutup modal"
            @click="$emit('close')"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Form Body -->
        <form autocomplete="off" class="modal-body" @submit.prevent="handleSubmit">
          <!-- 1. Gudang (Read-Only) -->
          <div class="form-group">
            <label class="form-label" for="edit-warehouse">Gudang</label>
            <input
              id="edit-warehouse"
              type="text"
              class="form-control input-readonly"
              :value="formData.warehouseName"
              disabled
              readonly
            />
            <span class="helper-text text-muted">
              Gudang bersifat read-only. Untuk pemindahan antar gudang gunakan modul Mutasi.
            </span>
          </div>

          <!-- 2. Tanggal Kadaluarsa -->
          <div class="form-group">
            <label class="form-label" for="edit-exp-date">Tanggal Kadaluarsa</label>
            <input
              id="edit-exp-date"
              v-model="formData.expiration_date"
              type="date"
              class="form-control"
              :class="{ 'is-invalid': clientErrors.expiration_date || apiErrors.expiration_date }"
            />
            <span v-if="clientErrors.expiration_date || apiErrors.expiration_date" class="invalid-feedback">
              {{ clientErrors.expiration_date || (apiErrors.expiration_date && apiErrors.expiration_date[0]) }}
            </span>
          </div>

          <!-- 3. Harga Jual -->
          <div class="form-group">
            <label class="form-label" for="edit-selling-price">
              Harga Jual <span class="required-mark">*</span>
            </label>
            <div class="currency-input-wrap">
              <span class="currency-prefix">Rp</span>
              <input
                id="edit-selling-price"
                v-model.number="formData.selling_price"
                type="number"
                min="0"
                step="any"
                class="form-control currency-input"
                :class="{ 'is-invalid': clientErrors.selling_price || apiErrors.selling_price }"
                required
              />
            </div>
            <span v-if="clientErrors.selling_price || apiErrors.selling_price" class="invalid-feedback">
              {{ clientErrors.selling_price || (apiErrors.selling_price && apiErrors.selling_price[0]) }}
            </span>
          </div>

          <!-- 4. Stok Sediaan -->
          <div class="form-group">
            <label class="form-label" for="edit-stock">
              Stok Sediaan <span class="required-mark">*</span>
            </label>
            <input
              id="edit-stock"
              v-model.number="formData.stock"
              type="number"
              min="0"
              step="any"
              class="form-control"
              :class="{ 'is-invalid': clientErrors.stock || apiErrors.stock }"
              required
            />
            <span class="helper-text stock-helper">
              Stok sebelumnya: <strong>{{ originalStock }} {{ itemUnit }}</strong>
            </span>
            <span v-if="clientErrors.stock || apiErrors.stock" class="invalid-feedback">
              {{ clientErrors.stock || (apiErrors.stock && apiErrors.stock[0]) }}
            </span>
          </div>

          <!-- Actions -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="isSubmitting"
              @click="$emit('close')"
            >
              Batal
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="isSubmitting"
            >
              <span v-if="isSubmitting" class="spinner-small"></span>
              {{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1.5rem;
}

.modal-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  width: 100%;
  max-width: 480px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.header-title-box {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-icon-box {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #f0fdfa;
  color: #0d9488;
}

.header-icon-box svg {
  width: 20px;
  height: 20px;
}

.modal-title {
  font-size: 1.0625rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-subtitle {
  font-size: 0.8125rem;
  color: #64748b;
  margin: 0.125rem 0 0 0;
}

.batch-sub {
  color: #0d9488;
  font-weight: 500;
}

.btn-close {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
}

.btn-close:hover:not(:disabled) {
  color: #475569;
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
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.form-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #334155;
}

.required-mark {
  color: #ef4444;
}

.form-control {
  width: 100%;
  height: 40px;
  padding: 0 0.875rem;
  font-size: 0.875rem;
  color: #1e293b;
  background: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
  transition: all 0.2s ease;
}

.form-control:focus {
  border-color: #0d9488;
  box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
}

.input-readonly {
  background: #f8fafc;
  color: #475569;
  border-color: #e2e8f0;
  cursor: not-allowed;
}

.currency-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.currency-prefix {
  position: absolute;
  left: 0.875rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  pointer-events: none;
}

.currency-input {
  padding-left: 2.25rem;
}

.helper-text {
  font-size: 0.75rem;
  line-height: 1.4;
}

.stock-helper {
  color: #475569;
  margin-top: 0.125rem;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.invalid-feedback {
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.125rem;
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 0.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  height: 40px;
  padding: 0 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.btn-secondary {
  background: #ffffff;
  border-color: #cbd5e1;
  color: #475569;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  color: #1e293b;
}

.btn-primary {
  background: #0d9488;
  color: #ffffff;
}

.btn-primary:hover:not(:disabled) {
  background: #0f766e;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner-small {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #ffffff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>

