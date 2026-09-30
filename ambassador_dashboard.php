<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
require_once './include/ambassador_helper.php';
requirePageRoles([ROLE_AMBASSADOR]);

$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, university, referral_code FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$referralCode = $me['referral_code'] ?? '';
$referralLink = $referralCode ? ambassadorReferralLink($referralCode) : '';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Ambassador Dashboard - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-10 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Welcome, <?= htmlspecialchars($me['name'] ?? '') ?></h2>
                <?php if (!empty($me['university'])): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Campus Ambassador &middot; <?= htmlspecialchars($me['university']) ?></p>
                <?php endif; ?>

                <!-- Referral link -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-1">Your referral link</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Share this link with students. Everyone who registers through it shows up below.</p>
                    <?php if ($referralLink): ?>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input id="referral-link" type="text" readonly value="<?= htmlspecialchars($referralLink) ?>"
                                class="flex-1 min-w-0 px-3 py-2 border rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-sm">
                            <button id="copy-referral-link" type="button" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium">Copy Link</button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Referral code: <span class="font-mono font-semibold"><?= htmlspecialchars($referralCode) ?></span></p>
                    <?php else: ?>
                        <p class="text-sm text-red-600">No referral code has been assigned to your account yet. Please contact the DawoodTech team.</p>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
                    <?php
                    $cards = [
                        'total' => ['Total Referred', 'text-indigo-600 dark:text-indigo-300'],
                        'in_progress' => ['In Process', 'text-amber-600 dark:text-amber-300'],
                        'assessment_passed' => ['Assessment Passed', 'text-emerald-600 dark:text-emerald-300'],
                        'interview' => ['Interview', 'text-blue-600 dark:text-blue-300'],
                        'hire' => ['Hired', 'text-green-600 dark:text-green-300'],
                        'rejected' => ['Rejected', 'text-red-600 dark:text-red-300'],
                    ];
                    foreach ($cards as $key => [$label, $color]): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-4">
                            <p class="text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400"><?= $label ?></p>
                            <p class="text-2xl font-bold mt-1 <?= $color ?>" data-stat="<?= $key ?>">-</p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Latest referrals; the full list is on ambassador_registrations.php -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Latest registrations</h3>
                        <a href="ambassador_registrations.php" class="text-sm font-medium text-indigo-600 dark:text-indigo-300 hover:underline whitespace-nowrap">View all &rarr;</a>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="min-w-full text-xs text-gray-800 dark:text-gray-100">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 uppercase text-gray-500 dark:text-gray-300">
                                <tr>
                                    <th class="px-6 py-3 text-left font-medium">Name</th>
                                    <th class="px-4 py-3 text-left font-medium">Technology</th>
                                    <th class="px-4 py-3 text-left font-medium">Applied On</th>
                                    <th class="px-4 py-3 text-left font-medium">Status</th>
                                    <th class="px-4 py-3 text-left font-medium">Assessment</th>
                                    <th class="px-4 py-3 text-left font-medium">Score</th>
                                </tr>
                            </thead>
                            <tbody id="latest-referrals" class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script src="assets/js/ambassador.js"></script>
    <script>
        async function loadReferrals() {
            const rows = await ambFetchReferrals();
            const count = s => rows.filter(r => r.status === s).length;
            const stats = {
                total: rows.length,
                in_progress: count('new') + count('contact') + count('assessment'),
                assessment_passed: rows.filter(r => r.assessment_status === 'pass').length,
                interview: count('interview'),
                hire: count('hire'),
                rejected: count('rejected'),
            };
            Object.entries(stats).forEach(([k, v]) => {
                const el = document.querySelector(`[data-stat="${k}"]`);
                if (el) el.textContent = v;
            });

            const latest = rows.slice(0, 5);
            document.getElementById('latest-referrals').innerHTML = latest.length
                ? latest.map(r => `
                    <tr>
                        <td class="px-6 py-3">${ambNameCell(r)}</td>
                        <td class="px-4 py-3">${ambEsc(r.technology) || '-'}</td>
                        <td class="px-4 py-3 whitespace-nowrap">${ambEsc(r.applied_on)}</td>
                        <td class="px-4 py-3">${ambBadge(AMB_STATUS, r.status)}</td>
                        <td class="px-4 py-3">${ambBadge(AMB_ASSESSMENT, r.assessment_status)}</td>
                        <td class="px-4 py-3 whitespace-nowrap">${ambScore(r)}</td>
                    </tr>`).join('')
                : '<tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">No students have registered through your link yet. Share it to get started!</td></tr>';
        }

        document.getElementById('copy-referral-link')?.addEventListener('click', async () => {
            const link = document.getElementById('referral-link').value;
            try {
                await navigator.clipboard.writeText(link);
                showToast('success', 'Referral link copied');
            } catch (err) {
                document.getElementById('referral-link').select();
                document.execCommand('copy');
                showToast('success', 'Referral link copied');
            }
        });

        loadReferrals();
    </script>
</body>

</html>
