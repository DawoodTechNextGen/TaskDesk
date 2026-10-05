<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4 && $_SESSION['user_role'] != 5)) {
    header('Location: index.php');
    exit;
}
include_once './include/connection.php';
include_once './include/bootcamp_helper.php';
requirePageModule(MODULE_BOOTCAMP);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Bootcamp Completions - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-wrap gap-3 justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Bootcamp Completions</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">How many students joined and completed each bootcamp, and how many certificates went out.</p>
                    </div>
                    <a href="bootcamp_registrations.php?status=completed" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">All Completed Students</a>
                </div>

                <!-- Totals across every bootcamp -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <?php
                    $cards = [
                        ['id' => 'total-bootcamps', 'label' => 'Bootcamps', 'color' => 'text-indigo-600'],
                        ['id' => 'total-joined', 'label' => 'Students Joined', 'color' => 'text-green-600'],
                        ['id' => 'total-completed', 'label' => 'Completed', 'color' => 'text-purple-600'],
                        ['id' => 'total-certificates', 'label' => 'Certificates Sent', 'color' => 'text-blue-600'],
                        ['id' => 'total-rate', 'label' => 'Completion Rate', 'color' => 'text-amber-600'],
                    ];
                    foreach ($cards as $card): ?>
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-5">
                            <div id="<?= $card['id'] ?>" class="text-3xl font-bold <?= $card['color'] ?>">–</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= $card['label'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Bootcamp-wise Record</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Joined = Enrolled + Completed. Completion rate = Completed ÷ Joined. Click a number to open that list.</p>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="completionsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <?php foreach (['Bootcamp', 'Sign-ups', 'Rejected', 'Joined', 'Still Enrolled', 'Completed', 'Certificates', 'Completion Rate'] as $h): ?>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600"><?= $h ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 text-sm text-gray-800 dark:text-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        function esc(str) {
            return (str ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function listLink(bootcampId, status, value) {
            if (!value) return '<span class="text-gray-400">0</span>';
            const qs = new URLSearchParams({ bootcamp_id: bootcampId });
            if (status) qs.set('status', status);
            return `<a href="bootcamp_registrations.php?${qs}" class="text-indigo-600 hover:underline font-medium">${value}</a>`;
        }

        function rateCell(rate) {
            if (rate === null) return '<span class="text-gray-400">–</span>';
            const bar = rate >= 75 ? 'bg-green-500' : (rate >= 40 ? 'bg-yellow-500' : 'bg-red-500');
            return `<div class="min-w-[110px]"><div class="font-semibold mb-1">${rate}%</div>
                <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full"><div class="h-1.5 rounded-full ${bar}" style="width:${rate}%"></div></div></div>`;
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const table = $('#completionsTable').DataTable({ ordering: false, pageLength: 25 });

            const res = await fetch('controller/bootcamp_registrations.php?action=completion_stats');
            const json = await res.json();
            if (!json.success) {
                showToast('error', json.message || 'Failed to load completion numbers');
                return;
            }

            let joined = 0, completed = 0, certs = 0;
            json.data.forEach(b => {
                const bJoined = b.enrolled_count + b.completed_count;
                joined += bJoined;
                completed += b.completed_count;
                certs += b.certificates_sent;

                table.row.add([
                    `<div class="font-medium">${esc(b.title)}</div><div class="text-xs text-gray-500 dark:text-gray-400 capitalize">${esc(b.bootcamp_status)}</div>`,
                    listLink(b.id, '', b.total),
                    listLink(b.id, 'rejected', b.rejected_count),
                    bJoined,
                    listLink(b.id, 'enrolled', b.enrolled_count),
                    listLink(b.id, 'completed', b.completed_count),
                    `${b.certificates_sent} sent${b.certificates_pending ? ` <span class="text-red-600">(${b.certificates_pending} pending)</span>` : ''}`,
                    rateCell(b.completion_rate)
                ]);
            });
            table.draw();

            document.getElementById('total-bootcamps').textContent = json.data.length;
            document.getElementById('total-joined').textContent = joined;
            document.getElementById('total-completed').textContent = completed;
            document.getElementById('total-certificates').textContent = certs;
            document.getElementById('total-rate').textContent = joined ? Math.round(completed / joined * 100) + '%' : '–';
        });
    </script>
</body>

</html>
