import axios from '@/utils/axios';

const API_URL = '/master-data/tariff-types';

class TariffTypeService {
    getTariffTypes(params) {
        return axios.get(API_URL, { params });
    }

    getTariffType(id) {
        return axios.get(`${API_URL}/${id}`);
    }

    createTariffType(data) {
        return axios.post(API_URL, data);
    }

    updateTariffType(id, data) {
        return axios.put(`${API_URL}/${id}`, data);
    }

    updateTariffTypeStatus(id, isActive) {
        return axios.patch(`${API_URL}/${id}/status`, { is_active: isActive });
    }

    deleteTariffType(id) {
        return axios.delete(`${API_URL}/${id}`);
    }
}

export default new TariffTypeService();
