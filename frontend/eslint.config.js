import { defineConfig, globalIgnores } from 'eslint/config'
import globals from 'globals'
import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import pluginVitest from '@vitest/eslint-plugin'
import pluginOxlint from 'eslint-plugin-oxlint'
import skipFormatting from 'eslint-config-prettier/flat'

export default defineConfig([
  {
    name: 'app/files-to-lint',
    files: ['**/*.{vue,js,mjs,jsx}'],
  },

  globalIgnores(['**/dist/**', '**/dist-ssr/**', '**/coverage/**', '**/*.cjs']),

  {
    languageOptions: {
      globals: {
        ...globals.browser,
      },
    },
  },

  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],

  {
    ...pluginVitest.configs.recommended,
    files: ['src/**/__tests__/*'],
  },

  ...pluginOxlint.buildFromOxlintConfigFile('.oxlintrc.json'),

  {
    // Modul yang sudah memakai design system. Tambahkan path modul berikutnya
    // di sini DAN di src/assets/styles/index.css (@source) saat migrasi.
    name: 'app/design-system',
    files: [
      'src/components/ui/**/*.vue',
      'src/components/master-data/buildings/**/*.vue',
      'src/views/master-data/bed/BuildingView.vue',
      'src/components/master-data/beds/**/*.vue',
      'src/views/master-data/bed/BedView.vue',
      'src/components/master-data/floors/**/*.vue',
      'src/views/master-data/bed/FloorView.vue',
      'src/components/master-data/wards/**/*.vue',
      'src/views/master-data/bed/WardView.vue',
      'src/components/master-data/rooms/**/*.vue',
      'src/views/master-data/bed/RoomView.vue',
      'src/components/master-data/room-classes/**/*.vue',
      'src/views/master-data/bed/RoomClassView.vue',
      'src/components/master-data/sirs-bed-availability/**/*.vue',
      'src/views/master-data/bed/SirsBedAvailabilityView.vue',
      'src/components/master-data/educations/**/*.vue',
      'src/views/master-data/EducationView.vue',
      'src/components/master-data/occupations/**/*.vue',
      'src/views/master-data/OccupationView.vue',
      'src/components/master-data/positions/**/*.vue',
      'src/views/master-data/PositionView.vue',
      'src/components/master-data/employees/**/*.vue',
      'src/components/master-data/EmployeeFormModal.vue',
      'src/views/master-data/EmployeeView.vue',
      'src/components/master-data/doctors/**/*.vue',
      'src/views/master-data/DoctorView.vue',
      'src/components/master-data/doctor-schedules/**/*.vue',
      'src/views/master-data/DoctorScheduleView.vue',
      'src/components/master-data/polyclinics/**/*.vue',
      'src/views/master-data/PolyclinicView.vue',
      'src/components/master-data/service-types/**/*.vue',
      'src/views/master-data/ServiceTypeView.vue',
      'src/components/master-data/insurance-types/**/*.vue',
      'src/views/master-data/InsuranceTypeView.vue',
      'src/components/master-data/nutrition-care/**/*.vue',
      'src/views/master-data/DietTypeView.vue',
      'src/components/master-data/activity-type/**/*.vue',
      'src/views/master-data/ActivityTypeView.vue',
      'src/components/master-data/letter-types/**/*.vue',
      'src/components/master-data/LetterTypeFormModal.vue',
      'src/views/master-data/LetterTypeView.vue',
    ],
    rules: {
      'vue/no-restricted-block': [
        'error',
        {
          element: 'style',
          message:
            'Pakai utility Tailwind + token. Lihat docs/frontend/standar-ui.md. Butuh gaya baru? Tambah variant di components/ui.',
        },
      ],
      'vue/no-static-inline-styles': 'error',
      // Larang warna arbitrer seperti bg-[#fff] / text-[rgb(...)] — pakai token.
      'vue/no-restricted-class': ['error', '/\\[(#|rgb|hsl)/'],
      'vue/require-default-prop': 'error',
      'vue/require-explicit-emits': 'error',
      'vue/no-unused-properties': 'error',
    },
  },

  skipFormatting,
])
