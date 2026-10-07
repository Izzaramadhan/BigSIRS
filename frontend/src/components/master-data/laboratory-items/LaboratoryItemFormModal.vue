<script setup>
import { reactive, watch } from 'vue'
import { laboratoryItemsService } from '@/services/master-data/laboratoryItems.service'

const props = defineProps({
  isOpen: Boolean,
  item: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])
const form = reactive({ name: '', reference_value: '', unit: '' })
const state = reactive({ submitting: false, errors: {}, generalError: '' })

watch(
  () => [props.isOpen, props.item],
  () => {
    if (!props.isOpen) return
    form.name = props.item?.name ?? ''
    form.reference_value = props.item?.reference_value ?? ''
    form.unit = props.item?.unit ?? ''
    state.errors = {}
    state.generalError = ''
  },
  { immediate: true },
)

const submit = async () => {
  if (state.submitting) return
  state.submitting = true
  state.errors = {}
  state.generalError = ''

  const payload = {
    name: form.name.trim(),
    reference_value: form.reference_value.trim() || null,
    unit: form.unit.trim() || null,
  }

  try {
    if (props.item) await laboratoryItemsService.update(props.item.id, payload)
    else await laboratoryItemsService.create(payload)
    emit('saved')
  } catch (error) {
    state.errors = error.response?.data?.errors ?? {}
    state.generalError = error.response?.data?.message || 'Gagal menyimpan item lab.'
  } finally {
    state.submitting = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-backdrop" @mousedown.self="emit('close')">
      <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="item-lab-title">
        <header class="modal-header">
          <div>
            <span class="eyebrow">Master Data Lab</span>
            <h2 id="item-lab-title">{{ item ? 'Edit Item Lab' : 'Tambah Item Lab' }}</h2>
          </div>
          <button type="button" class="icon-button" aria-label="Tutup" @click="emit('close')">&times;</button>
        </header>

        <form id="laboratory-item-form" class="modal-body" @submit.prevent="submit">
          <p v-if="state.generalError" class="alert-error">{{ state.generalError }}</p>

          <label class="field">
            <span>Nama Item Lab <b>*</b></span>
            <input v-model="form.name" maxlength="255" autocomplete="off" :aria-invalid="Boolean(state.errors.name)" />
            <small v-if="state.errors.name">{{ state.errors.name[0] }}</small>
          </label>

          <label class="field">
            <span>Standar Normal / Nilai Rujukan</span>
            <textarea v-model="form.reference_value" rows="4" placeholder="Contoh: Negatif, 30-70, L: 45, P: 50, <200"></textarea>
            <small v-if="state.errors.reference_value">{{ state.errors.reference_value[0] }}</small>
          </label>

          <label class="field">
            <span>Satuan</span>
            <input v-model="form.unit" maxlength="255" placeholder="Contoh: mg/dL (boleh kosong)" />
            <small v-if="state.errors.unit">{{ state.errors.unit[0] }}</small>
          </label>
        </form>

        <footer class="modal-footer">
          <button type="button" class="button secondary" :disabled="state.submitting" @click="emit('close')">Batal</button>
          <button type="submit" form="laboratory-item-form" class="button primary" :disabled="state.submitting">
            {{ state.submitting ? 'Menyimpan...' : item ? 'Simpan Perubahan' : 'Simpan' }}
          </button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-backdrop { position: fixed; inset: 0; z-index: 1100; display: grid; place-items: center; padding: 20px; background: rgba(9, 28, 47, .58); backdrop-filter: blur(3px); }
.modal-card { width: min(620px, 100%); max-height: calc(100vh - 40px); overflow: hidden; border-radius: 18px; background: #fff; box-shadow: 0 28px 80px rgba(8, 35, 58, .28); }
.modal-header, .modal-footer { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 22px 26px; }
.modal-header { border-bottom: 1px solid #e6edf2; }
.modal-header h2 { margin: 4px 0 0; color: #12344d; font-size: 1.35rem; }
.eyebrow { color: #0f8b8d; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.icon-button { border: 0; background: transparent; color: #607786; font-size: 2rem; line-height: 1; cursor: pointer; }
.modal-body { display: grid; gap: 19px; max-height: 60vh; overflow-y: auto; padding: 24px 26px; }
.field { display: grid; gap: 8px; color: #24465b; font-size: .9rem; font-weight: 700; }
.field b { color: #d34a4a; }
.field input, .field textarea { width: 100%; box-sizing: border-box; border: 1px solid #ccd9e2; border-radius: 10px; padding: 12px 14px; color: #17384e; font: inherit; font-weight: 500; outline: none; resize: vertical; }
.field input:focus, .field textarea:focus { border-color: #0f8b8d; box-shadow: 0 0 0 3px rgba(15, 139, 141, .12); }
.field small { color: #c43d3d; font-weight: 600; }
.alert-error { margin: 0; border-radius: 9px; padding: 10px 12px; background: #fff0f0; color: #a92f2f; }
.modal-footer { border-top: 1px solid #e6edf2; background: #f8fbfc; justify-content: flex-end; }
.button { min-width: 112px; border: 0; border-radius: 10px; padding: 11px 17px; font-weight: 800; cursor: pointer; }
.button:disabled { opacity: .65; cursor: not-allowed; }
.secondary { border: 1px solid #ccd9e2; background: #fff; color: #405d6f; }
.primary { background: #0f8b8d; color: #fff; }
@media (max-width: 560px) { .modal-backdrop { padding: 10px; } .modal-header, .modal-body, .modal-footer { padding-left: 18px; padding-right: 18px; } .modal-footer .button { flex: 1; } }
</style>
