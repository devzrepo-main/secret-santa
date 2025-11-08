<?php
// ---------- Database Connection ----------
$host = 'localhost';
$dbname = 'secret_santa';
$username = 'santa_user';
$password = 'SantaPass123!';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
  die('Database connection failed: ' . $conn->connect_error);
}

// ---------- Admin Password (hashed) ----------
// Generate new hash: php -r "echo password_hash('YourNewPass', PASSWORD_DEFAULT) . PHP_EOL;"
$ADMIN_PASS_HASH = '$2y$10$gq1aNR.WPKUzE1v7fTfsYeD8zEGiN8Xx2ngcGm3HcD6oT4eqmTyBi'; // Example
?>
