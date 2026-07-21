<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Car Workshop Online Appointment System</title>
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
        <span>Car Workshop <small style="font-size: 0.7rem; background: var(--primary); color: #fff; padding: 0.2rem 0.5rem; border-radius: 4px; margin-left: 0.5rem;">ADMIN</small></span>
      </a>
      <div class="nav-actions">
        <a href="index.php" class="btn btn-secondary">
          <i class="fa-solid fa-house"></i> User Booking Page
        </a>
      </div>
    </div>
  </nav>

  <main class="main-wrapper">

    <div class="admin-header">
      <div>
        <h2 style="font-size: 1.75rem; font-weight: 800;"><i class="fa-solid fa-users-gear text-primary"></i> Client Appointments Management</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">View, search, filter, change appointment dates, or reassign mechanics for active clients.</p>
      </div>
      <button class="btn btn-outline" id="btnRefresh">
        <i class="fa-solid fa-arrows-rotate"></i> Refresh List
      </button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
        <div>
          <div class="stat-value" id="statTotal">0</div>
          <div class="stat-label">Total Appointments</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--accent-light); color: var(--accent);"><i class="fa-solid fa-clock"></i></div>
        <div>
          <div class="stat-value" id="statToday">0</div>
          <div class="stat-label">Today's Appointments</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon" style="background: var(--warning-light); color: var(--warning);"><i class="fa-solid fa-user-gear"></i></div>
        <div>
          <div class="stat-value">5</div>
          <div class="stat-label">Senior Mechanics</div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
      <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: center;">
        <div>
          <label class="form-label" for="filterSearch">Search Client / License / Phone</label>
          <input type="text" id="filterSearch" class="form-input" placeholder="Type client name, phone, or license plate...">
        </div>
        <div>
          <label class="form-label" for="filterDate">Filter Date</label>
          <input type="date" id="filterDate" class="form-input">
        </div>
        <div>
          <label class="form-label" for="filterMechanic">Filter Mechanic</label>
          <select id="filterMechanic" class="form-input">
            <option value="0">All Mechanics</option>
            <option value="1">Karim Rahman</option>
            <option value="2">Tanvir Ahmed</option>
            <option value="3">Rahim Uddin</option>
            <option value="4">Shafiqul Islam</option>
            <option value="5">Mahfuz Khan</option>
          </select>
        </div>
        <div style="padding-top: 1.35rem;">
          <button class="btn btn-secondary" id="btnResetFilters" title="Reset Filters">
            <i class="fa-solid fa-filter-circle-xmark"></i> Clear
          </button>
        </div>
      </div>
    </div>

    <div class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Client Name & Address</th>
            <th>Phone</th>
            <th>Car License / Reg No</th>
            <th>Car Engine No</th>
            <th>Appointment Date</th>
            <th>Assigned Mechanic</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="appointmentsTbody">
          <tr>
            <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
              <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
              <p style="margin-top: 0.5rem;">Loading appointments list...</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </main>

  <div class="modal-overlay" id="editModal">
    <div class="modal-card">
      <div class="modal-header">
        <h3 class="modal-title"><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Appointment & Reassign Mechanic</h3>
        <button class="btn-close" id="btnCloseEdit">&times;</button>
      </div>

      <form id="editForm">
        <input type="hidden" id="edit_appointment_id" name="id">

        <div style="background: var(--surface-alt); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem; border: 1px solid var(--border);">
          <div style="font-weight: 700; color: var(--text-main);" id="editClientName">Client: Name</div>
          <div style="font-size: 0.85rem; color: var(--text-muted);" id="editClientDetails">Phone: 017000000 | License: DHAKA-123</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_date">New Appointment Date <span class="req">*</span></label>
          <input type="date" id="edit_date" name="appointment_date" class="form-input" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="edit_mechanic">Reassign Mechanic <span class="req">*</span></label>
          <select id="edit_mechanic" name="mechanic_id" class="form-input" required>
            <option value="">Loading mechanics for date...</option>
          </select>
          <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 0.35rem;" id="editSlotHelp">
            Select an available mechanic with free slots on the target date.
          </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
          <button type="button" class="btn btn-secondary" id="btnCancelEdit">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <div class="toast-container" id="toastContainer"></div>

  <footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> Car Workshop Online Appointment System | Admin Panel</p>
  </footer>

  <script src="admin.js"></script>
</body>
</html>
