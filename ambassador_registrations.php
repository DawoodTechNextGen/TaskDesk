<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
require_once './include/ambassador_helper.php';
if (!isAmbassadorUser()) {
    header('Location: index.php');
    exit;
}

$showInternshipType = showInternshipTypeColumn($conn);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'My Registrations - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-10 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Registrations</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Every student who registered through your referral link, and where they are in the internship process.</p>

                <div class="flex flex-wrap gap-2 mb-4" id="status-filters">
                    <?php
                    $filters = [
                        '' => 'All',
                        'new' => 'Applied',
                        'contact' => 'Contacted',
                        'assessment' => 'Assessment',
                        'interview' => 'Interview',
                        'hire' => 'Hired',
                        'rejected' => 'Rejected',
                    ];
                    foreach ($filters as $value => $label): ?>
                        <button type="button" data-status="<?= $value ?>"
                            class="status-filter px-4 py-2 rounded-lg text-sm font-semibold <?= $value === '' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200' ?>">
                            <?= $label ?> <span class="filter-count opacity-75"></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
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

    <script src="assets/js/ambassador.js"></script>
    <script>
        const SHOW_INTERNSHIP_TYPE = <?= json_encode($showInternshipType) ?>;
        let referrals = [];
        let statusFilter = '';

        // Status buttons filter on the raw status kept in `referrals`, so the
        // badge text in the Status column never has to be parsed back.
        $.fn.dataTable.ext.search.push((settings, data, dataIndex, rowData, counter) => {
            if (settings.nTable.id !== 'referralsTable' || statusFilter === '') return true;
            const row = referrals[dataIndex];
            return row && row.status === statusFilter;
        });

        const table = $('#referralsTable').DataTable({
            pageLength: 25,
            order: [[4, 'desc']],
            columnDefs: [{ targets: 3, visible: SHOW_INTERNSHIP_TYPE }],
            language: { emptyTable: 'No students have registered through your link yet.' }
        });

        document.querySelectorAll('.status-filter').forEach(btn => btn.addEventListener('click', () => {
            statusFilter = btn.dataset.status;
            document.querySelectorAll('.status-filter').forEach(b => {
                const on = b === btn;
                b.classList.toggle('bg-indigo-600', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('bg-gray-200', !on);
                b.classList.toggle('dark:bg-gray-700', !on);
                b.classList.toggle('text-gray-800', !on);
                b.classList.toggle('dark:text-gray-200', !on);
            });
            table.draw();
        }));

        async function loadReferrals() {
            referrals = await ambFetchReferrals();

            document.querySelectorAll('.status-filter').forEach(btn => {
                const s = btn.dataset.status;
                const n = s === '' ? referrals.length : referrals.filter(r => r.status === s).length;
                btn.querySelector('.filter-count').textContent = `(${n})`;
            });

            table.clear();
            referrals.forEach(r => {
                table.row.add([
                    ambNameCell(r),
                    ambContactCell(r),
                    ambEsc(r.technology) || '-',
                    ambEsc(r.internship_type),
                    ambEsc(r.applied_on),
                    ambBadge(AMB_STATUS, r.status),
                    ambBadge(AMB_ASSESSMENT, r.assessment_status),
                    ambScore(r)
                ]);
            });
            table.draw();
        }

        loadReferrals();
    </script>
</body>

</html>
