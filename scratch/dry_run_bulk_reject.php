<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/connection.php';

$sql = "SELECT id, name, status, updated_at FROM registrations 
        WHERE status = 'contact' 
        AND updated_at < DATE_SUB(NOW(), INTERVAL 15 DAY)";

$res = $conn->query($sql);
echo "Candidates that will be REJECTED (contact older than 15 days):\n";
echo "---------------------------------------------------------\n";
$count = 0;
while ($row = $res->fetch_assoc()) {
    $count++;
    echo "ID: {$row['id']} | Name: {$row['name']} | Updated At: {$row['updated_at']}\n";
}
echo "Total to be rejected: $count\n\n";

$sql2 = "SELECT id, name, status, updated_at FROM registrations 
         WHERE status = 'contact' 
         AND updated_at >= DATE_SUB(NOW(), INTERVAL 15 DAY)";

$res2 = $conn->query($sql2);
echo "Candidates that will REMAIN IN CONTACT (contact within last 15 days):\n";
echo "-------------------------------------------------------------\n";
$count2 = 0;
while ($row = $res2->fetch_assoc()) {
    $count2++;
    echo "ID: {$row['id']} | Name: {$row['name']} | Updated At: {$row['updated_at']}\n";
}
echo "Total to remain: $count2\n";
