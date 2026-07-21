<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Car Workshop Online Appointment System</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <nav class="navbar">
    <div class="nav-container">
      <a href="index.php" class="brand">
        <div class="brand-icon">
          <i class="fa-solid fa-wrench"></i>
        </div>
        <span>Car Workshop</span>
      </a>
      <div class="nav-actions">
        <button class="btn btn-secondary" id="btnHelp">
          <i class="fa-solid fa-circle-question"></i> Help & Guidelines
        </button>
        <a href="admin.php" class="btn btn-primary">
          <i class="fa-solid fa-user-shield"></i> Admin Panel
        </a>
      </div>
    </div>
  </nav>

  <main class="main-wrapper">

    <section class="hero-banner">
      <h1 class="hero-title">Book Senior Mechanic Online</h1>
      <p class="hero-subtitle">Avoid waiting in line. Select your preferred senior mechanic, check real-time daily slot availability, and reserve your car inspection instantly.</p>
      <div class="hero-badges">
        <span class="hero-chip"><i class="fa-solid fa-user-gear"></i> 5 Senior Mechanics</span>
        <span class="hero-chip"><i class="fa-solid fa-calendar-check"></i> Max 4 Cars / Mechanic / Day</span>
        <span class="hero-chip"><i class="fa-solid fa-shield-halved"></i> Instant Confirmation</span>
      </div>
    </section>

    <div class="booking-grid">

      <div class="card">
        <div class="card-title">
          <span><i class="fa-solid fa-user-nurse text-primary"></i> 1. Select Date & Mechanic</span>
          <span class="badge" style="font-size: 0.8rem; color: var(--text-muted);" id="dateLabel">Today</span>
        </div>

        <div class="date-selector-box">
          <label for="appointment_date"><i class="fa-solid fa-calendar-days"></i> Choose Appointment Date:</label>
          <input type="date" id="appointment_date" class="input-date" min="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>">
        </div>

        <div style="margin-bottom: 0.75rem; font-weight: 600; font-size: 0.9rem; color: var(--text-muted);">
          Available Senior Mechanics (Slot Status for selected date):
        </div>

        <div id="mechanicsContainer" class="mechanics-list">
          <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 0.5rem;">Loading mechanics availability...</p>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title">
          <span><i class="fa-solid fa-id-card text-primary"></i> 2. Client & Vehicle Details</span>
        </div>

        <form id="appointmentForm" novalidate>
          <input type="hidden" id="selected_mechanic_id" name="mechanic_id" value="">

          <div class="form-group">
            <label class="form-label" for="client_name">Full Name <span class="req">*</span></label>
            <input type="text" id="client_name" name="client_name" class="form-input" placeholder="e.g. Tanvir Hossain" required>
            <div class="field-error" id="err_client_name">Please enter your full name.</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="phone">Phone Number <span class="req">*</span></label>
            <input type="tel" id="phone" name="phone" class="form-input" placeholder="e.g. 01712345678" required>
            <div class="field-error" id="err_phone">Please enter a valid phone number (digits only).</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="address">Address <span class="req">*</span></label>
            <input type="text" id="address" name="address" class="form-input" placeholder="e.g. House 12, Road 4, Dhanmondi, Dhaka" required>
            <div class="field-error" id="err_address">Please enter your address.</div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
              <label class="form-label" for="car_license">Car License / Reg No. <span class="req">*</span></label>
              <input type="text" id="car_license" name="car_license" class="form-input" placeholder="e.g. DHAKA-METRO-GA-11-2233" required>
              <div class="field-error" id="err_car_license">Car License No. required.</div>
            </div>

            <div class="form-group">
              <label class="form-label" for="car_engine">Car Engine Number <span class="req">*</span></label>
              <input type="text" id="car_engine" name="car_engine" class="form-input" placeholder="e.g. ENG9948102" required>
              <div class="field-error" id="err_car_engine">Engine No. (numbers/alphanumeric) required.</div>
            </div>
          </div>

          <div id="selectionSummary" style="background: var(--surface-alt); border-radius: var(--radius-md); padding: 0.85rem; margin-bottom: 1.25rem; border: 1px solid var(--border); font-size: 0.875rem;">
            <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i>
            Selected Mechanic: <strong id="summaryMechanicName" style="color: var(--danger);">None Selected</strong>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 1rem;">
            <i class="fa-solid fa-paper-plane"></i> Confirm & Book Appointment
          </button>
        </form>
      </div>

    </div>
  </main>

  <div class="modal-overlay" id="helpModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-circle-info text-primary"></i> Appointment Guidelines & Help</h3>
        <button class="btn-close" id="btnCloseHelp">&times;</button>
      </div>
      <div class="modal-body" style="font-size: 0.925rem; line-height: 1.7; color: var(--text-main);">
        <h4 style="margin-bottom: 0.5rem; color: var(--primary);"><i class="fa-solid fa-sliders"></i> System Rules</h4>
        <ul style="margin-left: 1.25rem; margin-bottom: 1.25rem;">
          <li><strong>Mechanics Limit:</strong> There are 5 senior mechanics. Each mechanic is permitted a maximum of <strong>4 client cars per day</strong>.</li>
          <li><strong>Real-time Slots:</strong> Selecting an appointment date will display the live free slot count for each mechanic on that day.</li>
          <li><strong>Duplicate Appointment Limit:</strong> A client (identified by Phone or Car License Plate) can only hold <strong>1 appointment per date</strong>. Attempting to book multiple slots on the same date will be rejected.</li>
          <li><strong>Occupied Mechanic:</strong> If your desired mechanic has 0 slots remaining (4/4 booked), you must select another available mechanic for that date.</li>
        </ul>

        <h4 style="margin-bottom: 0.5rem; color: var(--primary);"><i class="fa-solid fa-clipboard-check"></i> How to Book</h4>
        <ol style="margin-left: 1.25rem; margin-bottom: 1.25rem;">
          <li>Pick your desired date on the date calendar picker.</li>
          <li>Click on an available mechanic card (highlighted in Green or Orange).</li>
          <li>Fill out your full details in the Client & Vehicle form.</li>
          <li>Click <strong>Confirm & Book Appointment</strong> to receive instant confirmation.</li>
        </ol>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="resultModal">
    <div class="modal-card" style="text-align: center;">
      <div id="resultIcon" style="font-size: 3.5rem; margin-bottom: 1rem;"></div>
      <h3 id="resultTitle" style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;"></h3>
      <p id="resultMessage" style="color: var(--text-muted); font-size: 1rem; margin-bottom: 1.5rem;"></p>
      <button class="btn btn-primary" id="btnCloseResult" style="margin: 0 auto; min-width: 140px;">OK</button>
    </div>
  </div>

  <div class="toast-container" id="toastContainer"></div>

  <footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Car Workshop Online Appointment System | CSE 391 Assignment 3</p>
  </footer>

  <script src="app.js"></script>
</body>
</html>
