<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location:' . BASE_URL . 'login');
    exit;
}
if($_SESSION['user_role'] !== 1){
    header('Location:' . BASE_URL . 'index.php');
    exit;
}
$page_title = 'Generate Offer Letter';
include_once "./include/headerLinks.php";

// Get user data from session
$user_id = $_SESSION['user_id'];

// Fetch user data and calculate internship duration
include_once './include/connection.php';
$user_query = $conn->prepare("SELECT name, tech_id, created_at FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();

    // Calculate dates - these are defaults that can be overridden by user input
    $default_start_date = date('d-M-Y', strtotime($user_data['created_at']));
    $default_end_date = date('d-M-Y', strtotime($user_data['created_at'] . ' + 3 months'));
    $default_issue_date = date('d-M-Y'); // Today's date

    // Get technology name
    $tech_query = $conn->prepare("SELECT name FROM technologies WHERE id = ?");
    $tech_query->bind_param("i", $user_data['tech_id']);
    $tech_query->execute();
    $tech_result = $tech_query->get_result();
    $tech_name = $tech_result->num_rows > 0 ? $tech_result->fetch_assoc()['name'] : 'Technology';
} else {
    // Redirect if user not found
    header('location: ' . BASE_URL . 'index.php');
    exit();
}
?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4">
        <!-- Toast templates will be inserted here dynamically -->
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

                    .offer-container {
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
                        position: relative;
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
                        gap: 12px;
                    }

                    .preview-header i {
                        font-size: 1.4rem;
                    }

                    .preview-header h2 {
                        font-size: 1.3rem;
                        font-weight: 600;
                    }

                    .letter-container {
                        padding: 0;
                        background-color: #ffffff;
                        min-height: 600px;
                        position: relative;
                        width: 100%;
                        height: 842px; /* A4 height approximation */
                        overflow: hidden;
                    }

                    .letter-content {
                        font-family: 'Arial', sans-serif;
                        color: #333;
                        line-height: 1.5;
                        position: relative;
                        width: 100%;
                        height: 100%;
                        font-size: 12px;
                    }

                    .background-img {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 0;
                        object-fit: cover;
                        display: block;
                    }

                    .content-box {
                        position: absolute;
                        top: 150px;
                        left: 60px;
                        right: 60px;
                        padding: 20px;
                        height: auto;
                        max-height: 650px;
                        z-index: 2;
                        background: transparent;
                    }

                    .section {
                        margin-bottom: 8px;
                        position: relative;
                        z-index: 3;
                        font-size: 12px;
                    }

                    .section.title h3 {
                        margin: 8px 0;
                        font-size: 14px;
                        font-weight: bold;
                        color: var(--dark);
                    }

                    .letter-to {
                        margin-bottom: 12px;
                        line-height: 1.4;
                        position: relative;
                        z-index: 3;
                        font-size: 12px;
                    }

                    .letter-body {
                        text-align: justify;
                        margin-bottom: 15px;
                        position: relative;
                        z-index: 3;
                    }

                    .letter-body p {
                        margin-bottom: 8px;
                        font-size: 12px;
                        line-height: 1.5;
                    }

                    .signature {
                        margin-top: 20px;
                        line-height: 1.4;
                        font-size: 11px;
                        position: relative;
                        z-index: 3;
                    }

                    .controls {
                        padding: 25px;
                        display: flex;
                        flex-direction: column;
                        gap: 20px;
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
                    }

                    .form-group {
                        margin-bottom: 20px;
                    }

                    .form-group label {
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                        color: var(--dark);
                    }

                    .form-control {
                        width: 100%;
                        padding: 12px 16px;
                        border: 2px solid #e9ecef;
                        border-radius: 8px;
                        font-size: 16px;
                        transition: var(--transition);
                        background: white;
                    }

                    .form-control:focus {
                        outline: none;
                        border-color: var(--primary);
                        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
                    }

                    .form-row {
                        display: flex;
                        gap: 15px;
                        margin-bottom: 20px;
                    }

                    .form-row .form-group {
                        flex: 1;
                        margin-bottom: 0;
                    }

                    .form-actions {
                        display: flex;
                        gap: 15px;
                        margin-top: 25px;
                    }

                    .btn {
                        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
                        border: none;
                        color: white;
                        padding: 14px 25px;
                        font-size: 18px;
                        font-weight: 600;
                        border-radius: 8px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 10px;
                        transition: var(--transition);
                        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
                        width: 100%;
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

                    .btn i {
                        font-size: 1.1rem;
                    }

                    .btn-secondary {
                        background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
                        box-shadow: 0 4px 12px rgba(149, 165, 166, 0.3);
                    }

                    .btn-secondary:hover {
                        background: linear-gradient(135deg, #7f8c8d 0%, #636e72 100%);
                        box-shadow: 0 6px 15px rgba(149, 165, 166, 0.4);
                    }

                    .btn-sm {
                        padding: 10px 20px;
                        font-size: 16px;
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

                    .date-hint {
                        font-size: 12px;
                        color: #666;
                        margin-top: 4px;
                        font-style: italic;
                    }

                    @media (max-width: 768px) {
                        .content {
                            flex-direction: column;
                        }

                        .info-card {
                            max-width: 100%;
                        }

                        h1 {
                            font-size: 2rem;
                        }

                        .form-row {
                            flex-direction: column;
                            gap: 20px;
                        }

                        .letter-container {
                            padding: 20px;
                        }

                        .company-name {
                            font-size: 1.5rem;
                        }
                    }
                </style>

                <div class="offer-container">
                    <header>
                        <h1>Offer Letter Generator</h1>
                        <p class="subtitle">Customize and download your internship offer letter</p>
                    </header>

                    <div class="content">
                        <div class="preview-section">
                            <div class="preview-header">
                                <i class="fas fa-file-contract"></i>
                                <h2>Offer Letter Preview</h2>
                            </div>
                            <div class="letter-container" id="offerLetterContent">
                                <div class="letter-content">
                                    <!-- Background Image -->
                                    <img src="./assets/images/offerletter.png" class="background-img" alt="Offer Letter Background">
                                    
                                    <div class="content-box">
                                        <div class="section" style="text-align:right;">
                                            <strong>Date:</strong> <span id="displayIssueDate"><?php echo $default_issue_date; ?></span>
                                        </div>
                                        
                                        <div class="section letter-to">
                                            <strong>To:</strong><br>
                                            <span id="displayNameInLetter"><?php echo htmlspecialchars($user_data['name']); ?></span><br>
                                            <strong>Designation:</strong> Intern – <span id="displayTechInLetter"><?php echo htmlspecialchars($tech_name); ?></span><br>
                                            DawoodTech NextGen
                                        </div>
                                        
                                        <div class="section title">
                                            <h3>Internship Offer – <span id="displaySubjectTech"><?php echo htmlspecialchars($tech_name); ?></span></h3>
                                        </div>
                                        
                                        <div class="section letter-body">
                                            <p>Dear <span id="displayBodyName"><?php echo htmlspecialchars($user_data['name']); ?></span>,</p>
                                            
                                            <p>We are pleased to offer you an internship opportunity from
                                            <strong><span id="displayStartDate"><?php echo $default_start_date; ?></span></strong> to <br> <strong><span id="displayEndDate"><?php echo $default_end_date; ?></span></strong> at <strong>DawoodTech NextGen</strong> as a
                                            <strong><span id="displayBodyTech"><?php echo htmlspecialchars($tech_name); ?></span> Intern</strong>.</p>
                                            
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
                                </div>
                            </div>
                            <div class="controls">
                                <button class="btn" id="downloadBtn" onclick="generatePDF()">
                                    <i class="fas fa-file-pdf"></i>
                                    Download Offer Letter
                                </button>
                                <div class="status" id="statusMessage">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Offer letter is ready for download</span>
                                </div>
                            </div>
                        </div>

                        <div class="info-card">
                            <div class="info-header">
                                <i class="fas fa-edit"></i>
                                <h2>Customize Offer Letter</h2>
                            </div>
                            
                            <form id="offerLetterForm">
                                <div class="form-group">
                                    <label for="studentName">Intern Name</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="studentName" 
                                           value="<?php echo htmlspecialchars($user_data['name']); ?>"
                                           onchange="updateOfferLetter()">
                                </div>

                                <div class="form-group">
                                    <label for="technology">Technology</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="technology" 
                                           value="<?php echo htmlspecialchars($tech_name); ?>"
                                           onchange="updateOfferLetter()">
                                    <div class="date-hint">Enter the technology/skill name</div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="startDate">Start Date</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="startDate" 
                                               placeholder="DD-MMM-YYYY"
                                               value="<?php echo $default_start_date; ?>"
                                               onchange="updateOfferLetter()">
                                    </div>
                                    <div class="form-group">
                                        <label for="endDate">End Date</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="endDate" 
                                               placeholder="DD-MMM-YYYY"
                                               value="<?php echo $default_end_date; ?>"
                                               onchange="updateOfferLetter()">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="issueDate">Issue Date</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="issueDate" 
                                           placeholder="DD-MMM-YYYY"
                                           value="<?php echo $default_issue_date; ?>"
                                           onchange="updateOfferLetter()">
                                    <div class="date-hint">Letter issue date (typically today)</div>
                                </div>

                                <div class="form-actions">
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            onclick="resetToDefaults()">
                                        <i class="fas fa-redo"></i>
                                        Reset to Defaults
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm" 
                                            onclick="updateOfferLetter()">
                                        <i class="fas fa-sync-alt"></i>
                                        Update Preview
                                    </button>
                                </div>
                            </form>

                            <div class="info-header" style="margin-top: 30px;">
                                <i class="fas fa-info-circle"></i>
                                <h2>Offer Letter Details</h2>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name:</div>
                                <div class="info-value" id="displayName"><?php echo htmlspecialchars($user_data['name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Technology:</div>
                                <div class="info-value" id="displayTech"><?php echo htmlspecialchars($tech_name); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Internship Period:</div>
                                <div class="info-value" id="displayPeriod"><?php echo $default_start_date . ' to ' . $default_end_date; ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Letter Issue:</div>
                                <div class="info-value" id="displayIssue"><?php echo $default_issue_date; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <!-- jsPDF and html2canvas CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    
    <script>
        const { jsPDF } = window.jspdf;

        // Default offer letter data from PHP
        const defaultOfferData = {
            name: "<?php echo $user_data['name']; ?>",
            technology: "<?php echo $tech_name; ?>",
            start_date: "<?php echo $default_start_date; ?>",
            end_date: "<?php echo $default_end_date; ?>",
            issue_date: "<?php echo $default_issue_date; ?>"
        };

        // Current offer letter data (can be modified by user)
        let offerData = {...defaultOfferData};

        function updateOfferLetter() {
            // Get values from input fields
            const nameInput = document.getElementById('studentName');
            const techInput = document.getElementById('technology');
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const issueDateInput = document.getElementById('issueDate');

            // Update offer data
            offerData.name = nameInput.value.trim() || defaultOfferData.name;
            offerData.technology = techInput.value.trim() || defaultOfferData.technology;
            offerData.start_date = startDateInput.value.trim() || defaultOfferData.start_date;
            offerData.end_date = endDateInput.value.trim() || defaultOfferData.end_date;
            offerData.issue_date = issueDateInput.value.trim() || defaultOfferData.issue_date;

            // Update all display elements
            document.getElementById('displayName').textContent = offerData.name;
            document.getElementById('displayTech').textContent = offerData.technology;
            document.getElementById('displayPeriod').textContent = offerData.start_date + ' to ' + offerData.end_date;
            document.getElementById('displayIssue').textContent = offerData.issue_date;

            // Update letter content
            document.getElementById('displayIssueDate').textContent = offerData.issue_date;
            document.getElementById('displayNameInLetter').textContent = offerData.name;
            document.getElementById('displayTechInLetter').textContent = offerData.technology;
            document.getElementById('displaySubjectTech').textContent = offerData.technology;
            document.getElementById('displayBodyName').textContent = offerData.name;
            document.getElementById('displayStartDate').textContent = offerData.start_date;
            document.getElementById('displayEndDate').textContent = offerData.end_date;
            document.getElementById('displayBodyTech').textContent = offerData.technology;

            // Show success message
            showStatus('Offer letter updated successfully!', 'success');
        }

        function resetToDefaults() {
            // Reset input fields to default values
            document.getElementById('studentName').value = defaultOfferData.name;
            document.getElementById('technology').value = defaultOfferData.technology;
            document.getElementById('startDate').value = defaultOfferData.start_date;
            document.getElementById('endDate').value = defaultOfferData.end_date;
            document.getElementById('issueDate').value = defaultOfferData.issue_date;

            // Reset offer data
            offerData = {...defaultOfferData};

            // Update all displays
            document.getElementById('displayName').textContent = defaultOfferData.name;
            document.getElementById('displayTech').textContent = defaultOfferData.technology;
            document.getElementById('displayPeriod').textContent = defaultOfferData.start_date + ' to ' + defaultOfferData.end_date;
            document.getElementById('displayIssue').textContent = defaultOfferData.issue_date;

            // Update letter content
            document.getElementById('displayIssueDate').textContent = defaultOfferData.issue_date;
            document.getElementById('displayNameInLetter').textContent = defaultOfferData.name;
            document.getElementById('displayTechInLetter').textContent = defaultOfferData.technology;
            document.getElementById('displaySubjectTech').textContent = defaultOfferData.technology;
            document.getElementById('displayBodyName').textContent = defaultOfferData.name;
            document.getElementById('displayStartDate').textContent = defaultOfferData.start_date;
            document.getElementById('displayEndDate').textContent = defaultOfferData.end_date;
            document.getElementById('displayBodyTech').textContent = defaultOfferData.technology;

            // Show success message
            showStatus('Offer letter reset to default values!', 'success');
        }

        function showStatus(message, type = 'success') {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            statusDiv.className = `status ${type === 'error' ? 'error' : ''}`;
            
            // Auto-hide after 3 seconds
            setTimeout(() => {
                if (type === 'success') {
                    statusDiv.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        <span>Offer letter is ready for download</span>
                    `;
                    statusDiv.className = 'status';
                }
            }, 3000);
        }

        async function generatePDF() {
            const downloadBtn = document.getElementById('downloadBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            // Show loading state
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
            downloadBtn.disabled = true;
            statusMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Generating PDF, please wait...</span>';
            
            try {
                const element = document.getElementById('offerLetterContent');
                
                // Use html2canvas to convert HTML to canvas
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    logging: false,
                    backgroundColor: '#ffffff'
                });
                
                const imgData = canvas.toDataURL('image/png');
                
                // Calculate PDF dimensions
                const imgWidth = 210; // A4 width in mm
                const pageHeight = 297; // A4 height in mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                // Create PDF
                const pdf = new jsPDF('p', 'mm', 'a4');
                let heightLeft = imgHeight;
                let position = 0;
                
                // Add image to PDF (handle multiple pages if needed)
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                
                while (heightLeft >= 0) {
                    position = heightLeft - imgHeight;
                    pdf.addPage();
                    pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }
                
                // Generate filename
                const timestamp = new Date().toISOString().replace(/[-:.TZ]/g, "");
                const safeName = offerData.name.replace(/[^a-zA-Z0-9]/g, '_');
                
                // Save PDF
                pdf.save(`OfferLetter_${safeName}_${timestamp}.pdf`);
                
                // Show success message
                statusMessage.innerHTML = '<i class="fas fa-check-circle"></i> <span>PDF generated successfully!</span>';
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                statusMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> <span>Error generating PDF. Please try again.</span>';
                statusMessage.className = 'status error';
            } finally {
                // Reset button state
                downloadBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Download Offer Letter';
                downloadBtn.disabled = false;
                
                // Reset status after 3 seconds
                setTimeout(() => {
                    statusMessage.innerHTML = '<i class="fas fa-check-circle"></i> <span>Offer letter is ready for download</span>';
                    statusMessage.className = 'status';
                }, 3000);
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateOfferLetter();
        });
    </script>
</body>

</html>