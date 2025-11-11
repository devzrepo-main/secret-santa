<?php
require_once("../config.php");
header("Content-Type: application/json");

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ---------- ADD MEMBER ---------- */
if ($action === 'add_member') {
  $password = $_POST['password'] ?? '';
  if ($password !== 'Secret123') {
    echo json_encode(['error' => 'Unauthorized invalid admin password.']);
    exit;
  }

  $name = trim($_POST['name'] ?? '');
  if ($name === '') {
    echo json_encode(['error' => 'No name provided.']);
    exit;
  }

  $stmt = $conn->prepare("INSERT INTO family_members (name, claimed) VALUES (?, 0)");
  $stmt->bind_param("s", $name);
  if ($stmt->execute())
    echo json_encode(['ok' => true, 'message' => 'Member added successfully!']);
  else
    echo json_encode(['error' => 'Error adding member (possibly duplicate).']);
  exit;
}

/* ---------- REMOVE MEMBER ---------- */
if ($action === 'remove_member') {
  $password = $_POST['password'] ?? '';
  if ($password !== 'Secret123') {
    echo json_encode(['error' => 'Unauthorized invalid admin password.']);
    exit;
  }

  $name = trim($_POST['name'] ?? '');
  if ($name === '') {
    echo json_encode(['error' => 'No name provided.']);
    exit;
  }

  $stmt = $conn->prepare("DELETE FROM family_members WHERE TRIM(name)=? LIMIT 1");
  $stmt->bind_param("s", $name);
  if ($stmt->execute() && $stmt->affected_rows > 0)
    echo json_encode(['ok' => true, 'message' => 'Member removed successfully.']);
  else
    echo json_encode(['error' => 'Member not found or already removed.']);
  exit;
}

/* ---------- CLAIM SECRET SANTA ---------- */
if ($action === 'claim') {
  $member = trim($_POST['member'] ?? '');
  $assigned_to = trim($_POST['assigned_to'] ?? '');

  if ($member === '' || $assigned_to === '') {
    echo json_encode(['error' => 'Missing fields.']);
    exit;
  }

  // mark as claimed
  $stmt = $conn->prepare("UPDATE family_members SET claimed=1 WHERE TRIM(name)=? LIMIT 1");
  $stmt->bind_param("s", $member);
  $stmt->execute();

  echo json_encode(['ok' => true, 'message' => '🎁 Your Secret Santa assignment has been recorded!']);
  exit;
}

/* ---------- STATUS (LIST) ---------- */
if ($action === 'status') {
  $res = $conn->query("SELECT name FROM family_members WHERE claimed=0 ORDER BY name");
  $remaining = [];
  while ($row = $res->fetch_assoc()) $remaining[] = $row['name'];
  echo json_encode(['ok' => true, 'remaining' => $remaining]);
  exit;
}

/* ---------- FINAL REVEAL ---------- */
if ($action === 'reveal_final') {
  $password = $_POST['password'] ?? '';
  if ($password !== 'Secret123') {
    echo json_encode(['error' => 'Unauthorized: invalid admin password.']);
    exit;
  }

  $res = $conn->query("SELECT name FROM family_members WHERE claimed=0");
  $remaining = [];
  while ($row = $res->fetch_assoc()) $remaining[] = $row['name'];
  $count = count($remaining);

  if ($count > 1) {
    echo json_encode([
      'ok' => false,
      'error' => 'Not all family members have entered their name and Secret Santa assignment. The reveal section will only be visible when there is one last name left — showing your true assignment.'
    ]);
  } elseif ($count === 1) {
    echo json_encode(['ok' => true, 'final_unclaimed' => $remaining[0]]);
  } else {
    echo json_encode(['ok' => false, 'error' => 'Everyone has already claimed their Secret Santa!']);
  }
  exit;
}

echo json_encode(['error' => 'No valid action.']);
?>
