import re

file_path = 'src/components/master-data/EmployeeFormModal.vue'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace <form @submit.prevent="submitForm" id="employeeForm"> with nothing, because we will wrap it outside.
# Also remove </form> which is right before </template> inside modal-body
content = content.replace('<form @submit.prevent="submitForm" id="employeeForm">', '')
content = content.replace('</form>\n        </template>\n      </div>', '        </template>\n      </div>')

# Wrap the body and footer inside <form>
# Find: <div class="employee-modal-body modal-body">
# Replace with: <form class="employee-form" @submit.prevent="submitForm">\n      <div class="employee-modal-body modal-body">
content = content.replace('<div class="employee-modal-body modal-body">', '<form class="employee-form" @submit.prevent="submitForm">\n      <div class="employee-modal-body modal-body">')

# Find: </footer>\n      </section>
# Replace with: </footer>\n      </form>\n      </section>
content = content.replace('</footer>\n      </section>', '</footer>\n      </form>\n      </section>')

# Remove `form="employeeForm"` from submit button
content = content.replace('form="employeeForm" ', '')

# Fix headers
# We have 3 <div  class="form-grid"> (note the two spaces)
parts = content.split('<div  class="form-grid">')
if len(parts) == 4:
    content = parts[0] + '<h4 class="section-title mb-4">Identitas</h4>\n            <div class="form-grid mb-6">' + parts[1] + '<h4 class="section-title mb-4 border-t pt-4 mt-2">Alamat & Personal</h4>\n            <div class="form-grid mb-6">' + parts[2] + '<h4 class="section-title mb-4 border-t pt-4 mt-2">Kepegawaian</h4>\n            <div class="form-grid mb-6">' + parts[3]
else:
    print("Warning: Did not find exactly 3 occurrences of <div  class=\"form-grid\">")

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Fix script completed")
