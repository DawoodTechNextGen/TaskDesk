<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
// Campus Ambassador accounts are Admin-managed, like Collaborators.
requirePageAdmin();
require_once "./include/university_helper.php";
ensureUniversitySchema($conn);
$universityOptions = $conn->query("SELECT name FROM universities WHERE is_active = 1 ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Campus Ambassadors - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Campus Ambassadors</h2>
                    <button id="add-ambassador-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium">
                        Add Ambassador
                    </button>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Each ambassador gets a personal referral link to the registration form. They can log in and view only the students who registered through their link, without making any changes.</p>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="ambassadorsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">University</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Referral Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Referrals</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Hired</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th>
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

    <div id="ambassador-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-lg p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold dark:text-gray-50 text-gray-950" id="ambassador-modal-title">Add Ambassador</h3>
                <button type="button" class="close-ambassador-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="ambassador-form">
                <input type="hidden" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">University</label>
                    <input type="text" name="university" required list="university-options" autocomplete="off" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    <datalist id="university-options">
                        <?php foreach ($universityOptions as $u): ?><option value="<?= htmlspecialchars($u["name"]) ?>"></option><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">
                        Password
                        <span class="text-xs text-gray-500" id="password-hint">(Leave blank to auto-generate)</span>
                    </label>
                    <input type="text" name="password" autocomplete="new-password" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="close-ambassador-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const table = $('#ambassadorsTable').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [{ targets: 7, orderable: false }]
        });
        let ambassadors = [];

        function esc(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        async function loadAmbassadors() {
            const res = await fetch('controller/ambassadors.php?action=list');
            const json = await res.json();
            if (!json.success) {
                showToast('error', json.message || 'Failed to load ambassadors');
                return;
            }
            ambassadors = json.data;
            table.clear();
            ambassadors.forEach(a => {
                const active = Number(a.status) === 1;
                table.row.add([
                    esc(a.name),
                    esc(a.university) || '-',
                    esc(a.email),
                    `<span class="font-mono font-semibold">${esc(a.referral_code)}</span>`,
                    a.total_referrals,
                    a.hired,
                    active
                        ? '<span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Active</span>'
                        : '<span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inactive</span>',
                    `<div class="inline-block text-left">
                        <button type="button" class="actions-toggle-btn px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold inline-flex items-center gap-1">
                            Actions
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div class="actions-dropdown-menu hidden fixed w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 overflow-hidden">
                            <button class="copy-link w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" data-link="${esc(a.referral_link)}">Copy Link</button>
                            <button type="button" class="edit-ambassador w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" data-id="${a.id}">Edit</button>
                            ${active ? `<button class="resend-welcome w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" data-id="${a.id}">Resend Welcome Email</button>` : ''}
                            <button class="toggle-ambassador w-full text-left px-3 py-2 text-xs font-medium ${active ? 'text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'}" data-id="${a.id}" data-status="${active ? 0 : 1}">${active ? 'Deactivate' : 'Activate'}</button>
                        </div>
                    </div>`
                ]);
            });
            table.draw(false);
        }

        // Row "Actions" menu. Positioned `fixed` next to its button so the
        // table's scroll container can't clip it when there are only a few rows.
        $(document).on('click', '.actions-toggle-btn', function(e) {
            e.stopPropagation();
            const menu = $(this).siblings('.actions-dropdown-menu');
            const wasHidden = menu.hasClass('hidden');
            $('.actions-dropdown-menu').addClass('hidden');
            if (!wasHidden) return;
            const rect = this.getBoundingClientRect();
            menu.removeClass('hidden');
            const menuHeight = menu.outerHeight();
            const openUp = rect.bottom + 4 + menuHeight > window.innerHeight;
            menu.css({
                top: openUp ? rect.top - menuHeight - 4 : rect.bottom + 4,
                left: Math.max(8, rect.right - menu.outerWidth())
            });
        });
        $(document).on('click', function() {
            $('.actions-dropdown-menu').addClass('hidden');
        });
        document.addEventListener('scroll', () => $('.actions-dropdown-menu').addClass('hidden'), true);

        const modal = document.getElementById('ambassador-modal');
        const form = document.getElementById('ambassador-form');

        function openModal(a) {
            form.reset();
            form.elements.id.value = a ? a.id : '';
            form.elements.name.value = a ? a.name : '';
            form.elements.email.value = a ? a.email : '';
            form.elements.university.value = a ? (a.university || '') : '';
            form.elements.password.value = a ? (a.plain_password || '') : '';
            document.getElementById('ambassador-modal-title').textContent = a ? 'Edit Ambassador' : 'Add Ambassador';
            document.getElementById('password-hint').textContent = a ? '(Leave blank to keep the current one)' : '(Leave blank to auto-generate)';
            modal.classList.remove('hidden');
        }

        document.getElementById('add-ambassador-btn').addEventListener('click', () => openModal(null));
        document.querySelectorAll('.close-ambassador-modal').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));

        form.addEventListener('submit', async e => {
            e.preventDefault();
            const fd = new FormData(form);
            fd.append('action', fd.get('id') ? 'update' : 'create');
            const res = await fetch('controller/ambassadors.php', { method: 'POST', body: fd });
            const json = await res.json();
            showToast(json.success ? 'success' : 'error', json.message);
            if (json.success) {
                modal.classList.add('hidden');
                loadAmbassadors();
            }
        });

        $('#ambassadorsTable tbody').on('click', '.edit-ambassador', function() {
            const a = ambassadors.find(x => String(x.id) === String(this.dataset.id));
            if (a) openModal(a);
        });

        $('#ambassadorsTable tbody').on('click', '.resend-welcome', async function() {
            const a = ambassadors.find(x => String(x.id) === String(this.dataset.id));
            if (!a || !confirm(`Send the welcome email (login + referral link) to ${a.email} again?`)) return;
            const res = await fetch('controller/ambassadors.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'resend_welcome', id: this.dataset.id })
            });
            const json = await res.json();
            showToast(json.success ? 'success' : 'error', json.message);
        });

        $('#ambassadorsTable tbody').on('click', '.copy-link', async function() {
            try {
                await navigator.clipboard.writeText(this.dataset.link);
                showToast('success', 'Referral link copied');
            } catch (err) {
                window.prompt('Copy the referral link:', this.dataset.link);
            }
        });

        $('#ambassadorsTable tbody').on('click', '.toggle-ambassador', async function() {
            const activating = this.dataset.status === '1';
            if (!activating && !confirm('Deactivate this ambassador? They will be signed out and their link will stop being credited.')) return;
            const res = await fetch('controller/ambassadors.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'toggle_status', id: this.dataset.id, status: this.dataset.status })
            });
            const json = await res.json();
            showToast(json.success ? 'success' : 'error', json.message);
            if (json.success) loadAmbassadors();
        });

        loadAmbassadors();
    </script>
</body>

</html>
