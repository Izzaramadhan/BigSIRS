import { dashboardMock } from '../mocks/dashboard';
// Using import.meta.env to determine if mock should be used
const useMock = import.meta.env.VITE_USE_DASHBOARD_MOCK === 'true';

export const dashboardService = {
  async getDashboardData() {
    if (useMock) {
      // Simulate network delay
      await new Promise(resolve => setTimeout(resolve, 800));
      return { data: dashboardMock };
    } else {
      // Simulate a real API call that would fail currently because API isn't ready
      // In reality, this would be:
      // return await api.get('/dashboard');
      throw new Error('API dashboard belum tersedia. Aktifkan VITE_USE_DASHBOARD_MOCK=true di .env.local untuk simulasi.');
    }
  }
};
