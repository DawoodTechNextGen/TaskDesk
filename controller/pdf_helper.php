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

        // Background Image Path
        $bgImagePath = __DIR__ . '/../assets/images/offerletter.png';
        
        // Use base64 if local file access issues occur in some environments, 
        // but local path is generally better for Dompdf
        $bgImage = $bgImagePath;
        if (!file_exists($bgImagePath)) {
            // Fallback to BASE_URL if local path fails (less ideal for PDF rendering)
            if (defined('BASE_URL')) {
                $bgImage = BASE_URL . 'assets/images/offerletter.png';
            }
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 0; padding: 0; }
                body {
                    margin: 0; padding: 0;
                    width: 595px; height: 842px;
                    font-family: "DejaVu Sans", Arial, sans-serif;
                    background-image: url("' . $bgImage . '");
                    background-size: 100% 100%;
                    background-repeat: no-repeat;
                    background-position: center;
                }
                .content-box {
                    margin: 200px 40px 40px 40px;
                    padding: 40px 40px;
                    height: 720px;
                    background: transparent;
                    font-size: 16px;
                    line-height: 1.5;
                }
                .section { margin-bottom: 20px; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .title {
                    font-size: 18pt; font-weight: bold;
                    margin: 30px 0; text-decoration: underline;
                }
                .body-text { text-align: justify; }
                .signature { margin-top: 40px; line-height: 1.6; }
            </style>
        </head>
        <body>
            <div class="content-box">
                <div class="section text-right">
                    <strong>Date:</strong> ' . htmlspecialchars($issueDate) . '
                </div>
                
                <div class="section">
                    <strong>To:</strong><br>
                    ' . htmlspecialchars($name) . '<br>
                    <strong>Designation:</strong> Intern – ' . htmlspecialchars($techName) . '<br>
                    DawoodTech NextGen
                </div>
                
                <div class="section text-center title">
                    Internship Offer – ' . htmlspecialchars($techName) . '
                </div>
                
                <div class="section body-text">
                    <p>Dear ' . htmlspecialchars($name) . ',</p>
                    
                    <p>We are pleased to offer you an internship opportunity from 
                    <strong>' . htmlspecialchars($startDate) . '</strong> to <strong>' . htmlspecialchars($endDate) . '</strong> at <strong>DawoodTech NextGen</strong> as a 
                    <strong>' . htmlspecialchars($techName) . ' Intern</strong>.</p>
                    
                    <p>This internship will provide you with the chance to enhance your skills, gain practical exposure, and contribute to real-world projects under professional guidance. We believe your dedication and efforts will add value to our team, and we look forward to your valuable contribution and growth during this program.</p>
                    
                    <p>We are confident that this experience will be a stepping stone in your professional journey, equipping you with the knowledge and confidence to excel in your career.</p>
                    
                    <p>We value our professional relationship and look forward to your continued support. Should you have any questions or require further details, please do not hesitate to contact us.</p>
                </div>
                
                <div class="signature">
                    <strong>Sincerely,</strong><br>
                    Qamar Naveed<br>
                    Founder<br>
                    <strong>DawoodTech NextGen</strong><br><br>
                    
                    <strong>Contact Information:</strong><br>
                    <strong>Phone: </strong>+92-311-7305346<br>
                    <strong>Email: </strong>info@dawoodtechnextgen.org<br>
                    <strong>Website: </strong>https://dawoodtechnextgen.org
                </div>
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    } catch (Exception $e) {
        error_log("PDF Helper Error: " . $e->getMessage());
        return null;
    }
}
