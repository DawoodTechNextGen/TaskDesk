<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location:' . BASE_URL . 'login');
    exit;
}

// Require DOMPDF
require_once 'vendor/autoload.php'; // Adjust path as needed
use Dompdf\Dompdf;
use Dompdf\Options;

$page_title = 'Generate Offer Letter';
include_once "./include/headerLinks.php";

// Get user data from session
$user_id = $_SESSION['user_id'];

// Fetch user data
include_once './include/connection.php';
$user_query = $conn->prepare("SELECT name, tech_id, created_at FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();

    // Get technology name
    $tech_query = $conn->prepare("SELECT name FROM technologies WHERE id = ?");
    $tech_query->bind_param("i", $user_data['tech_id']);
    $tech_query->execute();
    $tech_result = $tech_query->get_result();
    $tech_name = $tech_result->num_rows > 0 ? $tech_result->fetch_assoc()['name'] : 'Technology';
    
    // Use session data if exists, otherwise use defaults
    if (isset($_SESSION['form_data'])) {
        $name = $_SESSION['form_data']['name'];
        $tech_name = $_SESSION['form_data']['tech_name'];
        $start_date = $_SESSION['form_data']['start_date'];
        $end_date = $_SESSION['form_data']['end_date'];
        $issue_date = $_SESSION['form_data']['issue_date'];
    } else {
        $name = $user_data['name'];
        $start_date = date('d-M-Y', strtotime($user_data['created_at']));
        $end_date = date('d-M-Y', strtotime($user_data['created_at'] . ' + 3 months'));
        $issue_date = date('d-M-Y');
    }
} else {
    header('location: ' . BASE_URL . 'index.php');
    exit();
}

