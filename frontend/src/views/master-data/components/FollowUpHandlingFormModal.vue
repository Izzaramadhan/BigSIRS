<script setup>
import FollowUpHandlingFormModal from '@/components/master-data/follow-up-handlings/FollowUpHandlingFormModal.vue';

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  loading: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
});

defineEmits(['close', 'submit']);
</script>

<template>
  <FollowUpHandlingFormModal
    :is-open="isOpen"
    :initial-data="initialData"
    :loading="loading"
    :errors="errors"
    @close="$emit('close')"
    @submit="(payload) => $emit('submit', payload)"
  />
</template>
