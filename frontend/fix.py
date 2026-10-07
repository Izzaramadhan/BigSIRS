import os

file = 'src/views/master-data/ActivityTypeView.vue'
with open(file, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('activityTypeFormModal', 'ActivityTypeFormModal')
content = content.replace('useactivityTypes', 'useActivityTypes')
content = content.replace('fetchactivityTypes', 'fetchActivityTypes')
content = content.replace('deleteactivityTypes', 'deleteActivityTypes')
content = content.replace('deleteactivityType', 'deleteActivityType')

with open(file, 'w', encoding='utf-8') as f:
    f.write(content)

print("Fixed view")
