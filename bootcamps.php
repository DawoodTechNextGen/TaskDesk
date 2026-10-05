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
$canWrite = canWriteModule(MODULE_BOOTCAMP);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Bootcamps - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-wrap gap-3 justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Bootcamps</h2>
                    <div class="flex items-center gap-3">
                        <select id="status-filter" class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (bootcampStatusLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($canWrite): ?>
                            <button class="open-modal bg-indigo-600 text-white px-4 py-2 rounded-lg" data-modal="bootcamp-form-modal" data-mode="create">
                                + New Bootcamp
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">All Bootcamps</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Only bootcamps with status <strong>Open</strong> accept enrollments on the public page. Seats count every enrollment except rejected ones.</p>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="bootcampsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Mode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Seats</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Enrollments</th>
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

    <?php if ($canWrite): ?>
    <!-- Create / Edit Bootcamp Modal -->
    <div id="bootcamp-form-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-4">
                <h3 id="bootcamp-form-title" class="text-xl font-bold text-gray-950 dark:text-gray-50">New Bootcamp</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="bootcamp-form">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="slug_touched" value="0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Title *</label>
                        <input type="text" name="title" id="bf-title" required maxlength="150" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. Web Development Bootcamp - Batch 3">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Slug *</label>
                        <input type="text" name="slug" id="bf-slug" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. web-dev-bootcamp-batch-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Public sign-up link: <span class="font-mono"><?= htmlspecialchars(BOOTCAMP_PUBLIC_URL) ?>/<span id="bf-slug-preview">your-slug</span></span></p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Description</label>
                        <textarea name="description" rows="4" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="What is this bootcamp about and who is it for?"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">What students will learn</label>
                        <textarea name="highlights" rows="4" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="One point per line, e.g.&#10;HTML, CSS & JavaScript&#10;React basics&#10;Build and deploy 3 projects"></textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">One point per line - shown as a checklist on the public page.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Total Seats *</label>
                        <input type="number" name="total_seats" id="bf-seats" min="0" value="50" required class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = unlimited. Enrollment closes automatically when seats are full.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Fee (PKR)</label>
                        <input type="number" name="fee" min="0" step="1" value="0" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = Free.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Mode</label>
                        <select name="mode" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <?php foreach (bootcampModeLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Status</label>
                        <select name="status" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <?php foreach (bootcampStatusLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Draft is hidden from the public page.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Duration</label>
                        <input type="text" name="duration" maxlength="60" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. 6 Weeks">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Class Schedule</label>
                        <input type="text" name="schedule" maxlength="150" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. Sat & Sun, 10 AM - 1 PM">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Start Date</label>
                        <input type="date" name="start_date" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">End Date</label>
                        <input type="date" name="end_date" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Enrollment Opens</label>
                        <input type="datetime-local" name="registration_start" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Enrollment Closes</label>
                        <input type="datetime-local" name="registration_end" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Optional. Leave blank to keep enrollment open while the status is Open.</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Venue</label>
                        <input type="text" name="venue" maxlength="150" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Physical address, or leave blank for fully online">
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" id="bootcamp-form-submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save Bootcamp</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="delete-bootcamp-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-950 dark:text-gray-50 mb-3">Delete this bootcamp?</h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">This will permanently delete "<span id="delete-bootcamp-title" class="font-semibold"></span>". Bootcamps that already have enrollments cannot be deleted - close them instead.</p>
            <div class="flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="button" id="confirm-delete-bootcamp" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete Bootcamp</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let dataTable;
        let bootcampsCache = [];
        let deleteTargetId = null;
        const canWrite = <?= $canWrite ? 'true' : 'false' ?>;
        const publicUrl = <?= json_encode(BOOTCAMP_PUBLIC_URL) ?>;

        const statusBadgeClasses = {
            draft: 'bg-gray-100 text-gray-800',
            upcoming: 'bg-blue-100 text-blue-800',
            open: 'bg-green-100 text-green-800',
            closed: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-purple-100 text-purple-800',
        };
        const statusLabels = <?= json_encode(bootcampStatusLabels()) ?>;
        const modeLabels = <?= json_encode(bootcampModeLabels()) ?>;

        function esc(str) {
            return (str ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function formatDisplayDate(dateString) {
            if (!dateString) return null;
            const d = new Date(dateString.replace(' ', 'T'));
            if (isNaN(d)) return null;
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function toDatetimeLocal(dateString) {
            if (!dateString) return '';
            return dateString.replace(' ', 'T').substring(0, 16);
        }

        // Action column: small square icon buttons with a tooltip, coloured for
        // both light and dark mode.
        const ICONS = {
            edit: '<path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/><path d="M19.5 14.25V18A2.25 2.25 0 0117.25 20.25H6A2.25 2.25 0 013.75 18V6.75A2.25 2.25 0 016 4.5h3.75"/>',
            users: '<path d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
            award: '<circle cx="12" cy="9" r="6"/><path d="M8.5 14.3L7 21l5-2.5 5 2.5-1.5-6.7"/>',
            link: '<path d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>',
            external: '<path d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>',
            trash: '<path d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>'
        };
        const BTN_COLORS = {
            blue: 'bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/70',
            teal: 'bg-teal-50 text-teal-600 hover:bg-teal-100 dark:bg-teal-900/40 dark:text-teal-300 dark:hover:bg-teal-900/70',
            purple: 'bg-purple-50 text-purple-600 hover:bg-purple-100 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-900/70',
            gray: 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
            red: 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/70'
        };
        const iconSvg = (paths) => `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;
        const btnClass = (color) => `inline-flex items-center justify-center w-8 h-8 rounded-lg transition-colors ${BTN_COLORS[color]}`;
        function actionBtn(cls, color, title, icon, attrs = '') {
            return `<button type="button" class="${cls} ${btnClass(color)}" title="${title}" aria-label="${title}" ${attrs}>${iconSvg(icon)}</button>`;
        }
        function actionLink(href, color, title, icon, attrs = '') {
            return `<a href="${esc(href)}" class="${btnClass(color)}" title="${title}" aria-label="${title}" ${attrs}>${iconSvg(icon)}</a>`;
        }

        function seatsCell(b) {
            const filled = Number(b.seats_filled) || 0;
            const total = Number(b.total_seats) || 0;
            if (total === 0) {
                return `<span class="font-medium">${filled}</span> / <span title="Unlimited">&infin;</span>`;
            }
            const pct = Math.min(100, Math.round(filled / total * 100));
            const bar = pct >= 100 ? 'bg-red-500' : (pct >= 80 ? 'bg-yellow-500' : 'bg-green-500');
            return `
                <div class="min-w-[110px]">
                    <div class="flex justify-between mb-1"><span class="font-medium">${filled} / ${total}</span>${pct >= 100 ? '<span class="text-red-600 font-semibold">FULL</span>' : `<span>${total - filled} left</span>`}</div>
                    <div class="w-full h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full"><div class="h-1.5 rounded-full ${bar}" style="width:${pct}%"></div></div>
                </div>`;
        }

        async function loadBootcamps() {
            const res = await fetch('controller/bootcamps.php?action=get');
            const result = await res.json();
            if (!result.success) {
                showToast('error', result.message || 'Failed to load bootcamps');
                return;
            }

            bootcampsCache = result.data;
            const statusFilter = document.getElementById('status-filter').value;
            const filtered = statusFilter ? bootcampsCache.filter(b => b.status === statusFilter) : bootcampsCache;

            dataTable.clear();
            filtered.forEach(b => {
                const badgeClass = statusBadgeClasses[b.status] || 'bg-gray-100 text-gray-800';
                const start = formatDisplayDate(b.start_date);
                const end = formatDisplayDate(b.end_date);
                const dates = (start || end)
                    ? `<div class="whitespace-nowrap">${start || 'TBD'}</div><div class="whitespace-nowrap text-gray-500 dark:text-gray-400">to ${end || 'TBD'}</div>`
                    : 'TBD';
                const link = `${publicUrl}/${b.slug}`;

                dataTable.row.add([
                    `<div class="font-medium min-w-[170px]">${esc(b.title)}</div><div class="text-gray-500 dark:text-gray-400">${Number(b.fee) > 0 ? 'PKR ' + Number(b.fee).toLocaleString() : 'Free'}${b.duration ? ' &middot; ' + esc(b.duration) : ''}</div>`,
                    modeLabels[b.mode] || esc(b.mode),
                    `<span class="px-2 py-1 rounded-full text-xs font-semibold ${badgeClass}">${statusLabels[b.status] || esc(b.status)}</span>`,
                    dates,
                    seatsCell(b),
                    `<a href="bootcamp_registrations.php?bootcamp_id=${b.id}" class="inline-block min-w-[2rem] text-center px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200 font-semibold hover:underline">${b.registration_count}</a>`,
                    `
                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                        ${canWrite ? actionBtn('edit-bootcamp', 'blue', 'Edit bootcamp', ICONS.edit, `data-id="${b.id}"`) : ''}
                        ${actionLink(`bootcamp_registrations.php?bootcamp_id=${b.id}`, 'teal', 'View enrollments', ICONS.users)}
                        ${actionLink(`bootcamp_registrations.php?bootcamp_id=${b.id}&status=completed`, 'purple', 'Completed students', ICONS.award)}
                        ${actionBtn('copy-link', 'gray', 'Copy public sign-up link', ICONS.link, `data-link="${esc(link)}"`)}
                        ${actionLink(link, 'gray', 'Open public page', ICONS.external, 'target="_blank" rel="noopener"')}
                        ${canWrite ? actionBtn('delete-bootcamp', 'red', 'Delete bootcamp', ICONS.trash, `data-id="${b.id}" data-title="${esc(b.title)}"`) : ''}
                    </div>
                    `
                ]);
            });
            dataTable.draw();
        }

        function slugify(text) {
            return text.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        }

        function updateSlugPreview() {
            document.getElementById('bf-slug-preview').textContent = document.getElementById('bf-slug').value || 'your-slug';
        }

        function resetForm() {
            const form = document.getElementById('bootcamp-form');
            if (!form) return;
            form.reset();
            form.querySelector('[name="id"]').value = '';
            form.querySelector('[name="slug_touched"]').value = '0';
            document.getElementById('bootcamp-form-title').textContent = 'New Bootcamp';
            document.getElementById('bootcamp-form-submit').textContent = 'Save Bootcamp';
            updateSlugPreview();
        }

        function openEditModal(id) {
            const b = bootcampsCache.find(x => String(x.id) === String(id));
            if (!b) return;

            const form = document.getElementById('bootcamp-form');
            form.reset();
            form.querySelector('[name="id"]').value = b.id;
            form.querySelector('[name="slug_touched"]').value = '1';
            ['title', 'slug', 'description', 'highlights', 'mode', 'status', 'total_seats', 'duration', 'schedule', 'start_date', 'end_date', 'venue']
                .forEach(f => form.querySelector(`[name="${f}"]`).value = b[f] ?? '');
            form.querySelector('[name="fee"]').value = Number(b.fee) || 0;
            form.querySelector('[name="registration_start"]').value = toDatetimeLocal(b.registration_start);
            form.querySelector('[name="registration_end"]').value = toDatetimeLocal(b.registration_end);
            updateSlugPreview();

            document.getElementById('bootcamp-form-title').textContent = 'Edit Bootcamp';
            document.getElementById('bootcamp-form-submit').textContent = 'Update Bootcamp';
            document.getElementById('bootcamp-form-modal').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            dataTable = $('#bootcampsTable').DataTable({
                ordering: false,
                pageLength: 10
            });

            document.getElementById('status-filter').addEventListener('change', loadBootcamps);

            document.addEventListener('click', async (e) => {
                const copyBtn = e.target.closest('.copy-link');
                if (copyBtn) {
                    try {
                        await navigator.clipboard.writeText(copyBtn.dataset.link);
                        showToast('success', 'Public sign-up link copied');
                    } catch (err) {
                        window.prompt('Copy this link:', copyBtn.dataset.link);
                    }
                }
                if (e.target.closest('.edit-bootcamp')) {
                    openEditModal(e.target.closest('.edit-bootcamp').dataset.id);
                }
                if (e.target.closest('.delete-bootcamp')) {
                    const btn = e.target.closest('.delete-bootcamp');
                    deleteTargetId = btn.dataset.id;
                    document.getElementById('delete-bootcamp-title').textContent = btn.dataset.title;
                    document.getElementById('delete-bootcamp-modal').classList.remove('hidden');
                }
            });

            if (canWrite) {
                // Suggest a slug from the title until the slug is edited by hand.
                document.getElementById('bf-title').addEventListener('input', (e) => {
                    const form = document.getElementById('bootcamp-form');
                    if (form.querySelector('[name="slug_touched"]').value === '1') return;
                    document.getElementById('bf-slug').value = slugify(e.target.value);
                    updateSlugPreview();
                });
                document.getElementById('bf-slug').addEventListener('input', () => {
                    document.getElementById('bootcamp-form').querySelector('[name="slug_touched"]').value = '1';
                    updateSlugPreview();
                });

                document.getElementById('bootcamp-form').addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const id = e.target.querySelector('[name="id"]').value;
                    const formData = new FormData(e.target);
                    formData.append('action', id ? 'update' : 'create');

                    const submitBtn = document.getElementById('bootcamp-form-submit');
                    submitBtn.disabled = true;
                    try {
                        const res = await fetch('controller/bootcamps.php', { method: 'POST', body: formData });
                        const json = await res.json();
                        showToast(json.success ? 'success' : 'error', json.message);
                        if (json.success) {
                            document.getElementById('bootcamp-form-modal').classList.add('hidden');
                            resetForm();
                            loadBootcamps();
                        }
                    } catch (err) {
                        showToast('error', 'Request failed: ' + err.message);
                    } finally {
                        submitBtn.disabled = false;
                    }
                });

                document.getElementById('confirm-delete-bootcamp').addEventListener('click', async () => {
                    if (!deleteTargetId) return;
                    const res = await fetch('controller/bootcamps.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'delete', id: deleteTargetId })
                    });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    document.getElementById('delete-bootcamp-modal').classList.add('hidden');
                    if (json.success) loadBootcamps();
                });
            }

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.modal').classList.add('hidden');
                    if (e.target.closest('#bootcamp-form-modal')) resetForm();
                });
            });

            document.querySelectorAll('.open-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.dataset.mode === 'create') resetForm();
                    document.getElementById(btn.dataset.modal).classList.remove('hidden');
                });
            });

            loadBootcamps();
        });
    </script>
</body>

</html>
