<?php
session_start();
include '../include/config.php';
error_reporting(0);
ini_set('display_errors', 0);
include '../include/connection.php';
require_once '../include/bootcamp_helper.php';
require_once '../include/notification_helper.php';

// Bootcamp module: rows are created by the public bootcamp sign-up form, so the
// only thing this controller writes is the enrollment's stage.
enforceModuleAccess(MODULE_BOOTCAMP, [
    'update_status',
]);
header('Content-Type: application/json');

// The stages a bootcamp enrollment moves through. Kept here so the listing filter
// and update_status validate against the same list.
$bootcampStatuses = ['new', 'contact', 'enrolled', 'rejected'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Only Admin (1), Manager (4) and read-only Collaborators (5) can access bootcamp enrollments
if (!isset($_SESSION['user_role']) || !in_array((int)$_SESSION['user_role'], [ROLE_ADMIN, ROLE_MANAGER, ROLE_COLLABORATOR], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access for role: ' . ($_SESSION['user_role'] ?? 'none')
    ]);
    exit;
}

// Status / province / city filters and the global search box, built in one place.
function bootcampFilters()
{
    global $bootcampStatuses;

    $status = trim($_GET['status'] ?? '');
    $province = trim($_GET['province'] ?? '');
    $city = trim($_GET['city'] ?? '');
    $searchValue = trim($_GET['search']['value'] ?? '');

    $where = [];
    $params = [];
    $types = '';

    if ($status !== '' && in_array($status, $bootcampStatuses, true)) {
        $where[] = "b.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if ($province !== '') {
        $where[] = "b.province = ?";
        $params[] = $province;
        $types .= 's';
    }

    if ($city !== '') {
        $where[] = "b.city = ?";
        $params[] = $city;
        $types .= 's';
    }

    if ($searchValue !== '') {
        $where[] = "(b.name LIKE ? OR b.email LIKE ? OR b.mbl_number LIKE ? OR b.cnic LIKE ?)";
        for ($i = 0; $i < 4; $i++) {
            $params[] = "%{$searchValue}%";
            $types .= 's';
        }
    }

    return [
        'clause' => $where ? ' WHERE ' . implode(' AND ', $where) : '',
        'params' => $params,
        'types' => $types
    ];
}

