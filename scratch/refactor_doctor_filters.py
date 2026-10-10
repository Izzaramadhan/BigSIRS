import re

with open('frontend/src/components/master-data/doctors/DoctorFilters.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the is_active filter select
content = re.sub(
    r'<div class="filter-group">\s*<label for="status-filter" class="filter-label">Status</label>\s*<select\s*id="status-filter"\s*v-model="localFilters\.is_active"\s*class="filter-select"\s*@change="emitFilter"\s*>\s*<option :value="null">Semua Status</option>\s*<option :value="true">Aktif</option>\s*<option :value="false">Nonaktif</option>\s*</select>\s*</div>',
    '',
    content,
    flags=re.DOTALL
)

with open('frontend/src/components/master-data/doctors/DoctorFilters.vue', 'w', encoding='utf-8') as f:
    f.write(content)
