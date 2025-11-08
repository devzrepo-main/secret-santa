<?php
require_once 'config.php';

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
} else {
    echo "✅ Connected successfully to database: " . $conn->host_info . "<br>";

    // Check if the table exists
    $res = $conn->query("SHOW TABLES LIKE 'family_members'");
    if ($res && $res->num_rows > 0) {
        echo "✅ Table 'family_members' exists.<br>";

        // Optional: test insert
        $test = $conn->query("INSERT INTO family_members (name) VALUES ('ConnectionTest')");
        if ($test) {
            echo "✅ Insert test successful.<br>";
        } else {
            echo "⚠️ Insert failed: " . $conn->error . "<br>";
        }
    } else {
        echo "⚠️ Table 'family_members' not found.<br>";
    }
}
?>

