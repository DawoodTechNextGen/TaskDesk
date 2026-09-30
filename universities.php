<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
require_once './include/university_helper.php';
requirePageAdmin();
ensureUniversitySchema($conn);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Universities - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 pb-10 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-wrap justify-between items-center gap-3 mb-2">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Universities</h2>
                    <div class="flex flex-wrap gap-2">
                        <button id="import-btn" type="button" class="bg-white dark:bg-gray-800 border border-indigo-600 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-gray-700 px-4 py-2.5 rounded-lg font-medium">
                            Import JSON
                        </button>
                        <button id="add-btn" type="button" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium">
                            Add University
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-3xl">
                    Active universities appear in the University dropdown on the internship registration form. If none are active, the form hides the field.
                </p>

                <div class="flex flex-wrap items-center gap-3 mb-4 text-sm">
                    <span class="text-gray-600 dark:text-gray-300"><span id="active-count">-</span> active of <span id="total-count">-</span></span>
                    <button type="button" class="set-all px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200 font-medium" data-active="1">Activate all</button>
                    <button type="button" class="set-all px-3 py-1.5 rounded-lg bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200 font-medium" data-active="0">Deactivate all</button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="universitiesTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">University</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">City</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Registrations</th>
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

    <!-- Add / Edit -->
    <div id="university-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-lg p-6">
            <h3 class="text-xl font-bold dark:text-gray-50 text-gray-950 mb-4" id="university-modal-title">Add University</h3>
            <form id="university-form">
                <input type="hidden" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">University Name</label>
                    <input type="text" name="name" maxlength="191" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">City <span class="text-xs text-gray-500">(optional)</span></label>
                    <input type="text" name="city" maxlength="100" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import -->
    <div id="import-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold dark:text-gray-50 text-gray-950 mb-2">Import Universities (JSON)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Upload a .json file or paste JSON below. Universities already in the list are skipped. Two formats work:</p>
            <pre class="text-xs bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 rounded-lg p-3 mb-4 overflow-x-auto">["University of the Punjab", "NED University of Engineering and Technology"]

[
  {"name": "University of the Punjab", "city": "Lahore"},
  {"name": "Habib University", "city": "Karachi", "is_active": false}
]</pre>
            <form id="import-form">
                <div class="mb-3">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">JSON file</label>
                    <input type="file" name="file" accept=".json,application/json" class="block w-full text-sm text-gray-700 dark:text-gray-200">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">or paste JSON</label>
                    <textarea name="json" rows="8" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 font-mono text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Import</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const table = $('#universitiesTable').DataTable({
            pageLength: 25,
            order: [[0, 'asc']],
            columnDefs: [{ targets: 4, orderable: false }]
        });
        let universities = [];

        function esc(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        async function post(data) {
            const res = await fetch('controller/universities.php', { method: 'POST', body: data });
            const json = await res.json();
            showToast(json.success ? 'success' : 'error', json.message);
            return json;
        }

        async function loadUniversities() {
            const res = await fetch('controller/universities.php?action=list');
            const json = await res.json();
            if (!json.success) {
                showToast('error', json.message || 'Failed to load universities');
                return;
            }
            universities = json.data;
            document.getElementById('total-count').textContent = universities.length;
            document.getElementById('active-count').textContent = universities.filter(u => Number(u.is_active) === 1).length;

            table.clear();
            universities.forEach(u => {
                const active = Number(u.is_active) === 1;
                table.row.add([
                    `<span class="font-medium">${esc(u.name)}</span>`,
                    esc(u.city) || '-',
                    u.registrations,
                    active
                        ? '<span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">Active</span>'
                        : '<span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Inactive</span>',
                    `<div class="inline-block text-left">
                        <button type="button" class="actions-toggle-btn px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold inline-flex items-center gap-1">
                            Actions
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div class="actions-dropdown-menu hidden fixed w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50 overflow-hidden">
                            <button class="edit-university w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40" data-id="${u.id}">Edit</button>
                            <button class="toggle-university w-full text-left px-3 py-2 text-xs font-medium ${active ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40' : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40'}" data-id="${u.id}" data-active="${active ? 0 : 1}">${active ? 'Deactivate' : 'Activate'}</button>
                            <button class="delete-university w-full text-left px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40" data-id="${u.id}">Remove</button>
                        </div>
                    </div>`
                ]);
            });
            table.draw(false);
        }

        // Row "Actions" menu, positioned `fixed` so the table's scroll box can't clip it.
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
        $(document).on('click', () => $('.actions-dropdown-menu').addClass('hidden'));
        document.addEventListener('scroll', () => $('.actions-dropdown-menu').addClass('hidden'), true);

        const uniModal = document.getElementById('university-modal');
        const uniForm = document.getElementById('university-form');
        const importModal = document.getElementById('import-modal');
        const importForm = document.getElementById('import-form');

        function openUniversityModal(u) {
            uniForm.reset();
            uniForm.elements.id.value = u ? u.id : '';
            uniForm.elements.name.value = u ? u.name : '';
            uniForm.elements.city.value = u ? (u.city || '') : '';
            document.getElementById('university-modal-title').textContent = u ? 'Edit University' : 'Add University';
            uniModal.classList.remove('hidden');
        }

        document.getElementById('add-btn').addEventListener('click', () => openUniversityModal(null));
        document.getElementById('import-btn').addEventListener('click', () => {
            importForm.reset();
            importModal.classList.remove('hidden');
        });
        document.querySelectorAll('.close-modal').forEach(b => b.addEventListener('click', () => b.closest('.modal').classList.add('hidden')));

        uniForm.addEventListener('submit', async e => {
            e.preventDefault();
            const fd = new FormData(uniForm);
            fd.append('action', fd.get('id') ? 'update' : 'add');
            const json = await post(fd);
            if (json.success) {
                uniModal.classList.add('hidden');
                loadUniversities();
            }
        });

        importForm.addEventListener('submit', async e => {
            e.preventDefault();
            const fd = new FormData(importForm);
            const hasFile = importForm.elements.file.files.length > 0;
            if (!hasFile && !String(fd.get('json')).trim()) {
                showToast('error', 'Choose a JSON file or paste JSON first');
                return;
            }
            if (!hasFile) fd.delete('file');
            fd.append('action', 'import');
            const json = await post(fd);
            if (json.success) {
                importModal.classList.add('hidden');
                loadUniversities();
            }
        });

        $('#universitiesTable tbody').on('click', '.edit-university', function() {
            const u = universities.find(x => String(x.id) === String(this.dataset.id));
            if (u) openUniversityModal(u);
        });

        $('#universitiesTable tbody').on('click', '.toggle-university', async function() {
            const json = await post(new URLSearchParams({ action: 'toggle', id: this.dataset.id, is_active: this.dataset.active }));
            if (json.success) loadUniversities();
        });

        $('#universitiesTable tbody').on('click', '.delete-university', async function() {
            if (!confirm('Remove this university from the list? Past registrations keep their university name.')) return;
            const json = await post(new URLSearchParams({ action: 'delete', id: this.dataset.id }));
            if (json.success) loadUniversities();
        });

        document.querySelectorAll('.set-all').forEach(btn => btn.addEventListener('click', async () => {
            const activating = btn.dataset.active === '1';
            if (!confirm(activating ? 'Activate every university?' : 'Deactivate every university? The University field will be hidden from the registration form.')) return;
            const json = await post(new URLSearchParams({ action: 'set_all', is_active: btn.dataset.active }));
            if (json.success) loadUniversities();
        }));

        loadUniversities();
    </script>
</body>

</html>