// Function to generate PDF content with fixed background
function generateOfferLetterPDF($name, $startDate, $endDate, $tech_name, $issueDate)
{
    try {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $options->set('isPhpEnabled', true);
        $dompdf = new Dompdf($options);

        // Get absolute URL for background image
        $baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $bgImage = rtrim($baseUrl, '/') . '/assets/images/offerletter.png';
        
        // Fallback if image doesn't exist
        $imageCheck = @getimagesize($bgImage);
        if (!$imageCheck) {
            $bgImage = 'data:image/svg+xml;base64,' . base64_encode('
                <svg xmlns="http://www.w3.org/2000/svg" width="595" height="842">
                    <rect width="100%" height="100%" fill="white"/>
                    <rect x="10" y="10" width="575" height="822" fill="none" stroke="#3498db" stroke-width="2"/>
                    <text x="50%" y="50%" font-family="Arial" font-size="24" text-anchor="middle" fill="#3498db">
                        DawoodTech NextGen Offer Letter
                    </text>
                </svg>
            ');
        }

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Offer Letter - ' . htmlspecialchars($name) . '</title>
            <style>
                @page {
                    margin: 0;
                    padding: 0;
                }
                body {
                    margin: 0;
                    padding: 0;
                    width: 595px;
                    height: 842px;
                    position: relative;
                    font-family: "DejaVu Sans", Arial, sans-serif;
                    font-size: 12pt;
                    line-height: 1.5;
                }
                .background-image {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 595px;
                    height: 842px;
                    background-image: url("' . $bgImage . '");
                    background-size: cover;
                    background-repeat: no-repeat;
                    background-position: top center;
                    z-index: 1;
                    opacity: 1;
                }
                .content-wrapper {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 595px;
                    height: 842px;
                    z-index: 2;
                    padding: 120px 50px 50px 50px;
                    box-sizing: border-box;
                }
                .date-section {
                    text-align: right;
                    margin-bottom: 40px;
                }
                .to-section {
                    margin-bottom: 30px;
                }
                .title {
                    font-size: 20pt;
                    font-weight: bold;
                    text-align: center;
                    margin: 40px 0;
                    text-decoration: underline;
                }
                .body-content {
                    margin-bottom: 20px;
                    text-align: justify;
                }
                .signature-section {
                    margin-top: 60px;
                    line-height: 1.8;
                }
                strong {
                    color: #000000;
                }
                p {
                    margin: 0 0 15px 0;
                }
            </style>
        </head>
        <body>
            <div class="background-image"></div>
            <div class="content-wrapper">
                <div class="date-section">
                    <strong>Date:</strong> ' . htmlspecialchars($issueDate) . '
                </div>
                
                <div class="to-section">
                    <strong>To:</strong><br>
                    ' . htmlspecialchars($name) . '<br>
                    <strong>Designation:</strong> Intern – ' . htmlspecialchars($tech_name) . '<br>
                    DawoodTech NextGen
                </div>
                
                <div class="title">
                    Internship Offer – ' . htmlspecialchars($tech_name) . '
                </div>
                
                <div class="body-content">
                    <p>Dear ' . htmlspecialchars($name) . ',</p>
                    
                    <p>We are pleased to offer you an internship opportunity from 
                    <strong>' . $startDate . '</strong> to <strong>' . $endDate . '</strong> at <strong>DawoodTech NextGen</strong> as a 
                    <strong>' . htmlspecialchars($tech_name) . ' Intern</strong>.</p>
                    
                    <p>This internship will provide you with the chance to enhance your skills, gain practical exposure, and contribute to real-world projects under professional guidance. We believe your dedication and efforts will add value to our team, and we look forward to your valuable contribution and growth during this program.</p>
                    
                    <p>We are confident that this experience will be a stepping stone in your professional journey, equipping you with the knowledge and confidence to excel in your career.</p>
                    
                    <p>We value our professional relationship and look forward to your continued support. Should you have any questions or require further details, please do not hesitate to contact us.</p>
                </div>
                
                <div class="signature-section">
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
        error_log("PDF generation error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return null;
    }
}

// Handle PDF download request
if (isset($_GET['download']) && $_GET['download'] == 'true') {
    $pdfContent = generateOfferLetterPDF($name, $start_date, $end_date, $tech_name, $issue_date);
    
    if ($pdfContent) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="Offer_Letter_' . preg_replace('/[^a-zA-Z0-9]/', '_', $name) . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        echo $pdfContent;
        exit;
    } else {
        $_SESSION['error'] = "Failed to generate PDF. Please check server logs for details.";
        header('Location: ' . BASE_URL . 'generate_Offerletter.php');
        exit;
    }
}

// Handle form submission for editing fields
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save form data in session
    $_SESSION['form_data'] = [
        'name' => $_POST['name'],
        'tech_name' => $_POST['tech_name'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'issue_date' => $_POST['issue_date']
    ];
    
    // Update variables for display
    $name = $_POST['name'];
    $tech_name = $_POST['tech_name'];
    $start_date = date('d-M-Y', strtotime($_POST['start_date']));
    $end_date = date('d-M-Y', strtotime($_POST['end_date']));
    $issue_date = date('d-M-Y', strtotime($_POST['issue_date']));
    
    $_SESSION['success'] = "Fields updated successfully!";
    header('Location: ' . BASE_URL . 'generate_Offerletter.php');
    exit;
}
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast success bg-green-500 text-white p-4 rounded-lg shadow-lg">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast error bg-red-500 text-white p-4 rounded-lg shadow-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Modern Sidebar -->
        <?php include_once "./include/sideBar.php"; ?>
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            <?php include_once "./include/header.php" ?>
            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">

                <style>
                    :root {
                        --primary: #3498db;
                        --primary-dark: #2980b9;
                        --primary-light: #5dade2;
                        --secondary: #2c3e50;
                        --light: #f8f9fa;
                        --dark: #2c3e50;
                        --success: #2ecc71;
                        --error: #e74c3c;
                        --gray: #7f8c8d;
                        --border-radius: 12px;
                        --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
                        --transition: all 0.3s ease;
                    }

                    .offer-letter-container {
                        max-width: 1200px;
                        margin: 0 auto;
                    }

                    header {
                        text-align: center;
                        margin-bottom: 30px;
                    }

                    h1 {
                        font-size: 2.5rem;
                        font-weight: 700;
                        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
                        -webkit-background-clip: text;
                        background-clip: text;
                        color: transparent;
                        margin-bottom: 8px;
                    }

                    .subtitle {
                        font-size: 1.1rem;
                        color: var(--gray);
                        max-width: 600px;
                        margin: 0 auto;
                    }

                    .content {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 30px;
                        justify-content: center;
                    }

                    .preview-section {
                        flex: 1;
                        min-width: 300px;
                        max-width: 800px;
                        background: white;
                        border-radius: var(--border-radius);
                        box-shadow: var(--shadow);
                        overflow: hidden;
                        transition: var(--transition);
                    }

                    .preview-section:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                    }

                    .preview-header {
                        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                        color: white;
                        padding: 18px 25px;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                    }

                    .preview-header i {
                        font-size: 1.4rem;
                    }

                    .preview-header h2 {
                        font-size: 1.3rem;
                        font-weight: 600;
                    }

                    .edit-toggle-btn {
                        background: rgba(255, 255, 255, 0.2);
                        border: 1px solid rgba(255, 255, 255, 0.3);
                        color: white;
                        padding: 8px 16px;
                        border-radius: 6px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        transition: var(--transition);
                        font-size: 0.9rem;
                    }

                    .edit-toggle-btn:hover {
                        background: rgba(255, 255, 255, 0.3);
                    }

                    .preview-container {
                        padding: 25px;
                        background-color: #f8f9fa;
                        border-bottom: 1px solid #e9ecef;
                        min-height: 600px;
                        display: flex;
                        justify-content: center;
                        align-items: flex-start;
                    }

                    .offer-preview {
                        width: 595px;
                        height: 842px;
                        position: relative;
                        font-family: Arial, sans-serif;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                        border-radius: 4px;
                        overflow: hidden;
                    }

                    .background-preview {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-image: url('<?php echo BASE_URL; ?>assets/images/offerletter.png');
                        background-size: cover;
                        background-repeat: no-repeat;
                        background-position: top center;
                        z-index: 1;
                        opacity: 1;
                    }

                    .content-preview {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 2;
                        padding: 120px 50px 50px 50px;
                        box-sizing: border-box;
                        font-size: 12pt;
                        line-height: 1.5;
                    }

                    .offer-date {
                        text-align: right;
                        margin-bottom: 40px;
                    }

                    .offer-to {
                        margin-bottom: 30px;
                    }

                    .offer-title {
                        font-size: 20pt;
                        font-weight: bold;
                        text-align: center;
                        margin: 40px 0;
                        text-decoration: underline;
                    }

                    .offer-body {
                        margin-bottom: 30px;
                        text-align: justify;
                    }

                    .offer-signature {
                        margin-top: 60px;
                        line-height: 1.8;
                    }

                    .controls {
                        padding: 25px;
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
                    }

                    .edit-form-container {
                        padding: 25px;
                        background-color: #f8f9fa;
                        border-bottom: 1px solid #e9ecef;
                        display: none;
                    }

                    .edit-form {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 20px;
                    }

                    .form-group {
                        display: flex;
                        flex-direction: column;
                    }

                    .form-group.full-width {
                        grid-column: span 2;
                    }

                    .form-label {
                        font-weight: 600;
                        margin-bottom: 8px;
                        color: var(--dark);
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .form-label i {
                        color: var(--primary);
                        font-size: 0.9rem;
                    }

                    .form-input, .form-select {
                        padding: 12px 15px;
                        border: 2px solid #e1e5e9;
                        border-radius: 8px;
                        font-size: 16px;
                        transition: var(--transition);
                        background: white;
                    }

                    .form-input:focus, .form-select:focus {
                        outline: none;
                        border-color: var(--primary);
                        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
                    }

                    .form-actions {
                        grid-column: span 2;
                        display: flex;
                        gap: 15px;
                        justify-content: flex-end;
                        margin-top: 10px;
                    }

                    .btn {
                        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                        border: none;
                        color: white;
                        padding: 14px 25px;
                        font-size: 16px;
                        font-weight: 600;
                        border-radius: 8px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        transition: var(--transition);
                        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
                        text-decoration: none;
                        text-align: center;
                    }

                    .btn:hover {
                        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
                        transform: translateY(-2px);
                        box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
                        color: #ffff;
                    }

                    .btn:active {
                        transform: translateY(0);
                    }

                    .btn-secondary {
                        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
                        box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
                    }

                    .btn-secondary:hover {
                        background: linear-gradient(135deg, #7f8c8d 0%, #2c3e50 100%);
                        box-shadow: 0 6px 15px rgba(149, 165, 166, 0.4);
                    }

                    .btn-success {
                        background: linear-gradient(135deg, var(--success) 0%, #27ae60 100%);
                        box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
                    }

                    .btn-success:hover {
                        background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
                        box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
                    }

                    .btn i {
                        font-size: 1.1rem;
                    }

                    .info-card {
                        background: white;
                        border-radius: var(--border-radius);
                        box-shadow: var(--shadow);
                        padding: 25px;
                        flex: 1;
                        min-width: 300px;
                        max-width: 350px;
                        transition: var(--transition);
                        height: fit-content;
                    }

                    .info-card:hover {
                        transform: translateY(-5px);
                        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                    }

                    .info-header {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 20px;
                        padding-bottom: 15px;
                        border-bottom: 1px solid #e9ecef;
                    }

                    .info-header i {
                        font-size: 1.4rem;
                        color: var(--primary);
                    }

                    .info-header h2 {
                        font-size: 1.3rem;
                        font-weight: 600;
                        color: var(--dark);
                    }

                    .info-item {
                        display: flex;
                        margin-bottom: 18px;
                    }

                    .info-label {
                        font-weight: 600;
                        min-width: 120px;
                        color: var(--gray);
                    }

                    .info-value {
                        color: var(--dark);
                        flex: 1;
                    }

                    .status {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 12px 18px;
                        border-radius: 8px;
                        background-color: #e8f5e9;
                        color: #2e7d32;
                        margin-top: 15px;
                    }

                    .status.error {
                        background-color: #ffebee;
                        color: #c62828;
                    }

                    .status i {
                        font-size: 1.2rem;
                    }

                    .reset-btn {
                        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
                        margin-top: 10px;
                        width: 100%;
                        padding: 10px;
                        font-size: 14px;
                    }

                    .reset-btn:hover {
                        background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
                    }

                    @media (max-width: 768px) {
                        .content {
                            flex-direction: column;
                        }

                        .info-card {
                            max-width: 100%;
                        }

                        .preview-section {
                            max-width: 100%;
                        }

                        .offer-preview {
                            width: 100%;
                            height: auto;
                            aspect-ratio: 595/842;
                            transform: scale(0.9);
                            transform-origin: top center;
                        }

                        .edit-form {
                            grid-template-columns: 1fr;
                        }

                        .form-group.full-width {
                            grid-column: span 1;
                        }

                        .form-actions {
                            grid-column: span 1;
                            flex-direction: column;
                        }

                        h1 {
                            font-size: 2rem;
                        }
                    }

                    @media (max-width: 480px) {
                        .offer-preview {
                            transform: scale(0.8);
                        }
                        
                        .preview-header {
                            flex-direction: column;
                            align-items: flex-start;
                            gap: 15px;
                        }
                        
                        .edit-toggle-btn {
                            align-self: stretch;
                            justify-content: center;
                        }
                        
                        .content-preview {
                            padding: 100px 30px 30px 30px;
                        }
                    }
                </style>

                <div class="offer-letter-container">
                    <header>
                        <h1>Offer Letter Generator</h1>
                        <p class="subtitle">Customize and download your internship offer letter</p>
                    </header>

                    <div class="content">
                        <div class="preview-section">
                            <div class="preview-header">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-file-contract"></i>
                                    <h2>Offer Letter Preview</h2>
                                </div>
                                <button class="edit-toggle-btn" id="editToggleBtn">
                                    <i class="fas fa-edit"></i>
                                    Edit All Fields
                                </button>
                            </div>
                            
                            <!-- Edit Form -->
                            <div class="edit-form-container" id="editFormContainer">
                                <form method="POST" class="edit-form" id="editForm">
                                    <div class="form-group full-width">
                                        <label class="form-label">
                                            <i class="fas fa-user"></i>
                                            Intern Name
                                        </label>
                                        <input type="text" name="name" class="form-input" 
                                               value="<?php echo htmlspecialchars($name); ?>" 
                                               placeholder="Enter intern name" required>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">
                                            <i class="fas fa-laptop-code"></i>
                                            Technology/Field
                                        </label>
                                        <input type="text" name="tech_name" class="form-input" 
                                               value="<?php echo htmlspecialchars($tech_name); ?>" 
                                               placeholder="e.g., Web Development, Data Science" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt"></i>
                                            Start Date
                                        </label>
                                        <input type="date" name="start_date" class="form-input" 
                                               value="<?php echo date('Y-m-d', strtotime($start_date)); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt"></i>
                                            End Date
                                        </label>
                                        <input type="date" name="end_date" class="form-input" 
                                               value="<?php echo date('Y-m-d', strtotime($end_date)); ?>" required>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label class="form-label">
                                            <i class="fas fa-file-signature"></i>
                                            Issue Date
                                        </label>
                                        <input type="date" name="issue_date" class="form-input" 
                                               value="<?php echo date('Y-m-d', strtotime($issue_date)); ?>" required>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">
                                            <i class="fas fa-times"></i>
                                            Cancel
                                        </button>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i>
                                            Save & Update Preview
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Preview -->
                            <div class="preview-container">
                                <div class="offer-preview">
                                    <div class="background-preview"></div>
                                    <div class="content-preview">
                                        <div class="offer-date">
                                            <strong>Date:</strong> <?php echo $issue_date; ?>
                                        </div>
                                        
                                        <div class="offer-to">
                                            <strong>To:</strong><br>
                                            <?php echo htmlspecialchars($name); ?><br>
                                            <strong>Designation:</strong> Intern – <?php echo htmlspecialchars($tech_name); ?><br>
                                            DawoodTech NextGen
                                        </div>
                                        
                                        <div class="offer-title">
                                            Internship Offer – <?php echo htmlspecialchars($tech_name); ?>
                                        </div>
                                        
                                        <div class="offer-body">
                                            <p>Dear <?php echo htmlspecialchars($name); ?>,</p>
                                            <p>We are pleased to offer you an internship opportunity from 
                                            <strong><?php echo $start_date; ?></strong> to <strong><?php echo $end_date; ?></strong> at <strong>DawoodTech NextGen</strong> as a 
                                            <strong><?php echo htmlspecialchars($tech_name); ?> Intern</strong>.</p>
                                            <p>This internship will provide you with the chance to enhance your skills, gain practical exposure, and contribute to real-world projects under professional guidance. We believe your dedication and efforts will add value to our team, and we look forward to your valuable contribution and growth during this program.</p>
                                            <p>We are confident that this experience will be a stepping stone in your professional journey, equipping you with the knowledge and confidence to excel in your career.</p>
                                            <p>We value our professional relationship and look forward to your continued support. Should you have any questions or require further details, please do not hesitate to contact us.</p>
                                        </div>
                                        
                                        <div class="offer-signature">
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
                                </div>
                            </div>
                            
                            <div class="controls">
                                <a href="?download=true" class="btn" id="downloadBtn">
                                    <i class="fas fa-file-pdf"></i>
                                    Download Offer Letter (PDF)
                                </a>
                                <a href="?reset=true" class="btn reset-btn">
                                    <i class="fas fa-redo"></i>
                                    Reset to Default Values
                                </a>
                                <div class="status" id="statusMessage">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Ready to download. Click "Download Offer Letter" to get PDF.</span>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-header">
                                <i class="fas fa-info-circle"></i>
                                <h2>Letter Details</h2>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Intern Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($name); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Technology:</span>
                                <span class="info-value"><?php echo htmlspecialchars($tech_name); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Start Date:</span>
                                <span class="info-value"><?php echo $start_date; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">End Date:</span>
                                <span class="info-value"><?php echo $end_date; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Duration:</span>
                                <span class="info-value">
                                    <?php 
                                        $start = new DateTime($start_date);
                                        $end = new DateTime($end_date);
                                        $interval = $start->diff($end);
                                        echo $interval->format('%m months %d days');
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Issue Date:</span>
                                <span class="info-value"><?php echo $issue_date; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status:</span>
                                <span class="info-value" style="color: var(--success); font-weight: 600;">
                                    <?php echo isset($_SESSION['form_data']) ? '✓ Customized' : '✓ Default'; ?>
                                </span>
                            </div>
                            
                            <div class="info-header" style="margin-top: 25px; margin-bottom: 15px;">
                                <i class="fas fa-question-circle"></i>
                                <h2>Instructions</h2>
                            </div>
                            <div class="info-item">
                                <ul class="list-disc pl-5 space-y-2 text-sm text-gray-600">
                                    <li>Click "Edit All Fields" to customize</li>
                                    <li>Update any field as needed</li>
                                    <li>Click "Save & Update Preview"</li>
                                    <li>Download PDF when ready</li>
                                    <li>Use "Reset" to restore defaults</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editToggleBtn = document.getElementById('editToggleBtn');
            const editFormContainer = document.getElementById('editFormContainer');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const editForm = document.getElementById('editForm');
            const downloadBtn = document.getElementById('downloadBtn');
            
            let isEditing = false;
            
            // Check if there's an error to show edit form
            <?php if (isset($_SESSION['form_submit_error'])): ?>
                isEditing = true;
                editFormContainer.style.display = 'block';
                editToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Hide Editor';
                editToggleBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.3)';
            <?php endif; ?>
            
            // Toggle edit form
            editToggleBtn.addEventListener('click', function() {
                isEditing = !isEditing;
                
                if (isEditing) {
                    editFormContainer.style.display = 'block';
                    editToggleBtn.innerHTML = '<i class="fas fa-eye"></i> Hide Editor';
                    editToggleBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.3)';
                    // Smooth scroll to form
                    editFormContainer.scrollIntoView({ behavior: 'smooth' });
                } else {
                    editFormContainer.style.display = 'none';
                    editToggleBtn.innerHTML = '<i class="fas fa-edit"></i> Edit All Fields';
                    editToggleBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                }
            });
            
            // Cancel edit
            cancelEditBtn.addEventListener('click', function() {
                editFormContainer.style.display = 'none';
                editToggleBtn.innerHTML = '<i class="fas fa-edit"></i> Edit All Fields';
                editToggleBtn.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                isEditing = false;
            });
            
            // Form validation
            editForm.addEventListener('submit', function(e) {
                const nameInput = editForm.querySelector('input[name="name"]');
                const techInput = editForm.querySelector('input[name="tech_name"]');
                const startDate = editForm.querySelector('input[name="start_date"]');
                const endDate = editForm.querySelector('input[name="end_date"]');
                
                let isValid = true;
                
                // Reset previous errors
                document.querySelectorAll('.form-input').forEach(input => {
                    input.classList.remove('border-red-500');
                });
                
                // Validate name
                if (!nameInput.value.trim()) {
                    nameInput.classList.add('border-red-500');
                    isValid = false;
                }
                
                // Validate technology
                if (!techInput.value.trim()) {
                    techInput.classList.add('border-red-500');
                    isValid = false;
                }
                
                // Validate dates
                if (!startDate.value || !endDate.value) {
                    if (!startDate.value) startDate.classList.add('border-red-500');
                    if (!endDate.value) endDate.classList.add('border-red-500');
                    isValid = false;
                } else if (new Date(startDate.value) > new Date(endDate.value)) {
                    startDate.classList.add('border-red-500');
                    endDate.classList.add('border-red-500');
                    alert('End date must be after start date!');
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill all required fields correctly.');
                    return;
                }
                
                // Show loading state
                const submitBtn = editForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
                
                // The form will submit normally via POST
            });
            
            // Download button click
            downloadBtn.addEventListener('click', function(e) {
                // Show loading state
                const originalText = downloadBtn.innerHTML;
                downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
                downloadBtn.style.pointerEvents = 'none';
                
                // The download will be handled by the href link
                // Reset button state after a short delay
                setTimeout(() => {
                    downloadBtn.innerHTML = originalText;
                    downloadBtn.style.pointerEvents = 'auto';
                }, 3000);
            });
            
            // Set minimum end date based on start date
            const startDateInput = editForm.querySelector('input[name="start_date"]');
            const endDateInput = editForm.querySelector('input[name="end_date"]');
            
            if (startDateInput && endDateInput) {
                startDateInput.addEventListener('change', function() {
                    endDateInput.min = this.value;
                    if (endDateInput.value && new Date(endDateInput.value) < new Date(this.value)) {
                        endDateInput.value = this.value;
                    }
                });
            }
            
            // Auto-hide toast messages after 5 seconds
            setTimeout(() => {
                const toasts = document.querySelectorAll('.toast');
                toasts.forEach(toast => {
                    toast.style.transition = 'opacity 0.5s ease';
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                });
            }, 5000);
        });
    </script>
</body>

</html>