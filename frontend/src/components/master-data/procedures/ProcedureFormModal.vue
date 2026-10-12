<script setup>
import { ref, watch, computed } from 'vue';
import { useProcedureCategories } from '@/composables/useProcedureCategories';
import { usePolyclinics } from '@/composables/usePolyclinics';
import { useTariffTypes } from '@/composables/useTariffTypes';
import { useIcd9s } from '@/composables/useIcd9s';
import { useReportGroups } from '@/composables/useReportGroups';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import SearchableMultiSelect from '@/components/common/SearchableMultiSelect.vue';
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

const { items: procedureCategories, fetchProcedureCategories } = useProcedureCategories();
const { items: polyclinics, fetchPolyclinics } = usePolyclinics();
const { tariffTypes, fetchTariffTypes } = useTariffTypes();
const { icd9s, fetchIcd9s } = useIcd9s();
const { reportGroups, fetchReportGroups } = useReportGroups();

const formData = ref({
  code: '',
  name: '',
  procedure_category_id: '',
  icd9_cm_id: '',
  is_visible: true,
  polyclinics: [],
  report_groups: [],
  tariffs: []
});

const selectedTariffTypeId = ref('');

const isEdit = computed(() => !!props.editData);

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    if (procedureCategories.value.length === 0) await fetchProcedureCategories({ per_page: 500 });
    if (polyclinics.value.length === 0) await fetchPolyclinics({ per_page: 500 });
    if (tariffTypes.value.length === 0) await fetchTariffTypes({ per_page: 500 });
    if (icd9s.value.length === 0) await fetchIcd9s({ per_page: 500 });
    if (reportGroups.value.length === 0) await fetchReportGroups({ per_page: 500 });

    if (props.editData) {
      formData.value = {
        code: props.editData.code || '',
        name: props.editData.name || '',
        procedure_category_id: props.editData.procedure_category_id || '',
        icd9_cm_id: props.editData.icd9_cm_id || '',
        is_visible: props.editData.is_visible ?? true,
        polyclinics: props.editData.polyclinics?.map(p => p.id) || [],
        report_groups: props.editData.report_groups?.map(r => r.id) || [],
        tariffs: props.editData.tariffs?.map(t => {
          let mappedComponents = [];
          if (t.components && t.components.length > 0) {
            mappedComponents = t.components.map(c => ({
              id: c.id,
              tariff_component_id: c.tariff_component_id,
              amount: c.amount,
              _name: c.tariff_component?.name || 'Unknown',
              _percentage: c.percentage_snapshot || 0
            }));
          } else {
            const masterType = tariffTypes.value.find(mt => mt.id === t.tariff_type_id);
            if (masterType && masterType.components) {
              mappedComponents = masterType.components.map(comp => ({
                tariff_component_id: comp.tariff_component_id,
                amount: '', 
                _name: comp.component?.name || 'Unknown',
                _percentage: comp.percentage || 0
              }));
            }
          }
          return {
            id: t.id,
            tariff_type_id: t.tariff_type_id,
            _tariff_type_name: t.tariff_type?.name || 'Unknown',
            components: mappedComponents
          };
        }) || []
      };
      
      // Select the first available tariff type for editing
      if (formData.value.tariffs.length > 0) {
        selectedTariffTypeId.value = formData.value.tariffs[0].tariff_type_id;
      } else {
        selectedTariffTypeId.value = '';
      }
    } else {
      formData.value = {
        code: '',
        name: '',
        procedure_category_id: '',
        icd9_cm_id: '',
        is_visible: true,
        polyclinics: [],
        report_groups: [],
        tariffs: []
      };
      selectedTariffTypeId.value = '';
    }
  }
}, { immediate: true });

const currentTariff = computed(() => {
  if (!selectedTariffTypeId.value) return null;
  return formData.value.tariffs.find(t => t.tariff_type_id === selectedTariffTypeId.value);
});

const handleTariffTypeChange = () => {
  if (!selectedTariffTypeId.value) return;
  
  const existing = formData.value.tariffs.find(t => t.tariff_type_id === selectedTariffTypeId.value);
  if (!existing) {
    const masterType = tariffTypes.value.find(t => t.id === selectedTariffTypeId.value);
    if (masterType) {
      formData.value.tariffs.push({
        tariff_type_id: masterType.id,
        components: (masterType.components || []).map(comp => ({
          tariff_component_id: comp.tariff_component_id,
          amount: 0,
          _name: comp.component?.name || 'Unknown',
          _percentage: comp.percentage || 0
        }))
      });
    }
  }
};

