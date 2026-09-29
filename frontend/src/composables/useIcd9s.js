import { ref } from 'vue'
import icd9Service from '@/services/icd9';

export function useIcd9s() {
    const icd9s = ref([]);
    const loading = ref(false);
    const error = ref(null);

    const fetchIcd9s = async (params = {}) => {
        loading.value = true;
        error.value = null;
        try {
            const response = await icd9Service.getIcd9s(params);
            icd9s.value = response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'Gagal mengambil data ICD-9-CM';
            console.error('Error fetching ICD9s:', err);
        } finally {
            loading.value = false;
        }
    };

    return {
        icd9s,
        loading,
        error,
        fetchIcd9s
    };
}
