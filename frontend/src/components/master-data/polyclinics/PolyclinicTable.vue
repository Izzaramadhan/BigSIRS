<script setup>
import PolyclinicStatusBadge from './PolyclinicStatusBadge.vue';

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

const emit = defineEmits(['edit', 'toggle-status', 'delete', 'sort']);

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
            <th class="hide-md sortable" @click="handleSort('bpjs_code')">
              <div class="th-content">
                Kode BPJS
                <span class="sort-icon" v-if="sort.column === 'bpjs_code'">
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
            <tr v-for="i in 5" :key="`skeleton-${i}`" class="skeleton-row">
              <td><div class="skeleton-box skeleton-small"></div></td>
              <td><div class="skeleton-box skeleton-medium"></div></td>
              <td><div class="skeleton-box"></div></td>
              <td><div class="skeleton-box skeleton-medium"></div></td>
              <td><div class="skeleton-box skeleton-badge"></div></td>
              <td class="hide-sm"><div class="skeleton-box skeleton-small"></div></td>
              <td class="hide-sm"><div class="skeleton-box skeleton-small"></div></td>
              <td class="hide-md"><div class="skeleton-box skeleton-medium"></div></td>
              <td><div class="skeleton-box skeleton-circle"></div></td>
            </tr>
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
                <PolyclinicStatusBadge :is-active="item.is_active" />
              </td>
              <td class="hide-sm text-center">
                <span class="font-medium">{{ item.quota ?? 0 }}</span>
              </td>
              <td class="hide-sm text-center">
                <span class="font-medium">{{ item.jkn_quota ?? 0 }}</span>
              </td>
              <td class="hide-md">
                <span v-if="item.bpjs_code" class="font-mono text-sm">{{ item.bpjs_code }}</span>
                <span v-else class="text-muted">&mdash;</span>
              </td>
              <td class="actions-cell">
                <div class="action-buttons">
                  <button type="button" class="btn-action edit" @click="emit('edit', item)" aria-label="Edit" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button 
                    type="button" 
                    class="btn-action toggle" 
                    @click="emit('toggle-status', item)" 
                    :aria-label="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                    :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                  >
                    <svg v-if="item.is_active" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                      <path d="M7 11V7a5 5 0 0 1 9.9-1"></path>
                    </svg>
                  </button>
                  <button type="button" class="btn-action delete" @click="emit('delete', item)" aria-label="Arsipkan" title="Arsipkan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>
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
  border: 1px solid var(--color-border-soft);
  background: #ffffff;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  min-width: 900px;
}

.data-table th {
  background: #f8fafc;
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: var(--color-text-secondary);
  border-bottom: 1px solid var(--color-border-soft);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 1rem;
  border-bottom: 1px solid var(--color-border-soft);
  color: var(--color-text-secondary);
  vertical-align: middle;
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.col-no { width: 48px; }
.col-name { min-width: 180px; }
.col-actions { width: 110px; }

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #f1f5f9;
}

.th-content {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.sort-icon svg {
  width: 14px;
  height: 14px;
}

.badge-service {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 500;
  background: #eff6ff;
  color: #1d4ed8;
  border-radius: 999px;
  white-space: nowrap;
}

.font-mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.font-medium {
  font-weight: 500;
}

.text-navy {
  color: var(--color-text-navy);
}

.text-sm {
  font-size: 0.8rem;
}

.text-muted {
  color: #cbd5e1;
}

.text-center {
  text-align: center;
}

.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: flex-end;
}

.btn-action {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  border-radius: 6px;
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: all 0.2s;
}

.btn-action svg {
  width: 16px;
  height: 16px;
}

.btn-action.edit:hover { background: #e0f2fe; color: #0284c7; }
.btn-action.toggle:hover { background: #f3f4f6; color: #4b5563; }
.btn-action.delete:hover { background: #fef2f2; color: #ef4444; }

/* Skeleton Loading */
.skeleton-box {
  height: 16px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
  border-radius: 4px;
}

.skeleton-small { width: 40px; }
.skeleton-medium { width: 80px; }
.skeleton-badge { width: 60px; height: 20px; border-radius: 999px; }
.skeleton-circle { width: 24px; height: 24px; border-radius: 50%; margin-left: auto; }

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@media (max-width: 1200px) {
  .hide-md { display: none; }
}

@media (max-width: 768px) {
  .hide-sm { display: none; }
  .data-table { min-width: 600px; }
}
</style>
