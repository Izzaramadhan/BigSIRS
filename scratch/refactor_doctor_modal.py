import re
import sys

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Add import
content = content.replace(
    "import BaseSelect from '@/components/common/BaseSelect.vue';",
    "import BaseSelect from '@/components/common/BaseSelect.vue';\nimport MasterDataFormModal from '@/components/master-data/shared/MasterDataFormModal.vue';"
)

# 2. Template refactor
template_start = content.find('<template>')
template_end = content.find('</template>', template_start)

template_content = content[template_start:template_end + 11]

# Find the <div v-if="open" class="modal-overlay"...>
# and replace with <MasterDataFormModal>
new_template = re.sub(
    r'<div v-if="open" class="modal-overlay" @click\.self="emit\(\'close\'\)">\s*<div class="modal-content modal-xl">\s*<div class="modal-header">.*?</div>',
    """<MasterDataFormModal
    :is-open="open"
    :title="isEditMode ? 'Edit Dokter' : 'Tambah Dokter'"
    :is-submitting="isSubmitting || loadingDetail || !!detailError"
    :submit-text="isEditMode ? 'Simpan Perubahan' : 'Simpan'"
    size="xl"
    @close="emit('close')"
    @submit="handleSubmit"
  >
    <div class="modal-form">""",
    template_content,
    flags=re.DOTALL
)

# Replace <form ... class="modal-body" ...> with just <div class="modal-body">
new_template = re.sub(
    r'<form v-else id="doctor-form" @submit\.prevent="handleSubmit" class="modal-body" novalidate>',
    '<div v-else class="modal-body">',
    new_template
)

# Remove the sticky footer
new_template = re.sub(
    r'<div class="modal-footer sticky-footer">.*?</div>\s*</form>\s*</div>\s*</div>',
    '</div>\n    </div>\n  </MasterDataFormModal>',
    new_template,
    flags=re.DOTALL
)

# Remove the is_active checkbox group
new_template = re.sub(
    r'<div class="form-group checkbox-group"[^>]*>\s*<label class="checkbox-label">\s*<input type="checkbox" v-model="form\.professional\.is_active" class="form-checkbox">\s*<span class="label-text">Status Dokter Aktif</span>\s*</label>\s*</div>',
    '',
    new_template,
    flags=re.DOTALL
)

content = content[:template_start] + new_template + content[template_end + 11:]

# Remove unused CSS
content = re.sub(r'\.modal-overlay \{.*?\n\}\n\n@keyframes fadeIn \{.*?\n\}\n\n\.modal-content \{.*?\n\}\n\n@keyframes slideUp \{.*?\n\}\n\n\.modal-xl \{.*?\n\}\n\n\.modal-header \{.*?\n\}\n\n\.modal-title \{.*?\n\}\n\n\.btn-close \{.*?\n\}\n\n\.btn-close:hover \{.*?\n\}\n\n\.modal-body \{.*?\n\}\n\n\.modal-footer \{.*?\n\}\n\n\.sticky-footer \{.*?\n\}\n\n', '', content, flags=re.DOTALL)

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'w', encoding='utf-8') as f:
    f.write(content)
