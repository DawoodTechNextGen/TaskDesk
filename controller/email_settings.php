<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
include '../include/registration_helper.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array((int)$_SESSION['user_role'], [1, 4], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $internshipType = ($_GET['type'] ?? '0') === '1' ? 1 : 0;
        $settings = getRegistrationEmailSettings($conn, $internshipType);

        echo json_encode([
            'success' => true,
            'email_subject' => $settings['subject'],
            'email_body' => $settings['body'],
            'interested_link' => $settings['link']
        ]);
        break;

    case 'save':
        $internshipType = ($_POST['type'] ?? '0') === '1' ? 1 : 0;
        $subject = trim($_POST['email_subject'] ?? '');
        $body = trim($_POST['email_body'] ?? '');
        $link = trim($_POST['interested_link'] ?? '');

        // Quill's empty state is "<p><br></p>", not an empty string
        $bodyIsEmpty = ($body === '' || strip_tags($body) === '');

        if ($subject === '' || $bodyIsEmpty) {
            echo json_encode(['success' => false, 'message' => 'Subject and message body are required']);
            exit;
        }

        if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'message' => 'The interested/payment link must be a valid URL']);
            exit;
        }

        saveRegistrationEmailSettings($conn, $internshipType, $subject, $body, $link);

        $typeLabel = registrationTypeLabel($internshipType);
        logActivity('Update Email Settings', "Updated $typeLabel registration interest email template/link");

        echo json_encode(['success' => true, 'message' => "$typeLabel settings saved successfully"]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

if (isset($conn)) {
    mysqli_close($conn);
}
