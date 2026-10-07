const fs = require('fs');
const file = 'src/views/master-data/__tests__/EmployeeView.spec.js';
let content = fs.readFileSync(file, 'utf8');

// Replace everything up to `describe(`
const describeIndex = content.indexOf("describe('EmployeeView.vue Pagination'");
if (describeIndex > -1) {
  const newSetup = `import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import EmployeeView from '../EmployeeView.vue';
import { ref } from 'vue';
import { useEmployees } from '../../composables/useEmployees';

vi.mock('../../composables/useEmployees');

vi.mock('../../services/employee.service', () => ({
  default: {
    getPositions: vi.fn().mockResolvedValue({ data: [] })
  }
}));

let mockMeta;
let fetchEmployeesMock;

`;
  content = newSetup + content.substring(describeIndex);
  
  // Now modify beforeEach
  const beforeStart = content.indexOf('beforeEach(() => {');
  const beforeEnd = content.indexOf('});', beforeStart) + 3;
  
  const newBeforeEach = `beforeEach(() => {
    mockMeta = ref(null);
    fetchEmployeesMock = vi.fn();
    vi.mocked(useEmployees).mockReturnValue({
      employees: ref([]),
      loading: ref(false),
      error: ref(null),
      meta: mockMeta,
      fetchEmployees: fetchEmployeesMock,
      deleteEmployee: vi.fn()
    });
    vi.clearAllMocks();
  });`;
  
  content = content.substring(0, beforeStart) + newBeforeEach + content.substring(beforeEnd);
  
  // Remove the console logs I added
  content = content.replace('    console.log("meta in component:", wrapper.vm.meta);\n', '');
  content = content.replace('    console.log("HTML:", wrapper.html());\n', '');
  
  fs.writeFileSync(file, content, 'utf8');
  console.log("Updated EmployeeView.spec.js");
}
