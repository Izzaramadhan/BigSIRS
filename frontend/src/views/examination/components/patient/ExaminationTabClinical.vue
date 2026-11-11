<script setup>
import ExaminationSliceBidan from './ExaminationSliceBidan.vue';
import ExaminationSliceDokter from './ExaminationSliceDokter.vue';
import ExaminationSlicePerawat from './ExaminationSlicePerawat.vue';
import ExaminationSliceHais from './ExaminationSliceHais.vue';

defineProps({
  subTab: {
    type: String,
    default: 'dokter' // 'bidan' | 'dokter' | 'perawat' | 'hais'
  },
  patient: {
    type: Object,
    required: true
  },
  clinicalForm: {
    type: Object,
    required: true
  },
  surgeries: {
    type: Object,
    default: () => ({ requests: [] })
  },
  defaultUnit: {
    type: String,
    default: ''
  },
  defaultDoctor: {
    type: String,
    default: ''
  },
  doctors: {
    type: Array,
    default: () => []
  },
  midwives: {
    type: Array,
    default: () => []
  },
  physicalHistory: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['save-clinical']);
</script>

<template>
  <div class="clinical-workspace-panel">
    <!-- 1. ASESMEN AWAL BIDAN -->
    <ExaminationSliceBidan
      v-if="subTab === 'bidan'"
      :bidan="clinicalForm.bidan"
      :midwives="midwives"
      :physical-history="physicalHistory"
      @save="emit('save-clinical', 'Asesmen Awal Bidan')"
    />

    <!-- 2. ASESMEN AWAL DOKTER -->
    <ExaminationSliceDokter
      v-else-if="subTab === 'dokter'"
      :dokter="clinicalForm.dokter"
      :vitals="clinicalForm.vitals"
      :soap="clinicalForm.soap"
      :doctors="doctors"
      @save="emit('save-clinical', 'Asesmen Awal Dokter')"
    />

    <!-- 3. ASESMEN AWAL PERAWAT -->
    <ExaminationSlicePerawat
      v-else-if="subTab === 'perawat'"
      :patient="patient"
      :vitals="clinicalForm.vitals"
      :nursing="clinicalForm.nursing"
      @save="emit('save-clinical', 'Asesmen Awal Perawat')"
    />

    <!-- 4. MONITOR HAIS -->
    <ExaminationSliceHais
      v-else
      :hais="clinicalForm.hais"
      :surgeries="surgeries"
      @save="emit('save-clinical', 'Monitor HAIs')"
    />
  </div>
</template>
