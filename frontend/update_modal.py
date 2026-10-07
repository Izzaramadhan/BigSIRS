import re

file_path = 'src/components/master-data/EmployeeFormModal.vue'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Identitas
content = re.sub(
    r'<label for="national_id" class="form-label">NIK</label>',
    '<label for="national_id" class="form-label">NIK/No. KTP</label>',
    content
)
content = re.sub(
    r'placeholder="Masukkan NIK"',
    'placeholder="Masukkan NIK/No. KTP"',
    content
)
content = re.sub(
    r'<label for="name" class="form-label required">Nama Lengkap</label>',
    '<label for="name" class="form-label">Nama Lengkap <span class="required-indicator">*</span></label>',
    content
)

# Gender
content = re.sub(
    r'<label for="gender" class="form-label">Jenis Kelamin</label>\s*<SearchableSelect\s*v-model="form\.gender"\s*:options="genderOptions"\s*placeholder="Pilih Jenis Kelamin"\s*/>',
    '''<label class="form-label">Jenis Kelamin</label>
              <div class="radio-group">
                <label v-for="opt in genderOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.gender" :value="opt.id" name="gender" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>''',
    content,
    flags=re.DOTALL
)

# Blood type
content = re.sub(
    r'<label for="blood_type" class="form-label">Golongan Darah</label>\s*<SearchableSelect\s*v-model="form\.blood_type"\s*:options="bloodTypeOptions"\s*placeholder="Pilih Golongan Darah"\s*/>',
    '''<label class="form-label">Golongan Darah</label>
              <div class="radio-group">
                <label v-for="opt in bloodTypeOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.blood_type" :value="opt.id" name="blood_type" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>''',
    content,
    flags=re.DOTALL
)

# Religion
content = re.sub(
    r'<label for="religion" class="form-label">Agama</label>\s*<SearchableSelect\s*v-model="form\.religion"\s*:options="religionOptions"\s*placeholder="Pilih Agama"\s*/>',
    '''<label class="form-label">Agama</label>
              <div class="radio-group flex-wrap">
                <label v-for="opt in religionOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.religion" :value="opt.id" name="religion" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>''',
    content,
    flags=re.DOTALL
)

# Marital Status
content = re.sub(
    r'<label for="marital_status" class="form-label">Status Perkawinan</label>\s*<SearchableSelect\s*v-model="form\.marital_status"\s*:options="maritalStatusOptions"\s*placeholder="Pilih Status"\s*/>',
    '''<label class="form-label">Status Perkawinan</label>
              <div class="radio-group flex-wrap">
                <label v-for="opt in maritalStatusOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.marital_status" :value="opt.id" name="marital_status" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>''',
    content,
    flags=re.DOTALL
)

# Nationality
content = re.sub(
    r'<label for="nationality" class="form-label">Kewarganegaraan</label>\s*<SearchableSelect\s*v-model="form\.nationality"\s*:options="nationalityOptions"\s*placeholder="Pilih Kewarganegaraan"\s*/>',
    '''<label class="form-label">Kebangsaan</label>
              <div class="radio-group">
                <label v-for="opt in nationalityOptions" :key="opt.id" class="radio-label">
                  <input type="radio" v-model="form.nationality" :value="opt.id" name="nationality" />
                  <span>{{ opt.name }}</span>
                </label>
              </div>''',
    content,
    flags=re.DOTALL
)

# Alamat Tab
content = re.sub(
    r'<label for="address" class="form-label">Alamat Lengkap</label>',
    '<label for="address" class="form-label">Alamat</label>',
    content
)
content = re.sub(
    r'placeholder="Masukkan alamat lengkap"',
    'placeholder="Masukkan alamat (desa/jalan)"',
    content
)
content = re.sub(
    r'<label for="village_id" class="form-label">Wilayah \(Kelurahan, Kecamatan, Kabupaten/Kota\)</label>',
    '<label for="village_id" class="form-label">Wilayah</label>',
    content
)
content = re.sub(
    r'<label for="phone" class="form-label">No\. Telepon</label>',
    '<label for="phone" class="form-label">No. HP</label>',
    content
)
content = re.sub(
    r'placeholder="Masukkan no telepon"',
    'placeholder="Masukkan nomor HP"',
    content
)

# Replace postal_code with allergies
content = re.sub(
    r'<div class="form-group">\s*<label for="postal_code".*?</div>',
    '''<div class="form-group">
              <label for="allergies" class="form-label">Alergi</label>
              <textarea 
                id="allergies" 
                v-model="form.allergies" 
                class="form-input form-textarea" 
                :class="{ 'is-invalid': hasError('allergies') }"
                placeholder="Masukkan alergi (opsional)"
              ></textarea>
              <span v-if="hasError('allergies')" class="error-feedback">{{ getError('allergies') }}</span>
            </div>''',
    content,
    flags=re.DOTALL
)

