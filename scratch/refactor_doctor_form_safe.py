import sys

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add import MasterDataFormModal
content = content.replace(
    "import BaseSelect from '@/components/common/BaseSelect.vue';",
    "import BaseSelect from '@/components/common/BaseSelect.vue';\nimport MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';"
)

# 2. Template Opening
old_opening = """<template>
  <div v-if="open" class="modal-overlay" @click.self="emit('close')">
    <div class="modal-content modal-xl">
      <div class="modal-header">
        <h2 class="modal-title">{{ isEditMode ? 'Edit' : 'Tambah' }} Master Data Dokter</h2>
        <button type="button" class="btn-close" @click="emit('close')" aria-label="Tutup modal">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>"""

new_opening = """<template>
  <MasterDataFormModal
    :is-open="open"
    :title="isEditMode ? 'Edit Dokter' : 'Tambah Dokter'"
    :is-submitting="isSubmitting || loadingDetail || !!detailError"
    :submit-text="isEditMode ? 'Simpan Perubahan' : 'Simpan'"
    size="xl"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">"""

content = content.replace(old_opening, new_opening)

# 3. <form id="doctor-form"> to <div class="modal-body">
content = content.replace('<form v-else id="doctor-form" @submit.prevent="handleSubmit" class="modal-body" novalidate>', '<div v-else class="modal-body">')

# 4. Remove active status
status_block = """            <div class="form-group checkbox-group" style="margin-top: 2rem;">
              <label class="checkbox-label">
                <input type="checkbox" v-model="form.professional.is_active" class="form-checkbox">
                <span class="label-text">Status Dokter Aktif</span>
              </label>
            </div>"""

content = content.replace(status_block, "")

# 5. Template closing (Footer and </form>)
old_footer = """        <div class="modal-footer sticky-footer">
          <button type="button" class="btn-outline" @click="emit('close')" :disabled="isSubmitting">Batal</button>
          <button type="submit" form="doctor-form" class="btn-primary" :disabled="isSubmitting || loadingDetail || !!detailError">
            <template v-if="isSubmitting">
              <span class="spinner"></span> Menyimpan...
            </template>
            <template v-else>Simpan Master Data</template>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>"""

new_footer = """      </div>
    </div>
  </MasterDataFormModal>
</template>"""

content = content.replace(old_footer, new_footer)

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'w', encoding='utf-8') as f:
    f.write(content)
