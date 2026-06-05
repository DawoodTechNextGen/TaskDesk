<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

global $conn;

echo "Email Status counts:\n";
$res = $conn->query("SELECT email_status, COUNT(*) as count FROM registrations GROUP BY email_status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Status: " . $row['email_status'] . " -> Count: " . $row['count'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\nLast 5 registrations in 'contact' status:\n";
$res2 = $conn->query("SELECT id, name, email, status, email_status, updated_at FROM registrations WHERE status = 'contact' ORDER BY updated_at DESC LIMIT 5");
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
}
?>
