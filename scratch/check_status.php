<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

$res = $conn->query("SELECT status, COUNT(*) as count FROM registrations GROUP BY status");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
