<?php
// Read-only data for a Campus Ambassador's own dashboard (ambassador_dashboard.php).
// Only lists registrations carrying the logged-in ambassador's referral code,
// which is always read from their users row - never taken from the request.
// Assessment data is limited to the result summary; questions, answers and
// proctoring snapshots are never exposed here.
session_start();
include '../include/connection.php';
require_once '../include/ambassador_helper.php';
header('Content-Type: application/json');

if (!isAmbassadorUser()) {
    denyJson('Unauthorized');
}
session_write_close();

ensureInternshipTypeSchema($conn);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'my_referrals':
        $userId = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT referral_code, amb_show_email, amb_show_phone FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $me = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        $code = $me['referral_code'] ?? '';
        // Contact details stay masked unless the Admin allowed this ambassador to see them.
        $showEmail = !empty($me['amb_show_email']);
        $showPhone = !empty($me['amb_show_phone']);

        if ($code === '' || $code === null) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT r.id, r.name, r.email, r.mbl_number, r.city, r.university, r.status, r.internship_type,
                   DATE(r.created_at) AS applied_on, t.name AS technology,
                   ca.status AS assessment_status, ca.score, ca.total_marks, ca.percentage,
                   ca.completed_at AS assessment_completed_at
            FROM registrations r
            LEFT JOIN technologies t ON t.id = r.technology_id
            LEFT JOIN candidate_assessments ca ON ca.id = (
                SELECT ca2.id FROM candidate_assessments ca2
                WHERE ca2.registration_id = r.id ORDER BY ca2.id DESC LIMIT 1
            )
            WHERE r.ref_code = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'email' => $showEmail ? $row['email'] : maskEmail($row['email']),
                'phone' => $showPhone ? $row['mbl_number'] : maskPhone($row['mbl_number']),
                'city' => $row['city'],
                'university' => $row['university'],
                'technology' => $row['technology'],
                'applied_on' => $row['applied_on'],
                'status' => $row['status'],
                'internship_type' => internshipTypeLabel($row['internship_type']),
                'assessment_status' => $row['assessment_status'],
                'score' => $row['score'],
                'total_marks' => $row['total_marks'],
                'percentage' => $row['percentage'],
                'assessment_completed_at' => $row['assessment_completed_at'],
            ];
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $data]);
        break;

    default:
        denyJson('Invalid action');
}
