<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------ CONFIG ------------
$action        = $_POST['action'] ?? $_GET['action'] ?? '';
$adminPassword = 'Secret123';                 // Admin password for Alex
$notifyEmail   = 'ajuffras@gmail.com';        // Where the notification goes
$fromEmail     = 'devzrobby@gmail.com';       // Gmail sending address
$smtpPass      = 'tenkghbhbbwrujir';          // 16-char Gmail App Password (no spaces)

// ------------ EMAIL SENDER ------------
function sendSecretSantaEmail(string $to, string $subject, string $body, string $fromEmail, string $smtpPass): bool
{
    $mail = new PHPMailer(true);

    try {
        // SMTP setup
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $fromEmail;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom($fromEmail, 'Secret Santa Notifier');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Secret Santa email error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Check if only ONE unassigned name remains and, if so, notify Alex by email.
 * Called after successful claim so it does NOT depend on Alex clicking Reveal.
 */
function checkAndNotifyFinal(\mysqli $conn, string $notifyEmail, string $fromEmail, string $smtpPass): void
{
    $res = $conn->query("SELECT name FROM family_members WHERE assigned_to IS NULL");
    if (!$res) {
        // If query fails, just log and return
        error_log('Secret Santa final check query failed: ' . $conn->error);
        return;
    }

    $remaining = [];
    while ($row = $res->fetch_assoc()) {
        $remaining[] = $row['name'];
    }

    if (count($remaining) === 1) {
        $last = $remaining[0];

        $subject = "🎁 Secret Santa: Final Name Remaining!";
        $body    = "Hello Alex,\n\n"
                 . "There is now ONLY ONE name left in the Secret Santa pool.\n\n"
                 . "➡ The final remaining person is: $last\n\n"
                 . "This email was sent automatically when only one unassigned name remained.\n\n"
                 . "Merry Christmas! 🎄";

        // Fire and forget – even if it fails, we don't break the app
        sendSecretSantaEmail($notifyEmail, $subject, $body, $fromEmail, $smtpPass);
    }
}

// ============================================================
// ADMIN LOGIN
// ============================================================
if ($action === 'admin_login') {
    $password = $_POST['password'] ?? '';
    $ok = ($password === $adminPassword);

    echo json_encode([
        'ok'    => $ok,
        'error' => $ok ? null : 'Invalid admin password'
    ]);
    exit;
}

// ============================================================
// ADD MEMBER (Admin)
// ============================================================
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

    // Check for duplicate
    $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['error' => 'This name already exists.']);
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO family_members (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(['ok' => true, 'message' => "Added '$name' successfully."]);
    } else {
        echo json_encode(['error' => 'Database insert failed.']);
    }
    exit;
}

// ============================================================
// REMOVE MEMBER (Admin)
// ============================================================
if ($action === 'remove_member') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['error' => 'No member name specified.']);
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
    exit;
}

// ============================================================
// CLAIM SECRET SANTA (User)
// ============================================================
if ($action === 'claim') {
    $member      = trim($_POST['member'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    if ($member === '' || $assigned_to === '') {
        echo json_encode(['error' => 'Missing member or assigned name']);
        exit;
    }

    // Ensure assigned_to exists
    $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        echo json_encode(['error' => 'Assigned name not found in the list']);
        exit;
    }
    $check->close();

    // Ensure assigned_to not already chosen
    $check = $conn->prepare("SELECT id FROM family_members WHERE assigned_to = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['error' => 'That person has already been chosen.']);
        exit;
    }
    $check->close();

    // Store assignment
    $stmt = $conn->prepare("UPDATE family_members SET assigned_to = ? WHERE name = ?");
    $stmt->bind_param("ss", $assigned_to, $member);
    if ($stmt->execute()) {
        // After a successful claim, check if we are down to one remaining name
        checkAndNotifyFinal($conn, $notifyEmail, $fromEmail, $smtpPass);

        echo json_encode(['ok' => true, 'message' => "$member has been assigned to $assigned_to!"]);
    } else {
        echo json_encode(['error' => 'Database update failed']);
    }
    exit;
}

// ============================================================
// STATUS / LIST MEMBERS
// ============================================================
if ($action === 'status') {
    $result = $conn->query("SELECT name, assigned_to FROM family_members");
    if (!$result) {
        echo json_encode(['error' => 'Database query failed.']);
        exit;
    }

    $members   = [];
    $assigned  = 0;
    $remaining = [];

    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
        if (!empty($row['assigned_to'])) {
            $assigned++;
        } else {
            $remaining[] = $row['name'];
        }
    }

    echo json_encode([
        'ok'       => true,
        'total'    => count($members),
        'assigned' => $assigned,
        'remaining'=> $remaining
    ]);
    exit;
}

// ============================================================
// FINAL REVEAL (Admin) - still shows remaining names
// ============================================================
if ($action === 'reveal_final') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $result = $conn->query("SELECT name FROM family_members WHERE assigned_to IS NULL");
    if (!$result) {
        echo json_encode(['error' => 'Database query failed.']);
        exit;
    }

    $unclaimed = [];
    while ($row = $result->fetch_assoc()) {
        $unclaimed[] = $row['name'];
    }

    echo json_encode([
        'ok'             => true,
        'final_unclaimed'=> implode(', ', $unclaimed) ?: 'Everyone has been assigned!'
    ]);
    exit;
}

// ============================================================
// DEFAULT
// ============================================================
echo json_encode(['error' => 'No valid action']);
exit;
