<?php
/**
 * Shared constants/validation/upload helpers for the Hackathons admin module
 * (hackathons.php, hackathon_registrations.php, hackathon_leaderboard.php and
 * their controllers). The four `hackathon*` tables are owned by the public
 * Node.js/Express registration site (dawoodtechnextgen.com) and shared on the
 * same MySQL database - TaskDesk reads/writes them directly, so anything
 * saved here shows up on the public site immediately with no sync step.
 * Table structure must not change; only the validation rules below (mirrored
 * from that site's own validation) are enforced on this side too.
 */

define('HACKATHON_BANNER_DIR', 'uploads/hackathon_banners');

if (!function_exists('hackathonStatusLabels')) {
    function hackathonStatusLabels()
    {
        return [
            'draft'     => 'Draft',
            'upcoming'  => 'Upcoming',
            'open'      => 'Open',
            'closed'    => 'Closed',
            'completed' => 'Completed',
        ];
    }
}

if (!function_exists('hackathonModeLabels')) {
    function hackathonModeLabels()
    {
        return [
            'online' => 'Online',
            'onsite' => 'Onsite',
            'hybrid' => 'Hybrid',
        ];
    }
}

// lowercase letters/numbers, single hyphens between segments - matches the
// public site's own slug validation so a slug saved here is always a valid URL there.
if (!function_exists('isValidHackathonSlug')) {
    function isValidHackathonSlug($slug)
    {
        return is_string($slug) && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug) === 1;
    }
}

if (!function_exists('slugifyHackathonTitle')) {
    function slugifyHackathonTitle($title)
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');
    }
}

// +92 followed by 10 digits, same as the public registration form's WhatsApp field.
if (!function_exists('isValidHackathonMobile')) {
    function isValidHackathonMobile($mobile)
    {
        return is_string($mobile) && preg_match('/^\+92\d{10}$/', $mobile) === 1;
    }
}

if (!function_exists('isValidHackathonPersonName')) {
    function isValidHackathonPersonName($name)
    {
        return is_string($name) && preg_match('/^[A-Za-z\s]{2,50}$/', $name) === 1;
    }
}

/**
 * Validates and moves an uploaded banner image into uploads/hackathon_banners/,
 * returning the relative path to store in `hackathons`.`banner_url`.
 *
 * @return array{ok:bool, path:?string, message:?string}
 */
if (!function_exists('saveHackathonBanner')) {
    function saveHackathonBanner(array $file)
    {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'path' => null, 'message' => null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'path' => null, 'message' => 'Banner upload failed.'];
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = mime_content_type($file['tmp_name']);
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'path' => null, 'message' => 'Banner must be a JPG, PNG, WEBP or GIF image.'];
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return ['ok' => false, 'path' => null, 'message' => 'Banner image must be under 5MB.'];
        }

        $dir = __DIR__ . '/../' . HACKATHON_BANNER_DIR;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = 'banner_' . time() . '_' . uniqid() . '.' . $allowed[$mime];
        $fullPath = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            return ['ok' => false, 'path' => null, 'message' => 'Could not save the banner image.'];
        }

        return ['ok' => true, 'path' => HACKATHON_BANNER_DIR . '/' . $filename, 'message' => null];
    }
}

if (!function_exists('deleteHackathonBannerFile')) {
    function deleteHackathonBannerFile($relativePath)
    {
        if (empty($relativePath)) {
            return;
        }
        $fullPath = __DIR__ . '/../' . $relativePath;
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
