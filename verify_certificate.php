<?php
// verify_certificate.php
// Public verification portal for DawoodTech NextGen Internship Certificates

require_once 'include/config.php';
require_once 'include/connection.php';

$cert_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cert_data = null;
$error = true;
$error_msg = "Invalid Verification Code. The requested certificate ID is not recognized or has not been approved.";

if ($cert_id > 0) {
    $stmt = $conn->prepare("
        SELECT c.id, c.approve_status, c.created_at as issued_at, u.name as intern_name, u.created_at as joining_date, u.internship_type, u.internship_duration, t.name as tech_name 
        FROM certificate c
        JOIN users u ON c.intern_id = u.id
        LEFT JOIN technologies t ON u.tech_id = t.id
        WHERE c.id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("i", $cert_id);
        $stmt->execute();
        $cert_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cert_data) {
            if ($cert_data['approve_status'] == 1) {
                $error = false;

                // Calculate dates
                $duration = $cert_data['internship_duration'];
                if (empty($duration)) {
                    $duration = ($cert_data['internship_type'] == 0) ? '4 weeks' : '12 weeks';
                }
                $duration_str = '+' . $duration;
                
                $start_date = date('d F Y', strtotime($cert_data['joining_date']));
                $end_date = date('d F Y', strtotime($cert_data['joining_date'] . ' ' . $duration_str));
                $issue_date = $end_date;
            } else {
                $error_msg = "Verification Pending. This certificate has been created but is currently awaiting administrator approval.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Credentials | DawoodTech NextGen</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glow-green {
            box-shadow: 0 0 40px rgba(16, 185, 129, 0.15);
        }
        .glow-red {
            box-shadow: 0 0 40px rgba(239, 68, 68, 0.15);
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.5; }
            100% { transform: scale(1.2); opacity: 0; }
        }
        .pulse-effect {
            animation: pulse-ring 2s cubic-bezier(0.215, 0.610, 0.355, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-tr from-slate-50 via-slate-100 to-indigo-50/40 dark:from-slate-950 dark:via-slate-900 dark:to-indigo-950/20 min-h-screen flex flex-col transition-colors duration-300">
    
    <!-- Top Bar with Theme Toggle -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex justify-end items-center z-10">
        <button id="theme-toggle" class="p-2.5 rounded-xl bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200/60 dark:border-slate-700/60 hover:bg-slate-50 dark:hover:bg-slate-700 transition-all shadow-sm">
            <i class="fa-solid fa-moon dark:hidden text-lg"></i>
            <i class="fa-solid fa-sun hidden dark:block text-lg"></i>
        </button>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-xl duration-500 animate-in fade-in slide-in-from-bottom-12">
            
            <?php if (!$error && $cert_data): ?>
                <!-- SUCCESS STATE -->
                <div class="glass glow-green rounded-3xl p-8 md:p-10 shadow-2xl relative overflow-hidden transition-all duration-300">
                    <!-- Top Ribbon Decorative -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl"></div>

                    <!-- Seal Indicator -->
                    <div class="flex flex-col items-center text-center pb-8 border-b border-slate-200/50 dark:border-slate-700/40">
                        <div class="relative mb-4">
                            <div class="h-20 w-20 bg-emerald-500 rounded-full flex items-center justify-center text-white text-3xl shadow-lg shadow-emerald-500/30 relative z-10">
                                <i class="fa-solid fa-check"></i>
                            </div>
                        </div>
                        <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 uppercase tracking-widest border border-emerald-200/30">
                            ✓ Verified Credential
                        </span>
                        <h1 class="text-3xl font-extrabold mt-4 text-slate-900 dark:text-white tracking-tight">Certificate Authenticated</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2 text-sm">Credential ID: DT-CERT-<?php echo sprintf('%05d', $cert_data['id']); ?></p>
                    </div>

                    <!-- Details Box -->
                    <div class="py-8 space-y-6">
                        <!-- Holder Name -->
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Recipient Name</span>
                            <div class="text-2xl font-bold text-slate-800 dark:text-slate-100 mt-1"><?php echo htmlspecialchars($cert_data['intern_name']); ?></div>
                        </div>

                        <!-- Internship Domain -->
                        <div>
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Internship Program</span>
                            <div class="text-lg font-bold text-slate-800 dark:text-slate-200 mt-1">
                                <?php echo htmlspecialchars($cert_data['tech_name'] ?: 'Technology'); ?> Intern
                            </div>
                            <div class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">DawoodTech NextGen Internship</div>
                        </div>

                        <!-- Internship Period -->
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">From</span>
                                <div class="font-semibold text-slate-700 dark:text-slate-300 mt-1"><?php echo $start_date; ?></div>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">To</span>
                                <div class="font-semibold text-slate-700 dark:text-slate-300 mt-1"><?php echo $end_date; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Details -->
                    <div class="pt-6 border-t border-slate-200/50 dark:border-slate-700/40 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-xs text-slate-400 dark:text-slate-500">
                        <div>
                            <span class="block">Authority: <strong>DawoodTech NextGen</strong></span>
                            <span class="block mt-0.5">Verification Date: <?php echo date('j F Y, g:i a', strtotime($cert_data['issued_at'])); ?></span>
                        </div>
                        <a href="index.php" class="inline-flex items-center space-x-1.5 text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                            <span>Portal Home</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- ERROR STATE -->
                <div class="glass glow-red rounded-3xl p-8 md:p-10 shadow-2xl relative overflow-hidden transition-all duration-300">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/10 rounded-full blur-2xl"></div>
                    
                    <div class="flex flex-col items-center text-center pb-6 border-b border-slate-200/50 dark:border-slate-700/40">
                        <div class="h-16 w-16 bg-red-100 dark:bg-red-950/50 rounded-full flex items-center justify-center text-red-600 dark:text-red-400 text-2xl mb-4 border border-red-200/20">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300 uppercase tracking-widest border border-red-200/30">
                            ✗ Verification Error
                        </span>
                        <h1 class="text-2xl font-extrabold mt-4 text-slate-900 dark:text-white tracking-tight">Credential Not Verified</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-3 text-sm px-4 leading-relaxed"><?php echo htmlspecialchars($error_msg); ?></p>
                    </div>

                    <div class="py-6 text-sm text-slate-500 dark:text-slate-400 space-y-4">
                        <p class="font-medium text-slate-700 dark:text-slate-300">Why might a certificate fail to verify?</p>
                        <ul class="space-y-2 list-disc pl-5 text-xs text-slate-500">
                            <li>The QR code or ID parameter has been altered or contains a typo.</li>
                            <li>The credential may have been revoked or is no longer present in our systems.</li>
                            <li>The verification script was accessed directly without a valid identifier.</li>
                        </ul>
                    </div>

                    <div class="pt-6 border-t border-slate-200/50 dark:border-slate-700/40 flex justify-center">
                        <a href="index.php" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all text-sm">
                            Return to Portal
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full text-center py-6 text-xs text-slate-400 dark:text-slate-500 border-t border-slate-200/20 dark:border-slate-800/20">
        <p>&copy; <?php echo date('Y'); ?> DawoodTech NextGen. All rights reserved.</p>
    </footer>

    <!-- Theme Switcher Script -->
    <script>
        const themeToggleBtn = document.getElementById('theme-toggle');
        
        // Check local storage or system preference
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        themeToggleBtn.addEventListener('click', function() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });
    </script>
</body>
</html>