const combinedTariffTypes = computed(() => {
  const options = [...tariffTypes.value];
  
  // Inject saved tariffs that are not in the options
  formData.value.tariffs.forEach(t => {
    if (!options.some(opt => opt.id === t.tariff_type_id)) {
      options.push({
        id: t.tariff_type_id,
        name: (t._tariff_type_name || 'Unknown') + ' (Tersimpan)',
        isFallback: true
      });
    }
  });
  
  return options;
});

const handleTariffRemove = () => {
  if (!selectedTariffTypeId.value) return;
  if (confirm('Apakah Anda yakin ingin menghapus rincian tarif ini?')) {
    formData.value.tariffs = formData.value.tariffs.filter(t => t.tariff_type_id !== selectedTariffTypeId.value);
    selectedTariffTypeId.value = '';
  }
};

const currentTariffTotal = computed(() => {
  if (!currentTariff.value) return 0;
  return currentTariff.value.components.reduce((sum, comp) => sum + (Number(comp.amount) || 0), 0);
});

const tariffErrors = computed(() => {
  if (!props.errors) return [];
  const msgs = [];
  Object.keys(props.errors).forEach(key => {
    if (key.startsWith('tariffs')) {
      msgs.push(...props.errors[key]);
    }
  });
  return [...new Set(msgs)];
});

const isIncompleteTariffs = computed(() => {
  return formData.value.tariffs.some(t => {
    if (t.components.length === 0) return true;
    return t.components.some(c => c.amount === '' || c.amount === null || c.amount === undefined);
  });
});

const handleSubmit = () => {
  const payload = {
    ...formData.value,
    tariffs: formData.value.tariffs.map(t => ({
      id: t.id,
      tariff_type_id: t.tariff_type_id,
      components: t.components.map(c => ({
        id: c.id,
        tariff_component_id: c.tariff_component_id,
        amount: c.amount
      }))
    }))
  };
  emit('submit', payload);
};

const fieldError = (field) => props.errors?.[field] || null;
</script>

