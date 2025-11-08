/* ---------- ADD MEMBER (Admin) ---------- */
if ($action === 'add_member') {
  $password = $_POST['password'] ?? '';
  $adminPassword = 'Secret123'; // must match app.js

  if ($password !== $adminPassword) {
    echo json_encode(['error' => 'Unauthorized: invalid admin password']);
    exit;
  }

  $name = trim($_POST['name'] ?? '');
  if ($name === '') {
    echo json_encode(['error' => 'Name cannot be empty']);
    exit;
  }

  // Check for duplicate name
  $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
  $check->bind_param("s", $name);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    echo json_encode(['error' => 'This name already exists.']);
    $check->close();
    exit;
  }
  $check->close();

  // Insert name
  $stmt = $conn->prepare("INSERT INTO family_members (name) VALUES (?)");
  $stmt->bind_param("s", $name);
  if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => "Added '$name' successfully."]);
  } else {
    echo json_encode(['error' => 'Database insert failed.']);
  }
  $stmt->close();
  exit;
}
