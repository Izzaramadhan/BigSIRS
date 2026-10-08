<script setup>
import { ref, watch } from 'vue'
import { useLaboratoryGroups } from '@/composables/useLaboratoryGroups'
import { useLaboratoryCategories } from '@/composables/useLaboratoryCategories'
import { useLaboratoryItems } from '@/composables/useLaboratoryItems'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  group: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const { createLaboratoryGroup, updateLaboratoryGroup, loading, error } = useLaboratoryGroups()
const { laboratoryCategories, fetchLaboratoryCategories } = useLaboratoryCategories()
const { laboratoryItems, fetchLaboratoryItems } = useLaboratoryItems()

const formData = ref({
  laboratory_category_id: '',
  name: '',
  description: '',
  price: 0,
  is_active: true,
  items: []
})

const selectedItem = ref('')

const addItem = () => {
  if (!selectedItem.value) return
  
  const itemToAdd = laboratoryItems.value.find(i => i.id === selectedItem.value)
  if (itemToAdd && !formData.value.items.some(i => i.id === selectedItem.value)) {
    formData.value.items.push(itemToAdd)
  }
  selectedItem.value = ''
}

const removeItem = (id) => {
  formData.value.items = formData.value.items.filter(i => i.id !== id)
}

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    if (props.group) {
      formData.value = {
        laboratory_category_id: props.group.laboratory_category_id,
        name: props.group.name,
        description: props.group.description || '',
        price: props.group.price || 0,
        is_active: props.group.is_active,
        items: props.group.items || []
      }
    } else {
      formData.value = {
        laboratory_category_id: '',
        name: '',
        description: '',
        price: 0,
        is_active: true,
        items: []
      }
    }
    fetchLaboratoryCategories({ per_page: 100 })
    fetchLaboratoryItems({ per_page: 500 }) // fetch all items for lookup
  }
})

const submitForm = async () => {
  let success = false
  const payload = {
    ...formData.value,
    items: formData.value.items.map(i => i.id)
  }

  if (props.group) {
    const result = await updateLaboratoryGroup(props.group.id, payload)
    if (result) success = true
  } else {
    const result = await createLaboratoryGroup(payload)
    if (result) success = true
  }

  if (success) {
    emit('saved')
  }
}


</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ group ? 'Edit Grup Lab' : 'Tambah Grup Lab' }}</h3>
          <button class="btn-close" @click="$emit('close')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        
        <div class="modal-body">
          <div v-if="error" class="alert-error mb-4">
            <div v-if="typeof error === 'object'">
              <p v-for="(msgs, field) in error" :key="field" class="error-msg">
                {{ Array.isArray(msgs) ? msgs[0] : msgs }}
              </p>
            </div>
            <p v-else>{{ error }}</p>
          </div>

          <form @submit.prevent="submitForm" id="group-form">
            <div class="form-group">
              <label for="laboratory_category_id">Kategori Lab <span class="required">*</span></label>
              <select 
                id="laboratory_category_id" 
                v-model="formData.laboratory_category_id" 
                class="form-control" 
                required
              >
                <option value="">-- Pilih Kategori --</option>
                <option v-for="cat in laboratoryCategories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="name">Nama Grup <span class="required">*</span></label>
              <input 
                type="text" 
                id="name" 
                v-model="formData.name" 
                class="form-control" 
                required 
              />
            </div>

            <div class="form-group">
              <label for="description">Deskripsi</label>
              <textarea 
                id="description" 
                v-model="formData.description" 
                class="form-control" 
                rows="3"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="price">Harga</label>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input 
                  type="number" 
                  id="price" 
                  v-model="formData.price" 
                  class="form-control" 
                  min="0"
                />
              </div>
            </div>

            <div class="form-group checkbox-group">
              <input 
                type="checkbox" 
                id="is_active" 
                v-model="formData.is_active" 
              />
              <label for="is_active">Aktif</label>
            </div>

            <hr class="my-4" />
            <h4 class="section-title">Item Pemeriksaan Lab</h4>

            <div class="form-group">
              <label>Tambah Item Lab</label>
              <div class="item-selector">
                <select v-model="selectedItem" class="form-control">
                  <option value="">-- Pilih Item Lab --</option>
                  <option v-for="item in laboratoryItems" :key="item.id" :value="item.id" :disabled="formData.items.some(i => i.id === item.id)">
                    {{ item.name }} {{ item.unit ? `(${item.unit})` : '' }}
                  </option>
                </select>
                <button type="button" class="btn-add-item" @click="addItem" :disabled="!selectedItem">Tambah</button>
              </div>
            </div>

            <div class="selected-items">
              <div v-if="formData.items.length === 0" class="empty-items text-muted text-center py-4">
                Belum ada item yang dipilih
              </div>
              <ul v-else class="item-list">
                <li v-for="item in formData.items" :key="item.id" class="item-list-row">
                  <div class="item-info">
                    <span class="item-name">{{ item.name }}</span>
                    <span v-if="item.unit" class="item-unit">{{ item.unit }}</span>
                  </div>
                  <button type="button" class="btn-remove-item" @click="removeItem(item.id)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  </button>
                </li>
              </ul>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="loading">Batal</button>
          <button type="submit" form="group-form" class="btn-primary" :disabled="loading">
            <span v-if="loading">Menyimpan...</span>
            <span v-else>Simpan</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 500;
  padding: 1rem;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: #ffffff;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #ffffff;
  border-radius: 12px 12px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.btn-close {
  background: transparent;
  border: none;
  color: var(--color-text-secondary);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  display: flex;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f1f5f9;
  color: var(--color-text-navy);
}
.btn-close svg {
  width: 20px; height: 20px;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: var(--color-text-navy);
  font-size: 0.9rem;
}

