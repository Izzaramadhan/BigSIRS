import axios from 'axios';
import router from '../router';
import { useAuthStore } from '../stores/auth';

const baseURL = import.meta.env.VITE_API_BASE_URL;

if (!baseURL) {
  throw new Error('VITE_API_BASE_URL belum dikonfigurasi');
}

const api = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json'
  }
});

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && error.config.url !== '/auth/login') {
      const authStore = useAuthStore();
      authStore.clearAuth();
      
      if (router.currentRoute.value.name !== 'login') {
         router.push({ name: 'login' });
      }
    }
    return Promise.reject(error);
  }
);

export default api;
