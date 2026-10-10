import re

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'r', encoding='utf-8') as f:
    content = f.read()

# We'll just replace the entire style block with a cleaner one
# Find <style scoped>
style_start = content.find('<style scoped>')

# Find the end of the file or </style>
# The problem might be trailing things after </style>
# Let's replace from <style scoped> to the end of the file

new_style = """<style scoped>
.modal-form {
  padding: 1.5rem;
}

.section-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border: 1px solid var(--color-border-soft);
  box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.section-title {
  margin-top: 0;
  margin-bottom: 1.25rem;
  font-size: 1.05rem;
  font-weight: 600;
  color: var(--color-primary);
  border-bottom: 1px solid var(--color-border-soft);
  padding-bottom: 0.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.form-group {
  margin-bottom: 0;
}

.signature-preview {
  margin-top: 12px;
  border: 2px dashed #cbd5e1;
  padding: 12px;
  display: inline-flex;
  flex-direction: column;
  align-items: center;
  border-radius: 8px;
  background: #f8fafc;
}

.signature-preview img {
  max-width: 200px;
  max-height: 100px;
  display: block;
  border-radius: 4px;
}

.btn-remove-sig {
  margin-top: 8px;
  background: #fee2e2;
  color: #ef4444;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
}
.btn-remove-sig:hover {
  background: #fca5a5;
  color: #991b1b;
}

.loading-state, .error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.loading-state {
  color: var(--color-text-secondary);
}

.error-state {
  color: #991b1b;
  background: #fef2f2;
  border-radius: 8px;
  margin: 1rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
"""

if style_start != -1:
    content = content[:style_start] + new_style

with open('frontend/src/components/master-data/doctors/DoctorFormModal.vue', 'w', encoding='utf-8') as f:
    f.write(content)
