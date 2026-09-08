<?php
/**
 * Webcam proctoring captures taken periodically while a candidate is
 * mid-assessment (my_assessment.php / controller/candidate_assessment.php).
 * Images are stored on disk under /uploads/assessment_captures/{registration_id}/
 * with the file path recorded in `candidate_assessment_captures`, keyed by
 * registration_id so a reject/hire cleanup can find and delete them all.
 */

if (!function_exists('assessmentCaptureDir')) {
    function assessmentCaptureDir($registrationId)
    {
        $dir = __DIR__ . '/../uploads/assessment_captures/' . (int)$registrationId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
}

/**
 * Decodes a data-URI/base64 JPEG snapshot and stores it, recording the file
 * path against both the candidate_assessment and the registration.
 *
 * @return bool Whether the capture was saved successfully.
 */
if (!function_exists('saveAssessmentCapture')) {
    function saveAssessmentCapture($conn, $candidateAssessmentId, $registrationId, $base64Image)
    {
        if (strpos($base64Image, 'base64,') !== false) {
            $base64Image = substr($base64Image, strpos($base64Image, 'base64,') + 7);
        }
        $binary = base64_decode($base64Image, true);
        if ($binary === false || strlen($binary) < 100) {
            return false;
        }

        $dir = assessmentCaptureDir($registrationId);
        $filename = 'capture_' . time() . '_' . uniqid() . '.jpg';
        $fullPath = $dir . '/' . $filename;

        if (!file_put_contents($fullPath, $binary)) {
            return false;
        }

        $relativePath = 'uploads/assessment_captures/' . (int)$registrationId . '/' . $filename;

        $stmt = $conn->prepare("INSERT INTO candidate_assessment_captures (candidate_assessment_id, registration_id, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param('iis', $candidateAssessmentId, $registrationId, $relativePath);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

/**
 * Every capture for one candidate_assessment attempt, base64-encoded and
 * ready to embed as <img> tags in a PDF report.
 *
 * @return array Each item: ['data_uri', 'captured_at']
 */
if (!function_exists('getAssessmentCapturesForReport')) {
    function getAssessmentCapturesForReport($conn, $candidateAssessmentId)
    {
        $stmt = $conn->prepare("SELECT file_path, captured_at FROM candidate_assessment_captures WHERE candidate_assessment_id = ? ORDER BY captured_at ASC");
        $stmt->bind_param('i', $candidateAssessmentId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $captures = [];
        foreach ($rows as $row) {
            $fullPath = __DIR__ . '/../' . $row['file_path'];
            if (!file_exists($fullPath)) {
                continue;
            }
            $captures[] = [
                'data_uri' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($fullPath)),
                'captured_at' => $row['captured_at']
            ];
        }
        return $captures;
    }
}

/**
 * Deletes every captured image (files + DB rows) for the given registrations.
 * Called once a candidate is rejected or hired - proctoring photos have no
 * further purpose past that point. Safe to call with zero matching rows.
 *
 * @param array $registrationIds One or many registration ids (bulk-safe).
 */
if (!function_exists('deleteAssessmentCapturesForRegistrations')) {
    function deleteAssessmentCapturesForRegistrations($conn, array $registrationIds)
    {
        $registrationIds = array_values(array_unique(array_map('intval', array_filter($registrationIds))));
        if (empty($registrationIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($registrationIds), '?'));
        $types = str_repeat('i', count($registrationIds));

        $stmt = $conn->prepare("SELECT DISTINCT file_path, registration_id FROM candidate_assessment_captures WHERE registration_id IN ($placeholders)");
        $stmt->bind_param($types, ...$registrationIds);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as $row) {
            $fullPath = __DIR__ . '/../' . $row['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Best-effort cleanup of the now-empty per-registration folders.
        foreach ($registrationIds as $regId) {
            $dir = __DIR__ . '/../uploads/assessment_captures/' . $regId;
            if (is_dir($dir) && count(scandir($dir)) === 2) {
                @rmdir($dir);
            }
        }

        $delStmt = $conn->prepare("DELETE FROM candidate_assessment_captures WHERE registration_id IN ($placeholders)");
        $delStmt->bind_param($types, ...$registrationIds);
        $delStmt->execute();
        $delStmt->close();
    }
}
