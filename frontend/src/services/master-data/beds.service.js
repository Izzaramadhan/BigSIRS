import { createResourceService } from '@/services/core/createResourceService'

const resource = createResourceService('/master-data/beds')

export const bedsService = {
  ...resource,
  // Dipertahankan sebagai alias kompatibilitas untuk pemanggil lama.
  getBeds: (params) => resource.list(params),
}
