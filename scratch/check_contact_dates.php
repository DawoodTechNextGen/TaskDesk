<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

$res = $conn->query("SELECT id, name, status, updated_at FROM registrations WHERE status = 'contact' ORDER BY updated_at ASC");
while ($row = $res->fetch_assoc()) {
    $diffDays = (time() - strtotime($row['updated_at'])) / (60 * 60 * 24);
    echo "ID: {$row['id']} | Name: {$row['name']} | Status: {$row['status']} | Updated At: {$row['updated_at']} | Age (Days): " . round($diffDays, 1) . "\n";
}
