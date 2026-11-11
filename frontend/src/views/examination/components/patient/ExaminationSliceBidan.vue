<script setup>
import { ref } from 'vue';

const props = defineProps({
  bidan: {
    type: Object,
    required: true
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

const emit = defineEmits(['save']);

const isPhysicalHistoryOpen = ref(false);

const marriageDraft = ref({
  date_or_age: '',
  husband_order: ''
});

const pastBirthDraft = ref({
  birth_date: '',
  place: '',
  gestation_val: '',
  gestation_unit: 'Minggu',
  delivery_type: '',
  helper: '',
  complication: '',
  child_gender_kb: '',
  child_status: ''
});

const handleAddMarriage = () => {
  if (!marriageDraft.value.date_or_age && !marriageDraft.value.husband_order.trim()) {
    return;
  }
  props.bidan.marriage_rows.push({
    id: `MRG-${Date.now()}`,
    date_or_age: marriageDraft.value.date_or_age || '-',
    husband_order: marriageDraft.value.husband_order.trim() || '-'
  });
  marriageDraft.value = { date_or_age: '', husband_order: '' };
};

const handleRemoveMarriage = (id) => {
  props.bidan.marriage_rows = props.bidan.marriage_rows.filter((r) => r.id !== id);
};

const handleAddPastBirth = () => {
  if (!pastBirthDraft.value.birth_date && !pastBirthDraft.value.place.trim() && !pastBirthDraft.value.delivery_type.trim()) {
    return;
  }
  props.bidan.past_pregnancies.push({
    id: `PBR-${Date.now()}`,
    birth_date: pastBirthDraft.value.birth_date || '-',
    place: pastBirthDraft.value.place.trim() || '-',
    gestation: pastBirthDraft.value.gestation_val
      ? `${pastBirthDraft.value.gestation_val} ${pastBirthDraft.value.gestation_unit}`
      : '-',
    delivery_type: pastBirthDraft.value.delivery_type.trim() || '-',
    helper: pastBirthDraft.value.helper.trim() || '-',
    complication: pastBirthDraft.value.complication.trim() || '-',
    child_gender_kb: pastBirthDraft.value.child_gender_kb.trim() || '-',
    child_status: pastBirthDraft.value.child_status.trim() || '-'
  });
  pastBirthDraft.value = {
    birth_date: '',
    place: '',
    gestation_val: '',
    gestation_unit: 'Minggu',
    delivery_type: '',
    helper: '',
    complication: '',
    child_gender_kb: '',
    child_status: ''
  };
};

const handleRemovePastBirth = (id) => {
  props.bidan.past_pregnancies = props.bidan.past_pregnancies.filter((r) => r.id !== id);
};

const syncPregnancyDatesFromHpht = () => {
  const hphtStr = props.bidan.hpht;
  if (!hphtStr) return;

  const hphtDate = new Date(`${hphtStr}T00:00:00`);
  if (Number.isNaN(hphtDate.getTime())) return;

  // Taksiran Persalinan (Naegele: +280 days)
  const hplDate = new Date(hphtDate);
  hplDate.setDate(hplDate.getDate() + 280);
  props.bidan.hpl = hplDate.toISOString().split('T')[0];

  // Usia kehamilan dari HPHT ke tanggal asesmen
  const refStr = props.bidan.date || new Date().toISOString().split('T')[0];
  const refDate = new Date(`${refStr}T00:00:00`);
  const diffDays = Math.floor((refDate - hphtDate) / (1000 * 60 * 60 * 24));
  if (diffDays >= 0) {
    props.bidan.gestational_weeks = String(Math.floor(diffDays / 7));
    props.bidan.gestational_days = String(diffDays % 7);
  }
};
</script>

<template>
  <div class="bidan-assessment-wrap">
    <!-- Top Section: ASESMEN AWAL PASIEN RAWAT JALAN OLEH BIDAN -->
    <div class="bidan-panel">
      <div class="bidan-panel-header">
        <span class="bidan-panel-title">ASESMEN AWAL PASIEN RAWAT JALAN OLEH BIDAN</span>
        <span class="cw-pill cw-pill--primary">Kebidanan &amp; Obstetri</span>
      </div>

      <div class="bidan-panel-body">
        <div class="bidan-meta-row">
          <div class="meta-inline-field">
            <label class="cw-label" for="bidan-date">Tanggal :</label>
            <input id="bidan-date" v-model="bidan.date" type="date" class="cw-input font-mono" @change="syncPregnancyDatesFromHpht" />
          </div>
          <div class="meta-inline-field">
            <label class="cw-label" for="bidan-time">Jam :</label>
            <input id="bidan-time" v-model="bidan.time" type="time" class="cw-input font-mono" />
          </div>
        </div>

        <div class="complaint-inline-row">
          <label class="cw-label" for="bidan-chief-complaint">Keluhan Utama</label>
          <textarea
            id="bidan-chief-complaint"
            v-model="bidan.chief_complaint"
            rows="2"
            class="cw-textarea"
            placeholder="Tuliskan keluhan utama pasien..."
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Section 1: ANAMNESIS (SUBJEKTIF) -->
    <div class="bidan-panel">
      <div class="bidan-panel-header">
        <span class="bidan-panel-title">ANAMNESIS (SUBJEKTIF)</span>
      </div>

      <div class="bidan-panel-body">
        <!-- Info Source Row -->
        <div class="cw-grid-3 sub-section-block">
          <div class="cw-field">
            <span class="cw-label">Informasi didapat dari</span>
            <div class="chk-inline-list">
              <label class="chk-item">
                <input v-model="bidan.info_auto" type="checkbox" />
                <span>Auto anamnesa</span>
              </label>
              <label class="chk-item">
                <input v-model="bidan.info_allo" type="checkbox" />
                <span>Allo anamnesa</span>
              </label>
            </div>
          </div>
          <div class="cw-field">
            <label class="cw-label" for="bidan-info-name">Nama</label>
            <input id="bidan-info-name" v-model="bidan.info_name" type="text" class="cw-input" />
          </div>
          <div class="cw-field">
            <label class="cw-label" for="bidan-info-relation">Hubungan</label>
            <input id="bidan-info-relation" v-model="bidan.info_relation" type="text" class="cw-input" />
          </div>
        </div>

        <!-- 1. Riwayat Menstruasi -->
        <div class="sub-section-block">
          <h4 class="numbered-heading">1. Riwayat Menstruasi</h4>

          <div class="cw-grid-3 align-end-grid">
            <div class="cw-field">
              <label class="cw-label" for="bidan-menarche">Menarche</label>
              <div class="addon-group">
                <input id="bidan-menarche" v-model="bidan.menarche" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">Tahun</span>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-cycle">Siklus</label>
              <div class="addon-group">
                <input id="bidan-cycle" v-model="bidan.cycle" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">hari</span>
              </div>
            </div>

            <div class="cw-field">
              <span class="cw-label">Keteraturan</span>
              <div class="chk-inline-list chk-row-height">
                <label class="chk-item">
                  <input v-model="bidan.cycle_regular" type="checkbox" />
                  <span>Teratur</span>
                </label>
                <label class="chk-item">
                  <input v-model="bidan.cycle_irregular" type="checkbox" />
                  <span>Tidak Teratur</span>
                </label>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-duration">Lama</label>
              <div class="addon-group">
                <input id="bidan-duration" v-model="bidan.duration" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">hari</span>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-volume">Volume</label>
              <div class="addon-group">
                <input id="bidan-volume" v-model="bidan.volume" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">cc/jam</span>
              </div>
            </div>

            <div class="cw-field">
              <span class="cw-label">Keluhan saat haid</span>
              <div class="chk-inline-list">
                <label class="chk-item">
                  <input v-model="bidan.menstrual_complaint_none" type="checkbox" />
                  <span>Tidak</span>
                </label>
                <label class="chk-item">
                  <input v-model="bidan.menstrual_complaint_yes" type="checkbox" />
                  <span>Ya, Sebutkan</span>
                </label>
              </div>
              <input
                v-model="bidan.menstrual_complaint_note"
                type="text"
                class="cw-input"
                placeholder="Sebutkan keluhan saat haid..."
              />
            </div>
          </div>
        </div>

        <!-- 2. Riwayat -->
        <div class="sub-section-block">
          <h4 class="numbered-heading">2. Riwayat</h4>

          <!-- 2.1 Perkawinan -->
          <div class="sub-indented-block">
            <h5 class="sub-numbered-title">2.1 Perkawinan</h5>
            <div class="inline-check-bar">
              <span class="cw-label">Kawin :</span>
              <div class="chk-inline-list">
                <label class="chk-item">
                  <input v-model="bidan.married_yes" type="checkbox" />
                  <span>Ya</span>
                </label>
                <label class="chk-item">
                  <input v-model="bidan.married_no" type="checkbox" />
                  <span>Tidak</span>
                </label>
              </div>
            </div>

            <div class="cw-table-wrap mini-repeat-table">
              <table class="cw-table">
                <thead>
                  <tr>
                    <th>Umur</th>
                    <th>Suami Ke</th>
                    <th class="col-hash">#</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in bidan.marriage_rows" :key="row.id">
                    <td class="font-mono">{{ row.date_or_age }}</td>
                    <td>{{ row.husband_order }}</td>
                    <td class="col-hash">
                      <button type="button" class="cw-btn-danger-icon" @click="handleRemoveMarriage(row.id)">
                        -
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input v-model="marriageDraft.date_or_age" type="date" class="cw-input font-mono" />
                    </td>
                    <td>
                      <input v-model="marriageDraft.husband_order" type="text" class="cw-input" placeholder="Suami ke..." />
                    </td>
                    <td class="col-hash">
                      <button
                        type="button"
                        class="btn-inline-add btn-add-marriage"
                        title="Tambah baris perkawinan"
                        @click="handleAddMarriage"
                      >
                        +
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2.2 Hamil ini -->
          <div class="sub-indented-block">
            <h5 class="sub-numbered-title">2.2 Hamil ini</h5>
            <div class="cw-grid-2">
              <div class="cw-field">
                <div class="inline-check-bar">
                  <span class="cw-label">Hamil muda :</span>
                  <div class="chk-inline-list">
                    <label class="chk-item"><input v-model="bidan.early_nausea" type="checkbox" /><span>mual</span></label>
                    <label class="chk-item"><input v-model="bidan.early_vomit" type="checkbox" /><span>muntah</span></label>
                    <label class="chk-item"><input v-model="bidan.early_bleeding" type="checkbox" /><span>pendarahan</span></label>
                    <label class="chk-item"><input v-model="bidan.early_other" type="checkbox" /><span>lain lain</span></label>
                  </div>
                </div>
                <textarea v-model="bidan.early_note" rows="2" class="cw-textarea" placeholder="Keterangan keluhan hamil muda..."></textarea>
              </div>

              <div class="cw-field">
                <div class="inline-check-bar">
                  <span class="cw-label">Hamil Tua :</span>
                  <div class="chk-inline-list">
                    <label class="chk-item"><input v-model="bidan.late_dizzy" type="checkbox" /><span>pusing</span></label>
                    <label class="chk-item"><input v-model="bidan.late_headache" type="checkbox" /><span>sakit kepala</span></label>
                    <label class="chk-item"><input v-model="bidan.late_bleeding" type="checkbox" /><span>pendarahan</span></label>
                    <label class="chk-item"><input v-model="bidan.late_other" type="checkbox" /><span>lain lain</span></label>
                  </div>
                </div>
                <textarea v-model="bidan.late_note" rows="2" class="cw-textarea" placeholder="Keterangan keluhan hamil tua..."></textarea>
              </div>
            </div>
          </div>

          <!-- 2.3 Kehamilan persalinan dan nifas yang lalu -->
          <div class="sub-indented-block">
            <h5 class="sub-numbered-title">2.3 Kehamilan persalinan dan nifas yang lalu</h5>

            <div class="cw-grid-4 gpah-grid">
              <div class="addon-group addon-group--prefix">
                <span class="addon-prefix">G</span>
                <input id="bidan-g" v-model="bidan.g" type="text" class="cw-input font-mono" />
              </div>
              <div class="addon-group addon-group--prefix">
                <span class="addon-prefix">P</span>
                <input id="bidan-p" v-model="bidan.p" type="text" class="cw-input font-mono" />
              </div>
              <div class="addon-group addon-group--prefix">
                <span class="addon-prefix">A</span>
                <input id="bidan-a" v-model="bidan.a" type="text" class="cw-input font-mono" />
              </div>
              <div class="addon-group addon-group--prefix">
                <span class="addon-prefix">Hidup</span>
                <input id="bidan-hidup" v-model="bidan.hidup" type="text" class="cw-input font-mono" />
              </div>
            </div>

            <div class="cw-table-wrap past-birth-table-wrap">
              <table class="cw-table past-birth-table">
                <thead>
                  <tr>
                    <th>Tgl/ Tahun Partus</th>
                    <th>Tempat Partus</th>
                    <th>Umur Hamil</th>
                    <th>Jenis Persalinan</th>
                    <th>Penolong Persalinan</th>
                    <th>Penyulit</th>
                    <th>Anak Kel/ KB</th>
                    <th>Keadaan Anak Sek.</th>
                    <th class="col-hash">#</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in bidan.past_pregnancies" :key="row.id">
                    <td class="font-mono">{{ row.birth_date }}</td>
                    <td>{{ row.place }}</td>
                    <td>{{ row.gestation }}</td>
                    <td>{{ row.delivery_type }}</td>
                    <td>{{ row.helper }}</td>
                    <td>{{ row.complication }}</td>
                    <td>{{ row.child_gender_kb }}</td>
                    <td>{{ row.child_status }}</td>
                    <td class="col-hash">
                      <button type="button" class="cw-btn-danger-icon" @click="handleRemovePastBirth(row.id)">
                        -
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td><input v-model="pastBirthDraft.birth_date" type="date" class="cw-input font-mono" /></td>
                    <td><input v-model="pastBirthDraft.place" type="text" class="cw-input" /></td>
                    <td>
                      <div class="addon-group addon-group--compact">
                        <input v-model="pastBirthDraft.gestation_val" type="text" class="cw-input font-mono" />
                        <select v-model="pastBirthDraft.gestation_unit" class="addon-select">
                          <option value="Hari">Hari</option>
                          <option value="Minggu">Minggu</option>
                          <option value="Bulan">Bulan</option>
                        </select>
                      </div>
                    </td>
                    <td><input v-model="pastBirthDraft.delivery_type" type="text" class="cw-input" /></td>
                    <td><input v-model="pastBirthDraft.helper" type="text" class="cw-input" /></td>
                    <td><input v-model="pastBirthDraft.complication" type="text" class="cw-input" /></td>
                    <td><input v-model="pastBirthDraft.child_gender_kb" type="text" class="cw-input" /></td>
                    <td><input v-model="pastBirthDraft.child_status" type="text" class="cw-input" /></td>
                    <td class="col-hash">
                      <button
                        type="button"
                        class="btn-inline-add btn-add-past-birth"
                        title="Tambah riwayat persalinan"
                        @click="handleAddPastBirth"
                      >
                        +
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 3. Riwayat KB -->
        <div class="sub-section-block">
          <h4 class="numbered-heading">3. Riwayat KB</h4>
          <div class="cw-grid-2">
            <div class="cw-field">
              <div class="chk-inline-list">
                <label class="chk-item"><input v-model="bidan.kb_none" type="checkbox" /><span>Tidak</span></label>
                <label class="chk-item"><input v-model="bidan.kb_yes" type="checkbox" /><span>Ya, Sebutkan</span></label>
              </div>
              <input v-model="bidan.kb_method" type="text" class="cw-input" placeholder="Metode KB yang pernah digunakan..." />
            </div>
            <div class="cw-field">
              <label class="cw-label" for="bidan-kb-duration">Lama Pemakaian</label>
              <div class="addon-group">
                <input id="bidan-kb-duration" v-model="bidan.kb_duration" type="text" class="cw-input font-mono" />
                <select v-model="bidan.kb_duration_unit" class="addon-select">
                  <option value="Hari">Hari</option>
                  <option value="Minggu">Minggu</option>
                  <option value="Bulan">Bulan</option>
                  <option value="Tahun">Tahun</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- 4. Riwayat Penyakit Ginekologi -->
        <div class="sub-section-block">
          <h4 class="numbered-heading">4. Riwayat Penyakit Ginekologi</h4>
          <div class="chk-inline-list chk-mb">
            <label class="chk-item"><input v-model="bidan.gynecology_none" type="checkbox" /><span>Tidak</span></label>
            <label class="chk-item"><input v-model="bidan.gynecology_yes" type="checkbox" /><span>Ya, sebutkan</span></label>
          </div>
          <textarea v-model="bidan.gynecology_note" rows="2" class="cw-textarea" placeholder="Keterangan riwayat penyakit ginekologi..."></textarea>
        </div>

        <!-- 5. Riwayat Kehamilan Sekarang -->
        <div class="sub-section-block sub-section-block--last">
          <h4 class="numbered-heading">5. Riwayat Kehamilan Sekarang</h4>

          <div class="inline-check-bar chk-mb">
            <span class="cw-label">Riwayat Kehamilan :</span>
            <div class="chk-inline-list">
              <label class="chk-item"><input v-model="bidan.currently_pregnant" type="checkbox" /><span>Hamil</span></label>
              <label class="chk-item"><input v-model="bidan.currently_not_pregnant" type="checkbox" /><span>Tidak Hamil</span></label>
            </div>
          </div>

          <div class="cw-grid-3 chk-mb">
            <div class="cw-field">
              <label class="cw-label" for="bidan-hpht">HPHT</label>
              <input id="bidan-hpht" v-model="bidan.hpht" type="date" class="cw-input font-mono" @change="syncPregnancyDatesFromHpht" />
            </div>
            <div class="cw-field">
              <label class="cw-label" for="bidan-hpl">Taksiran Persalinan</label>
              <input id="bidan-hpl" v-model="bidan.hpl" type="date" class="cw-input font-mono" />
            </div>
            <div class="cw-field">
              <label class="cw-label" for="bidan-gravida-order">Kehamilan Ke</label>
              <input id="bidan-gravida-order" v-model="bidan.pregnancy_order" type="text" class="cw-input font-mono" />
            </div>
          </div>

          <div class="inline-check-bar chk-mb">
            <span class="cw-label">ANC :</span>
            <div class="chk-inline-list">
              <label class="chk-item"><input v-model="bidan.anc_none" type="checkbox" /><span>Tidak</span></label>
              <label class="chk-item"><input v-model="bidan.anc_yes" type="checkbox" /><span>Ya, Oleh</span></label>
              <label class="chk-item"><input v-model="bidan.anc_obgyn" type="checkbox" /><span>Dokter Kandungan</span></label>
              <label class="chk-item"><input v-model="bidan.anc_gp" type="checkbox" /><span>Dokter Umum</span></label>
              <label class="chk-item"><input v-model="bidan.anc_midwife" type="checkbox" /><span>Bidan</span></label>
            </div>
          </div>

          <div class="cw-table-wrap">
            <table class="cw-table">
              <thead>
                <tr>
                  <th class="col-hash">#</th>
                  <th>HPHT</th>
                  <th>Tgl Sekarang</th>
                  <th>Usia Kehamilan</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="col-hash">#</td>
                  <td class="font-mono">{{ bidan.hpht || bidan.date }}</td>
                  <td class="font-mono">{{ bidan.date }}</td>
                  <td>
                    <div class="gestational-age-pair">
                      <div class="addon-group">
                        <input id="bidan-ga-weeks" v-model="bidan.gestational_weeks" type="text" class="cw-input font-mono" />
                        <span class="addon-suffix">Minggu</span>
                      </div>
                      <div class="addon-group">
                        <input id="bidan-ga-days" v-model="bidan.gestational_days" type="text" class="cw-input font-mono" />
                        <span class="addon-suffix">Hari</span>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: PEMERIKSAAN FISIK (OBJEKTIF) -->
    <div class="bidan-panel">
      <div class="bidan-panel-header">
        <span class="bidan-panel-title">PEMERIKSAAN FISIK (OBJEKTIF)</span>
      </div>

      <div class="bidan-panel-body">
        <!-- Expandable Histori Pemeriksaan Fisik(Objektif) -->
        <div class="history-toggle-wrap">
          <button
            type="button"
            class="btn-history-toggle"
            :aria-expanded="isPhysicalHistoryOpen"
            @click="isPhysicalHistoryOpen = !isPhysicalHistoryOpen"
          >
            <span>{{ isPhysicalHistoryOpen ? '−' : '+' }} Histori Pemeriksaan Fisik(Objektif)</span>
          </button>

          <div v-if="isPhysicalHistoryOpen" class="history-drawer-box">
            <div v-if="physicalHistory.length === 0" class="cw-empty-state">
              Belum ada histori pemeriksaan fisik kebidanan sebelumnya.
            </div>
            <div v-else class="cw-table-wrap">
              <table class="cw-table">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>TFU</th>
                    <th>TBJ</th>
                    <th>DJJ</th>
                    <th>Letak / Presentasi / Posisi</th>
                    <th>Bidan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="hist in physicalHistory" :key="hist.id">
                    <td class="font-mono">{{ hist.date }}</td>
                    <td class="font-mono">{{ hist.tfu }}</td>
                    <td class="font-mono">{{ hist.tbj }}</td>
                    <td>{{ hist.djj }}</td>
                    <td>{{ hist.position }}</td>
                    <td>{{ hist.midwife }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Pemeriksaan Abdomen -->
        <div class="sub-section-block">
          <h4 class="numbered-heading">Pemeriksaan Abdomen</h4>

          <div class="cw-grid-2">
            <div class="cw-field">
              <label class="cw-label" for="bidan-tfu">TFU</label>
              <div class="addon-group">
                <input id="bidan-tfu" v-model="bidan.tfu" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">cm</span>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-tbj">TBJ</label>
              <div class="addon-group">
                <input id="bidan-tbj" v-model="bidan.tbj" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">gram</span>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-djj">DJJ</label>
              <div class="addon-group">
                <input id="bidan-djj" v-model="bidan.djj" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">x/menit</span>
                <select v-model="bidan.djj_rhythm" class="addon-select">
                  <option value="Teratur">Teratur</option>
                  <option value="Tidak Teratur">Tidak Teratur</option>
                </select>
              </div>
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-his">His</label>
              <div class="addon-group">
                <input id="bidan-his" v-model="bidan.his" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">x/menit</span>
              </div>
            </div>

            <div class="cw-field">
              <span class="cw-label">Letak :</span>
              <div class="chk-inline-list chk-row-height">
                <label class="chk-item"><input v-model="bidan.lie_longitudinal" type="checkbox" /><span>Memanjang</span></label>
                <label class="chk-item"><input v-model="bidan.lie_transverse" type="checkbox" /><span>Melintang</span></label>
                <label class="chk-item"><input v-model="bidan.lie_oblique" type="checkbox" /><span>Oblique</span></label>
              </div>
            </div>

            <div class="cw-field">
              <span class="cw-label">Presentasi Janin</span>
              <div class="chk-inline-list">
                <label class="chk-item"><input v-model="bidan.pres_head" type="checkbox" /><span>Kepala</span></label>
                <label class="chk-item"><input v-model="bidan.pres_breech" type="checkbox" /><span>Bokong</span></label>
                <label class="chk-item"><input v-model="bidan.pres_other" type="checkbox" /><span>Lainnya</span></label>
              </div>
              <input v-model="bidan.pres_note" type="text" class="cw-input" placeholder="Keterangan presentasi janin..." />
            </div>

            <div class="cw-field">
              <span class="cw-label">Posisi Janin :</span>
              <div class="chk-inline-list chk-row-height">
                <label class="chk-item"><input v-model="bidan.pos_puki" type="checkbox" /><span>PUKI</span></label>
                <label class="chk-item"><input v-model="bidan.pos_puka" type="checkbox" /><span>PUKA</span></label>
              </div>
            </div>

            <div class="cw-field">
              <span class="cw-label">Luka Operasi :</span>
              <div class="chk-inline-list">
                <label class="chk-item"><input v-model="bidan.scar_none" type="checkbox" /><span>Tidak</span></label>
                <label class="chk-item"><input v-model="bidan.scar_yes" type="checkbox" /><span>Ya, Kondisi luka. Jelaskan ..</span></label>
              </div>
              <input v-model="bidan.scar_note" type="text" class="cw-input" placeholder="Kondisi luka operasi..." />
            </div>

            <div class="cw-field cw-field--full">
              <label class="cw-label" for="bidan-dra">DRA(Diastesis Rektus Abdominis)</label>
              <textarea
                id="bidan-dra"
                v-model="bidan.dra"
                rows="2"
                class="cw-textarea"
                placeholder="DRA(Diastesis Rektus Abdominis)"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- Pemeriksaan Perineum dan Genitalia -->
        <div class="sub-section-block sub-section-block--last">
          <h4 class="numbered-heading">Pemeriksaan Perineum dan Genitalia</h4>

          <div class="cw-grid-3 chk-mb">
            <div class="cw-field">
              <label class="cw-label" for="bidan-vt">Pemeriksaan Dalam</label>
              <input id="bidan-vt" v-model="bidan.vaginal_exam" type="text" class="cw-input" />
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-discharge">Pengeluaran Pervaginam</label>
              <input id="bidan-discharge" v-model="bidan.vaginal_discharge" type="text" class="cw-input" />
            </div>

            <div class="cw-field">
              <label class="cw-label" for="bidan-bleeding-cc">Jumlah pendarahan</label>
              <div class="addon-group">
                <input id="bidan-bleeding-cc" v-model="bidan.bleeding_amount" type="text" class="cw-input font-mono" />
                <span class="addon-suffix">cc</span>
              </div>
            </div>
          </div>

          <div class="inline-check-bar">
            <span class="cw-label">Perineum :</span>
            <div class="chk-inline-list">
              <label class="chk-item"><input v-model="bidan.perineum_intact" type="checkbox" /><span>Utuh</span></label>
              <label class="chk-item"><input v-model="bidan.perineum_rupture" type="checkbox" /><span>Ruptur</span></label>
              <label class="chk-item"><input v-model="bidan.perineum_hecting" type="checkbox" /><span>Hecting</span></label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: BIDAN -->
    <div class="bidan-panel">
      <div class="bidan-panel-header">
        <span class="bidan-panel-title">BIDAN</span>
      </div>
      <div class="bidan-panel-body">
        <div class="cw-field">
          <label class="cw-label" for="bidan-midwife">Bidan</label>
          <select id="bidan-midwife" v-model="bidan.midwife" class="cw-select">
            <option value="">=== Pilih Bidan ===</option>
            <option v-for="mw in midwives" :key="mw" :value="mw">{{ mw }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Centered Save Footer -->
    <div class="bidan-submit-footer">
      <button type="button" class="cw-btn-primary btn-save-clinical btn-save-bidan" @click="emit('save')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        <span>Simpan</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.bidan-assessment-wrap {
  display: flex;
  flex-direction: column;
  gap: 1.15rem;
}

.bidan-panel {
  background: #ffffff;
  border: 1px solid var(--color-border-soft);
  border-radius: 10px;
  overflow: hidden;
}

.bidan-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--color-border-soft);
  flex-wrap: wrap;
}

.bidan-panel-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-navy);
  letter-spacing: 0.015em;
}

