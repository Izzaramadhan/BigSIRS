<script setup>
import { ref, watch, computed } from 'vue';
import { useTariffComponents } from '@/composables/useTariffComponents';

const props = defineProps({
  isOpen: Boolean,
  editData: Object,
  loading: Boolean,
  errors: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['close', 'save']);

const { 
  tariffComponents, 
  loading: searchLoading, 
  fetchTariffComponents 
} = useTariffComponents();

const formData = ref({
  name: '',
  code: '',
  description: '',
  is_active: true,
  components: []
});

const searchQuery = ref('');
const isDropdownOpen = ref(false);
const searchTimeout = ref(null);

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.editData) {
      formData.value = {
        name: props.editData.name,
        code: props.editData.code || '',
        description: props.editData.description || '',
        is_active: props.editData.is_active,
        components: props.editData.components ? props.editData.components.map(c => ({
          id: c.id,
          tariff_component_id: c.tariff_component_id,
          name: c.component ? c.component.name : 'Unknown',
          is_active: c.component ? c.component.is_active : false,
          percentage: c.percentage,
          needs_review: c.needs_review
        })) : []
      };
    } else {
      formData.value = {
        name: '',
        code: '',
        description: '',
        is_active: true,
        components: []
      };
    }
    searchQuery.value = '';
    isDropdownOpen.value = false;
  }
});

const handleSearch = (e) => {
  searchQuery.value = e.target.value;
  isDropdownOpen.value = true;
  
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value);
  }
  
  searchTimeout.value = setTimeout(() => {
    fetchTariffComponents({ search: searchQuery.value, is_active: true, per_page: 20 });
  }, 300);
};

const selectComponent = (comp) => {
  // Prevent duplicate if it's new (not legacy duplicate)
  const isExisting = formData.value.components.find(c => c.tariff_component_id === comp.id);
  if (isExisting && !isExisting.id) {
    alert('Komponen ini sudah ditambahkan.');
    isDropdownOpen.value = false;
    searchQuery.value = '';
    return;
  }
  
  formData.value.components.push({
    id: null,
    tariff_component_id: comp.id,
    name: comp.name,
    is_active: comp.is_active,
    percentage: 0,
    needs_review: false
  });
  
  searchQuery.value = '';
  isDropdownOpen.value = false;
};

const removeComponent = (index) => {
  formData.value.components.splice(index, 1);
};

const closeDropdown = () => {
  setTimeout(() => {
    isDropdownOpen.value = false;
  }, 200);
};

const totalPercentage = computed(() => {
  return formData.value.components.reduce((sum, comp) => sum + Number(comp.percentage || 0), 0);
});

const percentageStatus = computed(() => {
  const total = totalPercentage.value;
  if (total === 100) return 'success';
  if (total < 100) return 'warning';
  return 'danger';
});

const handleSubmit = () => {
  emit('save', {
    ...formData.value,
    code: formData.value.code.trim() === '' ? null : formData.value.code
  });
};
</script>

<template>
  <Teleport to="body">
    <div class="modal-overlay" v-if="isOpen" @click.self="$emit('close')">
      <div class="modal-content drawer-style">
        <div class="modal-header">
          <h2 class="modal-title">{{ editData ? 'Edit Jenis Tarif' : 'Tambah Jenis Tarif' }}</h2>
          <button class="btn-close" @click="$emit('close')">&times;</button>
        </div>
        
        <div class="modal-body">
          <div v-if="errors.general" class="alert alert-danger mb-4">
            {{ errors.general }}
          </div>

          <form @submit.prevent="handleSubmit" id="tariffTypeForm">
            <div class="form-row">
              <div class="form-group flex-1">
                <label class="form-label">Nama Jenis Tarif <span class="text-red">*</span></label>
                <input 
                  type="text" 
                  v-model="formData.name" 
                  class="form-control" 
                  :class="{'is-invalid': errors.name}"
                  required
                />
                <span class="error-text" v-if="errors.name">{{ errors.name[0] }}</span>
              </div>
              <div class="form-group flex-1">
                <label class="form-label">
                  Kode 
                  <span v-if="!editData || (editData && formData.code)" class="text-red">*</span>
                </label>
                <input 
                  type="text" 
                  v-model="formData.code" 
                  class="form-control uppercase" 
                  :class="{'is-invalid': errors.code}"
                  :required="!editData || (editData && editData.code !== null)"
                  :placeholder="editData && !editData.code ? 'Kode legacy kosong (Boleh diisi)' : ''"
                />
                <span class="error-text" v-if="errors.code">{{ errors.code[0] }}</span>
                <span class="help-text text-warning" v-if="editData && !editData.code && !formData.code">Kode legacy belum tersedia.</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Deskripsi</label>
              <textarea 
                v-model="formData.description" 
                class="form-control" 
                rows="2"
                :class="{'is-invalid': errors.description}"
              ></textarea>
              <span class="error-text" v-if="errors.description">{{ errors.description[0] }}</span>
            </div>

            <div class="form-group checkbox-group">
              <input type="checkbox" id="isActive" v-model="formData.is_active" class="form-checkbox" />
              <label for="isActive">Status Aktif</label>
            </div>

            <hr class="divider" />
            
            <h3 class="section-title">Komponen Pembentuk Tarif</h3>
            
            <div class="form-group">
              <label class="form-label">Cari & Tambah Komponen <span class="text-red">*</span></label>
              <div class="combobox-wrapper">
                <input 
                  type="text" 
                  :value="searchQuery"
                  @input="handleSearch"
                  @focus="handleSearch"
                  @blur="closeDropdown"
                  class="form-control" 
                  placeholder="Ketik nama komponen..."
                />
                
                <div class="dropdown-list" v-if="isDropdownOpen">
                  <div v-if="searchLoading" class="dropdown-item text-gray-500 text-center">Mencari...</div>
                  <div v-else-if="tariffComponents.length === 0" class="dropdown-item text-gray-500 text-center">Tidak ditemukan</div>
                  <div 
                    v-else 
                    v-for="comp in tariffComponents" 
                    :key="comp.id" 
                    class="dropdown-item"
                    @mousedown.prevent="selectComponent(comp)"
                  >
                    <div class="font-medium">{{ comp.name }}</div>
                    <div class="text-xs text-gray-400" v-if="comp.code">{{ comp.code }}</div>
                  </div>
                </div>
              </div>
              <span class="error-text" v-if="errors.components">{{ errors.components[0] }}</span>
            </div>

            <div class="components-table-wrapper">
              <table class="components-table">
                <thead>
                  <tr>
                    <th width="50%">Komponen</th>
                    <th width="35%">Persentase (%)</th>
                    <th width="15%" class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="formData.components.length === 0">
                    <td colspan="3" class="text-center py-4 text-gray-500">Belum ada komponen yang ditambahkan</td>
                  </tr>
                  <tr v-for="(comp, index) in formData.components" :key="index">
                    <td>
                      <div class="font-medium" :class="{'line-through text-gray-400': !comp.is_active}">{{ comp.name }}</div>
                      <div class="text-xs text-red mt-1" v-if="!comp.is_active">Komponen Tidak Aktif</div>
                    </td>
                    <td>
                      <div class="pct-input-group">
                        <input 
                          type="number" 
                          step="0.01" 
                          v-model="comp.percentage" 
                          class="form-control pct-input" 
                          :class="{'is-invalid': errors[`components.${index}.percentage`]}"
                          required
                        />
                        <span class="pct-addon">%</span>
                      </div>
                      <div class="error-text text-xs mt-1" v-if="errors[`components.${index}.percentage`]">
                        {{ errors[`components.${index}.percentage`][0] }}
                      </div>
                      <div class="error-text text-xs mt-1" v-if="errors[`components.${index}.tariff_component_id`]">
                        {{ errors[`components.${index}.tariff_component_id`][0] }}
                      </div>
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn-remove" @click="removeComponent(index)" title="Hapus baris">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td class="text-right font-bold">Total:</td>
                    <td class="font-bold" :class="`text-${percentageStatus}`">
                      {{ totalPercentage }}%
                    </td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

          </form>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" @click="$emit('close')" :disabled="loading">Batal</button>
          <button type="submit" form="tariffTypeForm" class="btn btn-primary" :disabled="loading">
            {{ loading ? 'Menyimpan...' : 'Simpan' }}
          </button>
        </div>
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
  display: flex;
  justify-content: flex-end;
  z-index: 1000;
}

