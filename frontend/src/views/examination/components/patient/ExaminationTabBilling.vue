<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  patient: {
    type: Object,
    required: true
  },
  procedures: {
    type: Array,
    default: () => []
  },
  orders: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['complete-examination']);

const isDateGroupOpen = ref(true);

const longMonthNames = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const formattedVisitDate = computed(() => {
  const raw = props.patient.admission_date;
  if (!raw) {
    const now = new Date();
    return `${now.getDate()} ${longMonthNames[now.getMonth()]} ${now.getFullYear()}`;
  }
  const parts = String(raw).split('-');
  if (parts.length !== 3) return raw;
  const day = Number(parts[2]);
  const monthIdx = Number(parts[1]) - 1;
  const year = parts[0];
  return `${day} ${longMonthNames[monthIdx] || parts[1]} ${year}`;
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(val) || 0);
};

const billingItems = computed(() => {
  const items = [
    {
      id: 'BILL-ADM',
      category: 'Administrasi',
      name: `Registrasi & Jasa Pemeriksaan (${props.patient.polyclinic || 'Poliklinik'})`,
      qty: 1,
      unitPrice: 75000,
      subtotal: 75000
    }
  ];

  props.procedures.forEach((proc) => {
    items.push({
      id: proc.id,
      category: proc.mode === 'Paket' ? 'Tindakan Paket' : 'Tindakan Medis',
      name: proc.name,
      qty: proc.quantity || 1,
      unitPrice: proc.tariff,
      subtotal: proc.subtotal ?? proc.tariff
    });
  });

  (props.orders.pharmacy || []).forEach((rx) => {
    const unitPrice = Number(rx.price) || 12500;
    const qty = Math.max(1, Number(rx.qty) || 1);
    items.push({
      id: rx.id,
      category: rx.mode === 'Paket' ? 'Farmasi / Paket Resep' : 'Farmasi / Obat',
      name: `${rx.name} (${rx.signa})`,
      qty,
      unitPrice,
      subtotal: qty * unitPrice
    });
  });

  (props.orders.racikan || []).forEach((rck) => {
    const total = Number(rck.total_price) || 25000;
    const qty = Math.max(1, Number(rck.qty) || 1);
    items.push({
      id: rck.id,
      category: 'Obat Racikan',
      name: `${rck.name} (${rck.signa})`,
      qty,
      unitPrice: Math.round(total / qty),
      subtotal: total
    });
  });

  (props.orders.lab || []).forEach((lab) => {
    const unitPrice = Number(lab.price) || 95000;
    items.push({
      id: lab.id,
      category: 'Laboratorium',
      name: lab.category ? `${lab.item} (${lab.category})` : lab.item,
      qty: 1,
      unitPrice,
      subtotal: unitPrice
    });
  });

  (props.orders.radiology || []).forEach((rad) => {
    const unitPrice = Number(rad.price) || 165000;
    items.push({
      id: rad.id,
      category: 'Radiologi',
      name: rad.category ? `${rad.item} (${rad.category} - ${rad.note || 'Umum/Residen'})` : rad.item,
      qty: 1,
      unitPrice,
      subtotal: unitPrice
    });
  });

  return items;
});

const totalBill = computed(() => {
  return billingItems.value.reduce((acc, item) => acc + item.subtotal, 0);
});
</script>

<template>
  <div class="clinical-workspace-panel bill-page-wrap">
    <!-- Outer Card: TAGIHAN -->
    <div class="bill-panel">
      <div class="bill-panel-header">
        <span class="bill-panel-title">TAGIHAN</span>
        <span class="cw-pill cw-pill--primary">Penjamin: {{ patient.guarantor_type || 'UMUM' }}</span>
      </div>

      <div class="bill-panel-body">
        <!-- Expandable Date Group Strip (e.g. 10 November 2026) -->
        <div class="bill-date-accordion">
          <button
            type="button"
            class="bill-date-bar"
            :aria-expanded="isDateGroupOpen"
            @click="isDateGroupOpen = !isDateGroupOpen"
          >
            <span class="bill-date-label">{{ formattedVisitDate }}</span>
            <span class="bill-date-meta">
              <span class="font-mono bill-date-total">{{ formatCurrency(totalBill) }}</span>
              <span class="bill-date-chevron">{{ isDateGroupOpen ? '−' : '+' }}</span>
            </span>
          </button>

          <div v-show="isDateGroupOpen" class="bill-date-content">
            <div class="cw-table-wrap">
              <table class="cw-table bill-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Kategori</th>
                    <th>Uraian Layanan / Item</th>
                    <th>Qty</th>
                    <th>Tarif Satuan</th>
                    <th>Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in billingItems" :key="row.id">
                    <td>{{ idx + 1 }}</td>
                    <td><span class="cw-pill">{{ row.category }}</span></td>
                    <td>{{ row.name }}</td>
                    <td class="font-mono">{{ row.qty }}</td>
                    <td class="font-mono">{{ formatCurrency(row.unitPrice) }}</td>
                    <td class="font-mono font-bold">{{ formatCurrency(row.subtotal) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="bill-total-row">
                    <td colspan="5" class="text-right font-bold">Total Estimasi Tagihan</td>
                    <td class="font-mono font-bold total-amount">{{ formatCurrency(totalBill) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Centered Complete Button Footer -->
    <div class="bill-complete-footer">
      <button type="button" class="cw-btn-primary btn-complete-billing" @click="emit('complete-examination')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>Selesai</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.bill-page-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.bill-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: hidden;
}

.bill-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.78rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
}

.bill-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.bill-panel-body {
  padding: 1.15rem 1.25rem;
}

.bill-date-accordion {
  border: 1px solid var(--color-border-soft);
  border-radius: 9px;
  overflow: hidden;
}

.bill-date-bar {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.78rem 1rem;
  background: #f8fafc;
  border: none;
  cursor: pointer;
  font-family: inherit;
  text-align: left;
  transition: background-color 0.15s ease;
}

.bill-date-bar:hover {
  background: #f1f5f9;
}

.bill-date-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text-navy);
}

.bill-date-meta {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
}

.bill-date-total {
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-primary-dark);
}

.bill-date-chevron {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-text-secondary);
}

.bill-date-content {
  padding: 0.9rem 1rem;
  border-top: 1px solid var(--color-border-soft);
  background: #ffffff;
}

.bill-total-row td {
  background: #f8fafc;
  border-top: 2px solid var(--color-border-soft);
  padding: 0.8rem 0.85rem;
}

.total-amount {
  color: var(--color-primary-dark);
  font-size: 1rem;
}

.text-right {
  text-align: right;
}

.bill-complete-footer {
  display: flex;
  justify-content: center;
}

.btn-complete-billing {
  min-width: 124px;
}

.btn-complete-billing svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}
</style>

