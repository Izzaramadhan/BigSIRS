<script setup>
import { reactive, watch, computed, ref } from 'vue';
import { useTariffComponents } from '@/composables/useTariffComponents';
import MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  editData: {
    type: Object,
    default: null
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'submit']);

const { 
  tariffComponents, 
  loading: searchLoading, 
  fetchTariffComponents 
} = useTariffComponents();

const form = reactive({
  name: '',
  code: '',
  description: '',
  components: []
});

const searchQuery = ref('');
const isDropdownOpen = ref(false);
const searchTimeout = ref(null);

const populateForm = (data) => {
  form.name = data.name || '';
  form.code = data.code || '';
  form.description = data.description || '';
  form.components = data.components ? data.components.map(c => ({
    id: c.id,
    tariff_component_id: c.tariff_component_id,
    name: c.component ? c.component.name : 'Unknown',
    percentage: c.percentage,
    needs_review: c.needs_review
  })) : [];
};

const resetForm = () => {
  form.name = '';
  form.code = '';
  form.description = '';
  form.components = [];
};

watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) return;

  if (props.editData) {
    populateForm(props.editData);
  } else {
    resetForm();
  }
  searchQuery.value = '';
  isDropdownOpen.value = false;
}, { immediate: true });

const handleSearch = (e) => {
  searchQuery.value = e.target.value;
  isDropdownOpen.value = true;
  
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  
  searchTimeout.value = setTimeout(() => {
    fetchTariffComponents({ search: searchQuery.value, per_page: 20 });
  }, 300);
};

const selectComponent = (comp) => {
  const isExisting = form.components.find(c => c.tariff_component_id === comp.id);
  if (isExisting) {
    isDropdownOpen.value = false;
    searchQuery.value = '';
    return;
  }
  
  form.components.push({
    id: null,
    tariff_component_id: comp.id,
    name: comp.name,
    percentage: 0,
    needs_review: false
  });
  
  searchQuery.value = '';
  isDropdownOpen.value = false;
};

const removeComponent = (index) => {
  form.components.splice(index, 1);
};

const closeDropdown = () => {
  setTimeout(() => {
    isDropdownOpen.value = false;
  }, 200);
};

const totalPercentage = computed(() => {
  return form.components.reduce((sum, comp) => sum + Number(comp.percentage || 0), 0);
});

const percentageStatus = computed(() => {
  const total = totalPercentage.value;
  if (total === 100) return 'success';
  if (total < 100) return 'warning';
  return 'danger';
});

const handleSubmit = () => {
  const payload = {
    name: form.name.trim(),
    code: form.code.trim() === '' ? null : form.code.trim(),
    description: form.description ? form.description.trim() : null,
    components: form.components.map(c => ({
      tariff_component_id: c.tariff_component_id,
      percentage: Number(c.percentage)
    }))
  };
  emit('submit', payload);
};