.bidan-panel-body {
  padding: 1.15rem 1.25rem;
}

.bidan-meta-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin-bottom: 0.9rem;
}

.meta-inline-field,
.complaint-inline-row {
  display: grid;
  grid-template-columns: 115px 1fr;
  gap: 0.75rem;
  align-items: center;
}

.complaint-inline-row {
  align-items: start;
}

.complaint-inline-row .cw-label {
  padding-top: 0.45rem;
}

.sub-section-block {
  padding-bottom: 1.1rem;
  margin-bottom: 1.1rem;
  border-bottom: 1px solid #f1f5f9;
}

.sub-section-block--last {
  padding-bottom: 0;
  margin-bottom: 0;
  border-bottom: none;
}

.numbered-heading {
  margin: 0 0 0.85rem 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-text-navy);
}

.sub-indented-block {
  margin-left: 0.65rem;
  padding-left: 0.85rem;
  border-left: 2px solid #e2e8f0;
  margin-bottom: 1rem;
}

.sub-indented-block:last-child {
  margin-bottom: 0;
}

.sub-numbered-title {
  margin: 0 0 0.6rem 0;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-primary-dark);
}

.inline-check-bar {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.chk-inline-list {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  flex-wrap: wrap;
}

.chk-row-height {
  min-height: 40px;
}

.chk-mb {
  margin-bottom: 0.65rem;
}

.chk-item {
  display: inline-flex;
  align-items: center;
  gap: 0.38rem;
  font-size: 0.84rem;
  font-weight: 500;
  color: var(--color-text-navy);
  cursor: pointer;
}

.chk-item input[type='checkbox'] {
  width: 14px;
  height: 14px;
  accent-color: var(--color-primary);
  cursor: pointer;
}

/* Input Groups (Prefix / Suffix Addons) */
.addon-group {
  display: flex;
  align-items: stretch;
  width: 100%;
}

.addon-group .cw-input {
  flex: 1;
  min-width: 0;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.addon-group--prefix .cw-input {
  border-radius: 0 8px 8px 0;
}

.addon-prefix,
.addon-suffix,
.addon-select {
  display: inline-flex;
  align-items: center;
  padding: 0 0.7rem;
  height: 40px;
  background: #f1f5f9;
  border: 1px solid var(--color-border-soft);
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--color-text-secondary);
  white-space: nowrap;
  box-sizing: border-box;
}

.addon-prefix {
  border-right: none;
  border-radius: 8px 0 0 8px;
}

.addon-suffix {
  border-left: none;
}

.addon-group > .addon-suffix:last-child,
.addon-group > .addon-select:last-child {
  border-radius: 0 8px 8px 0;
}

.addon-select {
  border-left: none;
  color: var(--color-text-navy);
  font-family: inherit;
  cursor: pointer;
}

.addon-group--compact {
  min-width: 125px;
}

.gpah-grid {
  margin-bottom: 0.85rem;
}

.mini-repeat-table {
  margin-top: 0.65rem;
}

.past-birth-table-wrap {
  margin-top: 0.35rem;
}

.past-birth-table th,
.past-birth-table td {
  min-width: 105px;
}

.past-birth-table th.col-hash,
.past-birth-table td.col-hash,
.col-hash {
  width: 48px;
  min-width: 48px;
  text-align: center;
}

.btn-inline-add {
  width: 34px;
  height: 34px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--color-primary);
  color: #ffffff;
  border: none;
  border-radius: 7px;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-inline-add:hover {
  background: var(--color-primary-dark);
}

.gestational-age-pair {
  display: grid;
  grid-template-columns: repeat(2, minmax(140px, 1fr));
  gap: 0.75rem;
}

/* History Toggle */
.history-toggle-wrap {
  margin-bottom: 1.1rem;
  padding-bottom: 0.85rem;
  border-bottom: 1px solid #f1f5f9;
}

.btn-history-toggle {
  background: transparent;
  border: none;
  padding: 0.25rem 0;
  font-size: 0.86rem;
  font-weight: 700;
  color: var(--color-text-navy);
  cursor: pointer;
  font-family: inherit;
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.btn-history-toggle:hover {
  color: var(--color-primary-dark);
}

.history-drawer-box {
  margin-top: 0.75rem;
}

.bidan-submit-footer {
  display: flex;
  justify-content: center;
  padding-top: 0.25rem;
}

.btn-save-bidan {
  min-width: 130px;
}

.btn-save-bidan svg {
  width: 15px;
  height: 15px;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .bidan-meta-row,
  .gestational-age-pair {
    grid-template-columns: 1fr;
  }

  .meta-inline-field,
  .complaint-inline-row {
    grid-template-columns: 1fr;
    gap: 0.35rem;
  }

  .sub-indented-block {
    margin-left: 0;
    padding-left: 0.65rem;
  }
}
</style>

