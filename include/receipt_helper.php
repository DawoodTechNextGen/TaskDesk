<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Creates the `receipts` table if it doesn't exist yet.
 */
function ensureReceiptSchema($conn) {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $conn->query("CREATE TABLE IF NOT EXISTS `receipts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `receipt_no` varchar(30) NOT NULL,
        `intern_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `notes` varchar(255) DEFAULT NULL,
        `issued_by` int(11) NOT NULL,
        `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_receipt_no` (`receipt_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Generates the next sequential receipt number, e.g. DTN-2026-0001.
 */
function generateReceiptNumber($conn) {
    $prefix = 'DTN-' . date('Y') . '-';
    $like = $prefix . '%';

    $stmt = $conn->prepare("SELECT receipt_no FROM receipts WHERE receipt_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $next = 1;
    if ($row) {
        $next = (int)substr($row['receipt_no'], strlen($prefix)) + 1;
    }

    return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
}

/**
 * Generates a fee receipt PDF.
 *
 * @param array $data receipt_no, intern_name, intern_email, tech_name, amount, notes, issue_date, issued_by_name
 * @return string|null PDF content as string
 */
function generateReceiptPdfHelper($data) {
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $logoPath = __DIR__ . '/../assets/images/logo.png';
        $logoUri = '';
        if (file_exists($logoPath)) {
            $logoUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $receiptNo = htmlspecialchars($data['receipt_no']);
        $internName = htmlspecialchars($data['intern_name']);
        $internEmail = htmlspecialchars($data['intern_email']);
        $techName = htmlspecialchars($data['tech_name']);
        $issueDate = htmlspecialchars($data['issue_date']);
        $notes = htmlspecialchars($data['notes'] ?? '');
        $amountFormatted = number_format((float)$data['amount'], 2);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page { margin: 0; padding: 0; }
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 8px;
                    color: #1f2937;
                    background: #ffffff;
                }
                .slip {
                    width: 380px;
                    background: #ffffff;
                    border: 1px solid #e5e7eb;
                    border-radius: 14px;
                    padding: 28px 28px 20px 28px;
                }
                .slip-logo { text-align: center; margin-bottom: 14px; }
                .slip-logo img { width: 165px; height: 36px; }

                .status { text-align: center; margin-bottom: 4px; }
                .status .check {
                    position: relative;
                    display: inline-block;
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    background: #16a34a;
                }
                .status .check .mark {
                    position: absolute;
                    top: 0; left: 0; right: 0; bottom: 3px;
                    margin: auto;
                    width: 16px;
                    height: 9px;
                    border-left: 3px solid #ffffff;
                    border-bottom: 3px solid #ffffff;
                    transform: rotate(-45deg);
                }
                .status .label {
                    margin-top: 10px;
                    font-size: 11pt;
                    font-weight: bold;
                    color: #16a34a;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                .amount-hero { text-align: center; margin: 18px 0 20px 0; }
                .amount-hero .k { font-size: 8.5pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
                .amount-hero .v { font-size: 25pt; font-weight: bold; color: #111827; margin-top: 5px; letter-spacing: 0.3px; }

                .dashed { border-top: 1.5px dashed #d1d5db; margin: 18px 0; }

                table.details { width: 100%; border-collapse: collapse; }
                table.details td { padding: 9px 0; font-size: 9.5pt; vertical-align: middle; line-height: 1.3; border-bottom: 1px solid #f1f2f4; }
                table.details td.k { color: #6b7280; width: 40%; font-weight: normal; }
                table.details td.v { color: #111827; font-weight: bold; text-align: right; }

                .terms { margin-top: 20px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; }
                .terms .title { font-size: 8.5pt; font-weight: bold; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
                .terms .body { font-size: 8.5pt; color: #7f1d1d; line-height: 1.5; }

                .footer { margin-top: 20px; text-align: center; }
                .footer .note1 { font-size: 8pt; color: #9ca3af; }
                .footer .note2 { font-size: 8pt; color: #9ca3af; margin-top: 3px; }
            </style>
        </head>
        <body>
            <div class="slip">
                <div class="slip-logo">' . ($logoUri ? '<img src="' . $logoUri . '" />' : '<strong>DawoodTech NextGen</strong>') . '</div>

                <div class="status">
                    <div class="check"><span class="mark"></span></div>
                    <div class="label">Payment Received</div>
                </div>

                <div class="amount-hero">
                    <div class="k">Amount Paid</div>
                    <div class="v">PKR ' . $amountFormatted . '</div>
                </div>

                <div class="dashed"></div>

                <table class="details">
                    <tr><td class="k">Receipt No.</td><td class="v">' . $receiptNo . '</td></tr>
                    <tr><td class="k">Date &amp; Time</td><td class="v">' . $issueDate . '</td></tr>
                    <tr><td class="k">Paid By</td><td class="v">' . $internName . '</td></tr>
                    <tr><td class="k">Email</td><td class="v">' . $internEmail . '</td></tr>
                    <tr><td class="k">Track</td><td class="v">' . $techName . '</td></tr>
                    ' . ($notes ? '<tr><td class="k">Payment For</td><td class="v">' . $notes . '</td></tr>' : '') . '
                    <tr><td class="k">Received By</td><td class="v">DawoodTech NextGen</td></tr>
                </table>

                <div class="terms">
                    <div class="title">Please Note</div>
                    <div class="body">If the intern/participant voluntarily leaves this internship or program before completion, the fee paid is strictly non-refundable.</div>
                </div>

                <div class="footer">
                    <div class="note1">This is a system-generated receipt and does not require a signature.</div>
                    <div class="note2">Generated by TaskDesk &mdash; DawoodTech NextGen</div>
                </div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html, 'UTF-8');
        $pageHeightPt = $notes ? 565 : 530;
        $dompdf->setPaper([0, 0, 340, $pageHeightPt]);
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("Receipt PDF Helper Error: " . $e->getMessage());
        return null;
    }
}
