import re

file_path = 'src/components/master-data/EmployeeFormModal.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove tabs bar
content = re.sub(r'<div class="tabs mb-4">[\s\S]*?</div>\s*', '', content)

# Remove v-show
content = content.replace('v-show="activeTab === \'identitas\'"', '')
content = content.replace('v-show="activeTab === \'alamat\'"', '')
content = content.replace('v-show="activeTab === \'kepegawaian\'"', '')

# Replace footer logic
footer_html = '''<footer v-if="!isLoadingData" class="employee-modal-footer modal-footer">
        <div class="footer-left">
          <button type="button" class="btn btn-outline" @click="close" :disabled="loading">
            Batal
          </button>
        </div>
        
        <div class="footer-right">
          <button type="submit" form="employeeForm" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}</span>
          </button>
        </div>
      </footer>'''

content = re.sub(r'<footer v-if="!isLoadingData" class="employee-modal-footer modal-footer">[\s\S]*?</footer>', footer_html, content)

# We actually don't need to wrap the body and footer in the form tag if we just use form="employeeForm" on the submit button!
# Let's keep the <form @submit.prevent="submitForm" id="employeeForm"> where it is, wrapping the fields.
# BUT we want a single scroll area.
# In the original structure:
# <div class="employee-modal-body modal-body">
#   <form @submit.prevent="submitForm" id="employeeForm">
#     ... fields
#   </form>
# </div>
# <footer class="employee-modal-footer modal-footer">
# Since the body has `overflow-y: auto`, scrolling works fine on all the fields within the form.
# So we DO NOT need to move the form tag to wrap the footer.
# In fact, the submit button has `form="employeeForm"` which natively connects it to the form!

# Remove activeTab from script
content = content.replace("const activeTab = ref('identitas');\n", "")
content = content.replace("activeTab.value = 'identitas';\n", "")
content = content.replace("activeTab.value = 'identitas';", "")
content = content.replace("const activeTab = ref('identitas');", "")

# Remove nextTab and prevTab methods
content = re.sub(r'const nextTab = \(\) => \{[\s\S]*?\};\s*', '', content)
content = re.sub(r'const prevTab = \(\) => \{[\s\S]*?\};\s*', '', content)

# Remove auto switch tab in catch block
content = re.sub(r'// Auto switch tab if error found in specific tabs[\s\S]*?\} else \{[\s\S]*?\}[\s\S]*?\}', '', content)

# Remove .tabs and .tab-item css
content = re.sub(r'\.tabs\s*\{[\s\S]*?\}\s*', '', content)
content = re.sub(r'\.tab-item\s*\{[\s\S]*?\}\s*', '', content)
content = re.sub(r'\.tab-item:hover\s*\{[\s\S]*?\}\s*', '', content)
content = re.sub(r'\.tab-item\.active\s*\{[\s\S]*?\}\s*', '', content)

# Add section headers
# The structure has <div class="form-grid"> for each tab section originally.
# Let's replace the first one with Identitas header, etc.
content = re.sub(r'<div class="form-grid">', '<h4 class="section-title mb-4">Identitas</h4>\n            <div class="form-grid mb-6">', content, count=1)
content = re.sub(r'<div class="form-grid">', '<h4 class="section-title mb-4 border-t pt-4">Alamat & Personal</h4>\n            <div class="form-grid mb-6">', content, count=1)
content = re.sub(r'<div class="form-grid">', '<h4 class="section-title mb-4 border-t pt-4">Kepegawaian</h4>\n            <div class="form-grid mb-6">', content, count=1)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Done")
