<?php
session_start();
include '../include/connection.php';
include '../include/receipt_helper.php';

if (!isset($_SESSION['user_id']) || (int)$_SESSION['user_role'] !== 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

ensureReceiptSchema($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'list': {
        header('Content-Type: application/json');
        $result = $conn->query("
            SELECT r.id, r.receipt_no, r.amount, r.notes, r.issued_at,
                   u.name AS intern_name, u.email AS intern_email
            FROM receipts r
            JOIN users u ON r.intern_id = u.id
            ORDER BY r.id DESC
            LIMIT 200
        ");
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    case 'generate': {
        $intern_id = (int)($_POST['intern_id'] ?? 0);
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $notes = trim($_POST['notes'] ?? '');

        if ($intern_id <= 0 || $amount <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please select an intern and enter a valid fee amount.']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT u.name, u.email, t.name AS tech_name
            FROM users u
            LEFT JOIN technologies t ON u.tech_id = t.id
            WHERE u.id = ? AND u.user_role = 2
        ");
        $stmt->bind_param('i', $intern_id);
        $stmt->execute();
        $intern = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$intern) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Intern not found.']);
            exit;
        }

        $receipt_no = generateReceiptNumber($conn);
        $issued_by = (int)$_SESSION['user_id'];

        $insert = $conn->prepare("INSERT INTO receipts (receipt_no, intern_id, amount, notes, issued_by) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param('sidsi', $receipt_no, $intern_id, $amount, $notes, $issued_by);
        if (!$insert->execute()) {
            $insert->close();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to save receipt record.']);
            exit;
        }
        $insert->close();

        logActivity('Generate Receipt', "Generated fee receipt $receipt_no for {$intern['name']} - " . number_format($amount, 2) . ' PKR');

        $pdf = generateReceiptPdfHelper([
            'receipt_no' => $receipt_no,
            'intern_name' => $intern['name'],
            'intern_email' => $intern['email'],
            'tech_name' => $intern['tech_name'] ?: 'N/A',
            'amount' => $amount,
            'notes' => $notes,
            'issue_date' => date('j F Y'),
        ]);

        if (!$pdf) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Receipt was saved but PDF generation failed.']);
            exit;
        }

        $filename = 'Receipt_' . $receipt_no . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $intern['name']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    case 'download': {
        $id = (int)($_GET['id'] ?? 0);

        $stmt = $conn->prepare("
            SELECT r.receipt_no, r.amount, r.notes, r.issued_at,
                   u.name AS intern_name, u.email AS intern_email, t.name AS tech_name
            FROM receipts r
            JOIN users u ON r.intern_id = u.id
            LEFT JOIN technologies t ON u.tech_id = t.id
            WHERE r.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $receipt = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$receipt) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Receipt not found.']);
            exit;
        }

        $pdf = generateReceiptPdfHelper([
            'receipt_no' => $receipt['receipt_no'],
            'intern_name' => $receipt['intern_name'],
            'intern_email' => $receipt['intern_email'],
            'tech_name' => $receipt['tech_name'] ?: 'N/A',
            'amount' => $receipt['amount'],
            'notes' => $receipt['notes'],
            'issue_date' => date('j F Y', strtotime($receipt['issued_at'])),
        ]);

        if (!$pdf) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PDF generation failed.']);
            exit;
        }

        $filename = 'Receipt_' . $receipt['receipt_no'] . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
}
