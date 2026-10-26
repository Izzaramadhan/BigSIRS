import './assets/main.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

// Enforce autocomplete="off" globally on all forms and text inputs
if (typeof window !== 'undefined') {
  const disableAutocomplete = (root) => {
    if (!root || root.nodeType !== 1) return;
    if (root.tagName === 'FORM' && !root.getAttribute('autocomplete')) {
      root.setAttribute('autocomplete', 'off');
    }
    if (root.tagName === 'INPUT') {
      const type = (root.getAttribute('type') || 'text').toLowerCase();
      if (!['checkbox', 'radio', 'hidden', 'file', 'color', 'range', 'button', 'submit', 'reset'].includes(type)) {
        if (!root.getAttribute('autocomplete')) {
          root.setAttribute('autocomplete', 'off');
        }
      }
    }
    const forms = root.querySelectorAll ? root.querySelectorAll('form:not([autocomplete])') : [];
    forms.forEach((f) => f.setAttribute('autocomplete', 'off'));
    const inputs = root.querySelectorAll ? root.querySelectorAll('input:not([autocomplete])') : [];
    inputs.forEach((inp) => {
      const type = (inp.getAttribute('type') || 'text').toLowerCase();
      if (!['checkbox', 'radio', 'hidden', 'file', 'color', 'range', 'button', 'submit', 'reset'].includes(type)) {
        inp.setAttribute('autocomplete', 'off');
      }
    });
  };

  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        disableAutocomplete(node);
      }
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      disableAutocomplete(document.body);
      observer.observe(document.body, { childList: true, subtree: true });
    });
  } else {
    disableAutocomplete(document.body);
    observer.observe(document.body, { childList: true, subtree: true });
  }

  document.addEventListener('focusin', (e) => disableAutocomplete(e.target), true);
}

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
