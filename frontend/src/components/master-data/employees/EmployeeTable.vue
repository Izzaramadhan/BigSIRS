<script setup>
import { defineProps, defineEmits } from 'vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    required: true
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['sort', 'edit', 'delete']);

const getRowNumber = (index, pagination) => {
  return (pagination.current_page - 1) * pagination.per_page + index + 1;
};

const handleSort = (column) => {
  emit('sort', {
    column,
    direction: props.sort.column === column && props.sort.direction === 'asc' ? 'desc' : 'asc'
  });
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-no">No</th>
            <th class="sortable" @click="handleSort('code')">
              <div class="th-content">
                NIP / ID
                <span class="sort-icon" v-if="sort.column === 'code'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="sortable col-name" style="width: 35%;" @click="handleSort('name')">
              <div class="th-content">
                Nama Pegawai
                <span class="sort-icon" v-if="sort.column === 'name'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th style="width: 20%">Jabatan</th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="5" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-muted">{{ getRowNumber(index, pagination) }}</td>
              <td class="code-text">{{ item.code || '-' }}</td>
              <td class="col-name font-medium text-navy">{{ item.name || item.person?.name || '-' }}</td>
              <td>{{ item.position?.name || '-' }}</td>
              <td class="actions-cell">
                <MasterDataActionButtons 
                  :is-active="true"
                  :show-toggle="false"
                  @edit="emit('edit', item)"
                  @delete="emit('delete', item)"
                />
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 1200px) {
  .hide-md { display: none; }
}

@media (max-width: 768px) {
  .hide-sm { display: none; }
  .data-table { min-width: 800px; }
}
</style>
