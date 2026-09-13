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
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Hackathons - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>

        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex flex-wrap gap-3 justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Hackathons</h2>
                    <div class="flex items-center gap-3">
                        <select id="status-filter" class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="">All Statuses</option>
                            <?php foreach (hackathonStatusLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="open-modal bg-indigo-600 text-white px-4 py-2 rounded-lg" data-modal="hackathon-form-modal" data-mode="create">
                            + New Hackathon
                        </button>
                    </div>
                </div>

                <!-- Hackathons Table -->
                <div class="my-5 bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">All Hackathons</h2>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="hackathonsTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Slug</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Mode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Event Dates</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider border border-gray-300 dark:border-gray-600">Registrations</th>
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

    <!-- Create / Edit Hackathon Modal -->
    <div id="hackathon-form-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex justify-between items-center mb-4">
                <h3 id="hackathon-form-title" class="text-xl font-bold text-gray-950 dark:text-gray-50">New Hackathon</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="hackathon-form" enctype="multipart/form-data">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="slug_touched" value="0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Title *</label>
                        <input type="text" name="title" id="hf-title" required maxlength="150" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Slug *</label>
                        <input type="text" name="slug" id="hf-slug" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="e.g. summer-hackathon-2026">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Lowercase letters/numbers, single hyphens. Used in the public registration URL - must stay unique.</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Description</label>
                        <textarea name="description" rows="4" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Mode</label>
                        <select name="mode" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <?php foreach (hackathonModeLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Status</label>
                        <select name="status" id="hf-status" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                            <?php foreach (hackathonStatusLabels() as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="allow_individual" id="hf-allow-individual" value="1" checked class="h-4 w-4">
                        <label for="hf-allow-individual" class="text-sm text-gray-900 dark:text-gray-100">Allow Individual Registration</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="allow_team" id="hf-allow-team" value="1" checked class="h-4 w-4">
                        <label for="hf-allow-team" class="text-sm text-gray-900 dark:text-gray-100">Allow Team Registration</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Min Team Size</label>
                        <input type="number" name="min_team_size" id="hf-min-size" min="1" value="1" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Max Team Size</label>
                        <input type="number" name="max_team_size" id="hf-max-size" min="1" value="4" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Registration Start</label>
                        <input type="datetime-local" name="registration_start" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Registration End</label>
                        <input type="datetime-local" name="registration_end" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Event Start</label>
                        <input type="datetime-local" name="event_start" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Event End</label>
                        <input type="datetime-local" name="event_end" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Venue</label>
                        <input type="text" name="venue" maxlength="150" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100" placeholder="Physical address, or leave blank for fully online">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1 text-gray-900 dark:text-gray-100">Banner Image</label>
                        <div id="hf-current-banner" class="hidden mb-2">
                            <img id="hf-current-banner-img" src="" alt="Current banner" class="h-24 rounded-lg border border-gray-200 dark:border-gray-700">
                        </div>
                        <input type="file" name="banner" accept="image/png,image/jpeg,image/webp,image/gif" class="form-input w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">JPG, PNG, WEBP or GIF, up to 5MB. Leave empty to keep the current banner when editing.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" id="hackathon-form-submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save Hackathon</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div id="delete-hackathon-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="animate-fadeIn bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 rounded-full bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-300">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9v4M12 17h.01M10.29 3.86l-8.18 14.18A2 2 0 004.03 21h15.94a2 2 0 001.92-2.96L13.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-950 dark:text-gray-50">Delete this hackathon?</h3>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">This will permanently delete "<span id="delete-hackathon-title" class="font-semibold"></span>" and cannot be undone.</p>
            <p class="text-sm text-red-600 dark:text-red-400 font-medium mb-4">All of its registrations, team members and leaderboard entries will also be deleted.</p>
            <div class="flex justify-end gap-3">
                <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                <button type="button" id="confirm-delete-hackathon" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete Hackathon</button>
            </div>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        let dataTable;
        let hackathonsCache = [];
        let deleteTargetId = null;

        const statusBadgeClasses = {
            draft: 'bg-gray-100 text-gray-800',
            upcoming: 'bg-blue-100 text-blue-800',
            open: 'bg-green-100 text-green-800',
            closed: 'bg-yellow-100 text-yellow-800',
            completed: 'bg-purple-100 text-purple-800',
        };
        const statusLabels = <?= json_encode(hackathonStatusLabels()) ?>;
        const modeLabels = <?= json_encode(hackathonModeLabels()) ?>;

        function esc(str) {
            return (str ?? '').toString().replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function formatDisplayDate(dateString) {
            if (!dateString) return 'N/A';
            const d = new Date(dateString.replace(' ', 'T'));
            if (isNaN(d)) return 'N/A';
            return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function toDatetimeLocal(dateString) {
            if (!dateString) return '';
            return dateString.replace(' ', 'T').substring(0, 16);
        }

        async function loadHackathons() {
            const res = await fetch('controller/hackathons.php?action=get');
            const result = await res.json();
            if (!result.success) return;

            hackathonsCache = result.data;
            const statusFilter = document.getElementById('status-filter').value;
            const filtered = statusFilter ? hackathonsCache.filter(h => h.status === statusFilter) : hackathonsCache;

            dataTable.clear();
            filtered.forEach(h => {
                const badgeClass = statusBadgeClasses[h.status] || 'bg-gray-100 text-gray-800';
                const eventDates = (h.event_start || h.event_end)
                    ? `${formatDisplayDate(h.event_start)} - ${formatDisplayDate(h.event_end)}`
                    : 'TBD';

                dataTable.row.add([
                    `<span class="font-medium">${esc(h.title)}</span>`,
                    esc(h.slug),
                    modeLabels[h.mode] || esc(h.mode),
                    `<span class="px-2 py-1 rounded-full text-xs font-semibold ${badgeClass}">${statusLabels[h.status] || esc(h.status)}</span>`,
                    eventDates,
                    `<a href="hackathon_registrations.php?id=${h.id}" class="text-indigo-600 hover:underline">${h.registration_count}</a>`,
                    `
                    <div class="flex items-center gap-3">
                        <button class="edit-hackathon text-blue-600" data-id="${h.id}" title="Edit">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12V10.5M13.5 2H12C7.28595 2 4.92893 2 3.46447 3.46447C2.49073 4.43821 2.16444 5.80655 2.0551 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M16.652 3.45506L17.3009 2.80624C18.3759 1.73125 20.1188 1.73125 21.1938 2.80624C22.2687 3.88124 22.2687 5.62415 21.1938 6.69914L20.5449 7.34795M16.652 3.45506C16.652 3.45506 16.7331 4.83379 17.9497 6.05032C19.1662 7.26685 20.5449 7.34795 20.5449 7.34795M16.652 3.45506L10.6872 9.41993C10.2832 9.82394 10.0812 10.0259 9.90743 10.2487C9.70249 10.5114 9.52679 10.7957 9.38344 11.0965C9.26191 11.3515 9.17157 11.6225 8.99089 12.1646L8.41242 13.9M20.5449 7.34795L17.5625 10.3304" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </button>
                        <a href="hackathon_registrations.php?id=${h.id}" class="text-teal-600" title="Registrations">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12C2 15.7712 2 17.6569 3.17157 18.8284C4.34315 20 6.22876 20 10 20H14C17.7712 20 19.6569 20 20.8284 18.8284C22 17.6569 22 15.7712 22 12C22 11.0542 22.0185 10.7271 22 10M13 4H10C6.22876 4 4.34315 4 3.17157 5.17157C2.51839 5.82475 2.22937 6.69989 2.10149 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><circle cx="19" cy="5" r="3" stroke="currentColor" stroke-width="1.5"></circle></svg>
                        </a>
                        <a href="hackathon_leaderboard.php?id=${h.id}" class="text-amber-600" title="Leaderboard">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 21H16M12 17V21M17 3H7V9C7 11.7614 9.23858 14 12 14C14.7614 14 17 11.7614 17 9V3Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"></path><path d="M7 5H4.5C3.67157 5 3 5.67157 3 6.5C3 8.433 4.567 10 6.5 10H7M17 5H19.5C20.3284 5 21 5.67157 21 6.5C21 8.433 19.433 10 17.5 10H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </a>
                        <button class="delete-hackathon text-red-600" data-id="${h.id}" data-title="${esc(h.title)}" title="Delete">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path><path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5M18.8334 8.5L18.6334 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </button>
                    </div>
                    `
                ]);
            });
            dataTable.draw();
        }

        function resetForm() {
            const form = document.getElementById('hackathon-form');
            form.reset();
            form.querySelector('[name="id"]').value = '';
            form.querySelector('[name="slug_touched"]').value = '0';
            document.getElementById('hackathon-form-title').textContent = 'New Hackathon';
            document.getElementById('hackathon-form-submit').textContent = 'Save Hackathon';
            document.getElementById('hf-current-banner').classList.add('hidden');
        }

        function openEditModal(id) {
            const h = hackathonsCache.find(x => String(x.id) === String(id));
            if (!h) return;

            const form = document.getElementById('hackathon-form');
            form.reset();
            form.querySelector('[name="id"]').value = h.id;
            form.querySelector('[name="slug_touched"]').value = '1';
            form.querySelector('[name="title"]').value = h.title;
            form.querySelector('[name="slug"]').value = h.slug;
            form.querySelector('[name="description"]').value = h.description || '';
            form.querySelector('[name="mode"]').value = h.mode;
            form.querySelector('[name="status"]').value = h.status;
            form.querySelector('[name="allow_individual"]').checked = String(h.allow_individual) === '1';
            form.querySelector('[name="allow_team"]').checked = String(h.allow_team) === '1';
            form.querySelector('[name="min_team_size"]').value = h.min_team_size;
            form.querySelector('[name="max_team_size"]').value = h.max_team_size;
            form.querySelector('[name="registration_start"]').value = toDatetimeLocal(h.registration_start);
            form.querySelector('[name="registration_end"]').value = toDatetimeLocal(h.registration_end);
            form.querySelector('[name="event_start"]').value = toDatetimeLocal(h.event_start);
            form.querySelector('[name="event_end"]').value = toDatetimeLocal(h.event_end);
            form.querySelector('[name="venue"]').value = h.venue || '';

            const bannerWrap = document.getElementById('hf-current-banner');
            if (h.banner_url) {
                document.getElementById('hf-current-banner-img').src = '<?= BASE_URL ?>' + h.banner_url;
                bannerWrap.classList.remove('hidden');
            } else {
                bannerWrap.classList.add('hidden');
            }

            document.getElementById('hackathon-form-title').textContent = 'Edit Hackathon';
            document.getElementById('hackathon-form-submit').textContent = 'Update Hackathon';
            document.getElementById('hackathon-form-modal').classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            dataTable = $('#hackathonsTable').DataTable({
                ordering: false,
                pageLength: 10
            });

            document.getElementById('status-filter').addEventListener('change', loadHackathons);

            // Auto-suggest slug from title until the admin edits the slug manually.
            document.getElementById('hf-title').addEventListener('input', (e) => {
                const form = document.getElementById('hackathon-form');
                if (form.querySelector('[name="slug_touched"]').value === '1') return;
                const slugified = e.target.value.toLowerCase().trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                document.getElementById('hf-slug').value = slugified;
            });
            document.getElementById('hf-slug').addEventListener('input', () => {
                document.getElementById('hackathon-form').querySelector('[name="slug_touched"]').value = '1';
            });

            document.getElementById('hackathon-form').addEventListener('submit', async (e) => {
                e.preventDefault();

                const minSize = parseInt(document.getElementById('hf-min-size').value, 10);
                const maxSize = parseInt(document.getElementById('hf-max-size').value, 10);
                if (minSize > maxSize) {
                    showToast('error', 'Min team size cannot exceed max team size.');
                    return;
                }
                if (!document.getElementById('hf-allow-individual').checked && !document.getElementById('hf-allow-team').checked) {
                    showToast('error', 'Enable at least one of Allow Individual or Allow Team.');
                    return;
                }

                const id = e.target.querySelector('[name="id"]').value;
                const formData = new FormData(e.target);
                formData.set('allow_individual', document.getElementById('hf-allow-individual').checked ? '1' : '0');
                formData.set('allow_team', document.getElementById('hf-allow-team').checked ? '1' : '0');
                formData.append('action', id ? 'update' : 'create');

                const submitBtn = document.getElementById('hackathon-form-submit');
                submitBtn.disabled = true;

                try {
                    const res = await fetch('controller/hackathons.php', { method: 'POST', body: formData });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) {
                        document.querySelector('#hackathon-form-modal .close-modal').click();
                        loadHackathons();
                    }
                } finally {
                    submitBtn.disabled = false;
                }
            });

            document.addEventListener('click', (e) => {
                if (e.target.closest('.edit-hackathon')) {
                    openEditModal(e.target.closest('.edit-hackathon').dataset.id);
                }
                if (e.target.closest('.delete-hackathon')) {
                    const btn = e.target.closest('.delete-hackathon');
                    deleteTargetId = btn.dataset.id;
                    document.getElementById('delete-hackathon-title').textContent = btn.dataset.title;
                    document.getElementById('delete-hackathon-modal').classList.remove('hidden');
                }
            });

            document.getElementById('confirm-delete-hackathon').addEventListener('click', async () => {
                if (!deleteTargetId) return;
                const res = await fetch('controller/hackathons.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'delete', id: deleteTargetId })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                document.getElementById('delete-hackathon-modal').classList.add('hidden');
                if (json.success) loadHackathons();
            });

            document.querySelectorAll('.close-modal').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.target.closest('.modal').classList.add('hidden');
                    if (e.target.closest('#hackathon-form-modal')) resetForm();
                });
            });

            document.querySelectorAll('.open-modal').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.dataset.mode === 'create') resetForm();
                    document.getElementById(btn.dataset.modal).classList.remove('hidden');
                });
            });

            loadHackathons();
        });
    </script>
</body>

</html>
