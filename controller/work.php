<?php
include 'include/connection.php';
require 'vendor/autoload.php';

while (true) {
    $job = $conn->query("SELECT * FROM jobs WHERE status='pending' ORDER BY id ASC LIMIT 1")->fetch_assoc();

    if ($job) {
        $conn->query("UPDATE jobs SET status='processing' WHERE id={$job['id']}");

        $data = json_decode($job['payload'], true);

        $success = sendCredentialsEmail(
            $data['email'],
            $data['name'],
            $data['password'],
            $data['role']
        );

        $conn->query("UPDATE jobs SET status='" . ($success ? 'done' : 'failed') . "' WHERE id={$job['id']}");
    }

    sleep(1); // prevents CPU 100%
}
// php worker.php for run this
