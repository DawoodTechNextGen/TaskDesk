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
$showInternshipType = showInternshipTypeColumn($conn);
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

                <!-- Referred students -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Students you referred</h3>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="referralsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600 text-xs uppercase text-gray-700 dark:text-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left">Name</th>
                                    <th class="px-4 py-3 text-left">Contact</th>
                                    <th class="px-4 py-3 text-left">Technology</th>
                                    <th class="px-4 py-3 text-left">Internship Type</th>
                                    <th class="px-4 py-3 text-left">Applied On</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Assessment</th>
                                    <th class="px-4 py-3 text-left">Score</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const SHOW_INTERNSHIP_TYPE = <?= json_encode($showInternshipType) ?>;

        function esc(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        const STATUS = {
            new: ['Applied', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
            contact: ['Contacted', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'],
            assessment: ['Assessment', 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'],
            interview: ['Interview', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'],
            hire: ['Hired', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
            rejected: ['Rejected', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
        };
        const ASSESSMENT = {
            pending: ['Not started', 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'],
            in_progress: ['In progress', 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'],
            pass: ['Passed', 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'],
            fail: ['Failed', 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'],
        };

        function badge(map, key) {
            if (!key || !map[key]) return '-';
            return `<span class="px-2 py-1 rounded-full text-[10px] font-semibold ${map[key][1]}">${map[key][0]}</span>`;
        }

        const table = $('#referralsTable').DataTable({
            pageLength: 25,
            order: [[4, 'desc']],
            columnDefs: [{ targets: 3, visible: SHOW_INTERNSHIP_TYPE }],
            language: { emptyTable: 'No students have registered through your link yet.' }
        });

        async function loadReferrals() {
            const res = await fetch('controller/ambassador.php?action=my_referrals');
            const json = await res.json();
            if (!json.success) {
                showToast('error', json.message || 'Failed to load your referrals');
                return;
            }

            const rows = json.data;
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

            table.clear();
            rows.forEach(r => {
                const score = (r.assessment_status === 'pass' || r.assessment_status === 'fail') && r.percentage !== null
                    ? `${parseFloat(r.percentage).toFixed(0)}% <span class="text-gray-400">(${esc(r.score)}/${esc(r.total_marks)})</span>`
                    : '-';
                table.row.add([
                    `<div class="font-medium">${esc(r.name)}</div><div class="text-gray-500 dark:text-gray-400">${esc(r.university) || esc(r.city)}</div>`,
                    `<div>${esc(r.email)}</div><div class="text-gray-500 dark:text-gray-400">${esc(r.phone)}</div>`,
                    esc(r.technology) || '-',
                    esc(r.internship_type),
                    esc(r.applied_on),
                    badge(STATUS, r.status),
                    badge(ASSESSMENT, r.assessment_status),
                    score
                ]);
            });
            table.draw();
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
