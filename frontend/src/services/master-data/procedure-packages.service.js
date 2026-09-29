import axios from '@/utils/axios';

export default {
    getPackages(params) {
        return axios.get('/master-data/procedure-packages', { params });
    },
    getPackage(id) {
        return axios.get(`/master-data/procedure-packages/${id}`);
    },
    createPackage(data) {
        return axios.post('/master-data/procedure-packages', data);
    },
    updatePackage(id, data) {
        return axios.put(`/master-data/procedure-packages/${id}`, data);
    },
    updateStatus(id, isActive) {
        return axios.patch(`/master-data/procedure-packages/${id}/status`, { is_active: isActive });
    },
    deletePackage(id) {
        return axios.delete(`/master-data/procedure-packages/${id}`);
    },
    deleteProcedurePackage(id) {
        return axios.delete(`/master-data/procedure-packages/${id}`);
    }
};
