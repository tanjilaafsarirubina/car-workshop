document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('appointmentsTbody');
  const filterSearch = document.getElementById('filterSearch');
  const filterDate = document.getElementById('filterDate');
  const filterMechanic = document.getElementById('filterMechanic');
  const btnResetFilters = document.getElementById('btnResetFilters');
  const btnRefresh = document.getElementById('btnRefresh');

  const statTotal = document.getElementById('statTotal');
  const statToday = document.getElementById('statToday');

  const editModal = document.getElementById('editModal');
  const editForm = document.getElementById('editForm');
  const btnCloseEdit = document.getElementById('btnCloseEdit');
  const btnCancelEdit = document.getElementById('btnCancelEdit');
  const editAppointmentId = document.getElementById('edit_appointment_id');
  const editClientName = document.getElementById('editClientName');
  const editClientDetails = document.getElementById('editClientDetails');
  const editDateInput = document.getElementById('edit_date');
  const editMechanicSelect = document.getElementById('edit_mechanic');

  let appointmentsList = [];

  loadAppointments();

  filterSearch.addEventListener('input', loadAppointments);
  filterDate.addEventListener('change', loadAppointments);
  filterMechanic.addEventListener('change', loadAppointments);
  btnRefresh.addEventListener('click', loadAppointments);

  btnResetFilters.addEventListener('click', () => {
    filterSearch.value = '';
    filterDate.value = '';
    filterMechanic.value = '0';
    loadAppointments();
  });

  btnCloseEdit.addEventListener('click', () => editModal.classList.remove('active'));
  btnCancelEdit.addEventListener('click', () => editModal.classList.remove('active'));
  editModal.addEventListener('click', (e) => {
    if (e.target === editModal) editModal.classList.remove('active');
  });

  editDateInput.addEventListener('change', (e) => {
    const targetDate = e.target.value;
    const currentMechanicId = editMechanicSelect.dataset.currentMechanicId;
    const originalDate = editMechanicSelect.dataset.originalDate;
    loadMechanicsForEdit(targetDate, currentMechanicId, originalDate);
  });

  function loadAppointments() {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
          <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
          <p style="margin-top: 0.5rem;">Fetching appointments...</p>
        </td>
      </tr>
    `;

    const search = encodeURIComponent(filterSearch.value.trim());
    const date = encodeURIComponent(filterDate.value);
    const mechanicId = filterMechanic.value;

    fetch(`api.php?action=get_appointments&search=${search}&date=${date}&mechanic_id=${mechanicId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          appointmentsList = data.appointments;
          renderTable(data.appointments);
          updateStats(data.appointments);
        } else {
          tbody.innerHTML = `<tr><td colspan="8" style="color: var(--danger); text-align: center;">${data.message}</td></tr>`;
        }
      })
      .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="8" style="color: var(--danger); text-align: center;">Error loading data from server.</td></tr>`;
      });
  }

  function renderTable(appointments) {
    if (!appointments || appointments.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
            <i class="fa-solid fa-folder-open fa-2x"></i>
            <p style="margin-top: 0.5rem; font-weight: 600;">No appointments found matching your criteria.</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = '';
    appointments.forEach(app => {
      const tr = document.createElement('tr');

      tr.innerHTML = `
        <td style="font-weight: 700; color: var(--text-muted);">#${app.id}</td>
        <td>
          <div class="client-cell">${escapeHtml(app.client_name)}</div>
          <div style="font-size: 0.8rem; color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> ${escapeHtml(app.address)}</div>
        </td>
        <td><span class="phone-badge"><i class="fa-solid fa-phone"></i> ${escapeHtml(app.phone)}</span></td>
        <td><span class="reg-badge"><i class="fa-solid fa-car"></i> ${escapeHtml(app.car_license)}</span></td>
        <td><span class="phone-badge">${escapeHtml(app.car_engine)}</span></td>
        <td><strong style="color: var(--primary);">${formatDate(app.appointment_date)}</strong></td>
        <td>
          <div style="font-weight: 700;"><i class="fa-solid fa-user-gear text-primary"></i> ${escapeHtml(app.mechanic_name)}</div>
          <div style="font-size: 0.775rem; color: var(--text-muted);">${escapeHtml(app.mechanic_specialty)}</div>
        </td>
        <td>
          <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-secondary btn-edit" data-id="${app.id}" title="Edit Date & Mechanic" style="padding: 0.4rem 0.75rem;">
              <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="btn btn-danger btn-delete" data-id="${app.id}" title="Cancel Appointment" style="padding: 0.4rem 0.75rem;">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </td>
      `;

      tbody.appendChild(tr);
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', () => openEditModal(btn.dataset.id));
    });

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => deleteAppointment(btn.dataset.id));
    });
  }

  function updateStats(appointments) {
    statTotal.innerText = appointments.length;

    // toISOString() is UTC, which is still "yesterday" in Dhaka until 6 AM.
    const now = new Date();
    const todayStr = [
      now.getFullYear(),
      String(now.getMonth() + 1).padStart(2, '0'),
      String(now.getDate()).padStart(2, '0')
    ].join('-');
    const todayCount = appointments.filter(a => a.appointment_date === todayStr).length;
    statToday.innerText = todayCount;
  }

  function openEditModal(appId) {
    const app = appointmentsList.find(a => a.id == appId);
    if (!app) return;

    editAppointmentId.value = app.id;
    editClientName.innerText = `Client: ${app.client_name}`;
    editClientDetails.innerText = `Phone: ${app.phone} | Car Reg: ${app.car_license} | Engine: ${app.car_engine}`;
    editDateInput.value = app.appointment_date;
    editMechanicSelect.dataset.currentMechanicId = app.mechanic_id;
    editMechanicSelect.dataset.originalDate = app.appointment_date;

    loadMechanicsForEdit(app.appointment_date, app.mechanic_id, app.appointment_date);
    editModal.classList.add('active');
  }

  function loadMechanicsForEdit(targetDate, currentMechanicId, originalDate) {
    editMechanicSelect.innerHTML = `<option value="">Loading mechanics for ${targetDate}...</option>`;

    fetch(`api.php?action=get_slots&date=${encodeURIComponent(targetDate)}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          editMechanicSelect.innerHTML = '';
          data.mechanics.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;

            // This appointment already holds one of its mechanic's slots on
            // its original date, so that slot is free for it to keep.
            let availSlots = m.available_slots;
            if (m.id == currentMechanicId && targetDate === originalDate) {
              availSlots += 1;
            }

            const isFull = (availSlots <= 0);
            opt.innerText = `${m.name} (${availSlots} / ${m.max_daily_slots} Free Slots) - ${m.specialty}`;

            if (isFull) {
              opt.innerText += ' [FULL]';
              opt.disabled = true;
            }

            if (m.id == currentMechanicId) {
              opt.selected = true;
            }

            editMechanicSelect.appendChild(opt);
          });
        }
      })
      .catch(err => {
        console.error(err);
        showToast('Error loading mechanic slots for target date.', 'error');
      });
  }

  editForm.addEventListener('submit', (e) => {
    e.preventDefault();

    const id = editAppointmentId.value;
    const newDate = editDateInput.value;
    const newMechanicId = editMechanicSelect.value;

    if (!newDate || !newMechanicId) {
      showToast('Please select both a valid date and mechanic.', 'error');
      return;
    }

    const formData = new FormData();
    formData.append('action', 'update_appointment');
    formData.append('id', id);
    formData.append('appointment_date', newDate);
    formData.append('mechanic_id', newMechanicId);

    fetch('api.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(data.message, 'success');
          editModal.classList.remove('active');
          loadAppointments();
        } else {
          showToast(data.message, 'error');
        }
      })
      .catch(err => {
        console.error(err);
        showToast('Failed to update appointment.', 'error');
      });
  });

  function deleteAppointment(appId) {
    if (!confirm(`Are you sure you want to cancel and delete appointment #${appId}?`)) return;

    const formData = new FormData();
    formData.append('action', 'cancel_appointment');
    formData.append('id', appId);

    fetch('api.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showToast(data.message, 'success');
          loadAppointments();
        } else {
          showToast(data.message, 'error');
        }
      })
      .catch(err => {
        console.error(err);
        showToast('Failed to delete appointment.', 'error');
      });
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', options);
  }

  function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fa-solid fa-circle-info"></i> <span>${msg}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 4000);
  }
});
