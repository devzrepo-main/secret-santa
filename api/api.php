<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../config.php');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$adminPassword = 'Secret123'; // master admin password

/* ---------- ADMIN LOGIN ---------- */
if ($action === 'admin_login') {
    $password = $_POST['password'] ?? '';
    if ($password === $adminPassword) {
        echo json_encode(['ok' => true, 'message' => 'Admin login successful']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Invalid admin password']);
    }
    exit;
}

/* ---------- ADD MEMBER (Admin) ---------- */
if ($action === 'add_member') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['error' => 'Name cannot be empty']);
        exit;
    }

    // Prevent duplicates
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

    // Insert new member
    $stmt = $conn->prepare("INSERT INTO family_members (name, assigned_to) VALUES (?, NULL)");
    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'message' => "Added '$name' successfully."]);
    } else {
        echo json_encode(['error' => 'Database insert failed.']);
    }

    $stmt->close();
    exit;
}

/* ---------- REMOVE MEMBER (Admin) ---------- */
if ($action === 'remove_member') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['error' => 'No member specified.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM family_members WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true, 'message' => "Removed '$name' successfully."]);
    } else {
        echo json_encode(['error' => 'Member not found or already removed.']);
    }

    $stmt->close();
    exit;
}

/* ---------- CLAIM SECRET SANTA (User) ---------- */
if ($action === 'claim') {
    $member = trim($_POST['member'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    if ($member === '' || $assigned_to === '') {
        echo json_encode(['error' => 'Missing member or assigned name']);
        exit;
    }

    // Verify assigned_to exists
    $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Assigned name not found in the list']);
        $check->close();
        exit;
    }
    $check->close();

    // Check if assigned_to already chosen
    $check = $conn->prepare("SELECT id FROM family_members WHERE assigned_to = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'That person has already been chosen.']);
        $check->close();
        exit;
    }
    $check->close();

    // Assign
    $stmt = $conn->prepare("UPDATE family_members SET assigned_to = ? WHERE name = ?");
    $stmt->bind_param("ss", $assigned_to, $member);

    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'message' => "$member has been assigned to $assigned_to!"]);
    } else {
        echo json_encode(['error' => 'Database update failed']);
    }

    $stmt->close();
    exit;
}

/* ---------- STATUS / LIST MEMBERS ---------- */
if ($action === 'status') {
    $result = $conn->query("SELECT name, assigned_to FROM family_members");

    $members = [];
    $assigned = 0;
    $remaining = [];

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
        if ($row['assigned_to']) {
            $assigned++;
        } else {
            $remaining[] = $row['name'];
        }
    }

    echo json_encode([
        'ok' => true,
        'total' => count($members),
        'assigned' => $assigned,
        'remaining' => $remaining
    ]);
    exit;
}

/* ---------- FINAL REVEAL (Admin Only) ---------- */
if ($action === 'reveal_final') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $result = $conn->query("SELECT name FROM family_members WHERE assigned_to IS NULL");

    $unclaimed = [];
    while ($row = $result->fetch_assoc()) {
        $unclaimed[] = $row['name'];
    }

    echo json_encode([
        'ok' => true,
        'final_unclaimed' => implode(', ', $unclaimed) ?: 'Everyone has been assigned!'
    ]);
    exit;
}

echo json_encode(['error' => 'No valid action']);
exit;
?>
