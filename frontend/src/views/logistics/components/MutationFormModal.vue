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

const getNowLocalDateTime = () => {
  const now = new Date();
  const offset = now.getTimezoneOffset();
  const local = new Date(now.getTime() - offset * 60000);
  return local.toISOString().slice(0, 16);
};

const generateNextMutationNumber = () => {
  const now = new Date();
  const yearMonth = now.toISOString().slice(0, 7).replace('-', '');
  const randomSeq = Math.floor(1000 + Math.random() * 9000);
  return `MUT-${yearMonth}-${randomSeq}`;
};

const createDefaultItem = () => ({
  id: Date.now() + Math.random(),
  medicineId: '',
  medicineName: '',
  availableStock: 0,
  quantity: 1,
  unit: 'Unit'
});

const formData = reactive({
  mutationNumber: generateNextMutationNumber(),
  date: getNowLocalDateTime(),
  sourceWarehouse: '',
  targetWarehouse: '',
  notes: '',
  items: [createDefaultItem()]
});

// Medicine selection in row
const onMedicineChange = (item) => {
  const med = props.medicines.find((m) => m.id === Number(item.medicineId));
  if (med) {
    item.medicineName = med.name;
    item.availableStock = med.availableStock;
    item.unit = med.unit;
    if (item.quantity > med.availableStock) {
      item.quantity = med.availableStock;
    }
  } else {
    item.medicineName = '';
    item.availableStock = 0;
  }
};

const addItemRow = () => {
  formData.items.push(createDefaultItem());
};

const removeItemRow = (index) => {
  if (formData.items.length > 1) {
    formData.items.splice(index, 1);
  } else {
    formData.items[0] = createDefaultItem();
  }
};

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      errors.value = {};
      formData.mutationNumber = generateNextMutationNumber();
      formData.date = getNowLocalDateTime();
      formData.sourceWarehouse = '';
      formData.targetWarehouse = '';
      formData.notes = '';
      formData.items = [createDefaultItem()];
    }
  }
);

const validate = () => {
  const errs = {};

  if (!formData.sourceWarehouse) {
    errs.sourceWarehouse = 'Gudang asal wajib dipilih.';
  }

  if (!formData.targetWarehouse) {
    errs.targetWarehouse = 'Gudang tujuan wajib dipilih.';
  }

  if (
    formData.sourceWarehouse &&
    formData.targetWarehouse &&
    formData.sourceWarehouse === formData.targetWarehouse
  ) {
    errs.targetWarehouse = 'Gudang tujuan tidak boleh sama dengan gudang asal.';
  }

  if (!formData.items || formData.items.length === 0) {
    errs.items = 'Minimal terdapat satu item mutasi.';
  } else {
    for (const it of formData.items) {
      if (!it.medicineId) {
        errs.items = 'Pastikan semua baris telah memilih obat.';
        break;
      }
      if (!it.quantity || Number(it.quantity) <= 0) {
        errs.items = 'Kuantitas mutasi harus lebih dari 0.';
        break;
      }
      if (Number(it.quantity) > Number(it.availableStock)) {
        errs.items = `Kuantitas untuk "${it.medicineName}" melebihi stok yang tersedia (${it.availableStock} ${it.unit}).`;
        break;
      }
    }
  }

  errors.value = errs;
  return Object.keys(errs).length === 0;
};

