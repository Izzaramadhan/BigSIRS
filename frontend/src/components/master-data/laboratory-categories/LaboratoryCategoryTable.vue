<script setup>
import MasterDataStatusBadge from '@/components/master-data/shared/MasterDataStatusBadge.vue';
import MasterDataSkeletonRow from '@/components/master-data/shared/MasterDataSkeletonRow.vue';
import MasterDataActionButtons from '@/components/master-data/shared/MasterDataActionButtons.vue';

defineProps({
  items: {
    type: Array,
    required: true,
    default: () => []
  },
  pagination: {
    type: Object,
    required: true
  },
  sort: {
    type: Object,
    default: () => ({ column: 'name', direction: 'asc' })
  },
  loading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['edit', 'delete', 'sort']);

const getRowNumber = (index, pagination) => {
  const currentPage = pagination?.current_page || 1;
  const perPage = pagination?.per_page || 10;
  return (currentPage - 1) * perPage + index + 1;
};

const handleSort = (column) => {
  emit('sort', column);
};

const isValidUrl = (url) => {
  if (!url || typeof url !== 'string') return false;
  try {
    const parsed = new URL(url);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
};
</script>

<template>
  <div class="table-wrapper">
    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <!-- 1. No -->
            <th class="col-no text-center">No</th>
            
            <!-- 2. Kategori -->
            <th class="sortable col-name" @click="handleSort('name')">
              <div class="th-content">
                <span>Kategori</span>
                <span class="sort-icon-box" v-if="sort && sort.column === 'name'">
                  <svg 
                    v-if="sort.direction === 'asc'" 
                    class="sort-icon" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="18 15 12 9 6 15"></polyline>
                  </svg>
                  <svg 
                    v-else 
                    class="sort-icon" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </span>
                <span class="sort-icon-box neutral" v-else>
                  <svg 
                    class="sort-icon neutral" 
                    width="14" 
                    height="14" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <polyline points="7 15 12 20 17 15"></polyline>
                    <polyline points="7 9 12 4 17 9"></polyline>
                  </svg>
                </span>
              </div>
            </th>

            <!-- 3. Deskripsi -->
            <th class="col-desc">Deskripsi</th>

            <!-- 4. Tipe -->
            <th class="col-type">Tipe</th>

            <!-- 5. Status -->
            <th class="col-status text-center">Status</th>

            <!-- 6. LOINC Code -->
            <th class="col-loinc">LOINC Code</th>

            <!-- 7. LOINC URL -->
            <th class="col-url">LOINC URL</th>

            <!-- 8. SNOMED Code -->
            <th class="col-snomed">SNOMED Code</th>

            <!-- 9. SNOMED URL -->
            <th class="col-snomed-url">SNOMED URL</th>

            <!-- 10. Aksi -->
            <th class="col-actions text-center">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <!-- Loading skeleton -->
          <template v-if="loading">
            <MasterDataSkeletonRow :columns="10" :rows="5" />
          </template>

          <!-- Empty fallback -->
          <template v-else-if="items.length === 0">
            <tr>
              <td colspan="10" class="text-center py-8 text-muted">
                Tidak ada data kategori lab.
              </td>
            </tr>
          </template>

          <!-- Data rows -->
          <template v-else>
            <tr v-for="(item, index) in items" :key="item.id">
              <!-- 1. No -->
              <td class="col-no text-center text-muted">
                {{ getRowNumber(index, pagination) }}
              </td>

              <!-- 2. Kategori -->
              <td class="col-name font-medium text-navy">
                {{ item.name || '-' }}
              </td>

              <!-- 3. Deskripsi -->
              <td class="col-desc text-muted">
                {{ item.description || '-' }}
              </td>

              <!-- 4. Tipe -->
              <td class="col-type">
                <span class="type-badge" v-if="item.type">
                  {{ item.type }}
                </span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 5. Status -->
              <td class="col-status text-center">
                <MasterDataStatusBadge :is-active="item.is_active" />
              </td>

              <!-- 6. LOINC Code -->
              <td class="col-loinc">
                <span class="code-badge" v-if="item.loinc_code">{{ item.loinc_code }}</span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 7. LOINC URL -->
              <td class="col-url">
                <a 
                  v-if="isValidUrl(item.loinc_url)" 
                  :href="item.loinc_url" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="table-link"
                  :title="item.loinc_url"
                >
                  <span class="url-text">{{ item.loinc_url }}</span>
                  <svg 
                    class="external-icon" 
                    width="12" 
                    height="12" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                  >
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                </a>
                <span v-else class="text-muted">{{ item.loinc_url || '-' }}</span>
              </td>

              <!-- 8. SNOMED Code -->
              <td class="col-snomed">
                <span class="code-badge" v-if="item.snomed_code">{{ item.snomed_code }}</span>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 9. SNOMED URL -->
              <td class="col-snomed-url">
                <a 
                  v-if="isValidUrl(item.snomed_url)" 
                  :href="item.snomed_url" 
                  target="_blank" 
                  rel="noopener noreferrer" 
                  class="table-link"
                  title="Buka SNOMED URL"
                >
                  <span class="url-text">Buka tautan</span>
                  <svg 
                    class="external-icon" 
                    width="12" 
                    height="12" 
                    viewBox="0 0 24 24" 
                    fill="none" 
                    stroke="currentColor" 
                    stroke-width="2" 
                    stroke-linecap="round" 
                    stroke-linejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                </a>
                <span v-else class="text-muted">-</span>
              </td>

              <!-- 10. Aksi -->
              <td class="col-actions text-center actions-cell">
                <MasterDataActionButtons 
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
.table-wrapper {
  width: 100%;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid var(--color-border-soft, #e2e8f0);
  background: var(--color-card-bg, #ffffff);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  min-width: 1100px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.85rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary, #64748b);
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  color: var(--color-text-navy, #0f172a);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no {
  width: 56px;
}

.col-name {
  min-width: 180px;
}

.col-desc {
  min-width: 180px;
  max-width: 260px;
}

.col-type {
  width: 130px;
}

.col-status {
  width: 100px;
}

.col-loinc {
  width: 120px;
}

.col-url {
  min-width: 150px;
  max-width: 220px;
}

.col-snomed {
  width: 130px;
}

.col-snomed-url {
  width: 130px;
  min-width: 110px;
}

.col-actions {
  width: 90px;
  text-align: center;
}

.actions-cell {
  text-align: center;
}

.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.2s;
}

.sortable:hover {
  background-color: #f1f5f9;
}

.th-content {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.sort-icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 14px;
  height: 14px;
  flex-shrink: 0;
}

.sort-icon {
  width: 14px !important;
  height: 14px !important;
  color: var(--color-primary, #0f766e);
  flex-shrink: 0;
}

.sort-icon.neutral {
  color: #94a3b8;
  opacity: 0.6;
}

.code-badge {
  display: inline-block;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  background-color: #f1f5f9;
  color: #334155;
  border-radius: 4px;
  border: 1px solid #cbd5e1;
  white-space: nowrap;
}

.type-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 4px;
  font-size: 0.775rem;
  font-weight: 500;
  text-transform: capitalize;
  background-color: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
  white-space: nowrap;
}

.table-link {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--color-primary, #0f766e);
  text-decoration: none;
  font-size: 0.825rem;
  max-width: 200px;
}

.table-link:hover {
  text-decoration: underline;
  color: #0d9488;
}

.url-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.external-icon {
  flex-shrink: 0;
  opacity: 0.75;
}

.text-center {
  text-align: center !important;
}

.text-muted {
  color: var(--color-text-secondary, #64748b);
  font-size: 0.85rem;
}

.text-navy {
  color: var(--color-text-navy, #0f172a);
}

.font-medium {
  font-weight: 500;
}

.py-8 {
  padding-top: 2rem !important;
  padding-bottom: 2rem !important;
}
</style>
