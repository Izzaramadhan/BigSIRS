<script setup>
defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  isSubmitting: {
    type: Boolean,
    default: false
  },
  isSubmitDisabled: {
    type: Boolean,
    default: false
  },
  submitText: {
    type: String,
    default: 'Simpan'
  },
  size: {
    type: String,
    default: 'md' // sm, md, lg
  }
});

defineEmits(['close', 'submit']);
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="modal-overlay" @click.self="!isSubmitting && $emit('close')">
      <div class="modal-content" :class="`modal-${size}`">
        <div class="modal-header">
          <h3>{{ title }}</h3>
          <button class="btn-close" @click="!isSubmitting && $emit('close')" :disabled="isSubmitting">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        
        <form @submit.prevent="$emit('submit')" class="modal-form-wrapper">
          <div class="modal-body">
            <slot></slot>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-cancel" @click="$emit('close')" :disabled="isSubmitting">Batal</button>
            <button type="submit" class="btn-primary" :disabled="isSubmitting || isSubmitDisabled">
              {{ isSubmitting ? 'Menyimpan...' : submitText }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-form-wrapper {
  display: flex;
  flex-direction: column;
  min-height: 0;
  flex: 1;
}
</style>
