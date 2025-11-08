$(function () {
  const API = 'api/api.php';
  let adminKey = ''; // stores password after login for this session

  // ---------- MEMBER SECTION ----------
  function renderMember() {
    const html = `
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3 text-success">Secret Santa</h5>
          <form id="claimForm" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Your name</label>
              <input type="text" class="form-control" id="memberName" placeholder="Type your name" />
            </div>
            <div class="col-md-6">
              <label class="form-label">Who are you assigned as Secret Santa to?</label>
              <input type="text" class="form-control" id="assignedTo" placeholder="Type the name you drew" />
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-success" type="submit">Submit</button>
            </div>
          </form>
          <div id="memberMsg" class="mt-2"></div>
          <p class="mt-3 text-muted small">
            If there are duplicate names please use a last initial. Names are removed from the pool as soon as they’re claimed. No list is shown.
          </p>
        </div>
      </div>`;
    $('#memberSection').html(html);

    $('#claimForm').on('submit', function (e) {
      e.preventDefault();
      const member = ($('#memberName').val() || '').trim();
      const assigned_to = ($('#assignedTo').val() || '').trim();
      if (!member || !assigned_to) {
        $('#memberMsg').html(`<span class="text-danger">Please fill in both fields.</span>`);
        return;
      }
      $.post(API, { action: 'claim', member, assigned_to }, function (res) {
        if (res.ok) {
          $('#memberMsg').html(`<span class="text-success">${res.message}</span>`);
          $('#assignedTo').val('');
        } else {
          $('#memberMsg').html(`<span class="text-danger">${res.error || 'Error saving claim.'}</span>`);
        }
      }, 'json').fail(() =>
        $('#memberMsg').html(`<span class="text-danger">Network error.</span>`)
      );
    });
  }

  // ---------- ADMIN LOGIN ----------
  function renderAdminLogin() {
    const html = `
      <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
          <h5 class="card-title mb-3 text-danger">Alex's Console</h5>
          <p>Manage family members and reveal the final Secret Santa.</p>
          <button id="adminLoginBtn" class="btn btn-success">Admin Login</button>
          <div id="adminLoginMsg" class="mt-2"></div>
          <div id="adminPanel" class="mt-4"></div>
        </div>
      </div>`;
    $('#adminSection').html(html);

    $('#adminLoginBtn').on('click', function () {
      const key = prompt('Enter the admin password:');
      if (key) {
        adminKey = key;
        $('#adminLoginMsg').html(`<span class="text-success">✅ Login successful!</span>`);
        renderAdminPanel();
      }
    });
  }

  // ---------- ADMIN PANEL ----------
  function renderAdminPanel() {
    const html = `
      <h5 class="text-danger mb-3">Manage Family Members</h5>
      <form id="addForm" class="row g-2 mb-3">
        <div class="col-sm-8">
          <input type="text" class="form-control" id="addName" placeholder="Enter a name (e.g., Alice)" />
        </div>
        <div class="col-sm-4 d-grid">
          <button class="btn btn-success" type="submit">Add</button>
        </div>
      </form>
      <div id="adminMsg" class="mb-3 small"></div>
      <div id="adminStatus" class="text-muted small"></div>
      <hr />
      <button id="revealBtn" class="btn btn-success">Reveal Remaining Name</button>
      <div id="revealMsg" class="mt-3"></div>
      <hr />
      <button id="logoutBtn" class="btn btn-outline-danger btn-sm mt-2">Logout Admin</button>`;
    $('#adminPanel').html(html);

    // ---------- Add Member ----------
    $('#addForm').on('submit', function (e) {
      e.preventDefault();
      const name = ($('#addName').val() || '').trim();
      if (!name) { $('#adminMsg').text('Please enter a name.'); return; }
      if (!adminKey) { $('#adminMsg').text('Please login as admin first.'); return; }

      $.post(API, { action: 'add_member', name, adminKey }, function (res) {
        if (res.ok) {
          $('#adminMsg').html(`<span class="text-success">${res.message}</span>`);
          $('#addName').val('');
          refreshStatus();
        } else {
          $('#adminMsg').html(`<span class="text-danger">${res.error || 'Error adding name.'}</span>`);
        }
      }, 'json').fail(() =>
        $('#adminMsg').html(`<span class="text-danger">Network error.</span>`)
      );
    });

    // ---------- Reveal Button ----------
    $('#revealBtn').on('click', function () {
      if (!adminKey) {
        $('#revealMsg').html(`<span class="text-danger">Login first.</span>`);
        return;
      }
      $.post(API, { action: 'reveal_final', password: adminKey }, function (res) {
        if (res.ok) {
          $('#revealMsg').html(`<h5 class="text-success">🎁 The last remaining name is: <b>${res.final_unclaimed}</b></h5>`);
        } else {
          $('#revealMsg').html(`<span class="text-danger">${res.error || 'Error checking remaining name.'}</span>`);
        }
      }, 'json').fail(() =>
        $('#revealMsg').html(`<span class="text-danger">Network error.</span>`)
      );
    });

    // ---------- Logout ----------
    $('#logoutBtn').on('click', function () {
      adminKey = '';
      $('#adminPanel').html('');
      $('#adminLoginMsg').html(`<span class="text-warning">Logged out.</span>`);
    });

    // ---------- Status ----------
    function refreshStatus() {
      $.getJSON(API, { action: 'status' }, function (res) {
        if (res.ok) {
          $('#adminStatus').html(
            `Total: ${res.total} | Assigned: ${res.assigned} | Remaining: ${res.remaining.join(', ')}`
          );
        }
      });
    }
    refreshStatus();
  }

  // ---------- Initialize ----------
  renderMember();
  renderAdminLogin();
});

