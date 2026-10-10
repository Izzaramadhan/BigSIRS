import re

with open('frontend/src/components/master-data/doctors/DoctorTable.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# Remove emit 'toggle-status'
content = content.replace(", 'toggle-status'", "")

# Remove statusLoadingId prop
content = re.sub(r'  statusLoadingId: {\s*type: \[Number, String\],\s*default: null\s*},?', '', content, flags=re.DOTALL)

# Remove isDoctorActive function
content = re.sub(r'const isDoctorActive = \(doctor\) => \{.*?\};\n', '', content, flags=re.DOTALL)

# Remove the status-pill button
content = re.sub(r'<button\s*type="button"\s*class="status-pill".*?</button>', '', content, flags=re.DOTALL)

# Remove status-pill CSS
content = re.sub(r'\.status-pill \{.*?\n\}\n\n\.status-pill--active \{.*?\n\}\n\n\.status-pill--inactive \{.*?\n\}\n\n\.status-pill:hover:not\(:disabled\) \{.*?\n\}\n\n\.status-pill:focus-visible \{.*?\n\}\n\n\.status-pill:disabled \{.*?\n\}\n\n', '', content, flags=re.DOTALL)
content = re.sub(r'\.status-toggle \{.*?\n\}\n\n\.status-toggle\.is-active \{.*?\n\}\n\n\.status-toggle\.is-active:hover \{.*?\n\}\n\n\.status-toggle\.is-inactive \{.*?\n\}\n\n\.status-toggle\.is-inactive:hover \{.*?\n\}\n\n', '', content, flags=re.DOTALL)

with open('frontend/src/components/master-data/doctors/DoctorTable.vue', 'w', encoding='utf-8') as f:
    f.write(content)
