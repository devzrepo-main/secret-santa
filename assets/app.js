$(function () {
  const API = 'api/api.php';
  let adminLoggedIn = false;

  // ---------- Admin: Secure Login + Manage Family Members ----------
  function renderAdmin() {
    const html = `
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3 text-danger">Admin Panel</h5>
          <button id="adminLogin" class="btn btn-success mb-3">Admin Login</button>
          <div id="adminContent" style="display:none;">
            <form id="addForm" class="row g-2">
              <div class="col-sm-8">
                <input type="text" class="form-control" id="addName" placeholder="Enter a name (e.g., Alice)" />
              </div>
              <div class="col-sm-4 d-grid">
                <button class="btn btn-success" type="submit">Add</button>
              </div>
            </form>
            <div id="adminMsg" class="mt-2 small"></div>
            <div id="adminList" class="mt-3"></div>
          </div>
        </div>
      </div>`;
    $('#adminSection').html(html);

    // Admin login button
    $('#adminLogin').on('click', function () {
      const password = prompt('Enter the admin password:');
      if (!password) return;

      $.post(API, { action: 'admin_login', password }, function (res) {
        if (res.ok) {
          adminLoggedIn = true;
          $('#adminLogin').hide();
          $('#adminContent').slideDown();
          refreshList();
        } else {
          alert('Invalid admin password.');
        }
      }, 'json').fail(() => alert('Network error.'));
    });

    // Add member (only if logged in)
    $('#adminSection').on('submit', '#addForm', function (e) {
      e.preventDefault();
      if (!adminLoggedIn) {
        $('#adminMsg').html(`<span class="text-danger">Unauthorized: please log in first.</span>`);
        return;
      }

      const name = ($('#addName').val() || '').trim();
      if (!name) {
        $('#adminMsg').html(`<span class="text-danger">Please enter a name.</span>`);
        return;
      }

      $.post(API, { action: 'add_member', name, password: 'Secret123' }, function (res) {
        if (res.ok) {
          $('#adminMsg').html(`<span class="text-success">${res.message}</span>`);
          $('#addName').val('');
          refreshList();
        } else {
          $('#adminMsg').html(`<span class="text-danger">${res.error || 'Error adding name.'}</span>`);
        }
      }, 'json').fail(() => {
        $('#adminMsg').html(`<span class="text-danger">Network error.</span>`);
      });
    });

    // Fetch the list of members
    function refreshList() {
      $.getJSON(API, { action: 'status' }, function (res) {
        if (res.ok) {
          const members = res.remaining;
          if (members.length === 0) {
            $('#adminList').html('<p class="text-muted mt-2">No family members added yet.</p>');
          } else {
            const list = members.map(n => `<li class="list-group-item">${n}</li>`).join('');
            $('#adminList').html(`<ul class="list-group list-group-flush mt-3">${list}</ul>`);
          }
        } else {
          $('#adminList').html('<p class="text-danger mt-2">Error loading member list.</p>');
        }
      }).fail(() => {
        $('#adminList').html('<p class="text-danger mt-2">Network error loading list.</p>');
      });
    }
  }

  // ---------- Member: Claim Secret Santa ----------
  function renderMember() {
    const html = `
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-3 text-success" style="text-shadow: 1px 1px 2px #000;">Merry Christmas!</h5>
          <form id="claimForm" class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Your name (The Secret Santa)</label>
              <input type="text" class="form-control" id="memberName" placeholder="Type your name" />
            </div>
            <div class="col-md-6">
              <label class="form-label">The person you were assigned to</label>
              <input type="text" class="form-control" id="assignedTo" placeholder="Type the name you drew" />
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-success" type="submit">Submit</button>
            </div>
          </form>
          <div id="memberMsg" class="mt-2"></div>
          <p class="mt-3 text-muted small">If anyone shares the same first name please add an initial at the end as well. Names are removed from the pool once claimed. No list is shown.</p>
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
      }, 'json').fail(() => {
        $('#memberMsg').html(`<span class="text-danger">Network error.</span>`);
      });
    });
  }

  // ---------- Admin Final Reveal ----------
  function renderReveal() {
    const html = `
      <div class="card shadow-sm mt-4 border-danger">
        <div class="card-body">
          <h5 class="card-title text-danger">Alex's Reveal</h5>
          <button id="revealBtn" class="btn btn-success">Reveal Remaining Name</button>
          <div id="revealMsg" class="mt-3"></div>
        </div>
      </div>`;
    $('#adminSection').append(html);

    $('#revealBtn').on('click', function () {
      const password = prompt('Enter the admin password:');
      if (!password) return;

      $.post(API, { action: 'reveal_final', password }, function (res) {
        if (res.ok) {
          $('#revealMsg').html(`<h5 class="text-success">🎁 The last remaining name is: <b>${res.final_unclaimed}</b></h5>`);
        } else {
          $('#revealMsg').html(`<span class="text-danger">${res.error || 'Error checking remaining name.'}</span>`);
        }
      }, 'json').fail(() => {
        $('#revealMsg').html(`<span class="text-danger">Network error.</span>`);
      });
    });
  }

  renderMember();
  renderAdmin();
  renderReveal();
});


