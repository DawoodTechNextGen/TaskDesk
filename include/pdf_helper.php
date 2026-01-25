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
            $issueDate = date('d-M-Y');
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
                    background-image: url("' . $bgImageUri . '");
                    background-size: 100% 100%;
                    background-repeat: no-repeat;
                    background-position: center;
                    width: 210mm; /* A4 width */
                    height: 297mm; /* A4 height */
                    position: relative;
                }
                .content-box {
                    position: absolute;
                    top: 200px;
                    left: 40px;
                    right: 40px;
                    padding: 40px;
                    height: 720px;
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
