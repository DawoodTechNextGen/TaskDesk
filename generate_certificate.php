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
$user_query = $conn->prepare("SELECT name, tech_id, created_at, internship_type, internship_duration FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();

if ($user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();

    // Calculate dates based on internship duration or type fallback
    $duration = $user_data['internship_duration'];
    if (empty($duration)) {
        $duration = ($user_data['internship_type'] == 0) ? '4 weeks' : '12 weeks';
    }
    $duration_str = '+' . $duration;
    
    $start_date = date('j F Y', strtotime($user_data['created_at']));
    $end_date = date('j F Y', strtotime($user_data['created_at'] . ' ' . $duration_str));

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

                    @font-face {
                        font-family: 'Montserrat';
                        font-style: normal;
                        font-weight: 400;
                        font-display: swap;
                        src: url('./assets/fonts/static/Montserrat-Regular.ttf') format('truetype');
                    }

                    @font-face {
                        font-family: 'Montserrat';
                        font-style: normal;
                        font-weight: 700;
                        font-display: swap;
                        src: url('./assets/fonts/static/Montserrat-SemiBold.ttf') format('truetype');
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
            issue_date: "<?php echo $issue_date; ?>",
            internship_type: <?php echo $user_data['internship_type']; ?>
        };

        // Load certificate template
        const template = new Image();
        // Use different template for Participation (Type 0) vs Completion (Type 1)
        template.src = certificateData.internship_type == 0 
            ? "assets/images/participation_certificate.png?v=<?php echo filemtime(__DIR__ . '/assets/images/participation_certificate.png'); ?>"
            : "assets/images/certificate.png?v=<?php echo filemtime(__DIR__ . '/assets/images/certificate.png'); ?>";

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

        // Canvas text only uses a web font once it has loaded, so draw again when they are ready.
        if (document.fonts) {
            Promise.all(['28px Montserrat', 'bold 28px Montserrat', 'bold 80px Caveat'].map(f => document.fonts.load(f)))
                .then(() => drawCertificate())
                .catch(() => {});
        }

        function formatDate(dateString) {
            const [year, month, day] = dateString.substring(0, 10).split('-').map(Number);
            return new Date(year, month - 1, day).toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
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

            // Text positions below are laid out for a 1790x1276 template; scale them to
            // whatever size the template really is (the current one is 300 DPI).
            const W = 1790, H = 1276;
            ctx.save();
            ctx.scale(canvas.width / W, canvas.height / H);

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
            ctx.fillText(name, W / 2, 680);

            // Main certificate text: carries on from "This certificate is proudly
            // presented to <name>", so the name is not repeated.
            // Draws [text, bold] segments as one centred line.
            const drawLine = (segments, y, size) => {
                const fontFor = (bold) => (bold ? "bold " : "") + size + "px Montserrat, Arial";
                let total = 0;
                segments.forEach(([text, bold]) => { ctx.font = fontFor(bold); total += ctx.measureText(text).width; });
                let x = W / 2 - total / 2;
                ctx.textAlign = "left";
                segments.forEach(([text, bold]) => {
                    ctx.font = fontFor(bold);
                    ctx.fillText(text, x, y);
                    x += ctx.measureText(text).width;
                });
                ctx.textAlign = "center";
            };

            drawLine([["for successfully completing his/her internship at ", false], ["DawoodTech NextGen", true]], 762, 28);
            drawLine([["from ", false], [startDate, true], [" to ", false], [endDate, true], [" in ", false], [technology, true], [".", false]], 808, 28);
            drawLine([["During this period, the intern showed dedication, professionalism, and a strong", false]], 866, 23);
            drawLine([["willingness to learn while contributing effectively to assigned projects.", false]], 900, 23);

            // Issue date
            // Centred over the DATE line in the middle of the page
            ctx.font = "bold 24px Montserrat, Arial";
            ctx.textAlign = "center";
            ctx.fillText(`${issueDate}`, 895, H - 75);
            ctx.restore();
        }



        function generatePDF() {
            const canvasWidth = canvas.width;
            const canvasHeight = canvas.height;
            // A4 landscape in points; the 300 DPI canvas is scaled onto it.
            const pdfWidth = 842;
            const pdfHeight = 595;

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
