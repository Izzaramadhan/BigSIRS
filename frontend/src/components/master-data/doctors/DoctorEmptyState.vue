<script setup>
import { SearchX, Users } from 'lucide-vue-next'
import BaseEmptyState from '@/components/ui/BaseEmptyState.vue'

defineProps({
  isSearch: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <BaseEmptyState
    v-if="isSearch"
    :icon="SearchX"
    title="Data tidak ditemukan"
    description="Tidak ada dokter yang cocok dengan kriteria pencarian Anda. Silakan coba kata kunci atau filter lain."
  >
    <template v-if="$slots.action" #action>
      <slot name="action" />
    </template>
  </BaseEmptyState>

  <BaseEmptyState
    v-else
    :icon="Users"
    title="Belum ada data Dokter"
    description="Data dokter masih kosong. Silakan tambahkan data dokter pertama Anda."
  >
    <template v-if="$slots.action" #action>
      <slot name="action" />
    </template>
  </BaseEmptyState>
</template>
