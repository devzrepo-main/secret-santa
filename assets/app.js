$(function () {

  const API = 'api/api.php';

  /* ---------------- MEMBER SECTION ---------------- */
  function renderMember() {
    const html = `
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Merry Christmas!</h5>
          <form id="claimForm" class="row g-3">
            <div class="col-md-6">
              <label>Your name (The Secret Santa):</label>
              <input id="memberName" class="form-control" />
            </div>
            <div class="col-md-6">
              <label>The person you drew:</label>
              <input id="assignedTo" class="form-control" />
            </div>
            <div class="col-12 d-grid">
              <button class="btn btn-success" type="submit">Submit</button>
            </div>
          </form>
          <div id="memberMsg" class="mt-2"></div>
           <p class="mt-3 text-muted small">
            For people with the same name please include your initial next to your name. Note: Names are removed from the pool as soon as they’re claimed. No list is shown.
          </p>
        </div>
      </div>`;
    $('#memberSection').html(html);

    $('#claimForm').on('submit', function (e) {
      e.preventDefault();
      const member = $('#memberName').val().trim();
      const assigned_to = $('#assignedTo').val().trim();

      if (!member || !assigned_to) {
        $('#memberMsg').html(`<span class="text-danger">Missing fields</span>`);
        return;
      }

      $.post(API, { action: 'claim', member, assigned_to }, function (res) {
        if (res.ok) $('#memberMsg').html(`<span class="text-success">${res.message}</span>`);
        else $('#memberMsg').html(`<span class="text-danger">${res.error}</span>`);
      }, 'json');
    });
  }

  /* ---------------- ADMIN PANEL ---------------- */
  function renderAdmin() {

    const html = `
      <div class="card shadow-sm border-danger mt-4">
        <div class="card-body">
          <h5 class="card-title text-danger">Admin Panel</h5>
          <button id="adminLogin" class="btn btn-success mb-3">Admin Login</button>

          <div id="adminPanel" style="display:none;">
            <form id="addForm" class="row g-2">
              <div class="col-sm-8">
                <input id="addName" class="form-control" placeholder="Enter a name" />
              </div>
              <div class="col-sm-4 d-grid">
                <button class="btn btn-success" type="submit">Add</button>
              </div>
            </form>
            <div id="adminMsg" class="mt-2"></div>

            <div id="adminList" class="mt-3"></div>
          </div>
        </div>
      </div>`;

    $('#adminWrapper').html(html);

    let adminPassword = null;

    $('#adminLogin').on('click', function () {
      adminPassword = prompt("Enter admin password:");
      if (!adminPassword) return;
      $('#adminPanel').slideDown();
      refreshList();
    });

    $('#addForm').on('submit', function (e) {
      e.preventDefault();
      const name = $('#addName').val().trim();
      if (!name) return;

      $.post(API,
        { action: 'add_member', name, password: adminPassword },
        function (res) {
          if (res.ok) {
            $('#adminMsg').html(`<span class="text-success">${res.message}</span>`);
            $('#addName').val('');
            refreshList();
          } else {
            $('#adminMsg').html(`<span class="text-danger">${res.error}</span>`);
          }
        },
        'json'
      );
    });

    $('#adminPanel').on('click', '.removeBtn', function () {
      const name = $(this).data('name');

      $.post(API,
        { action: 'remove_member', name, password: adminPassword },
        function (res) {
          if (res.ok) refreshList();
          else $('#adminMsg').html(`<span class="text-danger">${res.error}</span>`);
        },
        'json'
      );
    });

    function refreshList() {
      $.getJSON(API, { action: 'status' }, function (res) {

        if (!res.ok) return;

        if (!res.remaining.length) {
          $('#adminList').html(`<p>No members added.</p>`);
          return;
        }

        const list = res.remaining.map(n => `
          <li class="list-group-item d-flex justify-content-between">
            ${n}
            <button class="btn btn-sm btn-outline-danger removeBtn" data-name="${n}">
              Remove
            </button>
          </li>`).join('');

        $('#adminList').html(`<ul class="list-group">${list}</ul>`);
      });
    }
  }

  /* ---------------- FINAL REVEAL ---------------- */
  function renderReveal() {
    const html = `
      <div class="card shadow-sm border-danger mt-4">
        <div class="card-body">
          <h5 class="card-title text-danger">Alex's Final Reveal</h5>
          <button id="revealBtn" class="btn btn-success">Reveal Final Name</button>
          <div id="revealMsg" class="mt-3"></div>
        </div>
      </div>`;
    $('#revealSection').html(html);

    $('#revealBtn').on('click', function () {
      const password = prompt("Enter admin password:");
      if (!password) return;

      $.post(API, { action: 'reveal_final', password }, function (res) {
        if (res.ok) {
          $('#revealMsg').html(`<h4 class="text-success">${res.final_unclaimed}</h4>`);
        } else {
          $('#revealMsg').html(`<span class="text-danger">${res.error}</span>`);
        }
      }, 'json');
    });
  }

  /* ---------- RENDER EVERYTHING ---------- */
  renderMember();
  renderReveal();
  renderAdmin();
});

