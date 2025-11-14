<?php
header("Content-Type: application/json");
require_once("../config.php");

$ADMIN_PASS = "Secret123";  // Admin password
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ------------------------------------------
   ADD MEMBER (Admin)
------------------------------------------- */
if ($action === 'add_member') {
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['name'] ?? '');

    if ($password !== $ADMIN_PASS) {
        echo json_encode(['ok'=>false, 'error'=>'Invalid admin password']);
        exit;
    }
    if ($name === '') {
        echo json_encode(['ok'=>false, 'error'=>'Name cannot be empty']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO family_members (name, claimed) VALUES (?, 0)");
    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        echo json_encode(['ok'=>true, 'message'=>"$name added"]);
    } else {
        echo json_encode(['ok'=>false, 'error'=>'Duplicate or database error']);
    }
    exit;
}

/* ------------------------------------------
   REMOVE MEMBER (Admin)
------------------------------------------- */
if ($action === 'remove_member') {
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['name'] ?? '');

    if ($password !== $ADMIN_PASS) {
        echo json_encode(['ok'=>false, 'error'=>'Invalid admin password']);
        exit;
    }
    if ($name === '') {
        echo json_encode(['ok'=>false, 'error'=>'No name provided']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM family_members WHERE TRIM(name)=? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok'=>true, 'message'=>"$name removed"]);
    } else {
        echo json_encode(['ok'=>false, 'error'=>'Name not found']);
    }
    exit;
}

/* ------------------------------------------
   CLAIM SECRET SANTA (User)
------------------------------------------- */
if ($action === 'claim') {
    $member      = trim($_POST['member'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    if ($member === '' || $assigned_to === '') {
        echo json_encode(['ok'=>false, 'error'=>"Both fields required"]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE family_members SET claimed=1 WHERE TRIM(name)=? LIMIT 1");
    $stmt->bind_param("s", $member);
    $stmt->execute();

    echo json_encode(['ok'=>true, 'message'=>"Assignment recorded"]);
    exit;
}

/* ------------------------------------------
   STATUS (Remaining List)
------------------------------------------- */
if ($action === 'status') {
    $res = $conn->query("SELECT name FROM family_members WHERE claimed=0 ORDER BY name");
    $remaining = [];

    while ($row = $res->fetch_assoc()) {
        $remaining[] = $row['name'];
    }
    echo json_encode(['ok'=>true, 'remaining'=>$remaining]);
    exit;
}

/* ------------------------------------------
   FINAL REVEAL (Admin)
------------------------------------------- */
if ($action === 'reveal_final') {
    $password = $_POST['password'] ?? '';

    if ($password !== $ADMIN_PASS) {
        echo json_encode(['ok'=>false, 'error'=>'Invalid admin password']);
        exit;
    }

    $query = $conn->query("SELECT name FROM family_members WHERE claimed=0");
    $remaining = [];
    while ($row = $query->fetch_assoc()) $remaining[] = $row['name'];

    if (count($remaining) === 1) {
        echo json_encode(['ok'=>true, 'final_unclaimed'=>$remaining[0]]);
    } else {
        echo json_encode(['ok'=>false, 'error'=>'Reveal only allowed when 1 name remains']);
    }
    exit;
}

/* ------------------------------------------
   INVALID ACTION FALLBACK
------------------------------------------- */
echo json_encode(['ok'=>false, 'error'=>'No valid action']);
?>
