<?php
// Campus Ambassadors (role 7) represent DawoodTech at their university. Each one
// gets a unique referral code; the public registration form (the separate React
// app, REGISTRATION_FORM_URL) is shared as <form url>?ref=<code>, and its Node
// backend saves the code on the new row in registrations.ref_code.
//
// An ambassador can only view registrations carrying their own code - the code
// is always read from their users row, never from the request. The columns are
// added by ensureInternshipTypeSchema() in include/internship_type_helper.php.
require_once __DIR__ . '/internship_type_helper.php';

// e.g. "AYESHA-7K2Q": first name (letters only, max 8) + 4 random characters,
// retried until unused. Ambiguous characters (0/O, 1/I) are left out so a code
// read aloud or typed by hand still works.
if (!function_exists('generateReferralCode')) {
    function generateReferralCode($conn, $name)
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', explode(' ', trim($name))[0] ?? ''));
        $prefix = substr($prefix !== '' ? $prefix : 'AMB', 0, 8);
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        $check = $conn->prepare("SELECT id FROM users WHERE referral_code = ? LIMIT 1");
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = $prefix . '-' . $suffix;
            $check->bind_param('s', $code);
            $check->execute();
            if ($check->get_result()->num_rows === 0) {
                $check->close();
                return $code;
            }
        }
        $check->close();
        throw new RuntimeException('Could not generate a unique referral code');
    }
}

if (!function_exists('ambassadorReferralLink')) {
    function ambassadorReferralLink($code)
    {
        $base = REGISTRATION_FORM_URL;
        return $base . (strpos($base, '?') === false ? '?' : '&') . 'ref=' . rawurlencode($code);
    }
}

// Welcome email with the ambassador's login and referral link, sent after the
// response so creating an ambassador doesn't wait on SMTP. Email only - no
// mobile number is stored for ambassadors. Needs include/notification_helper.php.
if (!function_exists('queueAmbassadorWelcomeEmail')) {
    function queueAmbassadorWelcomeEmail($name, $email, $password, $university, $code)
    {
        $loginUrl = rtrim(BASE_URL, '/') . '/login.php';
        $link = ambassadorReferralLink($code);
        $e = function ($value) {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        };

        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; border-radius: 10px;'>
            <h2 style='color: #2563eb; text-align: center;'>Welcome, Campus Ambassador! 🎓</h2>
            <p>Dear <strong>" . $e($name) . "</strong>,</p>
            <p>You are now a <strong>DawoodTech NextGen Campus Ambassador</strong>" . ($university !== '' ? " at <strong>" . $e($university) . "</strong>" : '') . ". Thank you for representing our mission and vision on your campus.</p>

            <h3 style='color: #0f172a; margin-bottom: 8px;'>Your referral link</h3>
            <p style='margin-top: 0;'>Share this link with students. Everyone who registers through it is counted as your referral.</p>
            <div style='background: #eef2ff; padding: 15px; border-radius: 8px; margin: 12px 0 20px; word-break: break-all;'>
                <p style='margin: 0 0 6px;'><a href='" . $e($link) . "' style='color: #2563eb; font-weight: bold;'>" . $e($link) . "</a></p>
                <p style='margin: 0; color: #475569;'>Referral code: <strong>" . $e($code) . "</strong></p>
            </div>

            <h3 style='color: #0f172a; margin-bottom: 8px;'>Your dashboard login</h3>
            <div style='background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 12px 0 20px;'>
                <p><strong>URL:</strong> <a href='" . $e($loginUrl) . "'>" . $e($loginUrl) . "</a></p>
                <p><strong>Email:</strong> " . $e($email) . "</p>
                <p style='margin-bottom: 0;'><strong>Password:</strong> <code style='background: #e5e7eb; padding: 2px 5px; border-radius: 3px;'>" . $e($password) . "</code></p>
            </div>
            <p>On your dashboard you can see every student who registered through your link, where they are in the internship process, and their assessment result.</p>
            <p>Best regards,<br><strong>HR Department</strong><br>DawoodTech NextGen</p>
        </div>";

        queueNotification([
            'email' => $email,
            'name' => $name,
            'subject' => 'Welcome to the DawoodTech NextGen Campus Ambassador Program',
            'html_content' => $html,
        ]);
    }
}

// "ay****@gmail.com" - enough for an ambassador to recognise their student
// without exposing full contact details.
if (!function_exists('maskEmail')) {
    function maskEmail($email)
    {
        $email = (string)$email;
        $at = strpos($email, '@');
        if ($at === false) {
            return $email === '' ? '' : substr($email, 0, 2) . '****';
        }
        return substr($email, 0, min(2, $at)) . '****' . substr($email, $at);
    }
}

// "+92300****567"
if (!function_exists('maskPhone')) {
    function maskPhone($phone)
    {
        $phone = (string)$phone;
        $len = strlen($phone);
        if ($len <= 7) {
            return str_repeat('*', $len);
        }
        return substr($phone, 0, $len - 7) . '****' . substr($phone, -3);
    }
}
