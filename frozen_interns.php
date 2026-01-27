<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<?php
$page_title = 'Frozen Interns - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Frozen Interns</h2>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Interns on Freeze</h2>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="frozenTable" class="min-w-full">
                            <thead class="bg-blue-200 dark:bg-blue-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Technology</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Freeze Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Reason</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs text-gray-800 dark:text-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const table = $('#frozenTable').DataTable({
            ordering: false,
            pageLength: 10,
            language: {
                emptyTable: "No frozen interns",
                info: "Showing _START_ to _END_ of _TOTAL_ frozen interns",
                infoEmpty: "Showing 0 to 0 of 0 frozen interns"
            }
        });

        async function loadFrozenInterns() {
            try {
                const res = await fetch('controller/user.php?action=get_frozen_internees');
                const json = await res.json();

                if (json.success) {
                    table.clear();
                    json.data.forEach(u => {
                        const freezePeriod = `${formatDate(u.freeze_start_date)} - ${formatDate(u.freeze_end_date)}`;
                        
                        table.row.add([
                            u.id,
                            u.name,
                            u.email || '<em class="text-gray-400">No email</em>',
                            u.tech_name ? `<span class="text-blue-600 font-medium">${u.tech_name}</span>` : '<em class="text-gray-400">Not assigned</em>',
                            freezePeriod,
                            `<span class="text-sm text-gray-600 dark:text-gray-300">${u.freeze_reason || 'N/A'}</span>`
                        ]);
                    });
                    table.draw(false);
                }
            } catch (err) {
                console.error("Failed to load frozen interns:", err);
                showToast('error', 'Failed to load frozen interns');
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return d.toLocaleDateString('en-US', options);
        }

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white font-medium shadow-lg ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadFrozenInterns();
        });
    </script>
</body>

</html>
