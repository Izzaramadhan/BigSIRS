import re

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace classes
content = content.replace('class="form-input"', 'class="form-control"')
content = content.replace('class="form-row"', 'class="form-grid"')
content = content.replace('class="form-row grid-3"', 'class="form-grid" style="grid-template-columns: repeat(3, 1fr);"')
content = content.replace('class="form-row grid-4"', 'class="form-grid" style="grid-template-columns: repeat(4, 1fr);"')

# For error states
# old: :class="{ 'has-error': fieldError('xxx') }"
# new: :class="{ 'is-invalid': fieldError('xxx') }"
content = content.replace("'has-error'", "'is-invalid'")

# For error messages
# old: <span v-if="fieldError('xxx')" class="error-message">{{ fieldError('xxx') }}</span>
# new: <div v-if="fieldError('xxx')" class="invalid-feedback">{{ fieldError('xxx') }}</div>
content = re.sub(r'<span (v-if="fieldError[^>]+) class="error-message">(.*?)</span>', r'<div \1 class="invalid-feedback">\2</div>', content)

# Check if there are remaining form-input
content = content.replace('form-input', 'form-control')

# Write back
with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'w', encoding='utf-8') as f:
    f.write(content)
