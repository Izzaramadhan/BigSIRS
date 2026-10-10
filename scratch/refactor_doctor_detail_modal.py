import re

with open('frontend/src/components/master-data/doctors/DoctorDetailModal.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the status item
content = re.sub(
    r'<div class="detail-item">\s*<span class="detail-label">Status</span>\s*<span class="detail-value">\s*<span class="status-badge" :class="professional\.is_active \? \'active\' : \'inactive\'">\s*\{\{\s*professional\.is_active \? \'Aktif\' : \'Nonaktif\'\s*\}\}\s*</span>\s*</span>\s*</div>',
    '',
    content,
    flags=re.DOTALL
)

# Remove the status-badge styles
content = re.sub(r'\.status-badge \{.*?\n\}\n\n\.status-badge\.active \{.*?\n\}\n\n\.status-badge\.inactive \{.*?\n\}\n\n', '', content, flags=re.DOTALL)

with open('frontend/src/components/master-data/doctors/DoctorDetailModal.vue', 'w', encoding='utf-8') as f:
    f.write(content)
