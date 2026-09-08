<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates Offer Letter PDF content
 * 
 * @param string $name Intern name
 * @param string $startDate Start date
 * @param string $endDate End date
 * @param string $techName Technology name
 * @param string|null $issueDate Issue date (optional)
 * @return string|null PDF content as string
 */
function generateOfferLetterHelper($name, $startDate, $endDate, $techName, $issueDate = null) {
    try {
        if (!$issueDate) {
            $issueDate = date('j F Y');
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        // Handle background image
        $bgImageRelative = __DIR__ . '/../assets/images/offerletter.png';
        if (file_exists($bgImageRelative)) {
            $imageData = base64_encode(file_get_contents($bgImageRelative));
            $bgImageUri = 'data:image/png;base64,' . $imageData;
        } else {
            // Fallback to URL if local file not found
            if (defined('BASE_URL')) {
                $bgImageUri = BASE_URL . "assets/images/offerletter.png";
            } else {
                $bgImageUri = "";
            }
        }

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
                    padding: 0;
                    width: 210mm; /* A4 width */
                    height: 297mm; /* A4 height */
                    position: relative;
                }
                .background-img {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: -1;
                }
                .content-box {
                    position: absolute;
                    top: 200px;
                    left: 40px;
                    right: 40px;
                    padding: 40px;
                    height: 720px;
                    z-index: 1;
                }
                .section {
                    margin-bottom: 10px;
                }
                .signature {
                    margin-top: 30px;
                    line-height: 1.5;
                }
            </style>
        </head>
        <body>
            <img src="' . $bgImageUri . '" class="background-img" />
            <div class="content-box">
                <div class="section" style="text-align:right;">
                    <strong>Date:</strong> ' . htmlspecialchars($issueDate) . '
                </div>
                <div class="section">
                    <strong>To:</strong><br>
                    ' . htmlspecialchars($name) . '<br>
                    <strong>Designation:</strong> Intern – ' . htmlspecialchars($techName) . '<br>
                    DawoodTech NextGen
                </div>
                <div class="section title">
                    <h3>Internship Offer – ' . htmlspecialchars($techName) . '</h3>
                </div>
                <div class="section">
                    <p>Dear ' . htmlspecialchars($name) . ',</p>
                    <p>We are pleased to offer you an internship opportunity from
                    <strong>' . htmlspecialchars($startDate) . '</strong> to <br> <strong>' . htmlspecialchars($endDate) . '</strong> at <strong>DawoodTech NextGen</strong> as a
                    <strong>' . htmlspecialchars($techName) . ' Intern</strong>.</p>
                    <p>This internship will provide you with the chance to enhance your skills, gain practical exposure, and contribute to real-world projects under professional guidance. We believe your dedication and efforts will add value to our team, and we look forward to your valuable contribution and growth during this program.</p>
                    <p>We are confident that this experience will be a stepping stone in your professional journey, equipping you with the knowledge and confidence to excel in your career.</p>
                </div>
                <div class="signature">
                    <strong>Sincerely,</strong><br>
                    Qamar Naveed<br>
                    Founder<br>
                    <strong>DawoodTech NextGen</strong><br>
                    <strong>Contact Information:</strong><br>
                    <strong>Phone: </strong>+92-311-7305346<br>
                    <strong>Email: </strong>info@dawoodtechnextgen.org<br>
                    <strong>Website: </strong>https://dawoodtechnextgen.org
                </div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("PDF Helper Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generates Certificate PDF content
 * 
 * @param string $name Intern name
 * @param string $startDate Start date
 * @param string $endDate End date
 * @param string $techName Technology name
 * @param string $issueDate Issue date
 * @param int $internshipType Internship type (0 for participation, 1 for completion)
 * @param string $verifyUrl Verification URL for QR code
 * @return string|null PDF content as string
 */
function generateCertificateHelper($name, $startDate, $endDate, $techName, $issueDate, $internshipType, $verifyUrl) {
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        // Get template background (always certificate.png per user request)
        $bg_filename = 'certificate.png';
        $bgImagePath = __DIR__ . '/../assets/images/' . $bg_filename;
        if (file_exists($bgImagePath)) {
            $imageData = base64_encode(file_get_contents($bgImagePath));
            $bgImageUri = 'data:image/png;base64,' . $imageData;
        } else {
            // Fallback to URL if local file not found
            if (defined('BASE_URL')) {
                $bgImageUri = BASE_URL . "assets/images/" . $bg_filename;
            } else {
                $bgImageUri = "";
            }
        }

        // Fetch QR Code image as base64 for embedding (more robust than remote URL in HTML)
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($verifyUrl);
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 3.0, // 3 seconds timeout
            ]
        ]);
        $qrCodeContent = @file_get_contents($qrCodeUrl, false, $ctx);
        if ($qrCodeContent !== false) {
            $qrCodeUri = 'data:image/png;base64,' . base64_encode($qrCodeContent);
        } else {
            $qrCodeUri = $qrCodeUrl;
        }

        // Load local font as base64 to prevent remote downloads & delays
        $fontPath = __DIR__ . '/../assets/fonts/static/Caveat-Bold.ttf';
        if (file_exists($fontPath)) {
            $fontData = base64_encode(file_get_contents($fontPath));
            $fontUri = 'data:font/truetype;charset=utf-8;base64,' . $fontData;
        } else {
            $fontUri = '';
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @font-face {
                    font-family: "Caveat";
                    font-style: normal;
                    font-weight: 700;
                    src: url("' . $fontUri . '") format("truetype");
                }
                @page { margin: 0; padding: 0; size: A4 landscape; }
                body {
                    margin: 0;
                    padding: 0;
                    width: 842pt;
                    height: 595pt;
                    font-family: Arial, sans-serif;
                    position: relative;
                    background-image: url("' . $bgImageUri . '");
                    background-size: 100% 100%;
                    background-repeat: no-repeat;
                }
                .name-text {
                    position: absolute;
                    top: 275pt;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-family: \'Caveat\', \'Georgia\', cursive, serif;
                    font-size: 38pt;
                    font-weight: bold;
                    color: #2c3e50;
                }
                .cert-text {
                    position: absolute;
                    top: 340pt;
                    left: 60pt;
                    right: 60pt;
                    text-align: center;
                    font-size: 13pt;
                    line-height: 1.5;
                    color: #2c3e50;
                }
                .underline-text {
                    font-weight: bold;
                    text-decoration: underline;
                }
                .bold-text {
                    font-weight: bold;
                }
                .issue-date {
                    position: absolute;
                    left: 296pt;
                    top: 550pt;
                    font-size: 13pt;
                    font-weight: bold;
                    color: #2c3e50;
                }
                .qr-code {
                    position: absolute;
                    left: 690pt;
                    top: 72pt;
                    text-align: center;
                }
                .qr-code img {
                    width: 45pt;
                    height: 45pt;
                    display: block;
                    margin: 0 auto;
                }
                .qr-text {
                    font-size: 7.5pt;
                    font-weight: bold;
                    color: #2c3e50;
                    margin-top: 4pt;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
            </style>
        </head>
        <body>
            <div class="name-text">
                ' . htmlspecialchars($name) . '
            </div>
            
            <div class="cert-text">
                This is to certify that <span class="underline-text">' . htmlspecialchars($name) . '</span>
                has successfully completed his/her internship at <span class="bold-text">DawoodTech NextGen</span>
                <br/>
                from <span class="underline-text">' . htmlspecialchars($startDate) . '</span> to <span class="underline-text">' . htmlspecialchars($endDate) . '</span>
                in <span class="underline-text">' . htmlspecialchars($techName) . '</span>.
                <br/>
                <span style="font-size: 11pt;">During this period, the intern showed dedication, professionalism, and a strong willingness to learn while contributing effectively to assigned projects.</span>
            </div>
            
            <div class="issue-date">
                ' . htmlspecialchars($issueDate) . '
            </div>
            
            <div class="qr-code">
                <img src="' . $qrCodeUri . '" />
                <div class="qr-text">Scan to Verify</div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("Certificate PDF Helper Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generates an Assessment Result Report PDF, attached to the result email sent
 * when a candidate's attempt finalizes (controller/candidate_assessment.php).
 *
 * @param string $name Candidate name
 * @param string $assessmentTitle Assessment title
 * @param string $technology Technology/field name
 * @param string $status 'pass' or 'fail'
 * @param float $percentage Score percentage
 * @param string $completedAt Datetime the attempt finished (Y-m-d H:i:s)
 * @param string|null $startedAt Datetime the attempt began (Y-m-d H:i:s)
 * @return string|null PDF content as string
 */
function generateAssessmentResultHelper($name, $assessmentTitle, $technology, $status, $percentage, $completedAt, $startedAt = null) {
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $pass = $status === 'pass';
        $statusLabel = $pass ? 'PASSED' : 'NOT CLEARED';
        $statusColor = $pass ? '#16a34a' : '#dc2626';

        $logoUri = '';
        $logoPath = __DIR__ . '/../assets/images/logo.png';
        if (file_exists($logoPath)) {
            $logoUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page { margin: 0; padding: 0; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 50px; color: #1e293b; }
                .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
                .header img { max-height: 55px; margin-bottom: 10px; }
                .header h1 { font-size: 20px; margin: 0; color: #1e293b; }
                .row { margin-bottom: 14px; font-size: 13px; }
                .label { color: #64748b; display: inline-block; width: 160px; }
                .value { font-weight: bold; }
                .result-box { text-align: center; background: #f3f4f6; border-radius: 10px; padding: 24px; margin: 30px 0; }
                .result-status { font-size: 30px; font-weight: bold; color: ' . $statusColor . '; letter-spacing: 1px; }
                .result-score { font-size: 14px; color: #64748b; margin-top: 6px; }
                .footer { margin-top: 50px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logoUri ? '<img src="' . $logoUri . '" />' : '') . '
                <h1>Assessment Result Report</h1>
            </div>
            <div class="row"><span class="label">Candidate Name:</span><span class="value">' . htmlspecialchars($name) . '</span></div>
            <div class="row"><span class="label">Assessment:</span><span class="value">' . htmlspecialchars($assessmentTitle) . '</span></div>
            <div class="row"><span class="label">Field / Technology:</span><span class="value">' . htmlspecialchars($technology) . '</span></div>
            ' . ($startedAt ? '<div class="row"><span class="label">Start Time:</span><span class="value">' . htmlspecialchars(date('j F Y, h:i A', strtotime($startedAt))) . '</span></div>' : '') . '
            <div class="row"><span class="label">End Time:</span><span class="value">' . htmlspecialchars(date('j F Y, h:i A', strtotime($completedAt))) . '</span></div>

            <div class="result-box">
                <div class="result-status">' . $statusLabel . '</div>
                <div class="result-score">Score: ' . htmlspecialchars((string)$percentage) . '%</div>
            </div>

            <p style="font-size: 13px;">Our HR department will contact you soon regarding the next steps.</p>

            <div class="footer">
                DawoodTech NextGen &middot; This is a system-generated report.
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("Assessment Result PDF Helper Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generates a detailed, question-by-question Assessment Result Report PDF
 * for internal/admin use - every question, the candidate's selected answer,
 * whether it was correct, and the correct answer where they got it wrong.
 *
 * @param string $name Candidate name
 * @param string $assessmentTitle Assessment title
 * @param string $technology Technology/field name
 * @param string $status 'pass' or 'fail'
 * @param float $percentage Score percentage
 * @param string $completedAt Datetime the attempt finished (Y-m-d H:i:s)
 * @param array $questions Each item: ['question_html', 'points', 'selected_option_id', 'is_correct', 'options' => [['id','option_text','is_correct'], ...]]
 * @param string|null $startedAt Datetime the attempt began (Y-m-d H:i:s)
 * @param array $captures Webcam proctoring snapshots taken during the attempt. Each item: ['data_uri', 'captured_at']
 * @return string|null PDF content as string
 */
function generateDetailedAssessmentReportHelper($name, $assessmentTitle, $technology, $status, $percentage, $completedAt, $questions, $startedAt = null, $captures = []) {
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $pass = $status === 'pass';
        $statusLabel = $pass ? 'PASSED' : 'NOT CLEARED';
        $statusColor = $pass ? '#16a34a' : '#dc2626';

        $logoUri = '';
        $logoPath = __DIR__ . '/../assets/images/logo.png';
        if (file_exists($logoPath)) {
            $logoUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $questionsHtml = '';
        $qNum = 0;
        foreach ($questions as $q) {
            $qNum++;
            $answered = $q['selected_option_id'] !== null;
            $isCorrect = $answered && (int)$q['is_correct'] === 1;
            $qStatusLabel = !$answered ? 'UNANSWERED' : ($isCorrect ? 'CORRECT' : 'WRONG');
            $qStatusColor = !$answered ? '#94a3b8' : ($isCorrect ? '#16a34a' : '#dc2626');

            $optionsHtml = '';
            foreach ($q['options'] as $opt) {
                $isSelected = $answered && (int)$opt['id'] === (int)$q['selected_option_id'];
                $isRight = !empty($opt['is_correct']);
                $marker = '';
                $style = 'padding:6px 10px; margin-bottom:4px; border-radius:5px; font-size:11px;';
                if ($isSelected && $isRight) {
                    $style .= 'background:#dcfce7; color:#166534; font-weight:bold;';
                    $marker = ' &#10003; Selected &middot; Correct';
                } elseif ($isSelected && !$isRight) {
                    $style .= 'background:#fee2e2; color:#991b1b; font-weight:bold;';
                    $marker = ' &#10007; Selected &middot; Incorrect';
                } elseif (!$isSelected && $isRight) {
                    $style .= 'background:#f0fdf4; color:#166534;';
                    $marker = ' &#10003; Correct Answer';
                } else {
                    $style .= 'color:#475569;';
                }
                $optionsHtml .= '<div style="' . $style . '">' . htmlspecialchars($opt['option_text']) . $marker . '</div>';
            }

            $questionsHtml .= '
                <div style="margin-bottom:16px; padding:14px; border:1px solid #e2e8f0; border-radius:8px; page-break-inside: avoid;">
                    <table style="width:100%; border-collapse: collapse; margin-bottom:8px;">
                        <tr>
                            <td style="font-weight:bold; font-size:12px;">Q' . $qNum . '. (' . (int)$q['points'] . ' pts)</td>
                            <td style="text-align:right; font-size:11px; font-weight:bold; color:' . $qStatusColor . ';">' . $qStatusLabel . '</td>
                        </tr>
                    </table>
                    <div style="font-size:12px; margin-bottom:10px;">' . $q['question_html'] . '</div>
                    ' . $optionsHtml . '
                </div>';
        }

        $capturesHtml = '';
        if (!empty($captures)) {
            $tiles = '';
            foreach ($captures as $cap) {
                $tiles .= '
                    <div style="display:inline-block; width:130px; margin:0 10px 14px 0; text-align:center; vertical-align:top;">
                        <img src="' . $cap['data_uri'] . '" style="width:130px; height:98px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;" />
                        <div style="font-size:9px; color:#64748b; margin-top:3px;">' . htmlspecialchars(date('h:i:s A', strtotime($cap['captured_at']))) . '</div>
                    </div>';
            }
            $capturesHtml = '
            <div class="section-title">Webcam Proctoring Snapshots (' . count($captures) . ')</div>
            <div>' . $tiles . '</div>';
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page { margin: 0; padding: 0; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 50px; color: #1e293b; }
                .header { text-align: center; border-bottom: 3px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
                .header img { max-height: 55px; margin-bottom: 10px; }
                .header h1 { font-size: 20px; margin: 0; color: #1e293b; }
                .row { margin-bottom: 14px; font-size: 13px; }
                .label { color: #64748b; display: inline-block; width: 160px; }
                .value { font-weight: bold; }
                .result-box { text-align: center; background: #f3f4f6; border-radius: 10px; padding: 24px; margin: 30px 0; }
                .result-status { font-size: 30px; font-weight: bold; color: ' . $statusColor . '; letter-spacing: 1px; }
                .result-score { font-size: 14px; color: #64748b; margin-top: 6px; }
                .section-title { font-size: 15px; font-weight: bold; margin: 30px 0 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 6px; }
                .footer { margin-top: 50px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
                pre.ql-syntax { background: #1e293b; color: #e2e8f0; padding: 10px; border-radius: 6px; font-size: 10px; white-space: pre-wrap; }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logoUri ? '<img src="' . $logoUri . '" />' : '') . '
                <h1>Assessment Result Report (Detailed)</h1>
            </div>
            <div class="row"><span class="label">Candidate Name:</span><span class="value">' . htmlspecialchars($name) . '</span></div>
            <div class="row"><span class="label">Assessment:</span><span class="value">' . htmlspecialchars($assessmentTitle) . '</span></div>
            <div class="row"><span class="label">Field / Technology:</span><span class="value">' . htmlspecialchars($technology) . '</span></div>
            ' . ($startedAt ? '<div class="row"><span class="label">Start Time:</span><span class="value">' . htmlspecialchars(date('j F Y, h:i A', strtotime($startedAt))) . '</span></div>' : '') . '
            <div class="row"><span class="label">End Time:</span><span class="value">' . htmlspecialchars(date('j F Y, h:i A', strtotime($completedAt))) . '</span></div>

            <div class="result-box">
                <div class="result-status">' . $statusLabel . '</div>
                <div class="result-score">Score: ' . htmlspecialchars((string)$percentage) . '%</div>
            </div>

            <div class="section-title">Question-by-Question Breakdown</div>
            ' . $questionsHtml . '
            ' . $capturesHtml . '

            <div class="footer">
                DawoodTech NextGen &middot; This is a system-generated internal report.
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("Detailed Assessment Report PDF Helper Error: " . $e->getMessage());
        return null;
    }
}