.drawer-style {
  height: 100vh;
  width: 100%;
  max-width: 600px;
  background: #fff;
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s ease;
  border-radius: 0;
}

@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0;
}

.btn-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #64748b;
  line-height: 1;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  background: #fff;
}

/* Form Styles */
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
  display: block;
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-bottom: 0.5rem;
}

.form-control {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-control:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.is-invalid {
  border-color: #ef4444;
}

.is-invalid:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.error-text {
  display: block;
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.help-text {
  display: block;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.text-warning {
  color: #d97706;
}

.text-red {
  color: #ef4444;
}

.text-orange {
  color: #f97316;
}

.uppercase {
  text-transform: uppercase;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.divider {
  border: 0;
  border-top: 1px solid var(--color-border-soft);
  margin: 1.5rem 0;
}

.section-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--color-text-navy);
  margin: 0 0 1rem 0;
}

/* Combobox */
.combobox-wrapper {
  position: relative;
}

.dropdown-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  margin-top: 4px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 10;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.dropdown-item {
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid #f1f5f9;
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-item:hover {
  background: #f8fafc;
}

/* Components Table */
.components-table-wrapper {
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  overflow: hidden;
  margin-top: 1rem;
}

.components-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.components-table th,
.components-table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--color-border-soft);
}

.components-table th {
  background: #f8fafc;
  font-weight: 600;
  text-align: left;
}

.components-table tfoot td {
  background: #f8fafc;
  border-bottom: none;
}

.pct-input-group {
  display: flex;
  align-items: center;
}

.pct-input {
  border-radius: 6px 0 0 6px;
  border-right: none;
}

.pct-addon {
  background: #f1f5f9;
  border: 1px solid var(--color-border-soft);
  border-left: none;
  padding: 0.6rem 0.75rem;
  border-radius: 0 6px 6px 0;
  color: #475569;
}

.btn-remove {
  background: #fee2e2;
  color: #ef4444;
  border: none;
  width: 28px;
  height: 28px;
  border-radius: 4px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-remove:hover {
  background: #fca5a5;
}

.btn-remove svg {
  width: 16px;
  height: 16px;
}

.text-center { text-align: center; }
.text-right { text-align: right; }
.font-bold { font-weight: 700; }
.font-medium { font-weight: 500; }
.text-xs { font-size: 0.75rem; }
.text-gray-400 { color: #94a3b8; }
.text-gray-500 { color: #64748b; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.mt-1 { margin-top: 0.25rem; }
.mb-4 { margin-bottom: 1rem; }

.text-success { color: #10b981; }
.text-warning { color: #d97706; }
.text-danger { color: #ef4444; }

.line-through { text-decoration: line-through; }

/* Buttons */
.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  border: 1px solid transparent;
}

.btn-primary {
  background: var(--color-primary);
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

.btn-outline {
  background: #fff;
  border-color: var(--color-border-soft);
  color: var(--color-text-navy);
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Alerts */
.alert {
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

.alert-danger {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
}

.alert-warning {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
}
</style>