.required { color: #dc2626; }

.form-control {
  width: 100%;
  padding: 0.6rem 1rem;
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  font-size: 0.95rem;
  transition: all 0.2s;
  box-sizing: border-box;
}

.form-control:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-primary-light);
}

.input-group {
  display: flex;
  align-items: center;
}
.input-group-text {
  background: #f8fafc;
  border: 1px solid var(--color-border-soft);
  border-right: none;
  padding: 0.6rem 1rem;
  border-radius: 6px 0 0 6px;
  color: var(--color-text-secondary);
  font-weight: 500;
}
.input-group .form-control {
  border-radius: 0 6px 6px 0;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-group label {
  margin-bottom: 0;
  cursor: pointer;
}
.checkbox-group input {
  width: 1.1rem; height: 1.1rem; cursor: pointer;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--color-text-navy);
  margin-bottom: 1rem;
}

.item-selector {
  display: flex;
  gap: 0.5rem;
}

.btn-add-item {
  background: var(--color-primary-light);
  color: var(--color-primary-dark);
  border: none;
  padding: 0 1rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}
.btn-add-item:disabled { opacity: 0.5; cursor: not-allowed; }

.selected-items {
  border: 1px solid var(--color-border-soft);
  border-radius: 6px;
  background: #f8fafc;
  min-height: 100px;
  max-height: 250px;
  overflow-y: auto;
}

.item-list {
  list-style: none;
  padding: 0; margin: 0;
}
.item-list-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  background: #fff;
}
.item-list-row:last-child { border-bottom: none; }
.item-info { display: flex; align-items: center; gap: 0.5rem; }
.item-name { font-weight: 500; color: var(--color-text-navy); }
.item-unit { font-size: 0.8rem; color: #64748b; background: #f1f5f9; padding: 0.1rem 0.4rem; border-radius: 4px; }

.btn-remove-item {
  background: #fee2e2;
  color: #ef4444;
  border: none;
  width: 24px; height: 24px;
  border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-remove-item:hover { background: #fca5a5; }
.btn-remove-item svg { width: 14px; height: 14px; }

.my-4 { margin-top: 1.5rem; margin-bottom: 1.5rem; border-top: 1px solid var(--color-border-soft); border-bottom: none; border-left: none; border-right: none; }

.alert-error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #f87171;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
}
.error-msg { margin: 0 0 0.25rem 0; }
.error-msg:last-child { margin-bottom: 0; }
.mb-4 { margin-bottom: 1rem; }

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #ffffff;
  border-radius: 0 0 12px 12px;
}

.btn-cancel {
  padding: 0.6rem 1.25rem;
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  color: var(--color-text-navy);
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}
.btn-cancel:hover { background: #f8fafc; }

.btn-primary {
  padding: 0.6rem 1.25rem;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}
.btn-primary:hover { opacity: 0.9; }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }
</style>
