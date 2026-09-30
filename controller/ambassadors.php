<?php
// Admin management of Campus Ambassador accounts (ambassadors.php). The
// ambassador's own read-only dashboard is served by controller/ambassador.php.
session_start();
include '../include/connection.php';
require_once '../include/ambassador_helper.php';
require_once '../include/notification_helper.php';
header('Content-Type: application/json');

requireAdminAction();
ensureInternshipTypeSchema($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $role = ROLE_AMBASSADOR;
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.plain_password, u.university, u.referral_code, u.status,
                   DATE(u.created_at) AS created_on,
                   COUNT(r.id) AS total_referrals,
                   COALESCE(SUM(r.status = 'hire'), 0) AS hired
            FROM users u
            LEFT JOIN registrations r ON r.ref_code = u.referral_code
            WHERE u.user_role = ?
            GROUP BY u.id
            ORDER BY u.name ASC
        ");
        $stmt->bind_param('i', $role);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($data as &$row) {
            $row['referral_link'] = $row['referral_code'] ? ambassadorReferralLink($row['referral_code']) : '';
        }
        unset($row);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $university === '') {
            echo json_encode(['success' => false, 'message' => 'Name, a valid email and university are required']);
            exit;
        }
        if (emailTakenByOtherUser($conn, $email, 0)) {
            echo json_encode(['success' => false, 'message' => 'That email is already registered']);
            exit;
        }
        if ($password === '') {
            $password = generateStrictPassword(12);
        }

        try {
            $code = generateReferralCode($conn, $name);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not generate a referral code, please try again']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role = ROLE_AMBASSADOR;
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, plain_password, user_role, status, tech_id, supervisor_id, commission_rate, university, referral_code)
                                VALUES (?, ?, ?, ?, ?, 1, 0, 0, 0, ?, ?)");
        $stmt->bind_param('ssssiss', $name, $email, $hashed, $password, $role, $university, $code);
        try {
            $ok = $stmt->execute();
            $errno = $ok ? 0 : $stmt->errno;
        } catch (\mysqli_sql_exception $e) {
            $ok = false;
            $errno = $e->getCode();
        }
        if ($ok) {
            logActivity('Create User', "Created Campus Ambassador: $name ($email), code $code");
            queueAmbassadorWelcomeEmail($name, $email, $password, $university, $code);
            echo json_encode(['success' => true, 'message' => 'Campus Ambassador created! A welcome email with their login and referral link is on its way.']);
        } else {
            $duplicate = ($errno === 1062);
            echo json_encode(['success' => false, 'message' => $duplicate ? 'That email is already registered' : 'Failed to create ambassador']);
        }
        $stmt->close();
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $university === '') {
            echo json_encode(['success' => false, 'message' => 'Name, a valid email and university are required']);
            exit;
        }

        if (emailTakenByOtherUser($conn, $email, $id)) {
            echo json_encode(['success' => false, 'message' => 'That email is already registered']);
            exit;
        }

        // The referral code never changes, so links already shared keep working.
        $role = ROLE_AMBASSADOR;
        if ($password !== '') {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, university = ?, password = ?, plain_password = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('sssssii', $name, $email, $university, $hashed, $password, $id, $role);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, university = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('sssii', $name, $email, $university, $id, $role);
        }
        try {
            $ok = $stmt->execute();
            $errno = $ok ? 0 : $stmt->errno;
        } catch (\mysqli_sql_exception $e) {
            $ok = false;
            $errno = $e->getCode();
        }
        if ($ok) {
            logActivity('Update User', "Updated Campus Ambassador ID $id: $name ($email)");
            echo json_encode(['success' => true, 'message' => 'Updated successfully!']);
        } else {
            $duplicate = ($errno === 1062);
            echo json_encode(['success' => false, 'message' => $duplicate ? 'That email is already registered' : 'Update failed']);
        }
        $stmt->close();
        break;

    // Ambassadors are deactivated rather than deleted, so the students they
    // referred keep pointing at them. An inactive ambassador can't log in and
    // the registration form stops accepting their code.
    case 'toggle_status':
        $id = (int)($_POST['id'] ?? 0);
        $status = ((int)($_POST['status'] ?? 0) === 1) ? 1 : 0;
        $role = ROLE_AMBASSADOR;
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND user_role = ?");
        $stmt->bind_param('iii', $status, $id, $role);
        $ok = $stmt->execute();
        $stmt->close();
        if ($ok) {
            logActivity('Update User', "Campus Ambassador ID $id " . ($status ? 'activated' : 'deactivated'));
        }
        echo json_encode(['success' => $ok, 'message' => $ok ? ($status ? 'Ambassador activated' : 'Ambassador deactivated') : 'Failed to update status']);
        break;

    // Sends the welcome email again with the current password - e.g. after it
    // went to spam or the Admin changed the password.
    case 'resend_welcome':
        $id = (int)($_POST['id'] ?? 0);
        $role = ROLE_AMBASSADOR;
        $stmt = $conn->prepare("SELECT name, email, plain_password, university, referral_code, status FROM users WHERE id = ? AND user_role = ? LIMIT 1");
        $stmt->bind_param('ii', $id, $role);
        $stmt->execute();
        $amb = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$amb || empty($amb['referral_code'])) {
            echo json_encode(['success' => false, 'message' => 'Ambassador not found']);
            exit;
        }
        if ((int)$amb['status'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Activate this ambassador before sending the welcome email']);
            exit;
        }

        queueAmbassadorWelcomeEmail($amb['name'], $amb['email'], $amb['plain_password'], (string)$amb['university'], $amb['referral_code']);
        logActivity('Resend Ambassador Email', "Welcome email resent to {$amb['name']} ({$amb['email']})");
        echo json_encode(['success' => true, 'message' => 'Welcome email is being sent to ' . $amb['email']]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Login looks users up by email alone, so an ambassador can't share one with any other account.
function emailTakenByOtherUser($conn, $email, $exceptId)
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
    $stmt->bind_param('si', $email, $exceptId);
    $stmt->execute();
    $taken = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $taken;
}

function generateStrictPassword($length = 12)
{
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()-_=+{}[]<>?';

    $all = $upper . $lower . $numbers . $symbols;

    $password = '';
    $password .= $upper[random_int(0, strlen($upper) - 1)];
    $password .= $lower[random_int(0, strlen($lower) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];

    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }

    return str_shuffle($password);
}
