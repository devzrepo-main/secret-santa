$(function () {

  const API = 'api/api.php';

  /* =====================================
       MEMBER CLAIM SECTION
  ===================================== */
  function renderMember() {
    const html = `
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5 class="card-title text-success mb-3">Merry Christmas!</h5>
          <form id="claimForm" class="row g-3">

            <div class="col-md-6">
              <label class="form-label">Your name</label>
              <input type="text" id="memberName" class="form-control" />
            </div>

            <div class="col-md-6">
              <label class="form-label">The person you were assigned</label>
              <input type="text" id="assignedTo" class="form-control" />
            </div>

            <div class="col-12 d-grid">
              <button class="btn btn-success" type="submit">Submit</button>
            </div>
          </form>

          <div id="memberMsg" class="mt-2"></div>
        </div>
      </div>
    `;
    $('#memberSection').html(html);

    $("#claimForm").on("submit", function (e) {
      e.preventDefault();
      const member      = $("#memberName").val().trim();
      const assigned_to = $("#assignedTo").val().trim();

      if (!member || !assigned_to) {
        $('#memberMsg').html(`<span class="text-danger">Both fields required.</span>`);
        return;
      }

      $.post(API, { action: "claim", member, assigned_to }, function (res) {
        if (res.ok)
          $('#memberMsg').html(`<span class="text-success">${res.message}</span>`);
        else
          $('#memberMsg').html(`<span class="text-danger">${res.error}</span>`);
      }, "json").fail(() => {
        $('#memberMsg').html(`<span class="text-danger">Network error.</span>`);
      });
    });
  }

  /* =====================================
       ADMIN PANEL
  ===================================== */
  function renderAdmin() {
    const html = `
      <div class="card shadow-sm border-danger mt-4">
        <div class="card-body">

          <h5 class="card-title text-danger">Admin Panel</h5>
          <button id="adminLogin" class="btn btn-success mb-3">Admin Login</button>

          <div id="adminPanel" style="display:none;">
            <form id="addForm" class="row g-2">
              <div class="col-sm-8">
                <input class="form-control" id="addName" placeholder="Enter a name" />
              </div>
              <div class="col-sm-4 d-grid">
                <button class="btn btn-success" type="submit">Add</button>
              </div>
            </form>

            <div id="adminMsg" class="mt-2"></div>
            <div id="adminList" class="mt-3"></div>
          </div>

        </div>
      </div>
    `;
    $('#adminSection').html(html);

    let adminPassword = null;

    $("#adminLogin").on("click", function () {
      adminPassword = prompt("Admin password:");
      if (!adminPassword) return;
      $("#adminPanel").slideDown();
      updateList();
    });

    $("#addForm").on("submit", function (e) {
      e.preventDefault();
      const name = $("#addName").val().trim();
      if (!name) return;

      $.post(API, { action: "add_member", name, password: adminPassword }, function (res) {
        if (res.ok) {
          $('#adminMsg').html(`<span class="text-success">${res.message}</span>`);
          $("#addName").val('');
          updateList();
        } else {
          $('#adminMsg').html(`<span class="text-danger">${res.error}</span>`);
        }
      }, "json");
    });

    function updateList() {
      $.getJSON(API, { action: "status" }, function (res) {
        if (!res.ok) return;

        if (res.remaining.length === 0) {
          $("#adminList").html("<p class='text-muted'>No names yet.</p>");
          return;
        }

        let list = "<ul class='list-group list-group-flush'>";
        res.remaining.forEach(n => {
          list += `
            <li class="list-group-item d-flex justify-content-between">
              ${n}
              <button class="btn btn-sm btn-outline-danger removeBtn" data-name="${n}">
                Remove
              </button>
            </li>
          `;
        });
        list += "</ul>";

        $("#adminList").html(list);
      });
    }

    $("#adminSection").on("click", ".removeBtn", function () {
      const name = $(this).data("name");
      if (!confirm(`Remove "${name}"?`)) return;

      $.post(API, { action: "remove_member", name, password: adminPassword }, function (res) {
        if (res.ok) {
          $('#adminMsg').html(`<span class="text-success">${res.message}</span>`);
          updateList();
        } else {
          $('#adminMsg').html(`<span class="text-danger">${res.error}</span>`);
        }
      }, "json");
    });
  }

  /* =====================================
       FINAL REVEAL
  ===================================== */
  function renderReveal() {
    const html = `
      <div class="card shadow-sm border-danger mt-4">
        <div class="card-body">

          <h5 class="card-title text-danger">Final Reveal</h5>
          <button id="revealBtn" class="btn btn-success">Reveal</button>
          <div id="revealMsg" class="mt-3"></div>

        </div>
      </div>
    `;
    $('#revealSection').html(html);

    $("#revealBtn").on("click", function () {
      const password = prompt("Admin password:");
      if (!password) return;

      $.post(API, { action: "reveal_final", password }, function (res) {
        if (res.ok) {
          $('#revealMsg').html(`<h5 class="text-success"><b>${res.final_unclaimed}</b></h5>`);
        } else {
          $('#revealMsg').html(`<span class="text-danger">${res.error}</span>`);
        }
      }, "json");
    });
  }

  /* Bootstrap all components */
  renderMember();
  renderAdmin();
  renderReveal();

});
