<script setup>
import { ref, reactive, watch } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  issueToEdit: {
    type: Object,
    default: null
  },
  warehouses: {
    type: Array,
    default: () => []
  },
  officers: {
    type: Array,
    default: () => []
  },
  purposes: {
    type: Array,
    default: () => []
  },
  medicines: {
    type: Array,
    default: () => []
  },
  isSubmitting: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'submit']);

const errors = ref({});

const getNowDate = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const getNowTime = () => {
  const now = new Date();
  const hours = String(now.getHours()).padStart(2, '0');
  const minutes = String(now.getMinutes()).padStart(2, '0');
  return `${hours}:${minutes}`;
};

const generateNextIssueNumber = () => {
  const now = new Date();
  const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
  const randomSeq = Math.floor(1000 + Math.random() * 9000);
  return `OUT-${yearMonth}-${randomSeq}`;
};

const createDefaultItemRow = () => {
  const defaultMed = props.medicines[0] || {
    id: 1,
    name: 'Paracetamol 500 mg Tablet',
    unit: 'Tablet',
    defaultBatch: 'PCT-202604',
    expDate: '2027-04-15',
    availableStock: 1200
  };

  return {
    id: Date.now() + Math.random(),
    medicineId: defaultMed.id,
    name: defaultMed.name,
    batchNumber: defaultMed.defaultBatch,
    expDate: defaultMed.expDate,
    availableStock: defaultMed.availableStock,
    quantity: 10,
    unit: defaultMed.unit,
    notes: ''
  };
};

const formData = reactive({
  issueNumber: generateNextIssueNumber(),
  date: getNowDate(),
  time: getNowTime(),
  warehouse: props.warehouses[0] || 'Gudang Utama Farmasi (GOF-01)',
  locationDetail: 'Rak Distribusi',
  officerName: props.officers[0] || 'Apt. Budi Santoso, S.Farm',
  purpose: props.purposes[0] || 'Kebutuhan Internal',
  referenceNumber: '',
  notes: '',
  items: [createDefaultItemRow()]
});

// Watch when modal opens or issueToEdit changes
watch(
  () => [props.isOpen, props.issueToEdit],
  ([isOpen, toEdit]) => {
    if (isOpen) {
      if (toEdit) {
        // Editing mode
        formData.issueNumber = toEdit.issueNumber;
        const parts = (toEdit.dateTime || '').split(' ');
        formData.date = parts[0] || getNowDate();
        formData.time = parts[1] || getNowTime();
        formData.warehouse = toEdit.warehouse || props.warehouses[0];
        formData.locationDetail = toEdit.locationDetail || '';
        formData.officerName = toEdit.officerName || props.officers[0];
        formData.purpose = toEdit.purposeKey || toEdit.purpose || props.purposes[0];
        formData.referenceNumber = toEdit.referenceNumber || '';
        formData.notes = toEdit.notes || '';
        formData.items = toEdit.items && toEdit.items.length > 0
          ? JSON.parse(JSON.stringify(toEdit.items))
          : [createDefaultItemRow()];
      } else {
        // New addition mode
        formData.issueNumber = generateNextIssueNumber();
        formData.date = getNowDate();
        formData.time = getNowTime();
        formData.warehouse = props.warehouses[0] || 'Gudang Utama Farmasi (GOF-01)';
        formData.locationDetail = 'Rak Distribusi';
        formData.officerName = props.officers[0] || 'Apt. Budi Santoso, S.Farm';
        formData.purpose = props.purposes[0] || 'Kebutuhan Internal';
        formData.referenceNumber = '';
        formData.notes = '';
        formData.items = [createDefaultItemRow()];
      }
      errors.value = {};
    }
  },
  { immediate: true }
);

const onMedicineSelectChange = (itemRow) => {
  const found = props.medicines.find((m) => m.id === Number(itemRow.medicineId));
  if (found) {
    itemRow.name = found.name;
    itemRow.batchNumber = found.defaultBatch || 'BATCH-REG';
    itemRow.expDate = found.expDate || '2027-12-31';
    itemRow.availableStock = found.availableStock;
    itemRow.unit = found.unit;
    if (itemRow.quantity > found.availableStock) {
      itemRow.quantity = found.availableStock;
    }
  }
};

const addItemRow = () => {
  formData.items.push(createDefaultItemRow());
};

const removeItemRow = (index) => {
  if (formData.items.length > 1) {
    formData.items.splice(index, 1);
  }
};

