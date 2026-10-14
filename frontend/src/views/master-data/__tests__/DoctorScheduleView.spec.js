import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import DoctorScheduleView from '../DoctorScheduleView.vue';
import doctorScheduleService from '@/services/master-data/doctor-schedules.service';

vi.mock('@/services/master-data/doctor-schedules.service', () => ({
  default: {
    getDoctorSchedules: vi.fn(),
    getRecap: vi.fn(),
    getDoctorSchedule: vi.fn(),
    createDoctorSchedule: vi.fn(),
    updateDoctorSchedule: vi.fn(),
    deleteDoctorSchedule: vi.fn()
  }
}));

describe('DoctorScheduleView.vue', () => {
  const mockPaginatedResponse = {
    data: [
      {
        id: 1,
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi Sp.PD' },
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        day_of_week: 1,
        start_time: '08:00',
        end_time: '12:00',
        is_holiday: false
      }
    ],
    meta: {
      current_page: 1,
      last_page: 2,
      per_page: 1,
      total: 2
    }
  };

  const mockRecapResponse = {
    data: [
      {
        id: 1,
        doctor_id: 101,
        doctor: { id: 101, name: 'dr. Andi Sp.PD', specialization: 'Spesialis Penyakit Dalam' },
        polyclinic_id: 10,
        polyclinic: { id: 10, name: 'Poli Penyakit Dalam' },
        day_of_week: 1,
        start_time: '08:00',
        end_time: '12:00',
        is_holiday: false
      },
      {
        id: 2,
        doctor_id: 102,
        doctor: { id: 102, name: 'dr. Budi Sp.A', specialization: 'Spesialis Anak' },
        polyclinic_id: 20,
        polyclinic: { id: 20, name: 'Poli Anak' },
        day_of_week: 2,
        start_time: '09:00',
        end_time: '13:00',
        is_holiday: false
      }
    ]
  };

  beforeEach(() => {
    vi.clearAllMocks();
    doctorScheduleService.getDoctorSchedules.mockResolvedValue(mockPaginatedResponse);
    doctorScheduleService.getRecap.mockResolvedValue(mockRecapResponse);
  });

  it('fetches both paginated schedules and unpaginated recap on mount', async () => {
    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(1);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1);

    // Both tables should be rendered
    expect(wrapper.findComponent({ name: 'DoctorScheduleTable' }).exists()).toBe(true);
    expect(wrapper.findComponent({ name: 'DoctorScheduleRecapTable' }).exists()).toBe(true);
  });

  it('changing pagination does not refetch recap data', async () => {
    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(1);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1);

    // Simulate page change from pagination component
    const paginationComp = wrapper.findComponent({ name: 'MasterDataPagination' });
    await paginationComp.vm.$emit('page-change', 2);
    await flushPromises();

    // getDoctorSchedules called for page 2
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2);
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenLastCalledWith(
      expect.objectContaining({ page: 2 })
    );

    // getRecap should NOT be refetched by page change
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1);
  });

  it('clicking refresh button calls both fetchSchedules and fetchRecap', async () => {
    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(1);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1);

    // Emit refresh from filters component
    const filtersComp = wrapper.findComponent({ name: 'DoctorScheduleFilters' });
    await filtersComp.vm.$emit('refresh');
    await flushPromises();

    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2);
  });

  it('synchronizes both list and recap after creating a new schedule', async () => {
    doctorScheduleService.createDoctorSchedule.mockResolvedValue({ data: { id: 3 } });

    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(1);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(1);

    // Open add modal and submit
    const formModal = wrapper.findComponent({ name: 'DoctorScheduleFormModal' });
    await formModal.vm.$emit('save', {
      doctor_id: 101,
      polyclinic_id: 10,
      day_of_week: 3,
      start_time: '10:00',
      end_time: '14:00',
      is_holiday: false
    });
    await flushPromises();

    expect(doctorScheduleService.createDoctorSchedule).toHaveBeenCalled();
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2);

    const toast = wrapper.findComponent({ name: 'AppToast' });
    expect(toast.props('type')).toBe('success');
    expect(toast.props('message')).toContain('Jadwal dokter berhasil ditambahkan.');
  });

  it('synchronizes both list and recap after updating a schedule', async () => {
    doctorScheduleService.updateDoctorSchedule.mockResolvedValue({ data: { id: 1 } });

    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    // Trigger edit on row
    const table = wrapper.findComponent({ name: 'DoctorScheduleTable' });
    await table.vm.$emit('edit', mockPaginatedResponse.data[0]);

    // Submit modal
    const formModal = wrapper.findComponent({ name: 'DoctorScheduleFormModal' });
    await formModal.vm.$emit('save', {
      doctor_id: 101,
      polyclinic_id: 10,
      day_of_week: 4,
      start_time: '11:00',
      end_time: '15:00',
      is_holiday: true
    });
    await flushPromises();

    expect(doctorScheduleService.updateDoctorSchedule).toHaveBeenCalledWith(
      1,
      expect.objectContaining({ day_of_week: 4, is_holiday: true })
    );
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2);

    const toast = wrapper.findComponent({ name: 'AppToast' });
    expect(toast.props('type')).toBe('success');
    expect(toast.props('message')).toContain('Jadwal dokter berhasil diperbarui.');
  });

  it('synchronizes both list and recap after deleting a schedule', async () => {
    doctorScheduleService.deleteDoctorSchedule.mockResolvedValue({});

    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    // Trigger delete dialog on row
    const table = wrapper.findComponent({ name: 'DoctorScheduleTable' });
    await table.vm.$emit('delete', mockPaginatedResponse.data[0]);

    // Confirm deletion
    const deleteDialog = wrapper.findComponent({ name: 'MasterDataDeleteDialog' });
    await deleteDialog.vm.$emit('confirm');
    await flushPromises();

    expect(doctorScheduleService.deleteDoctorSchedule).toHaveBeenCalledWith(1);
    expect(doctorScheduleService.getDoctorSchedules).toHaveBeenCalledTimes(2);
    expect(doctorScheduleService.getRecap).toHaveBeenCalledTimes(2);

    const toast = wrapper.findComponent({ name: 'AppToast' });
    expect(toast.props('type')).toBe('success');
    expect(toast.props('message')).toContain('Jadwal dokter berhasil dihapus.');
  });

  it('shows warning toast if save succeeds but recap fetch fails', async () => {
    doctorScheduleService.createDoctorSchedule.mockResolvedValue({ data: { id: 3 } });

    const wrapper = mount(DoctorScheduleView, {
      global: {
        stubs: {
          DoctorScheduleFormModal: true,
          MasterDataDeleteDialog: true,
          Teleport: true
        }
      }
    });

    await flushPromises();

    // Next getRecap call fails
    doctorScheduleService.getRecap.mockRejectedValueOnce(new Error('Network error on recap'));

    const formModal = wrapper.findComponent({ name: 'DoctorScheduleFormModal' });
    await formModal.vm.$emit('save', {
      doctor_id: 101,
      polyclinic_id: 10,
      day_of_week: 5,
      start_time: '08:00',
      end_time: '12:00',
      is_holiday: false
    });
    await flushPromises();

    const toast = wrapper.findComponent({ name: 'AppToast' });
    expect(toast.props('type')).toBe('warning');
    expect(toast.props('title')).toBe('Data Tersimpan');
    expect(toast.props('message')).toContain('tetapi data rekap gagal dimuat ulang');
  });
});
