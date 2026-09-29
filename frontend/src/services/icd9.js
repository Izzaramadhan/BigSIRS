import axios from '@/utils/axios';

const API_URL = '/lookups/icd9-cms';

class Icd9Service {
    getIcd9s(params) {
        return axios.get(API_URL, { params });
    }
}

export default new Icd9Service();
