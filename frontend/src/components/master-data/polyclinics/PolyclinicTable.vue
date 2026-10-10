<script setup>
import MasterDataStatusBadge from '@/components/master-data/shared/MasterDataStatusBadge.vue';
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

defineProps({
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

const emit = defineEmits(['edit', 'delete', 'sort']);

const getRowNumber = (index, pagination) => {
  return (pagination.current_page - 1) * pagination.per_page + index + 1;
};

const handleSort = (column) => {
  emit('sort', column);
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
                Kode
                <span class="sort-icon" v-if="sort.column === 'code'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="sortable col-name" @click="handleSort('name')">
              <div class="th-content">
                Nama Poliklinik
                <span class="sort-icon" v-if="sort.column === 'name'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="sortable" @click="handleSort('service_type')">
              <div class="th-content">
                Jenis Layanan
                <span class="sort-icon" v-if="sort.column === 'service_type'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th>Gudang Default</th>
            <th>Status</th>
            <th class="hide-sm sortable" @click="handleSort('quota')">
              <div class="th-content">
                Kuota
                <span class="sort-icon" v-if="sort.column === 'quota'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="hide-sm sortable" @click="handleSort('jkn_quota')">
              <div class="th-content">
                Kuota JKN
                <span class="sort-icon" v-if="sort.column === 'jkn_quota'">
                  <svg v-if="sort.direction === 'asc'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                  <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </span>
              </div>
            </th>
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="9" :rows="5" />
          </template>

          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <td class="col-no text-muted">{{ getRowNumber(index, pagination) }}</td>
              <td><span class="font-mono font-medium">{{ item.code }}</span></td>
              <td class="col-name font-medium text-navy">{{ item.name }}</td>
              <td>
                <span v-if="item.service_type" class="badge-service">{{ item.service_type_label || item.service_type }}</span>
                <span v-else class="text-muted">&mdash;</span>
              </td>
              <td>
                <template v-if="item.warehouse">
                  <span>{{ item.warehouse.name }}</span>
                  <span v-if="item.warehouse.code" class="text-xs text-muted block mt-1">{{ item.warehouse.code }}</span>
                </template>
                <span v-else class="text-muted">Belum ditentukan</span>
              </td>
              <td>
                <MasterDataStatusBadge :is-active="item.is_active" />
              </td>
              <td class="hide-sm text-center">
                <span class="font-medium">{{ item.quota ?? 0 }}</span>
              </td>
              <td class="hide-sm text-center">
                <span class="font-medium">{{ item.jkn_quota ?? 0 }}</span>
              </td>
              <td class="actions-cell">
                <MasterDataActionButtons 
                  :is-active="item.is_active"
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
  .data-table { min-width: 600px; }
}
</style>
