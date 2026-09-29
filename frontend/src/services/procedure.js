import axios from '@/utils/axios';

const API_URL = '/master-data/procedures';

class ProcedureService {
    getProcedures(params) {
        return axios.get(API_URL, { params });
    }

    getProcedure(id) {
        return axios.get(`${API_URL}/${id}`);
    }

    createProcedure(data) {
        return axios.post(API_URL, data);
    }

    updateProcedure(id, data) {
        return axios.put(`${API_URL}/${id}`, data);
    }

    updateProcedureVisibility(id, isVisible) {
        return axios.patch(`${API_URL}/${id}/visibility`, { is_visible: isVisible });
    }

    deleteProcedure(id) {
        return axios.delete(`${API_URL}/${id}`);
    }
}

export default new ProcedureService();
