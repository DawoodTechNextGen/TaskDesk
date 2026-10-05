<?php
// Public verification page for DawoodTech NextGen bootcamp certificates.
// The certificate's QR code and the "Verify" button in its email link here
// with ?code=<certificate_code> (see include/bootcamp_certificate_helper.php).

require_once 'include/config.php';
require_once 'include/connection.php';
require_once 'include/bootcamp_helper.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
$cert = null;

if ($code !== '' && preg_match('/^DTN-BC-[A-F0-9]{8,12}$/', $code)) {
    $stmt = $conn->prepare("SELECT b.name, b.status, b.completed_at, b.certificate_code,
            bc.title AS bootcamp_title, bc.start_date, bc.end_date, bc.duration, bc.mode
        FROM " . BOOTCAMP_TABLE . " b
        LEFT JOIN " . BOOTCAMPS_TABLE . " bc ON bc.id = b.bootcamp_id
        WHERE b.certificate_code = ?");
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// A certificate stays valid only while its enrollment is still marked completed.
$valid = $cert && $cert['status'] === 'completed';
$fmt = fn($d) => $d ? date('d F Y', strtotime($d)) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Bootcamp Certificate | DawoodTech NextGen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Outfit', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <div class="text-center mb-6">
            <img src="assets/images/logo.png" alt="DawoodTech NextGen" class="h-12 mx-auto" onerror="this.style.display='none'">
            <p class="text-sm text-slate-500 mt-2 tracking-wide uppercase">Bootcamp Certificate Verification</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <?php if ($valid): ?>
                <div class="bg-green-600 text-white px-6 py-5 text-center">
                    <div class="text-4xl mb-1">&#10003;</div>
                    <div class="text-xl font-bold">Verified Certificate</div>
                    <div class="text-sm text-green-100">This certificate was issued by DawoodTech NextGen</div>
                </div>
                <div class="p-6 space-y-4">
                    <div class="text-center">
                        <div class="text-xs uppercase text-slate-400 tracking-wider">Awarded to</div>
                        <div class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($cert['name']) ?></div>
                    </div>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div class="col-span-2">
                            <dt class="text-slate-400">Bootcamp</dt>
                            <dd class="font-semibold text-slate-800"><?= htmlspecialchars($cert['bootcamp_title'] ?: 'DawoodTech NextGen Bootcamp') ?></dd>
                        </div>
                        <?php if ($cert['start_date'] || $cert['end_date']): ?>
                            <div class="col-span-2">
                                <dt class="text-slate-400">Bootcamp Dates</dt>
                                <dd class="font-semibold text-slate-800"><?= htmlspecialchars(trim(($fmt($cert['start_date']) ?? '') . ' – ' . ($fmt($cert['end_date']) ?? ''), ' –')) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if ($cert['duration']): ?>
                            <div>
                                <dt class="text-slate-400">Duration</dt>
                                <dd class="font-semibold text-slate-800"><?= htmlspecialchars($cert['duration']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <div>
                            <dt class="text-slate-400">Completed On</dt>
                            <dd class="font-semibold text-slate-800"><?= htmlspecialchars($fmt($cert['completed_at']) ?? '-') ?></dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-slate-400">Certificate ID</dt>
                            <dd class="font-mono font-semibold text-slate-800"><?= htmlspecialchars($cert['certificate_code']) ?></dd>
                        </div>
                    </dl>
                </div>
            <?php else: ?>
                <div class="bg-red-600 text-white px-6 py-5 text-center">
                    <div class="text-4xl mb-1">&#10007;</div>
                    <div class="text-xl font-bold">Certificate Not Found</div>
                </div>
                <div class="p-6 text-center text-slate-600 text-sm">
                    <?php if ($code === ''): ?>
                        No certificate ID was provided. Please use the link or QR code on the certificate.
                    <?php else: ?>
                        The certificate ID <span class="font-mono font-semibold"><?= htmlspecialchars($code) ?></span> is not recognised or is no longer valid.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">&copy; <?= date('Y') ?> DawoodTech NextGen</p>
    </div>
</body>
</html>