const validate = () => {
  const errs = {};
  if (!formData.warehouse) {
    errs.warehouse = 'Gudang Asal wajib dipilih.';
  }
  if (!formData.officerName) {
    errs.officerName = 'Petugas Otorisasi wajib diisi.';
  }
  if (!formData.purpose) {
    errs.purpose = 'Jenis Keperluan wajib dipilih.';
  }
  if (!formData.items || formData.items.length === 0) {
    errs.items = 'Minimal satu item obat harus ditambahkan.';
  } else {
    for (const it of formData.items) {
      if (!it.name || !it.quantity || Number(it.quantity) <= 0) {
        errs.items = 'Pastikan semua baris item memiliki kuantitas lebih dari 0.';
        break;
      }
      if (Number(it.quantity) > Number(it.availableStock)) {
        errs.items = `Kuantitas untuk "${it.name}" melebihi stok yang tersedia (${it.availableStock} ${it.unit}).`;
        break;
      }
    }
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSaveDraft = () => {
  if (!validate()) return;
  emit('submit', { ...formData, isDraft: true });
};

const handleSaveIssue = () => {
  if (!validate()) return;
  emit('submit', { ...formData, isDraft: false });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-xl">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h3>{{ issueToEdit ? 'Edit Draft Pengeluaran Barang' : 'Tambah Pengeluaran Barang' }}</h3>
            <p>Formulir pengeluaran sediaan farmasi untuk kebutuhan nonpenjualan & mutasi pengurangan inventori.</p>
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
          <form autocomplete="off" id="goods-issue-form" @submit.prevent="handleSaveIssue">
            <!-- Row 1: Nomor Pengeluaran & Tanggal/Waktu -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-no">
                  Nomor Pengeluaran
                  <span class="badge-auto">Otomatis</span>
                </label>
                <div class="input-readonly-wrap">
                  <span class="input-icon">#</span>
                  <input autocomplete="off"
                    id="form-issue-no"
                    v-model="formData.issueNumber"
                    type="text"
                    class="form-input readonly-input font-bold"
                    readonly
                  />
                </div>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-date">
                  Tanggal Pengeluaran <span class="required">*</span>
                </label>
                <input autocomplete="off"
                  id="form-issue-date"
                  v-model="formData.date"
                  type="date"
                  class="form-input"
                  required
                />
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-time">
                  Waktu Pengeluaran <span class="required">*</span>
                </label>
                <input autocomplete="off"
                  id="form-issue-time"
                  v-model="formData.time"
                  type="time"
                  class="form-input"
                  required
                />
              </div>
            </div>

            <!-- Row 2: Gudang Asal & Detail Lokasi -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-wh">
                  Gudang Asal <span class="required">*</span>
                </label>
                <select
                  id="form-issue-wh"
                  v-model="formData.warehouse"
                  class="form-select"
                  :class="{ 'has-error': errors.warehouse }"
                  required
                >
                  <option v-for="wh in warehouses" :key="wh" :value="wh">
                    {{ wh }}
                  </option>
                </select>
                <span v-if="errors.warehouse" class="error-msg">{{ errors.warehouse }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-loc">Detail Area / Rak</label>
                <input autocomplete="off"
                  id="form-issue-loc"
                  v-model="formData.locationDetail"
                  type="text"
                  class="form-input"
                  placeholder="Contoh: Rak A-12 (Injeksi & Parenteral)"
                />
              </div>
            </div>

            <!-- Row 3: Petugas Otorisasi & Jenis Keperluan -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-officer">
                  Petugas Otorisasi <span class="required">*</span>
                </label>
                <select
                  id="form-issue-officer"
                  v-model="formData.officerName"
                  class="form-select"
                  :class="{ 'has-error': errors.officerName }"
                  required
                >
                  <option v-for="off in officers" :key="off" :value="off">
                    {{ off }}
                  </option>
                </select>
                <span v-if="errors.officerName" class="error-msg">{{ errors.officerName }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-purpose">
                  Jenis Keperluan <span class="required">*</span>
                </label>
                <select
                  id="form-issue-purpose"
                  v-model="formData.purpose"
                  class="form-select"
                  :class="{ 'has-error': errors.purpose }"
                  required
                >
                  <option v-for="pur in purposes" :key="pur" :value="pur">
                    {{ pur }}
                  </option>
                </select>
                <span v-if="errors.purpose" class="error-msg">{{ errors.purpose }}</span>
              </div>
            </div>

            <!-- Row 4: Nomor Referensi / BAP & Catatan -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-ref">
                  Nomor Referensi / BAP / Disposisi
                </label>
                <input autocomplete="off"
                  id="form-issue-ref"
                  v-model="formData.referenceNumber"
                  type="text"
                  class="form-input"
                  placeholder="Contoh: Ref: SBPB-098/FAR/26 atau BAP-2026/09/04"
                />
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-issue-notes">
                  Catatan Pengeluaran
                </label>
                <input autocomplete="off"
                  id="form-issue-notes"
                  v-model="formData.notes"
                  type="text"
                  class="form-input"
                  placeholder="Catatan keperluan atau keterangan tambahan..."
                />
              </div>
            </div>

            <!-- Items Section -->
            <div class="items-form-section">
              <div class="items-form-header">
                <div>
                  <h4 class="items-form-title">Daftar Item Obat & Sediaan</h4>
                  <span class="items-form-sub">Tentukan item, nomor batch, masa kedaluwarsa, dan jumlah kuantitas keluar</span>
                </div>
                <button
                  type="button"
                  class="btn-add-item"
                  @click="addItemRow"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Tambah Item
                </button>
              </div>

              <span v-if="errors.items" class="error-msg mb-2">{{ errors.items }}</span>

              <div class="table-wrap">
                <table class="form-items-table">
                  <thead>
                    <tr>
                      <th style="width: 35%;">Item / Obat</th>
                      <th style="width: 15%;">No. Batch</th>
                      <th style="width: 15%;">Tgl. Expired</th>
                      <th style="width: 12%;" class="text-center">Stok Ada</th>
                      <th style="width: 15%;">Kuantitas Keluar</th>
                      <th style="width: 8%;" class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in formData.items" :key="row.id || idx">
                      <!-- Obat Select -->
                      <td>
                        <select
                          v-model="row.medicineId"
                          class="form-select select-sm"
                          @change="onMedicineSelectChange(row)"
                        >
                          <option v-for="med in medicines" :key="med.id" :value="med.id">
                            {{ med.name }}
                          </option>
                        </select>
                      </td>

                      <!-- Batch -->
                      <td>
                        <input autocomplete="off"
                          v-model="row.batchNumber"
                          type="text"
                          class="form-input input-sm"
                          placeholder="No. Batch"
                        />
                      </td>

                      <!-- Exp Date -->
                      <td>
                        <input autocomplete="off"
                          v-model="row.expDate"
                          type="date"
                          class="form-input input-sm"
                        />
                      </td>

                      <!-- Stok Ada -->
                      <td class="text-center">
                        <span class="stock-badge">{{ row.availableStock }} {{ row.unit }}</span>
                      </td>

                      <!-- Qty -->
                      <td>
                        <div class="qty-input-group">
                          <input autocomplete="off"
                            v-model.number="row.quantity"
                            type="number"
                            min="1"
                            :max="row.availableStock"
                            class="form-input input-sm text-center font-bold"
                          />
                          <span class="unit-label">{{ row.unit }}</span>
                        </div>
                      </td>

                      <!-- Hapus -->
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn-row-del"
                          :disabled="formData.items.length <= 1"
                          title="Hapus Baris"
                          @click="removeItemRow(idx)"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
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
              @click="handleSaveIssue"
            >
              <span v-if="isSubmitting" class="spinner"></span>
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <span>{{ issueToEdit ? 'Perbarui Pengeluaran' : 'Simpan Pengeluaran' }}</span>
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
  max-width: 950px;
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
  margin-bottom: 0.85rem;
}

.form-label {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--color-text-navy, #17243d);
  margin-bottom: 0.35rem;
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
.form-select {
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
.form-select:focus {
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

.mb-2 {
  margin-bottom: 0.5rem;
}

/* Items Section */
.items-form-section {
  border-top: 1px solid var(--color-border-soft, #e2e8f0);
  padding-top: 1.25rem;
  margin-top: 1rem;
}

.items-form-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 0.75rem;
}

.items-form-title {
  font-size: 0.92rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
  margin: 0;
}

.items-form-sub {
  font-size: 0.75rem;
  color: var(--color-text-secondary, #64748b);
}

.btn-add-item {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.85rem;
  font-size: 0.78rem;
  font-weight: 600;
  border-radius: 6px;
  background: var(--color-primary-light, #dff7f4);
  color: var(--color-primary, #087f78);
  border: 1px solid var(--color-primary, #087f78);
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-add-item:hover {
  background: #ccfbf1;
}

.btn-add-item svg {
  width: 14px;
  height: 14px;
}

.table-wrap {
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  overflow: hidden;
}

.form-items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.form-items-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 700;
  padding: 0.65rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
}

.form-items-table td {
  padding: 0.5rem 0.65rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  vertical-align: middle;
}

.select-sm,
.input-sm {
  padding: 0.4rem 0.55rem;
  font-size: 0.8rem;
}

.qty-input-group {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.unit-label {
  font-size: 0.75rem;
  color: #64748b;
  white-space: nowrap;
}

.stock-badge {
  display: inline-block;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  background: #f1f5f9;
  color: #475569;
  font-size: 0.74rem;
  font-weight: 600;
}

.btn-row-del {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 6px;
  border: 1px solid var(--color-border-soft, #cbd5e1);
  background: #ffffff;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-row-del:hover:not(:disabled) {
  background: #fee2e2;
  border-color: #fca5a5;
  color: #dc2626;
}

.btn-row-del:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-row-del svg {
  width: 14px;
  height: 14px;
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
  width: 14px;
  height: 14px;
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

.text-center { text-align: center; }
.font-bold { font-weight: 700; }

@media (max-width: 768px) {
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
