<script setup>
import { formatRupiah, formatBillingDate, getBillingStatusBadgeClass, getGuarantorBadgeClass } from '@/utils/billingFormatters';

const props = defineProps({
  records: {
    type: Array,
    required: true
  },
  totalCount: {
    type: Number,
    required: true
  },
  pageSubtotal: {
    type: Number,
    default: 0
  },
  currentPage: {
    type: Number,
    default: 1
  },
  perPage: {
    type: Number,
    default: 10
  },
  loading: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: null
  },
  selectedIds: {
    type: Object,
    required: true
  },
  areAllVisibleSelected: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits([
  'toggle-all',
  'toggle-row',
  'pay',
  'settle',
  'verify',
  'receipt',
  'notify-clinic',
  'cancel-note',
  'view-detail',
  'reset-filter',
  'refresh'
]);

const getPatientInitials = (name) => {
  if (!name) return 'PS';
  const clean = name.replace(/^(H\.|Hj\.|Ny\.|Tn\.|Nn\.|An\.)\s*/i, '').trim();
  const parts = clean.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  return clean.slice(0, 2).toUpperCase();
};
</script>

<template>
  <div class="card table-card">
    <!-- Table Header Info Banner -->
    <div class="table-card-header">
      <div class="header-left">
        <h2 class="table-title">Daftar Tagihan & Pelunasan Rawat Jalan</h2>
        <div class="header-badges-row">
          <span class="count-badge">
            Ditemukan {{ totalCount }} data tagihan
          </span>
          <span class="subtotal-badge font-mono">
            Subtotal Halaman Ini: <strong>{{ formatRupiah(pageSubtotal) }}</strong>
          </span>
        </div>
      </div>

      <div class="header-right">
        <div class="bridging-status-badge" title="Bridging SIMRS BPJS E-Klaim mode simulasi">
          <span class="sync-dot"></span>
          <span>Bridging: Mode Demo — Belum Diaktifkan</span>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="table-container">
      <table class="data-table" aria-label="Tabel Billing Rawat Jalan">
        <thead>
          <tr>
            <!-- 1. Checkbox Pilih Data -->
            <th class="col-checkbox text-center">
              <input
                type="checkbox"
                class="form-checkbox"
                :checked="areAllVisibleSelected"
                @change="emit('toggle-all', $event.target.checked)"
                aria-label="Pilih Semua Tagihan Halaman Ini"
              />
            </th>
            <!-- 2. Nomor Tagihan -->
            <th class="col-invoice">1. Nomor Tagihan</th>
            <!-- 3. Waktu Pelayanan -->
            <th class="col-time">2. Waktu Pelayanan</th>
            <!-- 4. No. RM & Pasien -->
            <th class="col-patient">3. No. RM & Pasien</th>
            <!-- 5. Poliklinik & DPJP -->
            <th class="col-poly">4. Poliklinik & DPJP</th>
            <!-- 6. Penjamin -->
            <th class="col-guarantor">5. Penjamin</th>
            <!-- 7. Total Tagihan -->
            <th class="col-total text-right">6. Total Tagihan</th>
            <!-- 8. Tanggungan Pasien -->
            <th class="col-liability text-right">7. Tanggungan Pasien</th>
            <!-- 9. Status -->
            <th class="col-status">8. Status</th>
            <!-- 10. Aksi Kasir -->
            <th class="col-actions text-center">9. Aksi Kasir</th>
          </tr>
        </thead>
        <tbody>
          <!-- Error State Fallback -->
          <tr v-if="error">
            <td colspan="10" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle icon-error">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Terjadi Kesalahan Pemuatan Data Billing</h3>
                <p class="empty-desc">{{ error }}</p>
                <button type="button" class="btn-primary" @click="emit('refresh')">
                  <span>Muat Ulang</span>
                </button>
              </div>
            </td>
          </tr>

          <!-- Skeleton Loading State -->
          <template v-else-if="loading">
            <tr v-for="n in 5" :key="'skel-' + n" class="skeleton-row">
              <td class="text-center"><div class="skel-bone bone-check"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-lg"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-md"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-sm"></div></td>
              <td><div class="skel-bone bone-badge"></div></td>
              <td class="text-center"><div class="skel-bone bone-btn"></div></td>
            </tr>
          </template>

          <!-- Empty State -->
          <tr v-else-if="records.length === 0">
            <td colspan="10" class="empty-cell">
              <div class="empty-wrapper">
                <div class="empty-icon-circle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <h3 class="empty-title">Tidak ada data tagihan yang ditemukan.</h3>
                <p class="empty-desc">
                  Periksa kembali kata kunci pencarian atau ubah filter untuk menampilkan data tagihan lainnya.
                </p>
                <button type="button" class="btn-primary" @click="emit('reset-filter')">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
                    <path d="M3 3v5h5"></path>
                  </svg>
                  <span>Reset Filter</span>
                </button>
              </div>
            </td>
          </tr>

          <!-- Data Rows (Exact Columns 1 to 10) -->
          <template v-else>
            <tr
              v-for="item in records"
              :key="item.id"
              :class="{
                'row-selected': selectedIds.has(item.id),
                'row-paid': item.status === 'Lunas',
                'row-ready': item.status === 'Siap Dibayar',
                'row-cancelled': item.status === 'Dibatalkan'
              }"
            >
              <!-- 1. Checkbox Pilih Data -->
              <td class="col-checkbox text-center">
                <input
                  type="checkbox"
                  class="form-checkbox"
                  :checked="selectedIds.has(item.id)"
                  :disabled="item.status === 'Dibatalkan'"
                  @change="emit('toggle-row', item.id)"
                  :aria-label="`Pilih tagihan ${item.invoice_number}`"
                />
              </td>

              <!-- 2. Nomor Tagihan -->
              <td class="col-invoice">
                <div class="invoice-cell-content">
                  <span class="invoice-num font-mono font-semibold">{{ item.invoice_number }}</span>
                  <span class="visit-num text-muted font-mono text-xs">{{ item.visit_number }}</span>
                </div>
              </td>

              <!-- 3. Waktu Pelayanan -->
              <td class="col-time">
                <div class="time-cell-content">
                  <span class="font-medium">{{ formatBillingDate(item.service_date) }}</span>
                  <span class="text-muted font-mono text-xs">{{ item.service_time }}</span>
                </div>
              </td>

              <!-- 4. No. RM & Pasien -->
              <td class="col-patient">
                <div class="patient-cell-content">
                  <div class="patient-avatar">
                    {{ getPatientInitials(item.patient_name) }}
                  </div>
                  <div class="patient-text-group">
                    <span class="patient-name font-semibold">{{ item.patient_name }}</span>
                    <span class="patient-demog text-muted text-xs font-mono">
                      {{ item.medical_record_number }} &bull; {{ item.gender }}, {{ item.age }} Th
                    </span>
                  </div>
                </div>
              </td>

              <!-- 5. Poliklinik & DPJP -->
              <td class="col-poly">
                <div class="poly-cell-content">
                  <span class="poly-name font-medium">{{ item.polyclinic }}</span>
                  <span class="doctor-name text-muted text-xs">{{ item.doctor }}</span>
                </div>
              </td>

              <!-- 6. Penjamin -->
              <td class="col-guarantor">
                <div class="guarantor-cell-content">
                  <span class="guarantor-badge" :class="getGuarantorBadgeClass(item.guarantor_type)">
                    {{ item.guarantor_type }}
                  </span>
                  <span class="sep-label text-xs font-medium" :class="item.sep_status === 'SEP Terbit' ? 'text-teal' : 'text-muted'">
                    {{ item.sep_status }}
                  </span>
                </div>
              </td>

              <!-- 7. Total Tagihan -->
              <td class="col-total text-right">
                <span class="total-nominal font-mono font-bold">{{ formatRupiah(item.total_amount) }}</span>
              </td>

              <!-- 8. Tanggungan Pasien -->
              <td class="col-liability text-right">
                <div class="liability-cell-content">
                  <span class="liability-nominal font-mono font-bold" :class="item.patient_responsibility > 0 ? 'text-liability' : 'text-zero'">
                    {{ formatRupiah(item.patient_responsibility) }}
                  </span>
                  <span class="liability-note text-xs" :class="item.patient_responsibility === 0 ? 'note-covered' : 'note-excess'">
                    {{ item.patient_note }}
                  </span>
                </div>
              </td>

              <!-- 9. Status -->
              <td class="col-status">
                <span class="billing-status-badge" :class="getBillingStatusBadgeClass(item.status)">
                  <span class="status-dot"></span>
                  <span>{{ item.status }}</span>
                </span>
              </td>

              <!-- 10. Aksi Kasir -->
              <td class="col-actions text-center">
                <div class="actions-wrapper">
                  <!-- Contextual Action Button -->
                  <!-- Siap Dibayar: Bayar -->
                  <button
                    v-if="item.status === 'Siap Dibayar'"
                    type="button"
                    class="btn-cashier btn-pay"
                    @click="emit('pay', item)"
                    title="Proses Pembayaran Tagihan"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                      <line x1="12" y1="8" x2="12" y2="16"></line>
                      <line x1="8" y1="12" x2="16" y2="12"></line>
                    </svg>
                    <span>Bayar</span>
                  </button>

                  <!-- Lunas: Kuitansi -->
                  <button
                    v-else-if="item.status === 'Lunas'"
                    type="button"
                    class="btn-cashier btn-receipt"
                    @click="emit('receipt', item)"
                    title="Cetak / Pratinjau Kuitansi"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                      <polyline points="14 2 14 8 20 8"></polyline>
                      <line x1="16" y1="13" x2="8" y2="13"></line>
                      <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Kuitansi</span>
                  </button>

                  <!-- Menunggu Verifikasi: Verifikasi -->
                  <button
                    v-else-if="item.status === 'Menunggu Verifikasi'"
                    type="button"
                    class="btn-cashier btn-verify"
                    @click="emit('verify', item)"
                    title="Verifikasi Dokumen Klaim"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="9 11 12 14 22 4"></polyline>
                      <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                    <span>Verifikasi</span>
                  </button>

                  <!-- Dibayar Sebagian: Lunasi -->
                  <button
                    v-else-if="item.status === 'Dibayar Sebagian'"
                    type="button"
                    class="btn-cashier btn-settle"
                    @click="emit('settle', item)"
                    title="Lunasi Sisa Ekses Tagihan"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="1" x2="12" y2="23"></line>
                      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                    <span>Lunasi</span>
                  </button>

                  <!-- Belum Lengkap: Notif Poli -->
                  <button
                    v-else-if="item.status === 'Belum Lengkap'"
                    type="button"
                    class="btn-cashier btn-notif-poli"
                    @click="emit('notify-clinic', item)"
                    title="Kirim Notifikasi Kelengkapan ke Poliklinik"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                      <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span>Notif Poli</span>
                  </button>

                  <!-- Dibatalkan: Catatan Batal -->
                  <button
                    v-else-if="item.status === 'Dibatalkan'"
                    type="button"
                    class="btn-cashier btn-cancel-note"
                    @click="emit('cancel-note', item)"
                    title="Lihat Alasan Pembatalan Tagihan"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <line x1="15" y1="9" x2="9" y2="15"></line>
                      <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <span>Catatan Batal</span>
                  </button>

                  <!-- Eye Button for Detail Tagihan -->
                  <button
                    type="button"
                    class="btn-view-detail"
                    @click="emit('view-detail', item)"
                    title="Lihat Rincian Lengkap Tagihan"
                    aria-label="Lihat Rincian Tagihan"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
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
.table-card {
  background: var(--color-card-bg, #ffffff);
  border: 1px solid var(--color-border-soft, #e2e8f0);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
}

.table-card-header {
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  flex-wrap: wrap;
  gap: 0.75rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.table-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text-navy, #1e293b);
}

.header-badges-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.count-badge {
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--color-primary, #087f78);
  background: var(--color-primary-light, #e6f6f5);
  padding: 0.2rem 0.65rem;
  border-radius: 6px;
}

.subtotal-badge {
  font-size: 0.8rem;
  color: #1e293b;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  padding: 0.2rem 0.65rem;
  border-radius: 6px;
}

.subtotal-badge strong {
  color: #0f766e;
}

.bridging-status-badge {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.75rem;
  color: #64748b;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  padding: 0.3rem 0.65rem;
  border-radius: 20px;
}

.sync-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #94a3b8;
}

.table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  text-align: left;
  font-size: 0.85rem;
  min-width: 1100px;
}

.data-table th {
  background: #f8fafc;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  padding: 0.85rem 0.75rem;
  border-bottom: 1px solid var(--color-border-soft, #e2e8f0);
  white-space: nowrap;
}

.data-table td {
  padding: 0.85rem 0.75rem;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
  vertical-align: middle;
}

.data-table tr:hover td {
  background: #f8fafc;
}

.row-selected td {
  background: #f0fdfa !important;
}

.row-paid td {
  background: #ffffff;
}

.row-ready td {
  background: #fafffd;
}

.row-cancelled td {
  opacity: 0.7;
}

/* Form Checkbox */
.form-checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: var(--color-primary, #087f78);
  border-radius: 4px;
}

/* Patient cell */
.patient-cell-content {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  min-width: 180px;
}

.patient-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background: var(--color-primary-light, #e6f6f5);
  color: var(--color-primary, #087f78);
  font-size: 0.75rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.patient-text-group {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.patient-name {
  color: var(--color-text-navy, #0f172a);
}

.invoice-cell-content, .time-cell-content, .poly-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

/* Guarantor cell */
.guarantor-cell-content {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 120px;
}

.guarantor-badge {
  display: inline-block;
  font-size: 0.72rem;
  font-weight: 700;
  padding: 0.15rem 0.45rem;
  border-radius: 4px;
  width: fit-content;
}

.guarantor-bpjs-pbi {
  background: #e0f2fe;
  color: #0369a1;
}

.guarantor-bpjs {
  background: #e6f6f5;
  color: #087f78;
}

.guarantor-inhealth {
  background: #fef3c7;
  color: #b45309;
}

.guarantor-insurance {
  background: #f3e8ff;
  color: #7e22ce;
}

.guarantor-general {
  background: #f1f5f9;
  color: #334155;
}

/* Liability cell */
.liability-cell-content {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.15rem;
}

.liability-nominal {
  font-size: 0.85rem;
}

.text-liability {
  color: #b91c1c;
}

.text-zero {
  color: #15803d;
}

.liability-note {
  font-size: 0.7rem;
}

.note-covered {
  color: #15803d;
}

.note-excess {
  color: #b45309;
}

/* Status Badges */
.billing-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  font-size: 0.775rem;
  font-weight: 600;
  white-space: nowrap;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.badge-status-ready {
  background: #e0f2fe;
  color: #0369a1;
}
.badge-status-ready .status-dot {
  background: #0284c7;
}

.badge-status-paid {
  background: #dcfce7;
  color: #15803d;
}
.badge-status-paid .status-dot {
  background: #16a34a;
}

.badge-status-verify {
  background: #fef3c7;
  color: #b45309;
}
.badge-status-verify .status-dot {
  background: #f59e0b;
}

.badge-status-partial {
  background: #ffedd5;
  color: #c2410c;
}
.badge-status-partial .status-dot {
  background: #f97316;
}

.badge-status-incomplete {
  background: #fee2e2;
  color: #b91c1c;
}
.badge-status-incomplete .status-dot {
  background: #ef4444;
}

.badge-status-cancelled {
  background: #f1f5f9;
  color: #64748b;
}
.badge-status-cancelled .status-dot {
  background: #94a3b8;
}

/* Actions */
.actions-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.btn-cashier {
  height: 32px;
  padding: 0 0.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.775rem;
  font-weight: 600;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s ease;
}

.btn-cashier svg {
  width: 14px;
  height: 14px;
}

.btn-pay {
  background: var(--color-primary, #087f78);
  color: #ffffff;
}
.btn-pay:hover {
  background: var(--color-primary-dark, #06635e);
}

.btn-receipt {
  background: #dcfce7;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
.btn-receipt:hover {
  background: #bbf7d0;
}

.btn-verify {
  background: #fef3c7;
  color: #b45309;
  border: 1px solid #fde68a;
}
.btn-verify:hover {
  background: #fde68a;
}

.btn-settle {
  background: #ffedd5;
  color: #c2410c;
  border: 1px solid #fed7aa;
}
.btn-settle:hover {
  background: #fed7aa;
}

.btn-notif-poli {
  background: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fecdd3;
}
.btn-notif-poli:hover {
  background: #fecdd3;
}

.btn-cancel-note {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #cbd5e1;
}
.btn-cancel-note:hover {
  background: #e2e8f0;
}

.btn-view-detail {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-view-detail:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.btn-view-detail svg {
  width: 15px;
  height: 15px;
}

/* Empty & Skeleton */
.empty-cell {
  padding: 3rem 1rem !important;
  text-align: center;
}

.empty-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  max-width: 420px;
  margin: 0 auto;
}

.empty-icon-circle {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-icon-circle.icon-error {
  background: #fee2e2;
  color: #ef4444;
}

.empty-icon-circle svg {
  width: 28px;
  height: 28px;
}

.empty-title {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
}

.empty-desc {
  margin: 0;
  font-size: 0.85rem;
  color: #64748b;
  line-height: 1.4;
}

.skel-bone {
  background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 16px;
}

.bone-check {
  width: 16px;
  height: 16px;
  margin: 0 auto;
}

.bone-sm {
  width: 70px;
}

.bone-md {
  width: 110px;
}

.bone-lg {
  width: 160px;
}

.bone-badge {
  width: 80px;
  height: 22px;
  border-radius: 20px;
}

.bone-btn {
  width: 80px;
  height: 28px;
  border-radius: 6px;
  margin: 0 auto;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}
</style>