<template>
  <MasterDataFormModal
    :is-open="isOpen"
    :title="isEdit ? 'Edit Tindakan' : 'Tambah Tindakan'"
    :is-submitting="isSubmitting"
    :is-submit-disabled="isIncompleteTariffs"
    size="xl"
    @close="$emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">
      <div v-if="errors?.general" class="alert-error mb-4">
        {{ errors.general }}
      </div>

      <div class="form-section">
        <h4 class="section-title">Informasi Dasar</h4>
        <div class="grid grid-cols-2 gap-4">
          <div class="form-group">
            <label class="form-label">Kode Tindakan</label>
            <input 
              type="text" 
              v-model="formData.code" 
              class="form-control" 
              :class="{ 'is-invalid': fieldError('code') }"
              placeholder="Kode Tindakan"
            />
            <span class="invalid-feedback" v-if="fieldError('code')">{{ fieldError('code')[0] }}</span>
          </div>
          
          <div class="form-group">
            <label class="form-label">ICD-9-CM</label>
            <SearchableSelect 
              v-model="formData.icd9_cm_id"
              :options="icd9s"
              placeholder="-- Pilih ICD-9 (Opsional) --"
              :class="{ 'is-invalid': fieldError('icd9_cm_id') }"
            />
            <span class="invalid-feedback" v-if="fieldError('icd9_cm_id')">{{ fieldError('icd9_cm_id')[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label required">Nama Tindakan</label>
            <input 
              type="text" 
              v-model="formData.name" 
              class="form-control" 
              :class="{ 'is-invalid': fieldError('name') }"
              placeholder="Nama Tindakan"
              required
            />
            <span class="invalid-feedback" v-if="fieldError('name')">{{ fieldError('name')[0] }}</span>
          </div>

          <div class="form-group">
            <label class="form-label required">Kategori Tindakan</label>
            <SearchableSelect 
              v-model="formData.procedure_category_id"
              :options="procedureCategories"
              placeholder="-- Pilih Kategori --"
              :class="{ 'is-invalid': fieldError('procedure_category_id') }"
            />
            <span class="invalid-feedback" v-if="fieldError('procedure_category_id')">{{ fieldError('procedure_category_id')[0] }}</span>
          </div>
        </div>
      </div>

      <div class="form-section mt-4">
        <h4 class="section-title">Detail Tarif</h4>
        
        <div v-if="tariffErrors.length > 0" class="alert-error mb-4">
          <ul class="error-list">
            <li v-for="(err, i) in tariffErrors" :key="i">{{ err }}</li>
          </ul>
        </div>

        <div v-if="isIncompleteTariffs" class="alert-warning mb-4">
          Rincian biaya tindakan ini belum lengkap. Silakan lengkapi nominal setiap komponen sebelum menyimpan.
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="form-group">
            <label class="form-label">Jenis Tarif</label>
            <select 
              v-model="selectedTariffTypeId" 
              class="form-control"
              @change="handleTariffTypeChange"
            >
              <option value="">-- Pilih Jenis Tarif --</option>
              <option v-for="tt in combinedTariffTypes" :key="tt.id" :value="tt.id">
                {{ tt.name }}
              </option>
            </select>
          </div>
        </div>

        <div v-if="currentTariff" class="tariff-details-card mt-3">
          <div class="flex justify-between items-center mb-2">
            <h5 class="text-navy font-bold">Rincian Komponen</h5>
            <button type="button" class="btn-sm btn-danger-outline" @click="handleTariffRemove">Hapus Tarif Ini</button>
          </div>
          <p class="text-sm text-gray-500 mb-3">
            Nominal adalah biaya final per komponen. Persentase hanya ditampilkan sebagai referensi dan tidak dihitung ulang oleh sistem.
          </p>

          <div v-if="currentTariff.components.length === 0" class="alert-warning mb-3">
            Jenis Tarif ini belum memiliki komponen. Lengkapi Master Jenis Tarif terlebih dahulu.
          </div>
          
          <table v-else class="components-table">
            <thead>
              <tr>
                <th>Komponen</th>
                <th width="15%">Persentase</th>
                <th width="35%">Biaya Final (Rp)</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(comp, cIndex) in currentTariff.components" :key="cIndex">
                <td>{{ comp._name }}</td>
                <td>{{ comp._percentage }}%</td>
                <td>
                  <input 
                    type="number" 
                    v-model.number="comp.amount" 
                    class="form-control text-right"
                    min="0"
                    step="0.01"
                    required
                  />
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2" class="text-right font-bold">Total:</td>
                <td class="text-right font-bold text-primary">Rp {{ currentTariffTotal.toLocaleString('id-ID') }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="form-section mt-4">
        <h4 class="section-title">Relasi dan Visibilitas</h4>
        
        <div class="grid grid-cols-2 gap-4">
          <div class="form-group">
            <label class="form-label">Poliklinik Terkait</label>
            <SearchableMultiSelect 
              v-model="formData.polyclinics"
              :options="polyclinics"
              placeholder="-- Pilih Poliklinik --"
            />
          </div>

          <div class="form-group">
            <label class="form-label">Kelompok Laporan</label>
            <SearchableMultiSelect 
              v-model="formData.report_groups"
              :options="reportGroups"
              placeholder="-- Pilih Kelompok Laporan --"
            />
          </div>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">Status Tindakan</label>
          <div class="status-options">
            <label class="radio-label">
              <input type="radio" v-model="formData.is_visible" :value="true" name="is_visible" />
              <span class="radio-text">Aktif (Tampil)</span>
            </label>
            <label class="radio-label">
              <input type="radio" v-model="formData.is_visible" :value="false" name="is_visible" />
              <span class="radio-text">Nonaktif (Sembunyi)</span>
            </label>
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
  gap: 1.5rem;
}

.grid {
  display: grid;
}

.grid-cols-2 {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.gap-4 {
  gap: 1rem;
}

.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }

.flex { display: flex; }
.justify-between { justify-content: space-between; }
.items-center { align-items: center; }

.section-title {
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin: 0 0 1rem 0;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--color-border-soft);
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

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.text-right {
  text-align: right;
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

.alert-error {
  background-color: #fee2e2;
  border-left: 4px solid #ef4444;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.error-list {
  margin: 0;
  padding-left: 1.5rem;
}

.alert-warning {
  background-color: #fef3c7;
  border-left: 4px solid #f59e0b;
  color: #b45309;
  padding: 0.75rem 1rem;
  border-radius: 4px;
  font-size: 0.9rem;
}

.tariff-details-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
}

.text-navy { color: var(--color-text-navy); }
.text-primary { color: var(--color-primary); }
.text-gray-500 { color: #64748b; }
.font-bold { font-weight: 600; }
.text-sm { font-size: 0.875rem; }

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 4px;
}

.btn-danger-outline {
  background: transparent;
  border: 1px solid #ef4444;
  color: #ef4444;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger-outline:hover {
  background: #ef4444;
  color: white;
}

.components-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
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
  background: #f8fafc;
  border-top: 2px solid #e2e8f0;
}

.status-options {
  display: flex;
  gap: 1.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.radio-label input[type="radio"] {
  accent-color: var(--color-primary);
  width: 1.1rem;
  height: 1.1rem;
}

.radio-text {
  font-size: 0.95rem;
  color: var(--color-text-navy);
}
</style>
