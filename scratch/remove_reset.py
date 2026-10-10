import re

with open('frontend/src/components/master-data/doctors/DoctorFilters.vue', 'r', encoding='utf-8') as f:
    content = f.read()

content = re.sub(
    r'<button[^>]*@click="emit\(\'reset\'\)"[^>]*>.*?</button>',
    '',
    content,
    flags=re.DOTALL
)

# And remove 'reset' from emits
content = content.replace("['filter', 'reset', 'refresh']", "['filter', 'refresh']")

with open('frontend/src/components/master-data/doctors/DoctorFilters.vue', 'w', encoding='utf-8') as f:
    f.write(content)