# Remove full-width class
content = re.sub(
    r'<div class="form-group full-width">\s*<label for="address"',
    '<div class="form-group">\n              <label for="address"',
    content
)
content = re.sub(
    r'<div class="form-group full-width">\s*<label for="village_id"',
    '<div class="form-group">\n              <label for="village_id"',
    content
)
content = re.sub(
    r'<div class="form-group full-width">\s*<label for="position_id"',
    '<div class="form-group">\n              <label for="position_id"',
    content
)

# is_active checkbox to radio
content = re.sub(
    r'<div class="form-group form-check"[^>]*>\s*<input[^>]*id="is_active"[^>]*>\s*<label[^>]*for="is_active"[^>]*>Status Aktif</label>\s*</div>',
    '''<div class="form-group">
              <label class="form-label">Status Pegawai</label>
              <div class="radio-group">
                <label class="radio-label">
                  <input type="radio" v-model="form.is_active" :value="true" name="is_active" />
                  <span>Aktif</span>
                </label>
                <label class="radio-label">
                  <input type="radio" v-model="form.is_active" :value="false" name="is_active" />
                  <span>Nonaktif</span>
                </label>
              </div>
            </div>''',
    content,
    flags=re.DOTALL
)

# Khonghucu typo fix
content = re.sub(
    r"\{ id: 'Konghucu', name: 'Konghucu' \}",
    "{ id: 'Khonghucu', name: 'Khonghucu' }",
    content
)

# defaultForm allergies
content = re.sub(
    r"address: '',\s*village_id: '',\s*postal_code: '',\s*phone: '',",
    "address: '',\n  village_id: '',\n  phone: '',\n  allergies: '',",
    content
)

# CSS add radio-group
if '.radio-group' not in content:
    content = content.replace(
        '.error-message {',
        '''.error-feedback {
  display: block;
  font-size: 0.75rem;
  color: #ef4444;
  margin-top: 0.375rem;
}

.radio-group {
  display: flex;
  gap: 1.5rem;
  align-items: center;
  min-height: 38px;
}

.radio-group.flex-wrap {
  flex-wrap: wrap;
  gap: 1rem 1.5rem;
}

.radio-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--color-text-navy);
  cursor: pointer;
  margin-bottom: 0;
}

.radio-label input[type="radio"] {
  width: 1rem;
  height: 1rem;
  accent-color: var(--color-primary);
  cursor: pointer;
  margin: 0;
}

.required-indicator {
  color: #ef4444;
}

.error-message {'''
    )

# Tab Error Navigation
content = re.sub(
    r"if \(error\.value\.name \|\| error\.value\.national_id\) \{",
    '''if (error.value.address || error.value.village_id || error.value.phone) {
        activeTab.value = 'alamat';
      } else {''',
    content
)

# Modal Footer
content = re.sub(
    r'<footer v-if="!isLoadingData" class="employee-modal-footer modal-footer">\s*<button type="button" class="btn btn-outline" @click="close" :disabled="loading">\s*Batal\s*</button>\s*<button type="submit" form="employeeForm" class="btn btn-primary" :disabled="loading">\s*<span v-if="loading" class="spinner"></span>\s*<span>{{ isEditing \? \'Simpan Perubahan\' : \'Simpan\' }}</span>\s*</button>\s*</footer>',
    '''<footer v-if="!isLoadingData" class="employee-modal-footer modal-footer">
        <div class="footer-left">
          <button v-if="activeTab !== 'identitas'" type="button" class="btn btn-secondary" @click="prevTab" :disabled="loading">
            Sebelumnya
          </button>
          <button v-else type="button" class="btn btn-outline" @click="close" :disabled="loading">
            Batal
          </button>
        </div>
        
        <div class="footer-right">
          <button v-if="activeTab !== 'kepegawaian'" type="button" class="btn btn-primary" @click="nextTab" :disabled="loading">
            Selanjutnya
          </button>
          <button v-else type="submit" form="employeeForm" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span>{{ isEditing ? 'Simpan Perubahan' : 'Simpan' }}</span>
          </button>
        </div>
      </footer>''',
    content
)

# nextTab and prevTab methods
if 'const nextTab =' not in content:
    content = content.replace(
        'const close = () => {',
        '''const nextTab = () => {
  if (activeTab.value === 'identitas') activeTab.value = 'alamat';
  else if (activeTab.value === 'alamat') activeTab.value = 'kepegawaian';
};

const prevTab = () => {
  if (activeTab.value === 'kepegawaian') activeTab.value = 'alamat';
  else if (activeTab.value === 'alamat') activeTab.value = 'identitas';
};

const close = () => {'''
    )

# Update footer css
content = re.sub(
    r'\.modal-footer \{\s*padding: 1\.25rem 1\.5rem;\s*border-top: 1px solid var\(--color-border-soft\);\s*display: flex;\s*justify-content: flex-end;\s*gap: 0\.75rem;\s*background: #f8fafc;\s*border-bottom-left-radius: 12px;\s*border-bottom-right-radius: 12px;\s*\}',
    '''.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid var(--color-border-soft);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
  border-bottom-left-radius: 12px;
  border-bottom-right-radius: 12px;
}

.footer-left,
.footer-right {
  display: flex;
  gap: 0.75rem;
}''',
    content
)


with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Done.")
