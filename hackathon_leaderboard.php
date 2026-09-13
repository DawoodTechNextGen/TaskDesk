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
$page_title = 'Leaderboard - ' . $hackathon['title'] . ' - TaskDesk';
include_once "./include/headerLinks.php"; ?>

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
                    <span class="text-gray-600 dark:text-gray-300">Leaderboard</span>
                </div>
                <div class="flex flex-wrap gap-3 justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?= htmlspecialchars($hackathon['title']) ?></h2>
                    <div class="flex items-center gap-3">
                        <a href="hackathon_registrations.php?id=<?= $hackathonId ?>" class="bg-teal-600 text-white px-4 py-2 rounded-lg text-sm">View Registrations</a>
                        <button class="open-modal bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm" data-modal="entry-form-modal" data-mode="create">+ Add Entry</button>
                    </div>
                </div>

                <?php if ($hackathon['status'] === 'completed'): ?>
                    <div class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-200">
                        This hackathon is marked <strong>Completed</strong>, so its leaderboard is now featured as "Recent Results &amp; Leaderboard" on the public landing page.
                    </div>
                <?php else: ?>
                    <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-200">
                        Once results are finalized here, set this hackathon's status to <strong>Completed</strong> (from the Hackathons list) to feature it as "Recent Results &amp; Leaderboard" on the public site immediately.
                    </div>
                <?php endif; ?>

                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Leaderboard Entries</h2>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="leaderboardTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Rank</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Display Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Project</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Score</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Prize</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Linked Registration</th>
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

    <!-- Add / Edit Entry Modal -->
    <div id="entry-form-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-4">
                <h3 id="entry-form-title" class="text-xl font-bold text-gray-950 dark:text-gray-50">Add Leaderboard Entry</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="entry-form">
                <input type="hidden" name="id" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Rank *</label>
                        <input type="number" name="rank" min="1" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Score</label>
                        <input type="number" name="score" step="0.01" min="0" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Display Name *</label>
                        <input type="text" name="display_name" required maxlength="150" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Team or participant name shown publicly">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Project Title</label>
                        <input type="text" name="project_title" maxlength="150" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Prize</label>
                        <input type="text" name="prize" maxlength="100" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. PKR 50,000 + Certificates">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Link to a Registration (optional)</label>
                        <input type="text" id="registration-search" autocomplete="off" placeholder="Search by team or participant name/email..." class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <input type="hidden" name="registration_id" id="registration-id-input" value="">
                        <div id="registration-search-results" class="hidden mt-1 border rounded-lg bg-white dark:bg-gray-700 max-h-40 overflow-y-auto text-sm"></div>
                        <div id="registration-selected" class="hidden mt-2 flex items-center gap-2 text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200 px-3 py-2 rounded-lg">
                            <span id="registration-selected-label" class="flex-1"></span>
                            <button type="button" id="registration-clear" class="text-indigo-600 dark:text-indigo-300 hover:underline">Clear</button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave unlinked for manually-entered or backfilled winners.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" id="entry-form-submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="delete-entry-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-950 dark:text-gray-50 mb-4">Delete this entry?</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">This will remove "<span id="delete-entry-name" class="font-semibold"></span>" from the leaderboard.</p>
            <div class="flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="button" id="confirm-delete-entry" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
            </div>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const hackathonId = <?= (int)$hackathonId ?>;
        let dataTable;
        let entriesCache = [];
        let deleteTargetId = null;
        let searchDebounce = null;

        function esc(str) {
            return (str ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        async function loadEntries() {
            const res = await fetch(`controller/hackathon_leaderboard.php?action=get&hackathon_id=${hackathonId}`);
            const result = await res.json();
            if (!result.success) {
                showToast('error', result.message || 'Failed to load leaderboard.');
                return;
            }

            entriesCache = result.data;
            dataTable.clear();
            entriesCache.forEach(entry => {
                const linked = entry.registration_id
                    ? esc(entry.registrant_team_name || entry.registrant_name || ('#' + entry.registration_id))
                    : '<span class="text-gray-400">Not linked</span>';

                dataTable.row.add([
                    `<span class="font-bold">#${entry.rank}</span>`,
                    esc(entry.display_name),
                    entry.project_title ? esc(entry.project_title) : '-',
                    entry.score !== null ? esc(entry.score) : '-',
                    entry.prize ? esc(entry.prize) : '-',
                    linked,
                    `
                    <div class="flex items-center gap-3">
                        <button class="edit-entry text-blue-600" data-id="${entry.id}" title="Edit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12V10.5M13.5 2H12C7.28595 2 4.92893 2 3.46447 3.46447C2.49073 4.43821 2.16444 5.80655 2.0551 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M16.652 3.45506L17.3009 2.80624C18.3759 1.73125 20.1188 1.73125 21.1938 2.80624C22.2687 3.88124 22.2687 5.62415 21.1938 6.69914L20.5449 7.34795M16.652 3.45506C16.652 3.45506 16.7331 4.83379 17.9497 6.05032C19.1662 7.26685 20.5449 7.34795 20.5449 7.34795M16.652 3.45506L10.6872 9.41993C10.2832 9.82394 10.0812 10.0259 9.90743 10.2487C9.70249 10.5114 9.52679 10.7957 9.38344 11.0965C9.26191 11.3515 9.17157 11.6225 8.99089 12.1646L8.41242 13.9M20.5449 7.34795L17.5625 10.3304" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </button>
                        <button class="delete-entry text-red-600" data-id="${entry.id}" data-name="${esc(entry.display_name)}" title="Delete">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </button>
                    </div>
                    `
                ]);
            });
            dataTable.draw();
        }

        function resetForm() {
            const form = document.getElementById('entry-form');
            form.reset();
            form.querySelector('[name="id"]').value = '';
            document.getElementById('registration-id-input').value = '';
            document.getElementById('registration-search').value = '';
            document.getElementById('registration-selected').classList.add('hidden');
            document.getElementById('registration-search-results').classList.add('hidden');
            document.getElementById('entry-form-title').textContent = 'Add Leaderboard Entry';
            document.getElementById('entry-form-submit').textContent = 'Save Entry';
        }

        function selectRegistration(reg) {
            document.getElementById('registration-id-input').value = reg.id;
            const label = reg.registration_type === 'team' ? (reg.team_name || reg.name) : reg.name;
            document.getElementById('registration-selected-label').textContent = `${label} (${reg.email})`;
            document.getElementById('registration-selected').classList.remove('hidden');
            document.getElementById('registration-search-results').classList.add('hidden');
            document.getElementById('registration-search').value = '';
        }

        function openEditModal(id) {
            const entry = entriesCache.find(x => String(x.id) === String(id));
            if (!entry) return;

            const form = document.getElementById('entry-form');
            resetForm();
            form.querySelector('[name="id"]').value = entry.id;
            form.querySelector('[name="rank"]').value = entry.rank;
            form.querySelector('[name="score"]').value = entry.score ?? '';
            form.querySelector('[name="display_name"]').value = entry.display_name;
            form.querySelector('[name="project_title"]').value = entry.project_title || '';
            form.querySelector('[name="prize"]').value = entry.prize || '';

            if (entry.registration_id) {
                selectRegistration({
                    id: entry.registration_id,
                    registration_type: entry.registrant_team_name ? 'team' : 'individual',
                    team_name: entry.registrant_team_name,
                    name: entry.registrant_name,
                    email: ''
                });
                document.getElementById('registration-selected-label').textContent = entry.registrant_team_name || entry.registrant_name || ('#' + entry.registration_id);
            }

            document.getElementById('entry-form-title').textContent = 'Edit Leaderboard Entry';
            document.getElementById('entry-form-submit').textContent = 'Update Entry';
            document.getElementById('entry-form-modal').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            dataTable = $('#leaderboardTable').DataTable({
                ordering: false,
                pageLength: 10
            });

            document.getElementById('registration-search').addEventListener('input', (e) => {
                clearTimeout(searchDebounce);
                const q = e.target.value.trim();
                if (q.length < 2) {
                    document.getElementById('registration-search-results').classList.add('hidden');
                    return;
                }
                searchDebounce = setTimeout(async () => {
                    const res = await fetch(`controller/hackathon_leaderboard.php?action=search_registrations&hackathon_id=${hackathonId}&q=${encodeURIComponent(q)}`);
                    const json = await res.json();
                    const resultsBox = document.getElementById('registration-search-results');
                    if (!json.success || !json.data.length) {
                        resultsBox.innerHTML = '<div class="px-3 py-2 text-gray-500">No matches</div>';
                        resultsBox.classList.remove('hidden');
                        return;
                    }
                    resultsBox.innerHTML = json.data.map(r => {
                        const label = r.registration_type === 'team' ? (r.team_name || r.name) : r.name;
                        return `<div class="registration-option px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer" data-reg='${JSON.stringify(r).replace(/'/g, "&#39;")}'>${esc(label)} <span class="text-gray-400">(${esc(r.email)})</span></div>`;
                    }).join('');
                    resultsBox.classList.remove('hidden');
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (e.target.closest('.registration-option')) {
                    const reg = JSON.parse(e.target.closest('.registration-option').dataset.reg);
                    selectRegistration(reg);
                }
                if (e.target.closest('#registration-clear')) {
                    document.getElementById('registration-id-input').value = '';
                    document.getElementById('registration-selected').classList.add('hidden');
                }
                if (e.target.closest('.edit-entry')) {
                    openEditModal(e.target.closest('.edit-entry').dataset.id);
                }
                if (e.target.closest('.delete-entry')) {
                    const btn = e.target.closest('.delete-entry');
                    deleteTargetId = btn.dataset.id;
                    document.getElementById('delete-entry-name').textContent = btn.dataset.name;
                    document.getElementById('delete-entry-modal').classList.remove('hidden');
                }
            });

            document.getElementById('entry-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                formData.append('hackathon_id', hackathonId);
                const id = e.target.querySelector('[name="id"]').value;
                formData.append('action', id ? 'update' : 'create');

                const submitBtn = document.getElementById('entry-form-submit');
                submitBtn.disabled = true;
                try {
                    const res = await fetch('controller/hackathon_leaderboard.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) {
                        document.querySelector('#entry-form-modal .close-modal').click();
                        loadEntries();
                    }
                } finally {
                    submitBtn.disabled = false;
                }
            });

            document.getElementById('confirm-delete-entry').addEventListener('click', async () => {
                if (!deleteTargetId) return;
                const res = await fetch('controller/hackathon_leaderboard.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'delete', id: deleteTargetId, hackathon_id: hackathonId })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                document.getElementById('delete-entry-modal').classList.add('hidden');
                if (json.success) loadEntries();
            });

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.modal').classList.add('hidden');
                    if (e.target.closest('#entry-form-modal')) resetForm();
                });
            });

            document.querySelectorAll('.open-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.dataset.mode === 'create') resetForm();
                    document.getElementById(btn.dataset.modal).classList.remove('hidden');
                });
            });

            loadEntries();
        });
    </script>
</body>

</html>
