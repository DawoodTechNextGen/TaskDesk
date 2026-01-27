<?php
include 'include/connection.php';
$result = $conn->query("DESCRIBE tasks");
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
