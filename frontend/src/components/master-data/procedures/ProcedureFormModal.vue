<script setup>
import { ref, watch, computed } from 'vue';
import { useProcedureCategories } from '@/composables/useProcedureCategories';
import { usePolyclinics } from '@/composables/usePolyclinics';
import { useTariffTypes } from '@/composables/useTariffTypes';
import { useIcd9s } from '@/composables/useIcd9s';
import { useReportGroups } from '@/composables/useReportGroups';
import SearchableSelect from '@/components/common/SearchableSelect.vue';
import SearchableMultiSelect from '@/components/common/SearchableMultiSelect.vue';

const props = defineProps({
  isOpen: Boolean,
  editData: Object,
  loading: Boolean,
  errors: Object
});

const emit = defineEmits(['close', 'save']);

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
  
  // Check if this tariff already exists in formData
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
  emit('save', payload);
};


</script>

<template>
  <div v-if="isOpen" class="modal-backdrop">
    <div class="modal-container modal-xl">
      <div class="modal-header">
        <h3 class="modal-title">{{ isEdit ? 'Edit Tindakan' : 'Tambah Tindakan' }}</h3>
        <button class="modal-close" @click="$emit('close')">&times;</button>
      </div>
      
      <div class="modal-body">
        <div v-if="errors?.general" class="alert alert-danger mb-4">
          {{ errors.general }}
        </div>

        <form @submit.prevent="handleSubmit" class="form-layout">
          <div class="form-section">
            <h4 class="section-title">Informasi Dasar</h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label>Kode Tindakan</label>
                <input 
                  type="text" 
                  v-model="formData.code" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors?.code }"
                  placeholder="Kode Tindakan"
                />
                <span class="error-feedback" v-if="errors?.code">{{ errors.code[0] }}</span>
              </div>
              
              <div class="form-group">
                <label>ICD-9-CM</label>
                <SearchableSelect 
                  v-model="formData.icd9_cm_id"
                  :options="icd9s"
                  placeholder="-- Pilih ICD-9 (Opsional) --"
                  :class="{ 'is-invalid': errors?.icd9_cm_id }"
                />
                <span class="error-feedback" v-if="errors?.icd9_cm_id">{{ errors.icd9_cm_id[0] }}</span>
              </div>

              <div class="form-group">
                <label>Nama Tindakan <span class="required">*</span></label>
                <input 
                  type="text" 
                  v-model="formData.name" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors?.name }"
                  placeholder="Nama Tindakan"
                  required
                />
                <span class="error-feedback" v-if="errors?.name">{{ errors.name[0] }}</span>
              </div>

              <div class="form-group">
                <label>Kategori Tindakan <span class="required">*</span></label>
                <SearchableSelect 
                  v-model="formData.procedure_category_id"
                  :options="procedureCategories"
                  placeholder="-- Pilih Kategori --"
                  :class="{ 'is-invalid': errors?.procedure_category_id }"
                />
                <span class="error-feedback" v-if="errors?.procedure_category_id">{{ errors.procedure_category_id[0] }}</span>
              </div>
            </div>
          </div>

          <div class="form-section mt-4">
            <h4 class="section-title">Detail Tarif</h4>
            
            <div v-if="tariffErrors.length > 0" class="alert alert-danger mb-4">
              <ul style="margin:0; padding-left:1.5rem">
                <li v-for="(err, i) in tariffErrors" :key="i">{{ err }}</li>
              </ul>
            </div>

            <div v-if="isIncompleteTariffs" class="alert alert-warning mb-4" style="background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; padding: 1rem; border-radius: 8px;">
              Rincian biaya tindakan ini belum lengkap. Silakan lengkapi nominal setiap komponen sebelum menyimpan.
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="form-group">
                <label>Jenis Tarif</label>
                <select 
                  v-model="selectedTariffTypeId" 
                  class="form-control"
                  @change="handleTariffTypeChange"
                >
                  <option value="">-- Pilih Jenis Tarif --</option>
                  <option v-for="tt in tariffTypes" :key="tt.id" :value="tt.id">
                    {{ tt.name }}
                    <span v-if="formData.tariffs.some(t => t.tariff_type_id === tt.id)">(Tersimpan)</span>
                  </option>
                </select>
              </div>
            </div>

            <div v-if="currentTariff" class="tariff-details-card mt-2">
              <div class="flex justify-between items-center mb-2">
                <h5 class="text-navy font-bold">Rincian Komponen</h5>
                <button type="button" class="btn btn-sm btn-danger-outline" @click="handleTariffRemove">Hapus Tarif Ini</button>
              </div>
              <p class="text-sm text-gray-500 mb-3">
                Nominal adalah biaya final per komponen. Persentase hanya ditampilkan sebagai referensi dan tidak dihitung ulang oleh sistem.
              </p>

              <div v-if="currentTariff.components.length === 0" class="alert alert-warning mb-3" style="background: #fef2f2; color: #991b1b; border: 1px solid #f87171; padding: 0.75rem; border-radius: 6px;">
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
                        class="form-control form-control-sm text-right"
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
                <label>Poliklinik Terkait</label>
                <SearchableMultiSelect 
                  v-model="formData.polyclinics"
                  :options="polyclinics"
                  placeholder="-- Pilih Poliklinik --"
                />
              </div>

              <div class="form-group">
                <label>Kelompok Laporan</label>
                <SearchableMultiSelect 
                  v-model="formData.report_groups"
                  :options="reportGroups"
                  placeholder="-- Pilih Kelompok Laporan --"
                />
              </div>
            </div>

            <div class="form-group mt-3">
              <label>Tampil</label>
              <select v-model="formData.is_visible" class="form-control" style="width: 200px;">
                <option :value="true">Ya</option>
                <option :value="false">Tidak</option>
              </select>
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="$emit('close')" :disabled="loading">Batal</button>
        <button type="button" class="btn btn-primary" @click="handleSubmit" :disabled="loading || isIncompleteTariffs">
          {{ loading ? 'Menyimpan...' : 'Simpan' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal-container {
  background: #ffffff;
  border-radius: 12px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
.modal-xl {
  max-width: 1000px;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.modal-close {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  line-height: 1;
  color: #64748b;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #0f172a;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Grid layout utility */
.grid { display: grid; }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gap-4 { gap: 1rem; }
.mt-4 { margin-top: 1rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-2 { margin-top: 0.5rem; }
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
  margin-top: 0;
  margin-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.4rem;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--color-text-navy);
}

.required { color: #ef4444; }

.form-control {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-family: inherit;
  font-size: 0.95rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-sizing: border-box;
}

.form-control-sm {
  padding: 0.4rem 0.6rem;
  font-size: 0.85rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
}

.is-invalid {
  border-color: #f87171 !important;
}

.error-feedback {
  color: #ef4444;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

.checkbox-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 0.5rem;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e2e8f0;
  padding: 0.75rem;
  border-radius: 6px;
  background: #f8fafc;
}

.checkbox-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  cursor: pointer;
}

/* Tariffs Panel */
.tariff-details-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.25rem;
}

.text-navy { color: var(--color-text-navy); }
.font-bold { font-weight: 700; }
.text-sm { font-size: 0.875rem; }
.text-gray-500 { color: #64748b; }
.text-right { text-align: right !important; }
.text-primary { color: var(--color-primary); }

.components-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
}

.components-table th, .components-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.components-table th {
  background: #f1f5f9;
  font-weight: 600;
  text-align: left;
  color: #475569;
}

.components-table tfoot td {
  background: #f8fafc;
  border-top: 2px solid #e2e8f0;
}

/* Buttons */
.btn {
  padding: 0.6rem 1.25rem;
  border-radius: 6px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  font-family: inherit;
  border: 1px solid transparent;
}

.btn-sm {
  padding: 0.4rem 0.8rem;
  font-size: 0.8rem;
}

.btn-primary {
  background: var(--color-primary);
  color: #ffffff;
}
.btn-primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border-color: #cbd5e1;
}
.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-danger-outline {
  background: transparent;
  color: #ef4444;
  border-color: #ef4444;
}
.btn-danger-outline:hover {
  background: #fef2f2;
}
</style>