const handleSubmit = () => {
  if (!validate()) return;
  emit('submit', { ...formData });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content modal-xl">
        <!-- Header -->
        <div class="modal-header">
          <div>
            <h3>Tambah Mutasi Barang</h3>
            <p>Formulir pemindahan stok sediaan obat antarunit farmasi.</p>
          </div>
          <button type="button" class="btn-close" @click="$emit('close')" :disabled="isSubmitting" aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <form autocomplete="off" id="mutation-form" @submit.prevent="handleSubmit">
            <!-- Row 1: Nomor Mutasi & Tanggal -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-mutation-num">Nomor Mutasi</label>
                <input autocomplete="off"
                  id="form-mutation-num"
                  v-model="formData.mutationNumber"
                  type="text"
                  class="form-input readonly-input font-bold"
                  readonly
                />
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-mutation-date">
                  Tanggal & Waktu Mutasi <span class="required-mark">*</span>
                </label>
                <input autocomplete="off"
                  id="form-mutation-date"
                  v-model="formData.date"
                  type="datetime-local"
                  class="form-input"
                  required
                />
              </div>
            </div>

            <!-- Row 2: Gudang Asal & Gudang Tujuan -->
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label" for="form-source-wh">
                  Gudang Asal <span class="required-mark">*</span>
                </label>
                <select
                  id="form-source-wh"
                  v-model="formData.sourceWarehouse"
                  class="form-select"
                  :class="{ 'has-error': errors.sourceWarehouse }"
                >
                  <option value="" disabled>Pilih Gudang Asal</option>
                  <option v-for="wh in warehouses" :key="wh" :value="wh">
                    {{ wh }}
                  </option>
                </select>
                <span v-if="errors.sourceWarehouse" class="error-message">{{ errors.sourceWarehouse }}</span>
              </div>

              <div class="form-group flex-1">
                <label class="form-label" for="form-target-wh">
                  Gudang Tujuan <span class="required-mark">*</span>
                </label>
                <select
                  id="form-target-wh"
                  v-model="formData.targetWarehouse"
                  class="form-select"
                  :class="{ 'has-error': errors.targetWarehouse }"
                >
                  <option value="" disabled>Pilih Gudang Tujuan</option>
                  <option v-for="wh in warehouses" :key="wh" :value="wh">
                    {{ wh }}
                  </option>
                </select>
                <span v-if="errors.targetWarehouse" class="error-message">{{ errors.targetWarehouse }}</span>
              </div>
            </div>

            <!-- Row 3: Catatan Keperluan -->
            <div class="form-group">
              <label class="form-label" for="form-notes">Catatan Keperluan</label>
              <textarea
                id="form-notes"
                v-model="formData.notes"
                class="form-textarea"
                rows="2"
                placeholder="Misal: Restock harian poli rawat jalan shift sore..."
              ></textarea>
            </div>

            <!-- Items Table Section -->
            <div class="items-section">
              <div class="items-header">
                <div>
                  <h4 class="items-title">Daftar Item Obat / Sediaan</h4>
                  <p class="items-subtitle">Tentukan jenis sediaan dan jumlah stok yang dipindahkan</p>
                </div>
                <button
                  type="button"
                  class="btn-outline btn-sm"
                  @click="addItemRow"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                  </svg>
                  Tambah Item
                </button>
              </div>

              <span v-if="errors.items" class="error-message mb-2">{{ errors.items }}</span>

              <div class="items-table-wrapper">
                <table class="items-table">
                  <thead>
                    <tr>
                      <th style="width: 45%;">Obat / Sediaan <span class="required-mark">*</span></th>
                      <th style="width: 20%;">Stok Tersedia</th>
                      <th style="width: 18%;">Kuantitas Mutasi <span class="required-mark">*</span></th>
                      <th style="width: 12%;">Satuan</th>
                      <th style="width: 48px; text-align: center;">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(it, idx) in formData.items" :key="it.id">
                      <!-- Obat select -->
                      <td>
                        <select
                          v-model="it.medicineId"
                          class="form-select item-select"
                          @change="onMedicineChange(it)"
                          required
                          aria-label="Pilih Obat"
                        >
                          <option value="" disabled>Pilih Obat...</option>
                          <option v-for="med in medicines" :key="med.id" :value="med.id">
                            {{ med.name }}
                          </option>
                        </select>
                      </td>

                      <!-- Stok Tersedia -->
                      <td>
                        <span class="stock-badge font-medium">
                          {{ it.availableStock ? it.availableStock.toLocaleString('id-ID') : '0' }} {{ it.unit }}
                        </span>
                      </td>

                      <!-- Kuantitas Mutasi -->
                      <td>
                        <input autocomplete="off"
                          v-model.number="it.quantity"
                          type="number"
                          min="1"
                          :max="it.availableStock || 9999"
                          class="form-input item-input"
                          required
                          aria-label="Kuantitas Mutasi"
                        />
                      </td>

                      <!-- Satuan -->
                      <td class="text-muted font-medium">
                        {{ it.unit }}
                      </td>

                      <!-- Hapus Row -->
                      <td class="text-center">
                        <button
                          type="button"
                          class="btn-delete-row"
                          title="Hapus Baris"
                          aria-label="Hapus Baris"
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
          <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">
            Batal
          </button>
          <button
            type="submit"
            form="mutation-form"
            class="btn-primary"
            :disabled="isSubmitting"
          >
            <svg v-if="isSubmitting" class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
              <path d="M12 2a10 10 0 0 1 10 10"></path>
            </svg>
            <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Mutasi' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.readonly-input {
  background: #f8fafc;
  color: var(--color-primary, #087f78);
  cursor: not-allowed;
}

.items-section {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--color-border-soft, #e8edf3);
}

.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}

.items-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-navy, #17243d);
}

.items-subtitle {
  margin: 0.15rem 0 0;
  font-size: 0.78rem;
  color: var(--color-text-secondary, #64748b);
}

.btn-sm {
  padding: 0.4rem 0.75rem;
  font-size: 0.8rem;
  border-radius: 6px;
}

.items-table-wrapper {
  overflow-x: auto;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 8px;
  background: #ffffff;
}

.items-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.82rem;
  min-width: 650px;
}

.items-table th {
  background: #f8fafc;
  padding: 0.65rem 0.75rem;
  text-align: left;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.items-table td {
  padding: 0.6rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #f1f5f9);
  vertical-align: middle;
}

.items-table tbody tr:last-child td {
  border-bottom: none;
}

.item-select,
.item-input {
  padding: 0.4rem 0.5rem;
  font-size: 0.82rem;
  height: 34px;
}

.stock-badge {
  display: inline-flex;
  padding: 0.2rem 0.5rem;
  background: #f1f5f9;
  border-radius: 4px;
  font-size: 0.78rem;
  color: #334155;
}

.btn-delete-row {
  width: 30px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: var(--color-danger, #d64545);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-delete-row:hover {
  background: var(--color-danger-bg, #fff0f0);
}

.btn-delete-row svg {
  width: 16px;
  height: 16px;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
    gap: 0;
  }
}
</style>
