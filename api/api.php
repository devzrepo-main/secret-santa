<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../config.php');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$adminPassword = 'Secret123'; // Master admin password

/* ============================================================
   SEND EMAIL ALERT WHEN ONE NAME IS LEFT
   ============================================================ */
function notifyAdminFinalReveal($remainingName) {
    // Admin receives notification
    $to = "ajuffras@gmail.com";

    // Your sending Gmail
    $from = "devzrobby@gmail.com";
    $fromName = "Secret Santa Notifier";

    $subject = "🎄 Secret Santa: Final Name Revealed!";
    $message = "Hello Alex,\n\n"
             . "There is now ONLY ONE name left in the Secret Santa pool.\n"
             . "The remaining unassigned person is:\n\n"
             . "➡ $remainingName\n\n"
             . "This message was automatically sent when the pool reached 1 name.\n\n"
             . "Merry Christmas! 🎅🎁";

    // SMTP settings
    $smtpServer = "smtp.gmail.com";
    $smtpPort   = 587;
    $smtpUser   = "devzrobby@gmail.com";
    $smtpPass   = "tenkghbhbbwrujir"; // <-- Replace with generated 16-character app password

    // Create email headers
    $headers = "From: $fromName <$from>";

    // Use PHPMailer for real SMTP sending
    require_once(__DIR__ . '/../smtp/PHPMailer.php');
    require_once(__DIR__ . '/../smtp/SMTP.php');
    require_once(__DIR__ . '/../smtp/Exception.php');

    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = $smtpServer;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = 'tls';
    $mail->Port = $smtpPort;

    $mail->setFrom($from, $fromName);
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body = $message;

    $mail->send();
}

/* ============================================================
   ADMIN LOGIN
   ============================================================ */
if ($action === 'admin_login') {
    $password = $_POST['password'] ?? '';
    echo json_encode([
        'ok' => $password === $adminPassword,
        'error' => $password !== $adminPassword ? 'Invalid admin password' : null
    ]);
    exit;
}

/* ============================================================
   ADD MEMBER
   ============================================================ */
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

    // Duplicate check
    $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'This name already exists.']);
        exit;
    }
    $check->close();

    // Insert
    $stmt = $conn->prepare("INSERT INTO family_members (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    echo json_encode(['ok' => true, 'message' => "$name added successfully"]);
    exit;
}

/* ============================================================
   REMOVE MEMBER
   ============================================================ */
if ($action === 'remove_member') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['error' => 'No member name specified']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM family_members WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $name);
    $stmt->execute();

    if ($stmt->affected_rows > 0)
        echo json_encode(['ok' => true, 'message' => "$name removed"]);
    else
        echo json_encode(['error' => 'Member not found']);

    exit;
}

/* ============================================================
   CLAIM (USER ASSIGNMENT)
   ============================================================ */
if ($action === 'claim') {
    $member = trim($_POST['member'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? '');

    if ($member === '' || $assigned_to === '') {
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }

    // Assigned_to must exist
    $check = $conn->prepare("SELECT id FROM family_members WHERE name = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        echo json_encode(['error' => 'Assigned name not found']);
        exit;
    }
    $check->close();

    // Assigned_to can't be chosen twice
    $check = $conn->prepare("SELECT id FROM family_members WHERE assigned_to = ?");
    $check->bind_param("s", $assigned_to);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['error' => 'That person has already been chosen']);
        exit;
    }

    // Update assignment
    $stmt = $conn->prepare("UPDATE family_members SET assigned_to = ? WHERE name = ?");
    $stmt->bind_param("ss", $assigned_to, $member);
    $stmt->execute();

    echo json_encode(['ok' => true, 'message' => "$member has been assigned to $assigned_to"]);
    exit;
}

/* ============================================================
   STATUS (LIST MEMBERS)
   ============================================================ */
if ($action === 'status') {
    $res = $conn->query("SELECT name, assigned_to FROM family_members");
    $members = [];
    $assigned = 0;
    $remaining = [];

    while ($row = $res->fetch_assoc()) {
        $members[] = $row;
        if ($row['assigned_to']) $assigned++;
        else $remaining[] = $row['name'];
    }

    echo json_encode([
        'ok' => true,
        'total' => count($members),
        'assigned' => $assigned,
        'remaining' => $remaining
    ]);
    exit;
}

/* ============================================================
   FINAL REVEAL + AUTO EMAIL
   ============================================================ */
if ($action === 'reveal_final') {
    $password = $_POST['password'] ?? '';
    if ($password !== $adminPassword) {
        echo json_encode(['error' => 'Unauthorized: invalid admin password']);
        exit;
    }

    $res = $conn->query("SELECT name FROM family_members WHERE assigned_to IS NULL");
    $remaining = [];
    while ($row = $res->fetch_assoc()) $remaining[] = $row['name'];

    if (count($remaining) === 1) {
        // AUTO SEND EMAIL ALERT
        notifyAdminFinalReveal($remaining[0]);

        echo json_encode([
            'ok' => true,
            'final_unclaimed' => $remaining[0]
        ]);
        exit;
    }

    echo json_encode([
        'ok' => false,
        'error' => 'Not ready — more than one person remains unassigned'
    ]);
    exit;
}

/* ============================================================
   DEFAULT
   ============================================================ */
echo json_encode(['error' => 'No valid action']);
exit;