const fieldError = (field) => props.errors?.[field]?.[0] || null;
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="editData ? 'Edit Jenis Tarif' : 'Tambah Jenis Tarif'"
    :is-submitting="isSubmitting"
    size="lg"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <div v-if="errors.general" class="alert-error">
        {{ errors.general }}
      </div>

      <div class="form-row">
        <div class="form-group flex-1">
          <label for="name" class="form-label required">Nama Jenis Tarif</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="form-control"
            :class="{ 'is-invalid': fieldError('name') }"
            required
            maxlength="255"
          >
          <div v-if="fieldError('name')" class="invalid-feedback">{{ fieldError('name') }}</div>
        </div>
        
        <div class="form-group flex-1">
          <label for="code" class="form-label required">Kode</label>
          <input
            id="code"
            v-model="form.code"
            type="text"
            class="form-control uppercase"
            :class="{ 'is-invalid': fieldError('code') }"
            required
            maxlength="50"
          >
          <div v-if="fieldError('code')" class="invalid-feedback">{{ fieldError('code') }}</div>
        </div>
      </div>

      <div class="form-group">
        <label for="description" class="form-label">Deskripsi <span class="text-muted font-normal text-sm">(Opsional)</span></label>
        <textarea
          id="description"
          v-model="form.description"
          class="form-control form-textarea"
          :class="{ 'is-invalid': fieldError('description') }"
          placeholder="Masukkan deskripsi jenis tarif"
          rows="3"
        ></textarea>
        <div v-if="fieldError('description')" class="invalid-feedback">{{ fieldError('description') }}</div>
      </div>

      <hr class="divider" />
      
      <div class="components-section">
        <h3 class="section-title">Komponen Pembentuk Tarif</h3>
        
        <div class="form-group">
          <label class="form-label required">Cari & Tambah Komponen</label>
          <div class="combobox-wrapper">
            <input 
              type="text" 
              v-model="searchQuery"
              @input="handleSearch"
              @focus="handleSearch"
              @blur="closeDropdown"
              class="form-control" 
              placeholder="Ketik nama komponen tarif..."
            />
            
            <div class="combobox-dropdown" v-if="isDropdownOpen && searchQuery">
              <div v-if="searchLoading" class="dropdown-item text-center text-muted">Mencari...</div>
              <div v-else-if="tariffComponents.length === 0" class="dropdown-item text-center text-muted">Tidak ditemukan.</div>
              <div 
                v-else 
                v-for="comp in tariffComponents" 
                :key="comp.id" 
                class="dropdown-item"
                @mousedown.prevent="selectComponent(comp)"
              >
                {{ comp.name }}
              </div>
            </div>
          </div>
          <div v-if="fieldError('components')" class="invalid-feedback block mt-1">{{ fieldError('components') }}</div>
        </div>

        <div class="components-table-wrapper">
          <table class="components-table">
            <thead>
              <tr>
                <th>Nama Komponen</th>
                <th width="150">Persentase (%)</th>
                <th width="80" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="form.components.length === 0">
                <td colspan="3" class="text-center text-muted py-3">Belum ada komponen dipilih</td>
              </tr>
              <tr v-for="(comp, idx) in form.components" :key="idx">
                <td class="font-medium text-navy">{{ comp.name }}</td>
                <td>
                  <div class="percentage-input">
                    <input 
                      type="number" 
                      v-model="comp.percentage" 
                      class="form-control text-right" 
                      min="0" 
                      max="100" 
                      step="0.01"
                      required
                    />
                    <span class="pct-sign">%</span>
                  </div>
                </td>
                <td class="text-center">
                  <button type="button" class="btn-icon delete" @click="removeComponent(idx)" title="Hapus Komponen">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td class="text-right font-medium">Total Persentase:</td>
                <td class="font-bold text-right" :class="`text-${percentageStatus}`">
                  {{ totalPercentage }}%
                </td>
                <td></td>
              </tr>
            </tfoot>
          </table>
          <div v-if="totalPercentage !== 100 && form.components.length > 0" class="alert-warning mt-2">
            Peringatan: Total persentase komponen idealnya adalah 100%. Saat ini {{ totalPercentage }}%.
          </div>
        </div>
      </div>
    </div>
  </MasterDataFormModal>
</template>

<style scoped>
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-row {
  display: flex;
  gap: 1rem;
}

.flex-1 {
  flex: 1;
}

.alert-error {
  background-color: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.alert-warning {
  background-color: #fef3c7;
  border-left: 4px solid #f59e0b;
  color: #b45309;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.85rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text-navy);
}

.required::after {
  content: '*';
  color: #ef4444;
  margin-left: 0.25rem;
}

.form-control {
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background-color: #fff;
  width: 100%;
}

.form-control.uppercase {
  text-transform: uppercase;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.invalid-feedback {
  font-size: 0.8rem;
  color: #ef4444;
  margin-top: 0.25rem;
}

.block {
  display: block;
}

.mt-1 {
  margin-top: 0.25rem;
}

.mt-2 {
  margin-top: 0.5rem;
}

.divider {
  border: none;
  border-top: 1px solid var(--color-border-soft);
  margin: 0.5rem 0;
}

.section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 1rem 0;
}

/* Combobox styles */
.combobox-wrapper {
  position: relative;
}

.combobox-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background-color: #f8fafc;
}

/* Table styles inside modal */
.components-table-wrapper {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
  margin-top: 0.5rem;
}

.components-table {
  width: 100%;
  border-collapse: collapse;
}

.components-table th,
.components-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.9rem;
}

.components-table th {
  background: #f1f5f9;
  text-align: left;
  font-weight: 600;
  color: #475569;
}

.components-table tfoot td {
  background: #f1f5f9;
}

.components-table tbody tr {
  background: white;
}

.percentage-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: #94a3b8;
  border-radius: 4px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: #fee2e2;
  color: #ef4444;
}

.btn-icon svg {
  width: 18px;
  height: 18px;
}

.text-navy { color: var(--color-text-navy); }
.text-muted { color: var(--color-text-secondary); }
.text-center { text-align: center; }
.text-right { text-align: right; }
.font-medium { font-weight: 500; }
.font-bold { font-weight: 600; }
.text-success { color: #16a34a; }
.text-warning { color: #d97706; }
.text-danger { color: #dc2626; }
.py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
</style>
