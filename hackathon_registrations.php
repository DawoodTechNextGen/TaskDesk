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
include_once './include/hackathon_helper.php';
requirePageModule(MODULE_HACKATHONS);

$hackathonId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM hackathons WHERE id = ?");
$stmt->bind_param('i', $hackathonId);
$stmt->execute();
$hackathon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$hackathon) {
    header('Location: hackathons.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Registrations - ' . $hackathon['title'] . ' - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<style>
    .expand-icon {
        width: 10px;
        height: 10px;
        display: inline-block;
        position: relative;
        cursor: pointer;
        transition: transform 300ms ease;
    }
    .expand-icon .bar {
        position: absolute;
        background-color: currentColor;
        border-radius: 2px;
    }
    .expand-icon .horizontal {
        width: 100%;
        height: 1.5px;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
    }
    .expand-icon .vertical {
        height: 100%;
        width: 1.5px;
        left: 50%;
        top: 0;
        transform: translateX(-50%);
    }
    tr.shown .expand-icon {
        transform: rotate(45deg);
    }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex items-center gap-2 mb-1 text-sm">
                    <a href="hackathons.php" class="text-indigo-600 hover:underline">Hackathons</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-600 dark:text-gray-300">Registrations</span>
                </div>
                <div class="flex flex-wrap gap-3 justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($hackathon['title']) ?></h2>
                    <div class="flex items-center gap-3">
                        <a href="hackathon_leaderboard.php?id=<?= $hackathonId ?>" class="bg-amber-600 text-white px-4 py-2 rounded-lg text-sm">Manage Leaderboard</a>
                        <button id="export-csv" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-sm">Export CSV</button>
                    </div>
                </div>

                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-2">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Registrations</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Use the search box below to filter by name, email or team name. Click a row to see full details.</p>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="registrationsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-4 py-3 border border-gray-300 dark:border-gray-600"></th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Name / Team</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">WhatsApp</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">City</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Track</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="delete-registration-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-950 dark:text-gray-50 mb-4">Delete this registration?</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">This will remove "<span id="delete-registration-name" class="font-semibold"></span>" and, if it's a team, all of its team members. This cannot be undone.</p>
            <div class="flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="button" id="confirm-delete-registration" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const hackathonId = <?= (int)$hackathonId ?>;
        const hackathonTitle = <?= json_encode($hackathon['title']) ?>;
        let dataTable;
        let registrationsCache = [];
        let deleteTargetId = null;

        function esc(str) {
            return (str ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function formatDisplayDate(dateString) {
            if (!dateString) return 'N/A';
            const d = new Date(dateString.replace(' ', 'T'));
            if (isNaN(d)) return 'N/A';
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' +
                d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        }

        function formatDetails(reg) {
            let membersHtml = '';
            if (reg.registration_type === 'team' && reg.members && reg.members.length) {
                membersHtml = `
                    <table class="min-w-full text-xs mt-2 border border-gray-200 dark:border-gray-700">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left">Member Name</th>
                                <th class="px-3 py-2 text-left">Email</th>
                                <th class="px-3 py-2 text-left">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${reg.members.map(m => `
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-3 py-2">${esc(m.name)}</td>
                                    <td class="px-3 py-2">${esc(m.email)}</td>
                                    <td class="px-3 py-2">${String(m.is_leader) === '1' ? '<span class=\"px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-semibold\">Leader</span>' : 'Member'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            return `
                <div class="expand-wrapper p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <p class="text-sm"><span class="font-semibold">Project Idea:</span> ${reg.project_idea ? esc(reg.project_idea) : '<span class="text-gray-400">Not provided</span>'}</p>
                    ${membersHtml}
                </div>
            `;
        }

        async function loadRegistrations() {
            const res = await fetch(`controller/hackathon_registrations.php?action=get&hackathon_id=${hackathonId}`);
            const result = await res.json();
            if (!result.success) {
                showToast('error', result.message || 'Failed to load registrations.');
                return;
            }

            registrationsCache = result.data;
            dataTable.clear();
            registrationsCache.forEach(reg => {
                const typeLabel = reg.registration_type === 'team'
                    ? `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">Team</span>`
                    : `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Individual</span>`;
                const nameOrTeam = reg.registration_type === 'team' ? (reg.team_name || reg.name) : reg.name;

                dataTable.row.add([
                    '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>',
                    typeLabel,
                    esc(nameOrTeam),
                    esc(reg.email),
                    esc(reg.mbl_number),
                    esc(reg.city || '-'),
                    esc(reg.technology_name || '-'),
                    formatDisplayDate(reg.created_at),
                    `<button class="delete-registration text-red-600" data-id="${reg.id}" data-name="${esc(nameOrTeam)}" title="Delete">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                    </button>`
                ], false);
            });
            dataTable.draw();
        }

        function exportCsv() {
            const headers = ['Registration ID', 'Type', 'Team/Participant Name', 'Row (Leader/Member/Individual)', 'Row Name', 'Row Email', 'WhatsApp', 'City', 'Track', 'Project Idea', 'Submitted At'];
            const rows = [headers];

            registrationsCache.forEach(reg => {
                const nameOrTeam = reg.registration_type === 'team' ? (reg.team_name || reg.name) : reg.name;
                if (reg.registration_type === 'team' && reg.members && reg.members.length) {
                    reg.members.forEach(m => {
                        rows.push([
                            reg.id, reg.registration_type, nameOrTeam,
                            String(m.is_leader) === '1' ? 'Leader' : 'Member',
                            m.name, m.email, reg.mbl_number, reg.city || '', reg.technology_name || '',
                            reg.project_idea || '', reg.created_at
                        ]);
                    });
                } else {
                    rows.push([
                        reg.id, reg.registration_type, nameOrTeam, 'Individual',
                        reg.name, reg.email, reg.mbl_number, reg.city || '', reg.technology_name || '',
                        reg.project_idea || '', reg.created_at
                    ]);
                }
            });

            const csv = rows.map(row => row.map(cell => {
                let text = (cell ?? '').toString();
                if (text.includes(',') || text.includes('\n') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                return text;
            }).join(',')).join('\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `Hackathon_${hackathonTitle.replace(/[^a-z0-9]+/gi, '_')}_Registrations.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        document.addEventListener('DOMContentLoaded', () => {
            dataTable = $('#registrationsTable').DataTable({
                ordering: false,
                pageLength: 10,
                columnDefs: [{ orderable: false, targets: 0, className: 'details-control cursor-pointer text-center select-none' }]
            });

            $('#registrationsTable tbody').on('click', 'td.details-control', function () {
                const tr = $(this).closest('tr');
                const row = dataTable.row(tr);
                const regId = tr.find('.delete-registration').data('id');
                const reg = registrationsCache.find(r => String(r.id) === String(regId));

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else if (reg) {
                    row.child(formatDetails(reg)).show();
                    tr.addClass('shown');
                }
            });

            document.getElementById('export-csv').addEventListener('click', exportCsv);

            document.addEventListener('click', (e) => {
                if (e.target.closest('.delete-registration')) {
                    const btn = e.target.closest('.delete-registration');
                    deleteTargetId = btn.dataset.id;
                    document.getElementById('delete-registration-name').textContent = btn.dataset.name;
                    document.getElementById('delete-registration-modal').classList.remove('hidden');
                }
            });

            document.getElementById('confirm-delete-registration').addEventListener('click', async () => {
                if (!deleteTargetId) return;
                const res = await fetch('controller/hackathon_registrations.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'delete', id: deleteTargetId, hackathon_id: hackathonId })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                document.getElementById('delete-registration-modal').classList.add('hidden');
                if (json.success) loadRegistrations();
            });

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', (e) => e.target.closest('.modal').classList.add('hidden'));
            });

            loadRegistrations();
        });
    </script>
</body>

</html>
