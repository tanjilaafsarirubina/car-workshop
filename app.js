document.addEventListener('DOMContentLoaded', () => {
  const dateInput = document.getElementById('appointment_date');
  const mechanicsContainer = document.getElementById('mechanicsContainer');
  const selectedMechanicIdInput = document.getElementById('selected_mechanic_id');
  const summaryMechanicName = document.getElementById('summaryMechanicName');
  const appointmentForm = document.getElementById('appointmentForm');

  const helpModal = document.getElementById('helpModal');
  const btnHelp = document.getElementById('btnHelp');
  const btnCloseHelp = document.getElementById('btnCloseHelp');

  const resultModal = document.getElementById('resultModal');
  const resultIcon = document.getElementById('resultIcon');
  const resultTitle = document.getElementById('resultTitle');
  const resultMessage = document.getElementById('resultMessage');
  const btnCloseResult = document.getElementById('btnCloseResult');

  let currentMechanicsData = [];
  let selectedMechanic = null;

  fetchMechanicsSlots(dateInput.value);

  dateInput.addEventListener('change', (e) => {
    fetchMechanicsSlots(e.target.value);
  });

  btnHelp.addEventListener('click', () => helpModal.classList.add('active'));
  btnCloseHelp.addEventListener('click', () => helpModal.classList.remove('active'));
  helpModal.addEventListener('click', (e) => {
    if (e.target === helpModal) helpModal.classList.remove('active');
  });

  btnCloseResult.addEventListener('click', () => resultModal.classList.remove('active'));

  function fetchMechanicsSlots(selectedDate) {
    mechanicsContainer.innerHTML = `
      <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
        <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
        <p style="margin-top: 0.5rem;">Fetching available mechanics for ${selectedDate}...</p>
      </div>
    `;

    fetch(`api.php?action=get_slots&date=${encodeURIComponent(selectedDate)}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          currentMechanicsData = data.mechanics;
          renderMechanicsList(data.mechanics);
        } else {
          mechanicsContainer.innerHTML = `<div class="toast error">${data.message || 'Error fetching slots.'}</div>`;
        }
      })
      .catch(err => {
        console.error(err);
        mechanicsContainer.innerHTML = `<div style="color: var(--danger); text-align: center; padding: 1rem;">Failed to connect to server.</div>`;
      });
  }

  function renderMechanicsList(mechanics) {
    mechanicsContainer.innerHTML = '';
    selectedMechanic = null;
    selectedMechanicIdInput.value = '';
    summaryMechanicName.innerText = 'None Selected';
    summaryMechanicName.style.color = 'var(--danger)';

    if (!mechanics || mechanics.length === 0) {
      mechanicsContainer.innerHTML = '<p>No mechanics found.</p>';
      return;
    }

    mechanics.forEach(m => {
      const card = document.createElement('div');
      card.className = `mechanic-card ${m.is_full ? 'disabled' : ''}`;
      card.dataset.id = m.id;

      let badgeClass = 'available';
      let badgeText = `${m.available_slots} / ${m.max_daily_slots} Free Slots`;

      if (m.is_full) {
        badgeClass = 'full';
        badgeText = 'FULL (0 Slots)';
      } else if (m.available_slots <= 1) {
        badgeClass = 'warning';
      }

      card.innerHTML = `
        <div class="mechanic-info">
          <div class="mechanic-avatar">
            <i class="fa-solid fa-user-gear"></i>
          </div>
          <div class="mechanic-details">
            <h4>${m.name}</h4>
            <p>${m.specialty}</p>
          </div>
        </div>
        <div>
          <span class="slot-badge ${badgeClass}">${badgeText}</span>
        </div>
      `;

      if (!m.is_full) {
        card.addEventListener('click', () => {
          document.querySelectorAll('.mechanic-card').forEach(c => c.classList.remove('selected'));
          card.classList.add('selected');
          selectedMechanic = m;
          selectedMechanicIdInput.value = m.id;
          summaryMechanicName.innerText = `${m.name} (${m.specialty})`;
          summaryMechanicName.style.color = 'var(--primary)';
        });
      }

      mechanicsContainer.appendChild(card);
    });
  }

  appointmentForm.addEventListener('submit', (e) => {
    e.preventDefault();

    document.querySelectorAll('.field-error').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));

    const clientName = document.getElementById('client_name').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const address = document.getElementById('address').value.trim();
    const carLicense = document.getElementById('car_license').value.trim();
    const carEngine = document.getElementById('car_engine').value.trim();
    const date = dateInput.value;
    const mechanicId = selectedMechanicIdInput.value;

    let isValid = true;

    if (!clientName) {
      showError('client_name', 'err_client_name', 'Please enter your name.');
      isValid = false;
    }
    if (!phone || !/^[0-9+\s\-()]{6,20}$/.test(phone)) {
      showError('phone', 'err_phone', 'Please enter a valid phone number with digits only.');
      isValid = false;
    }
    if (!address) {
      showError('address', 'err_address', 'Please enter your address.');
      isValid = false;
    }
    if (!carLicense) {
      showError('car_license', 'err_car_license', 'Please enter your car license / reg number.');
      isValid = false;
    }
    if (!carEngine || !/^[a-zA-Z0-9\s\-]+$/.test(carEngine)) {
      showError('car_engine', 'err_car_engine', 'Please enter a valid engine number (alphanumeric).');
      isValid = false;
    }
    if (!mechanicId) {
      showToast('Please select a mechanic from the left list to proceed.', 'error');
      isValid = false;
    }

    if (!isValid) return;

    const formData = new FormData();
    formData.append('action', 'book');
    formData.append('client_name', clientName);
    formData.append('phone', phone);
    formData.append('address', address);
    formData.append('car_license', carLicense);
    formData.append('car_engine', carEngine);
    formData.append('appointment_date', date);
    formData.append('mechanic_id', mechanicId);

    fetch('api.php', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showResultModal(
            '<i class="fa-solid fa-circle-check" style="color: var(--accent);"></i>',
            'Appointment Approved!',
            data.message
          );
          appointmentForm.reset();
          selectedMechanicIdInput.value = '';
          summaryMechanicName.innerText = 'None Selected';
          summaryMechanicName.style.color = 'var(--danger)';
          fetchMechanicsSlots(dateInput.value);
        } else {
          showResultModal(
            '<i class="fa-solid fa-circle-xmark" style="color: var(--danger);"></i>',
            'Appointment Request Declined',
            data.message
          );
        }
      })
      .catch(err => {
        console.error(err);
        showToast('Server error while submitting appointment.', 'error');
      });
  });

  function showError(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    const err = document.getElementById(errId);
    if (input) input.classList.add('error');
    if (err) {
      err.innerText = msg;
      err.classList.add('active');
    }
  }

  function showResultModal(iconHtml, title, msg) {
    resultIcon.innerHTML = iconHtml;
    resultTitle.innerText = title;
    resultMessage.innerText = msg;
    resultModal.classList.add('active');
  }

  function showToast(msg, type = 'info') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="fa-solid fa-info-circle"></i> <span>${msg}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.remove();
    }, 4000);
  }
});
