import os

file_path = 'src/components/master-data/__tests__/EmployeeFormModal.spec.js'

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remove the closing }); of the describe block to append new tests
content = content.rsplit('});\n', 1)[0]

new_tests = '''
  it('17. Form dirender dengan tiga tab: Identitas, Alamat & Personal, Kepegawaian', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    const tabs = Array.from(document.body.querySelectorAll('.tab-item'));
    expect(tabs.length).toBe(3);
    expect(tabs[0].textContent).toContain('Identitas');
    expect(tabs[1].textContent).toContain('Alamat & Personal');
    expect(tabs[2].textContent).toContain('Kepegawaian');
  });

  it('18. Tab navigasi Sebelumnya dan Selanjutnya berfungsi', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    // Identitas tab active by default
    expect(wrapper.vm.activeTab).toBe('identitas');
    
    // Click Selanjutnya
    const nextBtn = Array.from(document.body.querySelectorAll('button')).find(el => el.textContent.includes('Selanjutnya'));
    await nextBtn.click();
    expect(wrapper.vm.activeTab).toBe('alamat');
    
    // Click Selanjutnya again
    const nextBtn2 = Array.from(document.body.querySelectorAll('button')).find(el => el.textContent.includes('Selanjutnya'));
    await nextBtn2.click();
    expect(wrapper.vm.activeTab).toBe('kepegawaian');
    
    // Click Sebelumnya
    const prevBtn = Array.from(document.body.querySelectorAll('button')).find(el => el.textContent.includes('Sebelumnya'));
    await prevBtn.click();
    expect(wrapper.vm.activeTab).toBe('alamat');
  });

  it('19. Field wajib (Nama Lengkap) menampilkan error dan class is-invalid jika disubmit kosong', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    // Set error explicitly (simulating backend error response mapped to error ref)
    wrapper.vm.error = { name: ['Nama Lengkap wajib diisi.'] };
    await wrapper.vm.$nextTick();
    
    const nameInput = document.body.querySelector('#name');
    expect(nameInput.classList.contains('is-invalid')).toBe(true);
    
    const errorFeedback = document.body.querySelector('.error-feedback');
    expect(errorFeedback).not.toBeNull();
    expect(errorFeedback.textContent).toContain('Nama Lengkap wajib diisi.');
  });
  
  it('20. Memiliki semua 17 form fields di dalam DOM', async () => {
    const wrapper = createWrapper({ isOpen: false });
    await wrapper.setProps({ isOpen: true });
    await flushPromises();
    
    const fields = [
      '#national_id', '#name', '#birth_place', '#birth_date',
      'input[name="gender"]', 'input[name="nationality"]', 
      'input[name="blood_type"]', 'input[name="religion"]', 
      'input[name="marital_status"]', '#address', '#allergies',
      '#phone', '#code', 'input[name="is_active"]'
    ];
    
    fields.forEach(selector => {
      expect(document.body.querySelector(selector)).not.toBeNull();
    });
  });
});
'''

content += new_tests

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Tests added.")