switch ($action) {

    // ===============================
    // BOOTCAMP ENROLLMENTS LIST
    // ===============================
    case 'get_bootcamp_registrations':

        // DataTables params
        $start  = (int)($_GET['start'] ?? 0);
        $length = (int)($_GET['length'] ?? 10);
        $orderColumnIndex = (int)($_GET['order'][0]['column'] ?? 1);
        $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

        // Index 0 is the expand-icon column (not orderable) and the last index is
        // Actions (not orderable) - both skipped by DataTables automatically, so
        // only the columns actually shown map here. Province, CNIC and Created At
        // moved into the expand row and are no longer sortable from the header.
        $columns = [
            1 => 'b.name',
            2 => 'b.email',
            3 => 'b.mbl_number',
            4 => 'b.city',
            5 => 'b.status'
        ];

        $orderBy = $columns[$orderColumnIndex] ?? 'b.name';

        $sqlBase = "FROM " . BOOTCAMP_TABLE . " b";

        $filters = bootcampFilters();
        $whereClause = $filters['clause'];
        $params = $filters['params'];
        $types = $filters['types'];

        // Filtered count
        $countSql = "SELECT COUNT(*) total $sqlBase $whereClause";
        $stmt = $conn->prepare($countSql);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $recordsFiltered = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        // Data query
        $dataSql = "
        SELECT b.id, b.name, b.email, b.mbl_number, b.province, b.city, b.cnic,
               b.status, b.email_status, DATE(b.created_at) created_at
        $sqlBase
        $whereClause
        ORDER BY $orderBy $orderDir
        LIMIT ?, ?
    ";

        $params[] = $start;
        $params[] = $length;
        $types .= 'ii';

        $stmt = $conn->prepare($dataSql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'draw' => (int)($_GET['draw'] ?? 0),
            'recordsTotal' => $recordsFiltered,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
        exit;

        // ===============================
        // PROVINCE / CITY FILTER OPTIONS
        // ===============================
    case 'get_filter_options':

        $provinces = [];
        $result = $conn->query("SELECT DISTINCT province FROM " . BOOTCAMP_TABLE . " WHERE province <> '' ORDER BY province ASC");
        while ($row = $result->fetch_assoc()) {
            $provinces[] = $row['province'];
        }

        // Cities narrow down to the selected province so the two dropdowns stay in sync.
        $province = trim($_GET['province'] ?? '');
        $cities = [];

        if ($province !== '') {
            $stmt = $conn->prepare("SELECT DISTINCT city FROM " . BOOTCAMP_TABLE . " WHERE city <> '' AND province = ? ORDER BY city ASC");
            $stmt->bind_param('s', $province);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $cities[] = $row['city'];
            }
            $stmt->close();
        } else {
            $result = $conn->query("SELECT DISTINCT city FROM " . BOOTCAMP_TABLE . " WHERE city <> '' ORDER BY city ASC");
            while ($row = $result->fetch_assoc()) {
                $cities[] = $row['city'];
            }
        }

        echo json_encode([
            'success' => true,
            'provinces' => $provinces,
            'cities' => $cities
        ]);
        exit;

        // ===============================
        // UPDATE ENROLLMENT STAGE
        // ===============================
    case 'update_status':
        $id = (int)($_POST['id'] ?? 0);
        $newStatus = $_POST['status'] ?? '';
        $sendEmail = isset($_POST['send_email']) && $_POST['send_email'] == '1';
        $emailMessage = $_POST['email_message'] ?? '';
        $contactVia = $_POST['contact_via'] ?? '';

        if ($id <= 0 || empty($newStatus)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }

        if (!in_array($newStatus, $bootcampStatuses, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        // Fetch the enrollment first, both for the audit log and so a bogus id
        // reports an error instead of a no-op "success".
        $name_stmt = $conn->prepare("SELECT name, email FROM " . BOOTCAMP_TABLE . " WHERE id = ?");
        $name_stmt->bind_param('i', $id);
        $name_stmt->execute();
        $enrollee = $name_stmt->get_result()->fetch_assoc();
        $name_stmt->close();

        if (!$enrollee) {
            echo json_encode(['success' => false, 'message' => 'Enrollment not found']);
            exit;
        }

        if ($sendEmail) {
            if (empty($enrollee['email'])) {
                echo json_encode(['success' => false, 'message' => 'Enrollee email not found']);
                exit;
            }

            if (empty($emailMessage)) {
                $emailMessage = "Thank you for enrolling in the DawoodTech NextGen Bootcamp.\n\nTo proceed with your enrollment, please reply on WhatsApp with the word \"Interested\".\n\nWe will then share the next steps and bootcamp details.\n\nBest Regards,\nDawoodTech NextGen Team";
            }

            $enrollee_name = $enrollee['name'];
            $enrollee_email = $enrollee['email'];

            // Same email design as the registrations contact email, worded for the bootcamp
            $current_year = date('Y');
            $formattedMessage = nl2br(htmlspecialchars($emailMessage));
            $waNumber = COMPANY_WHATSAPP;
            $waMessage = 'Interested in Bootcamp';
            $waLink = 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMessage);

            $htmlContent = "
            <style>
                @media screen and (max-width: 600px) {
                    .email-container {
                        padding: 20px 10px !important;
                    }
                    .email-card {
                        border-radius: 12px !important;
                    }
                    .email-header {
                        padding: 20px 20px !important;
                    }
                    .email-body {
                        padding: 30px 20px !important;
                    }
                    .email-logo {
                        max-height: 40px !important;
                    }
                    .email-footer {
                        padding: 24px 20px !important;
                    }
                }
            </style>
            <div class=\"email-container\" style=\"background-color: #F8FAFC; padding: 40px 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; min-height: 100%;\">
                <div class=\"email-card\" style=\"max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;\">
                    <!-- White Header with Logo and Blue bottom border -->
                    <div class=\"email-header\" style=\"background-color: #FFFFFF; padding: 28px 32px; text-align: center; border-bottom: 4px solid #2563EB;\">
                        <img class=\"email-logo\" src=\"cid:logo_cid\" alt=\"DawoodTech NextGen\" style=\"max-height: 52px; width: auto; max-width: 100%; height: auto; display: inline-block;\">
                    </div>
                    <!-- Body Content -->
                    <div class=\"email-body\" style=\"padding: 40px 32px; color: #0F172A; line-height: 1.6; font-size: 16px;\">
                        <div style=\"margin-bottom: 24px;\">
                            <span style=\"background-color: #E0E7FF; color: #2563EB; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block;\">NextGen Bootcamp</span>
                        </div>
                        <p style=\"margin-top: 0; font-weight: 700; font-size: 20px; color: #1E293B; letter-spacing: -0.3px;\">Dear " . htmlspecialchars($enrollee_name) . ",</p>
                        <div style=\"margin: 24px 0; color: #334155; border-left: 4px solid #2563EB; padding-left: 18px;\">
                            " . $formattedMessage . "
                        </div>

                        <!-- Interactive WhatsApp Button -->
                        <div style=\"text-align: center; margin: 36px 0 24px 0;\">
                            <p style=\"font-size: 14px; color: #64748B; margin-bottom: 12px; font-weight: 500;\">Are you interested? Let's connect directly on WhatsApp:</p>
                            <a href=\"" . $waLink . "\" target=\"_blank\" style=\"background-color: #25D366; color: #FFFFFF; padding: 12px 30px; border-radius: 12px; font-size: 15px; font-weight: 700; text-decoration: none; display: inline-block; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.25); text-align: center; vertical-align: middle;\">
                                <img src=\"cid:whatsapp_logo_cid\" alt=\"WhatsApp\" style=\"width: 18px; height: 18px; vertical-align: middle; margin-right: 8px; display: inline-block;\">
                                <span style=\"vertical-align: middle; display: inline-block;\">Message on WhatsApp</span>
                            </a>
                        </div>

                        <div style=\"margin-top: 32px; padding-top: 24px; border-top: 1px solid #E2E8F0; text-align: center;\">
                            <p style=\"margin: 0; font-size: 13px; color: #64748B;\">If you have any questions, please feel free to reach out to us on WhatsApp.</p>
                        </div>
                    </div>
                    <!-- Footer with Dark Slate matching the logo text -->
                    <div class=\"email-footer\" style=\"background-color: #1E293B; padding: 28px 24px; text-align: center; font-size: 12px; color: #94A3B8; border-top: 1px solid #E2E8F0;\">
                        <p style=\"margin: 0 0 8px 0; font-weight: 600; color: #FFFFFF; font-size: 13px;\">DawoodTech NextGen</p>
                        <p style=\"margin: 0; font-size: 11px;\">&copy; " . $current_year . " DawoodTech. All rights reserved.</p>
                    </div>
                </div>
            </div>";

            // Send notification email using PHPMailer with fallback
            $subject = 'Bootcamp Enrollment Update - DawoodTech NextGen';
            $emailSent = sendEmailPHPMailer($enrollee_email, $enrollee_name, $subject, $htmlContent, null, '', 'primary');
            if (!$emailSent) {
                $emailSent = sendEmailPHPMailer($enrollee_email, $enrollee_name, $subject, $htmlContent, null, '', 'gmail');
            }
        }

        $email_status = 0;
        if ($sendEmail) {
            $email_status = $emailSent ? 1 : 2;
        } elseif ($contactVia === 'whatsapp') {
            $email_status = 3;
        }

        $stmt = $conn->prepare("UPDATE " . BOOTCAMP_TABLE . " SET status = ?, email_status = ? WHERE id = ?");
        $stmt->bind_param('sii', $newStatus, $email_status, $id);

        if ($stmt->execute()) {
            logActivity('Update Bootcamp Status', "Updated bootcamp enrollment status for {$enrollee['name']} to: " . ucfirst($newStatus));

            if ($sendEmail) {
                $successMsg = $emailSent
                    ? 'Status updated to contact and email sent successfully'
                    : 'Status updated to contact, but email sending failed. Please check SMTP settings.';
                echo json_encode(['success' => true, 'message' => $successMsg]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $conn->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>
