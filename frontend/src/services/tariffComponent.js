import api from '@/utils/axios';

export default {
    getTariffComponents(params) {
        return api.get('/master-data/tariff-components', { params });
    },

    getTariffComponent(id) {
        return api.get(`/master-data/tariff-components/${id}`);
    },

    createTariffComponent(data) {
        return api.post('/master-data/tariff-components', data);
    },

    updateTariffComponent(id, data) {
        return api.put(`/master-data/tariff-components/${id}`, data);
    },

    updateTariffComponentStatus(id, isActive) {
        return api.patch(`/master-data/tariff-components/${id}/status`, { is_active: isActive });
    },

    deleteTariffComponent(id) {
        return api.delete(`/master-data/tariff-components/${id}`);
    }
};
