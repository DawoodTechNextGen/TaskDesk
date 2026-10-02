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
                    <div class="flex gap-2">
                        <button id="make-intern-btn" class="bg-white dark:bg-gray-800 border border-indigo-600 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-gray-700 px-5 py-2.5 rounded-lg font-medium">
                            Make Intern an Ambassador
                        </button>
                        <button id="add-ambassador-btn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium">
                            Add Ambassador
                        </button>
                    </div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Each ambassador gets a personal referral link to the registration form. They can log in and view only the students who registered through their link, without making any changes. An intern made an ambassador keeps their intern account and sees "My Referrals" in their sidebar.</p>

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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Student Contacts</th>
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
                <!-- Shown only in "Make Intern an Ambassador" mode -->
                <div class="mb-4" id="intern-field">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Intern</label>
                    <div id="intern-picker" class="relative w-full">
                        <select name="intern_id" class="searchable-select hidden">
                            <option value="">Select intern</option>
                        </select>
                        <input type="text" class="searchable-input w-full px-3 py-2 pr-10 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer" placeholder="Search intern by name or email..." autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </span>
                        <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-lg mt-1 shadow-xl max-h-60 overflow-y-auto text-sm"></ul>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Their intern login, tasks and attendance stay as they are.</p>
                </div>
                <!-- Shown when editing an intern-ambassador -->
                <p class="mb-4 text-sm text-gray-700 dark:text-gray-300" id="intern-note"></p>
                <div class="mb-4 account-field">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Ayesha Khan" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4 account-field">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Email</label>
                    <input type="email" name="email" required placeholder="e.g. ayesha@gmail.com" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">University</label>
                    <!-- Not .searchable-wrapper: footerLinks auto-inits those, this one is created below so the page keeps a handle on it. -->
                    <div id="university-picker" class="relative w-full">
                        <select name="university" class="searchable-select hidden">
                            <option value="">Select university</option>
                            <?php foreach ($universityOptions as $u): ?><option value="<?= htmlspecialchars($u["name"]) ?>"><?= htmlspecialchars($u["name"]) ?></option><?php endforeach; ?>
                        </select>
                        <input type="text" class="searchable-input w-full px-3 py-2 pr-10 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 cursor-pointer" placeholder="Search university..." autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </span>
                        <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-lg mt-1 shadow-xl max-h-60 overflow-y-auto text-sm"></ul>
                    </div>
                    <?php if (!$universityOptions): ?>
                        <p class="text-xs text-amber-600 mt-1">No active universities. Add them from the Universities page first.</p>
                    <?php endif; ?>
                </div>
                <div class="mb-4">
                    <span class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Student contact details</span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Unchecked = the ambassador sees them masked (e.g. ay****@gmail.com).</p>
                    <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200 mb-1">
                        <input type="checkbox" name="show_email" value="1" class="rounded"> Show full email
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                        <input type="checkbox" name="show_phone" value="1" class="rounded"> Show full phone number
                    </label>
                </div>
                <div class="mb-4 account-field">
                    <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">
                        Password
                        <span class="text-xs text-gray-500" id="password-hint">(Leave blank to auto-generate)</span>
                    </label>
                    <input type="text" name="password" placeholder="Leave blank to auto-generate" autocomplete="new-password" class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
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
            columnDefs: [{ targets: 8, orderable: false }]
        });
        let ambassadors = [];

        function esc(str) {
            return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function contactsBadge(a) {
            const shown = [];
            if (Number(a.amb_show_email) === 1) shown.push('Email');
            if (Number(a.amb_show_phone) === 1) shown.push('Phone');
            return shown.length
                ? `<span class="px-2 py-1 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">${shown.join(' + ')} visible</span>`
                : '<span class="text-gray-500 dark:text-gray-400">Masked</span>';
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
                    esc(a.name) + (a.is_intern ? ' <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200">Intern</span>' : ''),
                    esc(a.university) || '-',
                    esc(a.email),
                    `<span class="font-mono font-semibold">${esc(a.referral_code)}</span>`,
                    a.total_referrals,
                    a.hired,
                    contactsBadge(a),
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
        const uniWrapper = document.getElementById('university-picker');
        const uniSelect = uniWrapper.querySelector('select');
        const uniInput = uniWrapper.querySelector('.searchable-input');
        const uniPicker = new SearchableSelect(uniWrapper);

        // Typing without picking from the list leaves no university selected.
        uniInput.addEventListener('input', () => {
            const opt = uniSelect.options[uniSelect.selectedIndex];
            if (!opt || opt.text !== uniInput.value) uniSelect.value = '';
        });

        function setUniversity(name) {
            uniPicker.clear();
            if (!name) return;
            // Keep a university that has since been switched off or renamed.
            if (![...uniSelect.options].some(o => o.value === name)) {
                uniSelect.add(new Option(name, name));
            }
            uniPicker.setValues(name);
        }

        const internWrapper = document.getElementById('intern-picker');
        const internSelect = internWrapper.querySelector('select');
        const internInput = internWrapper.querySelector('.searchable-input');
        const internPicker = new SearchableSelect(internWrapper);
        internInput.addEventListener('input', () => {
            const opt = internSelect.options[internSelect.selectedIndex];
            if (!opt || opt.text !== internInput.value) internSelect.value = '';
        });

        // 'new' = brand-new ambassador account, 'intern' = make an existing
        // intern one. Hidden fields are disabled so `required` and FormData skip them.
        let modalMode = 'new';

        function showFields(selector, show) {
            document.querySelectorAll(selector).forEach(el => {
                el.classList.toggle('hidden', !show);
                el.querySelectorAll('input, select').forEach(i => i.disabled = !show);
            });
        }

        async function loadInterns() {
            internSelect.length = 1;
            internPicker.clear();
            const res = await fetch('controller/ambassadors.php?action=available_interns');
            const json = await res.json();
            if (!json.success) {
                showToast('error', json.message || 'Failed to load interns');
                return;
            }
            json.data.forEach(i => internSelect.add(new Option(`${i.name} (${i.email})`, i.id)));
            internPicker.populate();
        }

        function openModal(a, mode = 'new') {
            form.reset();
            modalMode = a ? (a.is_intern ? 'edit-intern' : 'edit') : mode;
            const isInternMode = modalMode === 'intern' || modalMode === 'edit-intern';
            showFields('#intern-field', modalMode === 'intern');
            showFields('.account-field', !isInternMode);
            const note = document.getElementById('intern-note');
            note.classList.toggle('hidden', modalMode !== 'edit-intern');
            note.innerHTML = modalMode === 'edit-intern'
                ? `<strong>${esc(a.name)}</strong> (${esc(a.email)}) is an intern. Their name, email and password are managed from the Active Interns page.`
                : '';

            form.elements.id.value = a ? a.id : '';
            if (!isInternMode) {
                form.elements.name.value = a ? a.name : '';
                form.elements.email.value = a ? a.email : '';
                form.elements.password.value = a ? (a.plain_password || '') : '';
            }
            setUniversity(a ? (a.university || '') : '');
            form.elements.show_email.checked = a ? Number(a.amb_show_email) === 1 : false;
            form.elements.show_phone.checked = a ? Number(a.amb_show_phone) === 1 : false;
            document.getElementById('ambassador-modal-title').textContent =
                modalMode === 'intern' ? 'Make Intern an Ambassador' : (a ? 'Edit Ambassador' : 'Add Ambassador');
            document.getElementById('password-hint').textContent = a ? '(Leave blank to keep the current one)' : '(Leave blank to auto-generate)';
            form.elements.password.placeholder = a ? 'Leave blank to keep the current one' : 'Leave blank to auto-generate';
            if (modalMode === 'intern') loadInterns();
            modal.classList.remove('hidden');
        }

        document.getElementById('add-ambassador-btn').addEventListener('click', () => openModal(null));
        document.getElementById('make-intern-btn').addEventListener('click', () => openModal(null, 'intern'));
        document.querySelectorAll('.close-ambassador-modal').forEach(b => b.addEventListener('click', () => modal.classList.add('hidden')));

        form.addEventListener('submit', async e => {
            e.preventDefault();
            if (modalMode === 'intern' && !internSelect.value) {
                showToast('error', 'Please select an intern from the list');
                internInput.focus();
                return;
            }
            if (!uniSelect.value) {
                showToast('error', 'Please select a university from the list');
                uniInput.focus();
                return;
            }
            const fd = new FormData(form);
            fd.append('action', modalMode === 'intern' ? 'make_intern_ambassador' : (fd.get('id') ? 'update' : 'create'));
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
            const amb = ambassadors.find(x => String(x.id) === String(this.dataset.id));
            const warning = amb && amb.is_intern
                ? 'Remove ambassador access? Their "My Referrals" page is hidden and their link stops being credited. Their internship and login are not affected.'
                : 'Deactivate this ambassador? They will be signed out and their link will stop being credited.';
            if (!activating && !confirm(warning)) return;
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
