<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "santa_user";
$password = "SantaPass123!";
$dbname = "secret_santa";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
  exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ---------- ADD MEMBER (Admin only) ---------- */
if ($action === 'add_member') {
  $name = trim($_POST['name'] ?? '');
  $adminKey = $_POST['adminKey'] ?? '';
  $adminPassword = 'Secret123';

  if ($adminKey !== $adminPassword) {
    echo json_encode(['error' => 'Unauthorized: invalid admin password']);
    exit;
  }

  if ($name === '') {
    echo json_encode(['error' => 'Name cannot be empty']);
    exit;
  }

  $stmt = $conn->prepare("INSERT INTO family_members (name) VALUES (?)");
  $stmt->bind_param("s", $name);

  if ($stmt->execute()) {
    echo json_encode(['ok' => true, 'message' => 'Name added successfully.']);
  } else {
    if ($conn->errno === 1062) {
      echo json_encode(['error' => 'That name already exists.']);
    } else {
      echo json_encode(['error' => 'Error adding name: ' . $conn->error]);
    }
  }
  exit;
}

/* ---------- CLAIM SECRET SANTA ---------- */
if ($action === 'claim') {
  $member = trim($_POST['member'] ?? '');
  $assigned_to = trim($_POST['assigned_to'] ?? '');

  if ($member === '' || $assigned_to === '') {
    echo json_encode(['error' => 'Both fields are required']);
    exit;
  }

  // Ensure assigned_to isn't already taken
  $check = $conn->prepare("SELECT id FROM family_members WHERE assigned_to = ?");
  $check->bind_param("s", $assigned_to);
  $check->execute();
  $check->store_result();
  if ($check->num_rows > 0) {
    echo json_encode(['error' => "$assigned_to has already been chosen."]);
    exit;
  }

  // Record assignment
  $stmt = $conn->prepare("UPDATE family_members SET assigned_to=? WHERE name=?");
  $stmt->bind_param("ss", $assigned_to, $member);

  if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['ok' => true, 'message' => 'Assignment recorded successfully.']);
  } else {
    echo json_encode(['error' => 'Could not record assignment. Check the name you entered.']);
  }
  exit;
}

/* ---------- STATUS ---------- */
if ($action === 'status') {
  $result = $conn->query("SELECT name, assigned_to FROM family_members");
  $total = $assigned = 0;
  $remaining = [];

  while ($row = $result->fetch_assoc()) {
    $total++;
    if ($row['assigned_to']) {
      $assigned++;
    } else {
      $remaining[] = $row['name'];
    }
  }

  echo json_encode([
    'ok' => true,
    'total' => $total,
    'assigned' => $assigned,
    'remaining' => $remaining
  ]);
  exit;
}

/* ---------- FINAL REVEAL ---------- */
if ($action === 'reveal_final') {
  $password = $_POST['password'] ?? '';
  $adminPassword = 'Secret123';

  if ($password !== $adminPassword) {
    echo json_encode(['error' => 'Invalid password.']);
    exit;
  }

  $res = $conn->query("SELECT name FROM family_members WHERE assigned_to IS NULL LIMIT 1");
  if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo json_encode(['ok' => true, 'final_unclaimed' => $row['name']]);
  } else {
    echo json_encode(['error' => 'No unclaimed names found.']);
  }
  exit;
}

/* ---------- ADMIN LOGIN ---------- */
if ($action === 'admin_login') {
  $password = $_POST['password'] ?? '';
  $adminPassword = 'Secret123'; // The correct password
  if ($password === $adminPassword) {
    echo json_encode(['ok' => true, 'message' => 'Admin login successful']);
  } else {
    echo json_encode(['ok' => false, 'error' => 'Invalid admin password']);
  }
  exit;
}


/* ---------- DEFAULT ---------- */
echo json_encode(['error' => 'No valid action']);
?>

