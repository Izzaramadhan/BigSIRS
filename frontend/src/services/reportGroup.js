import axios from '@/utils/axios';

const API_URL = '/master-data/report-groups';

class ReportGroupService {
    getReportGroups(params) {
        return axios.get(API_URL, { params });
    }
}

export default new ReportGroupService();
