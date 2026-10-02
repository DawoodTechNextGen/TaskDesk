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
        // Ambassador accounts plus Interns who are also ambassadors. `status`
        // here is the ambassador side (amb_active), not the intern's own login.
        $role = ROLE_AMBASSADOR;
        $internRole = ROLE_INTERN;
        $stmt = $conn->prepare("
            SELECT u.id, u.name, u.email, u.plain_password, u.university, u.referral_code,
                   u.amb_active AS status, u.user_role,
                   u.amb_show_email, u.amb_show_phone,
                   DATE(u.created_at) AS created_on,
                   COUNT(r.id) AS total_referrals,
                   COALESCE(SUM(r.status = 'hire'), 0) AS hired
            FROM users u
            LEFT JOIN registrations r ON r.ref_code = u.referral_code
            WHERE u.user_role = ? OR (u.user_role = ? AND u.referral_code IS NOT NULL)
            GROUP BY u.id
            ORDER BY u.name ASC
        ");
        $stmt->bind_param('ii', $role, $internRole);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($data as &$row) {
            $row['referral_link'] = $row['referral_code'] ? ambassadorReferralLink($row['referral_code']) : '';
            $row['is_intern'] = (int)$row['user_role'] === ROLE_INTERN;
            if ($row['is_intern']) {
                // The intern's login is theirs; this page never shows or changes it.
                $row['plain_password'] = null;
            }
        }
        unset($row);
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    case 'create':
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $password = $_POST['password'] ?? '';
        $showEmail = !empty($_POST['show_email']) ? 1 : 0;
        $showPhone = !empty($_POST['show_phone']) ? 1 : 0;

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $university === '') {
            echo json_encode(['success' => false, 'message' => 'Name, a valid email and university are required']);
            exit;
        }
        if (emailTakenByOtherUser($conn, $email, 0)) {
            $existing = $conn->prepare("SELECT user_role FROM users WHERE email = ? LIMIT 1");
            $existing->bind_param('s', $email);
            $existing->execute();
            $existingRole = (int)($existing->get_result()->fetch_assoc()['user_role'] ?? 0);
            $existing->close();
            echo json_encode(['success' => false, 'message' => $existingRole === ROLE_INTERN
                ? 'This email belongs to an intern. Use "Make Intern an Ambassador" instead - they keep their intern account.'
                : 'That email is already registered']);
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
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, plain_password, user_role, status, tech_id, supervisor_id, commission_rate, university, referral_code, amb_show_email, amb_show_phone, amb_active)
                                VALUES (?, ?, ?, ?, ?, 1, 0, 0, 0, ?, ?, ?, ?, 1)");
        $stmt->bind_param('ssssissii', $name, $email, $hashed, $password, $role, $university, $code, $showEmail, $showPhone);
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

    // Pick list for "Make Intern an Ambassador": active interns not already one.
    case 'available_interns':
        $internRole = ROLE_INTERN;
        $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE user_role = ? AND status = 1 AND referral_code IS NULL ORDER BY name ASC");
        $stmt->bind_param('i', $internRole);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        echo json_encode(['success' => true, 'data' => $data]);
        break;

    // Gives an existing Intern a referral code on their own account. Their role,
    // login and internship are untouched; they get "My Referrals" in the sidebar.
    case 'make_intern_ambassador':
        $id = (int)($_POST['intern_id'] ?? 0);
        $university = trim($_POST['university'] ?? '');
        $showEmail = !empty($_POST['show_email']) ? 1 : 0;
        $showPhone = !empty($_POST['show_phone']) ? 1 : 0;

        if ($id <= 0 || $university === '') {
            echo json_encode(['success' => false, 'message' => 'Select an intern and a university']);
            exit;
        }

        $internRole = ROLE_INTERN;
        $stmt = $conn->prepare("SELECT name, email, referral_code FROM users WHERE id = ? AND user_role = ? AND status = 1 LIMIT 1");
        $stmt->bind_param('ii', $id, $internRole);
        $stmt->execute();
        $intern = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$intern) {
            echo json_encode(['success' => false, 'message' => 'Intern not found or inactive']);
            exit;
        }
        if (!empty($intern['referral_code'])) {
            echo json_encode(['success' => false, 'message' => 'This intern is already an ambassador']);
            exit;
        }

        try {
            $code = generateReferralCode($conn, $intern['name']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Could not generate a referral code, please try again']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET referral_code = ?, university = ?, amb_show_email = ?, amb_show_phone = ?, amb_active = 1
                                WHERE id = ? AND user_role = ? AND referral_code IS NULL");
        $stmt->bind_param('ssiiii', $code, $university, $showEmail, $showPhone, $id, $internRole);
        $ok = $stmt->execute() && $stmt->affected_rows === 1;
        $stmt->close();
        if (!$ok) {
            echo json_encode(['success' => false, 'message' => 'Failed to make this intern an ambassador']);
            exit;
        }

        logActivity('Update User', "Made Intern {$intern['name']} ({$intern['email']}) a Campus Ambassador, code $code");
        queueAmbassadorWelcomeEmail($intern['name'], $intern['email'], null, $university, $code);
        echo json_encode(['success' => true, 'message' => $intern['name'] . ' is now a Campus Ambassador. Their internship is unchanged; a welcome email with the referral link is on its way.']);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $university = trim($_POST['university'] ?? '');
        $password = $_POST['password'] ?? '';
        $showEmail = !empty($_POST['show_email']) ? 1 : 0;
        $showPhone = !empty($_POST['show_phone']) ? 1 : 0;

        // For an Intern-ambassador only the ambassador fields change; their name,
        // email and password belong to their intern account (edited from Users).
        if (isInternAmbassador($conn, $id)) {
            if ($university === '') {
                echo json_encode(['success' => false, 'message' => 'University is required']);
                exit;
            }
            $internRole = ROLE_INTERN;
            $stmt = $conn->prepare("UPDATE users SET university = ?, amb_show_email = ?, amb_show_phone = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('siiii', $university, $showEmail, $showPhone, $id, $internRole);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                logActivity('Update User', "Updated ambassador settings of Intern ID $id");
            }
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Updated successfully!' : 'Update failed']);
            exit;
        }

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
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, university = ?, amb_show_email = ?, amb_show_phone = ?, password = ?, plain_password = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('sssiissii', $name, $email, $university, $showEmail, $showPhone, $hashed, $password, $id, $role);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, university = ?, amb_show_email = ?, amb_show_phone = ? WHERE id = ? AND user_role = ?");
            $stmt->bind_param('sssiiii', $name, $email, $university, $showEmail, $showPhone, $id, $role);
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
    // the registration form stops accepting their code. For an Intern-ambassador
    // only the ambassador side (amb_active) changes - their intern login stays.
    case 'toggle_status':
        $id = (int)($_POST['id'] ?? 0);
        $status = ((int)($_POST['status'] ?? 0) === 1) ? 1 : 0;
        $role = ROLE_AMBASSADOR;
        $internRole = ROLE_INTERN;
        $stmt = $conn->prepare("UPDATE users SET amb_active = ?, status = IF(user_role = ?, ?, status)
                                WHERE id = ? AND (user_role = ? OR (user_role = ? AND referral_code IS NOT NULL))");
        $stmt->bind_param('iiiiii', $status, $role, $status, $id, $role, $internRole);
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
        $internRole = ROLE_INTERN;
        $stmt = $conn->prepare("SELECT name, email, plain_password, university, referral_code, amb_active AS status, user_role
                                FROM users WHERE id = ? AND user_role IN (?, ?) LIMIT 1");
        $stmt->bind_param('iii', $id, $role, $internRole);
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

        // An Intern keeps their own login, so no password goes out for them.
        $password = (int)$amb['user_role'] === ROLE_INTERN ? null : $amb['plain_password'];
        queueAmbassadorWelcomeEmail($amb['name'], $amb['email'], $password, (string)$amb['university'], $amb['referral_code']);
        logActivity('Resend Ambassador Email', "Welcome email resent to {$amb['name']} ({$amb['email']})");
        echo json_encode(['success' => true, 'message' => 'Welcome email is being sent to ' . $amb['email']]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function isInternAmbassador($conn, $id)
{
    $role = ROLE_INTERN;
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE id = ? AND user_role = ? AND referral_code IS NOT NULL LIMIT 1");
    $stmt->bind_param('ii', $id, $role);
    $stmt->execute();
    $found = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $found;
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
