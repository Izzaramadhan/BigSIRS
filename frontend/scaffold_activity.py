import os
import re

files_to_copy = [
    ('src/composables/useDietTypes.js', 'src/composables/useActivityTypes.js'),
    ('src/views/master-data/DietTypeView.vue', 'src/views/master-data/ActivityTypeView.vue'),
    ('src/components/master-data/nutrition-care/DietTypeFormModal.vue', 'src/components/master-data/activity-type/ActivityTypeFormModal.vue'),
    ('src/views/master-data/__tests__/DietTypeView.spec.js', 'src/views/master-data/__tests__/ActivityTypeView.spec.js'),
    ('src/components/master-data/nutrition-care/__tests__/DietTypeFormModal.spec.js', 'src/components/master-data/activity-type/__tests__/ActivityTypeFormModal.spec.js'),
]

for src, dst in files_to_copy:
    with open(src, 'r', encoding='utf-8') as f:
        content = f.read()
    
    content = content.replace('DietType', 'ActivityType')
    content = content.replace('dietType', 'activityType')
    content = content.replace('diet-types', 'activity-types')
    content = content.replace('diet_types', 'activity_types')
    content = content.replace('diet_type', 'activity_type')
    content = content.replace('Diet Type', 'Activity Type')
    content = content.replace('Asuhan Gizi', 'Jenis Kegiatan')
    content = content.replace('asuhan gizi', 'jenis kegiatan')
    content = content.replace('nutrition-care', 'activity-type')
    
    os.makedirs(os.path.dirname(dst), exist_ok=True)
    with open(dst, 'w', encoding='utf-8') as f:
        f.write(content)

print("Scaffold complete.")
