<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

$res = $conn->query("SELECT id, name, email FROM registrations WHERE status = 'new' LIMIT 15");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Email: {$row['email']}\n";
}
