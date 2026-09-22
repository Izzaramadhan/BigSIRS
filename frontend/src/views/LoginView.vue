<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../utils/axios';

const router = useRouter();
const authStore = useAuthStore();

const username = ref('');
const password = ref('');
const captchaId = ref('');
const rawCaptchaAnswer = ref('');
const captchaAnswer = computed({
  get: () => rawCaptchaAnswer.value,
  set: (value) => {
    rawCaptchaAnswer.value = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  }
});
const captchaImage = ref('');
const showPassword = ref(false);
const errorMessage = ref('');
const currentYear = new Date().getFullYear();

const fetchCaptcha = async () => {
  try {
    const response = await api.get('/auth/captcha');
    captchaId.value = response.data.data.captcha_id;
    captchaImage.value = response.data.data.captcha_image;
    captchaAnswer.value = '';
  } catch (error) {
    console.error('Failed to fetch CAPTCHA', error);
  }
};

onMounted(() => {
  fetchCaptcha();
});

const handleLogin = async () => {
  errorMessage.value = '';
  const success = await authStore.login(username.value, password.value, captchaId.value, captchaAnswer.value);
  
  if (success) {
    const redirect = router.currentRoute.value.query.redirect || '/dashboard';
    router.push(redirect);
  } else {
    errorMessage.value = authStore.error;
    captchaAnswer.value = '';
    fetchCaptcha();
  }
};
</script>

<template>
  <main class="login-layout">
    <div class="login-wrapper">
      
      <div class="login-brand" aria-label="Sisfomedika">
        <img src="@/assets/logo/logo-sisfo.webp" alt="" class="login-brand__logo" />
        <span class="login-brand__name">SISFOMEDIKA</span>
      </div>

      <div class="login-card">
        <div class="card-header">
          <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="header-icon"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <div class="header-text">
            <h1>Masukkan Username dan Password</h1>
            <p>Silakan login untuk mengakses sistem</p>
          </div>
        </div>
        
        <div v-if="errorMessage" class="error-alert" aria-live="assertive">
          {{ errorMessage }}
        </div>

        <form @submit.prevent="handleLogin" class="login-form">
          <!-- Username -->
          <div class="form-group">
            <div class="input-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </span>
              <input 
                type="text" 
                id="username" 
                v-model="username" 
                required 
                placeholder="Username"
                aria-label="Username"
              />
            </div>
          </div>
          
          <!-- Password -->
          <div class="form-group">
            <div class="input-wrapper">
              <span class="input-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              </span>
              <input 
                :type="showPassword ? 'text' : 'password'" 
                id="password" 
                v-model="password" 
                required 
                placeholder="Password"
                aria-label="Password"
              />
              <button type="button" class="toggle-password" @click="showPassword = !showPassword" aria-label="Toggle password visibility">
                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
            </div>
          </div>

          <!-- Captcha Row -->
          <div class="captcha-row">
            <div class="captcha-display">
              <img v-if="captchaImage" :src="captchaImage" alt="CAPTCHA" class="captcha-img" />
              <div v-else class="captcha-placeholder">Memuat...</div>
              <button type="button" class="refresh-captcha" @click="fetchCaptcha" aria-label="Refresh CAPTCHA">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              </button>
            </div>
            <div class="captcha-input-wrapper">
              <input 
                type="text" 
                v-model="captchaAnswer" 
                required 
                placeholder="MASUKKAN KODE DI ATAS"
                aria-label="CAPTCHA Answer"
                class="captcha-input"
                inputmode="text"
                autocapitalize="characters"
                autocomplete="off"
                spellcheck="false"
                maxlength="5"
              />
            </div>
          </div>

          <!-- Form Options -->
          <div class="form-options">
            <label class="checkbox-container">
              <input type="checkbox" v-model="showPassword" />
              <span class="checkmark"></span>
              Lihat Password
            </label>
            <a href="#" class="help-link">Bantuan Login?</a>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-submit" :disabled="authStore.loading">
            <span class="btn-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg>
            </span>
            {{ authStore.loading ? 'MEMPROSES...' : 'LOGIN' }}
          </button>
        </form>
      </div>

    </div>
    
    <footer class="login-footer">
      <p>© 2018 - {{ currentYear }} <strong>BigSirs</strong> | PT. Sisfomedika</p>
    </footer>
  </main>
</template>

<style scoped>
.login-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background-image: url('@/assets/background/background-login.webp');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  position: relative;
  background-color: #e0f2f1; /* Fallback */
}

.login-wrapper {
  width: 100%;
  max-width: 500px;
  padding: 2rem;
  z-index: 10;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.login-brand {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 2rem;
}

.login-brand__logo {
  max-height: 40px;
  width: auto;
  object-fit: contain;
}

.login-brand__name {
  color: #123246;
  font-size: clamp(1.35rem, 2.2vw, 1.9rem);
  font-weight: 700;
  letter-spacing: 0.06em;
  line-height: 1;
}

.login-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 2.5rem;
  width: 100%;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.icon-wrapper {
  background-color: #e6f7f5;
  color: #0f766e;
  padding: 0.75rem;
  border-radius: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.header-text h1 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.25rem 0;
}

.header-text p {
  font-size: 0.85rem;
  color: #64748b;
  margin: 0;
}

.form-group {
  margin-bottom: 1.25rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  color: #94a3b8;
  display: flex;
  align-items: center;
}

.input-wrapper input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #334155;
  background-color: #f8fafc;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.input-wrapper input:focus {
  outline: none;
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
  background-color: #ffffff;
}

.input-wrapper input::placeholder {
  color: #94a3b8;
}

.toggle-password {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  padding: 0;
}

.toggle-password:hover {
  color: #475569;
}

.captcha-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.captcha-display {
  flex: 1;
  position: relative;
  height: 46px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #a7f3d0;
  background-color: #f0fdf4;
  display: flex;
  align-items: center;
}

.captcha-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.captcha-placeholder {
  width: 100%;
  text-align: center;
  color: #0f766e;
  font-size: 0.875rem;
}

.refresh-captcha {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #0f766e;
  cursor: pointer;
  padding: 0.25rem;
  display: flex;
}

.refresh-captcha:hover {
  color: #042f2e;
}

.captcha-input-wrapper {
  flex: 1;
}

.captcha-input {
  width: 100%;
  height: 46px;
  padding: 0 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.85rem;
  background-color: #f8fafc;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.captcha-input:focus {
  outline: none;
  border-color: #0f766e;
  box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
  background-color: #ffffff;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  font-size: 0.875rem;
}

.checkbox-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  color: #64748b;
  user-select: none;
}

.checkbox-container input {
  cursor: pointer;
}

.help-link {
  color: #0f766e;
  text-decoration: none;
  font-weight: 500;
}

.help-link:hover {
  text-decoration: underline;
}

.btn-submit {
  width: 100%;
  padding: 0.875rem;
  background-color: #0f766e;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background-color: #0d9488;
}

.btn-submit:disabled {
  background-color: #94a3b8;
  cursor: not-allowed;
}

.btn-icon {
  display: flex;
  align-items: center;
}

.error-alert {
  background-color: #fef2f2;
  color: #b91c1c;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
  border-left: 4px solid #ef4444;
}

.login-footer {
  position: absolute;
  bottom: 1.5rem;
  width: 100%;
  text-align: center;
  color: #64748b;
  font-size: 0.8rem;
}

.login-footer strong {
  color: #475569;
}

@media (max-width: 640px) {
  .login-card {
    padding: 1.5rem;
  }
  
  .captcha-row {
    flex-direction: column;
  }
}
</style>
