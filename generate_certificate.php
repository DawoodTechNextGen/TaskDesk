<?php
session_start();
include_once './include/config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location:' . BASE_URL . 'login');
    exit;
}
// if($_SESSION['approval_status'] == 0){
//     header('Location:'.BASE_URL.'index.php');
// }
$page_title = 'Generate Certificate';
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

    // Calculate dates
    $start_date = date('d-M-Y', strtotime($user_data['created_at']));
    $end_date = date('d-M-Y', strtotime($user_data['created_at'] . ' + 3 months'));

    $issue_date = $end_date;

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

                    @font-face {
                        font-family: 'Caveat';
                        font-style: normal;
                        font-weight: 700;
                        font-display: swap;
                        src: url('./assets/fonts/static/Caveat-Bold.ttf') format('truetype');
                    }

                    .certificate-container {
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
                        gap: 12px;
                    }

                    .preview-header i {
                        font-size: 1.4rem;
                    }

                    .preview-header h2 {
                        font-size: 1.3rem;
                        font-weight: 600;
                    }

                    .canvas-container {
                        padding: 25px;
                        display: flex;
                        justify-content: center;
                        background-color: #f8f9fa;
                        border-bottom: 1px solid #e9ecef;
                    }

                    canvas {
                        border-radius: 8px;
                        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                        max-width: 100%;
                        height: auto;
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

                    .hidden {
                        display: none !important;
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
                    }
                </style>

                <div class="certificate-container">
                    <header>
                        <h1>Certificate Preview</h1>
                        <p class="subtitle">Review and download your certificate of completion</p>
                    </header>

                    <div class="content">
                        <div class="preview-section">
                            <div class="preview-header">
                                <i class="fas fa-certificate"></i>
                                <h2>Certificate Preview</h2>
                            </div>
                            <div class="canvas-container">
                                <canvas id="certCanvas"></canvas>
                            </div>
                            <div class="controls">
                                <button class="btn" id="downloadBtn" onclick="generatePDF()">
                                    <i class="fas fa-file-pdf"></i>
                                    Download Certificate
                                </button>
                                <div class="status" id="statusMessage">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Certificate is ready for download</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <!-- jsPDF CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        const {
            jsPDF
        } = window.jspdf;

        const canvas = document.getElementById("certCanvas");
        const ctx = canvas.getContext("2d");
        const downloadBtn = document.getElementById("downloadBtn");
        const statusMessage = document.getElementById("statusMessage");

        // Certificate data from PHP
        const certificateData = {
            name: "<?php echo $user_data['name']; ?>",
            technology: "<?php echo $tech_name; ?>",
            start_date: "<?php echo $start_date; ?>",
            end_date: "<?php echo $end_date; ?>",
            issue_date: "<?php echo $issue_date; ?>"
        };

        // Load certificate template
        const template = new Image();
        template.src = "assets/images/certificate.png"; // Your certificate background image

        template.onload = function() {
            // Set canvas size to match template
            canvas.width = template.width;
            canvas.height = template.height;

            // Draw the certificate
            drawCertificate();
        };

        template.onerror = function() {
            // If template fails to load, create a basic certificate
            canvas.width = 1200;
            canvas.height = 800;
            drawCertificate();
        };

        function formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return new Date(dateString).toLocaleDateString(undefined, options);
        }

        function drawCertificate() {
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw background (template or fallback)
            if (template.complete && template.naturalWidth !== 0) {
                ctx.drawImage(template, 0, 0, canvas.width, canvas.height);
            } else {
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                ctx.strokeStyle = '#3498db';
                ctx.lineWidth = 10;
                ctx.strokeRect(50, 50, canvas.width - 100, canvas.height - 100);

                ctx.fillStyle = '#2c3e50';
                ctx.font = 'bold 48px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('CERTIFICATE OF COMPLETION', canvas.width / 2, 150);
            }

            // Certificate data
            const name = certificateData.name;
            const technology = certificateData.technology;
            const startDate = certificateData.start_date;
            const endDate = certificateData.end_date;
            const issueDate = certificateData.issue_date;

            ctx.textAlign = "center";
            ctx.fillStyle = "#2c3e50";

            // Student Name Large
            ctx.font = 'bold 80px "Caveat", cursive, Arial';
            ctx.fillText(name, canvas.width / 2, 680);

            // Main certificate text
            ctx.font = "28px Arial";
            const lineHeight = 40;
            let yPosition = 750;

            // Line 1
            ctx.fillText("This is to certify that", canvas.width / 2, yPosition);
            yPosition += lineHeight;

            // Name underlined
            ctx.font = "bold 28px Arial";
            ctx.fillText(name, canvas.width / 2, yPosition);
            const nameWidth = ctx.measureText(name).width;
            ctx.beginPath();
            ctx.moveTo(canvas.width / 2 - nameWidth / 2, yPosition + 5);
            ctx.lineTo(canvas.width / 2 + nameWidth / 2, yPosition + 5);
            ctx.stroke();
            yPosition += lineHeight;

            // Line 2
            // One-line sentence, only last part bold
            let text1 = "has successfully completed his/her internship at ";
            let text2 = "DawoodTech NextGen";

            // Measure widths
            ctx.font = "28px Arial";
            let text1Width = ctx.measureText(text1).width;

            ctx.font = "bold 28px Arial";
            let text2Width = ctx.measureText(text2).width;

            // Center whole line
            let totaltWidth = text1Width + text2Width;
            let startX = (canvas.width - totaltWidth) / 2;

            // Draw normal text
            ctx.font = "28px Arial";
            ctx.fillText(text1, startX + text1Width / 2, yPosition);

            // Draw bold text immediately after
            ctx.font = "bold 28px Arial";
            ctx.fillText(text2, startX + text1Width + text2Width / 2, yPosition);

            yPosition += lineHeight;

            /* *******************************
               NEW UPDATED DATE SECTION
               Only dates bold & underlined
               from / to normal & no underline
            ******************************** */
            const fromText = "from ";
            const toText = " to ";

            ctx.font = "28px Arial";
            const fromWidth = ctx.measureText(fromText).width;
            const toWidth = ctx.measureText(toText).width;

            ctx.font = "bold 28px Arial";
            const startWidth = ctx.measureText(startDate).width;
            const endWidth = ctx.measureText(endDate).width;

            const totalWidth = fromWidth + startWidth + toWidth + endWidth;
            let xPos = (canvas.width / 2) - (totalWidth / 2);

            // Draw from
            ctx.font = "28px Arial";
            ctx.fillText(fromText, xPos + fromWidth / 2, yPosition);
            xPos += fromWidth;

            // Start date bold + underline
            ctx.font = "bold 28px Arial";
            ctx.fillText(startDate, xPos + startWidth / 2, yPosition);

            ctx.beginPath();
            ctx.moveTo(xPos, yPosition + 5);
            ctx.lineTo(xPos + startWidth, yPosition + 5);
            ctx.stroke();
            xPos += startWidth;

            // Draw to
            ctx.font = "28px Arial";
            ctx.fillText(toText, xPos + toWidth / 2, yPosition);
            xPos += toWidth;

            // End date bold + underline
            ctx.font = "bold 28px Arial";
            ctx.fillText(endDate, xPos + endWidth / 2, yPosition);

            ctx.beginPath();
            ctx.moveTo(xPos, yPosition + 5);
            ctx.lineTo(xPos + endWidth, yPosition + 5);
            ctx.stroke();

            yPosition += lineHeight;

            const techText1 = "in ";
            ctx.font = "28px Arial";
            const tech1Width = ctx.measureText(techText1).width;

            ctx.font = "bold 28px Arial";
            const techBoldWidth = ctx.measureText(technology).width;

            const techTotalWidth = tech1Width + techBoldWidth;
            let techX = (canvas.width / 2) - (techTotalWidth / 2);

            // Draw "as a"
            ctx.font = "28px Arial";
            ctx.fillText(techText1, techX + tech1Width / 2, yPosition);
            techX += tech1Width;
            // Draw Technology (BOLD)
            ctx.font = "bold 28px Arial";
            ctx.fillText(technology, techX + techBoldWidth / 2, yPosition);
            ctx.beginPath();
            ctx.moveTo(techX, yPosition + 5);
            ctx.lineTo(techX + techBoldWidth, yPosition + 5);
            ctx.stroke();
            yPosition += lineHeight * 1.5;

            // Additional content
            ctx.font = "28px Arial";
            ctx.fillText("During this period, the intern showed dedication, professionalism, and a strong", canvas.width / 2, yPosition);
            yPosition += lineHeight;
            ctx.fillText("willingness to learn while contributing effectively to assigned projects.", canvas.width / 2, yPosition);

            // Issue date
            ctx.font = "bold 30px Arial";
            ctx.textAlign = "left";
            ctx.fillText(`${issueDate}`, 630, canvas.height - 75);
        }



        function generatePDF() {
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            const pdfWidth = canvasWidth * 0.75;
            const pdfHeight = canvasHeight * 0.75;

            const pdf = new jsPDF({
                orientation: "landscape",
                unit: "pt",
                format: [pdfWidth, pdfHeight],
                compress: false,
            });

            const imgData = canvas.toDataURL("image/jpeg", 1.0);

            pdf.addImage(imgData, "JPEG", 0, 0, pdfWidth, pdfHeight, undefined, "FAST");

            const timestamp = new Date().toISOString().replace(/[-:.TZ]/g, "");

            pdf.save(`Certificate_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $user_data['name']); ?>_${timestamp}.pdf`);
        }

        // Initialize certificate when page loads
        document.addEventListener('DOMContentLoaded', function() {
            drawCertificate();
        });
    </script>
</body>

</html>