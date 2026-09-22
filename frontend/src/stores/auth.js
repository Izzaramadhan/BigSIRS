import { defineStore } from 'pinia';
import api from '../utils/axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    loading: false,
    error: null,
    initialized: false,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user
  },
  actions: {
    async initializeAuth() {
      if (this.initialized) return;

      if (!this.token) {
        this.user = null;
        this.initialized = true;
        return;
      }

      try {
        const response = await api.get('/auth/me');
        this.user = response.data.data ?? response.data;
      } catch {
        this.clearAuth();
      } finally {
        this.initialized = true;
      }
    },
    clearAuth() {
      this.user = null;
      this.token = null;
      localStorage.removeItem('token');
    },
    async login(username, password, captchaId, captchaAnswer) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/auth/login', {
          username,
          password,
          captcha_id: captchaId,
          captcha_answer: captchaAnswer,
        });
        
        this.token = response.data.data.token;
        localStorage.setItem('token', this.token);
        
        if (response.data.data.user) {
          this.user = response.data.data.user;
        } else {
          await this.fetchUser();
        }
        
        return true;
      } catch (err) {
        if (err.response?.data?.message) {
          this.error = err.response.data.message;
        } else {
          this.error = 'Terjadi kesalahan saat login.';
        }
        this.clearAuth();
        return false;
      } finally {
        this.loading = false;
      }
    },
    async fetchUser() {
      if (!this.token) return;
      try {
        const response = await api.get('/auth/me');
        this.user = response.data.data ?? response.data;
      } catch {
        this.clearAuth();
      }
    },
    async logout() {
      try {
        await api.post('/auth/logout');
      } catch (err) {
        console.error('Gagal logout di sisi server', err);
      } finally {
        this.clearAuth();
      }
    }
  }
});
