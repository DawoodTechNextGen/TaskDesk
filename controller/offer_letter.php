<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
require_once '../include/pdf_helper.php';

// Manual Offer Letter download. Built server-side with the same template as the
// letter emailed on hire, so the text is sharp and it is always a single A4 page.
if (!isset($_SESSION['user_id']) || (int)($_SESSION['user_role'] ?? 0) !== ROLE_ADMIN) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}
session_write_close();

$name = trim($_POST['name'] ?? '');
$technology = trim($_POST['technology'] ?? '');
$startDate = trim($_POST['start_date'] ?? '');
$endDate = trim($_POST['end_date'] ?? '');
$issueDate = trim($_POST['issue_date'] ?? '');

if ($name === '' || $technology === '' || $startDate === '' || $endDate === '') {
    http_response_code(400);
    echo 'Name, technology, start date and end date are required';
    exit;
}

$pdfContent = generateOfferLetterHelper($name, $startDate, $endDate, $technology, $issueDate !== '' ? $issueDate : null);
if (!$pdfContent) {
    http_response_code(500);
    echo 'Failed to generate offer letter';
    exit;
}

$fileName = 'OfferLetter_' . preg_replace('/[^A-Za-z0-9]+/', '_', $name) . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
