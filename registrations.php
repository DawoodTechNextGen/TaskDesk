<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Only admin access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 1) {
    header('Location: index.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Registrations - TaskDesk';
include_once "./include/headerLinks.php"; ?>


<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Registrations</h2>
                    <div class="flex items-center space-x-3">
                        <label for="statusFilter" class="text-sm text-gray-600 dark:text-gray-300">Status:</label>
                        <select id="statusFilter" class="px-3 py-2 border rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            <option value="">All</option>
                            <option value="new">New</option>
                            <option value="contact">Contact</option>
                            <option value="hire">Hire</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">New Registrations</h2>
                    </div>
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="registrationsTable" class="min-w-full">
                            <thead></thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>


    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function toTitle(s) {
            return String(s).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white font-medium shadow-lg animate-slide-in ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        async function loadRegistrations(status = '') {
            try {
                const res = await fetch('controller/registrations.php?action=get_registrations' + (status ? '&status=' + encodeURIComponent(status) : ''));
                const json = await res.json();

                if (!json.success) {
                    showToast('error', json.message || 'Failed to load registrations');
                    return;
                }

                const data = json.data || [];

                // Destroy existing DataTable if present
                if ($.fn.dataTable.isDataTable('#registrationsTable')) {
                    $('#registrationsTable').DataTable().destroy();
                }

                const $thead = $('#registrationsTable thead');
                const $tbody = $('#registrationsTable tbody');
                $thead.empty();
                $tbody.empty();

                if (data.length > 0) {
                    const preferred = ['id', 'name', 'email', 'country', 'mbl_number', 'cnic', 'city', 'technology', 'internship_type', 'experience', 'status'];
                    const keys = Object.keys(data[0]);
                    const keysOrdered = preferred.filter(k => keys.includes(k)).concat(keys.filter(k => !preferred.includes(k)));
                    // Exclude columns we don't want to show
                    const keysFiltered = keysOrdered.filter(k => k !== 'updated_at');

                    const headerMap = { 'mbl_number': 'Contact', 'cnic': 'City', 'city': 'CNIC' };
                    const headRow = '<tr>' + keysFiltered.map(k => `<th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">${headerMap[k] || toTitle(k)}</th>`).join('') + '<th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th></tr>';
                    $thead.append(headRow);

                    data.forEach(row => {
                        const cells = keysFiltered.map(k => {
                            let cell = '';
                            if (k === 'status') {
                                const raw = String(row[k] ?? '').toUpperCase();
                                const statusClassMap = {
                                    'NEW': 'bg-blue-100 text-blue-800',
                                    'CONTACT': 'bg-yellow-100 text-yellow-800',
                                    'HIRE': 'bg-green-100 text-green-800',
                                    'REJECTED': 'bg-red-100 text-red-800'
                                };
                                const cls = statusClassMap[raw] || 'bg-gray-100 text-gray-800';
                                cell = `<span class="px-2 py-1 rounded-full text-xs font-medium ${cls}">${raw}</span>`;
                            } else if (k === 'technology') {
                                cell = escapeHTML(row['technology'] ?? '');
                            } else if (k === 'cnic') {
                                // Show City value under CNIC header (per request: swap)
                                cell = escapeHTML(row['city'] ?? row['cnic'] ?? '');
                            } else if (k === 'city') {
                                // Show CNIC value under City header (per request: swap)
                                cell = escapeHTML(row['cnic'] ?? row['city'] ?? '');
                            } else if (k === 'mbl_number') {
                                // Display as Contact
                                cell = escapeHTML(row['mbl_number'] ?? '');
                            } else {
                                cell = escapeHTML(row[k]);
                            }

                            return `<td class="px-6 py-3 text-sm">${cell}</td>`;
                        }).join('');

                        // Actions cell: status select and update button
                        const actionsCell = (function() {
                            const id = row.id ?? '';
                            const current = (row.status || '').toLowerCase();
                            const opts = [
                                {v:'new', l:'New'},
                                {v:'contact', l:'Contact'},
                                {v:'hire', l:'Hire'},
                                {v:'rejected', l:'Rejected'}
                            ];
                            let sel = `<select class="status-select px-2 py-1 border rounded text-sm" data-id="${id}">` +
                                opts.map(o => `<option value="${o.v}"${current===o.v ? ' selected' : ''}>${o.l}</option>`).join('') +
                                `</select>`;
                            let btn = `<button class="update-status inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded" data-id="${id}">Update</button>`;
                            return `<td class="px-6 py-3 text-sm"><div class="flex items-center space-x-2">${sel}${btn}</div></td>`;
                        })();

                        const tr = '<tr>' + cells + actionsCell + '</tr>';
                        $tbody.append(tr);
                    });

                } else {
                    $thead.append('<tr><th class="px-6 py-3">No registrations yet</th></tr>');
                }

                $('#registrationsTable').DataTable({
                    ordering: false,
                    pageLength: 10
                });

            } catch (err) {
                console.error('Failed to load registrations:', err);
                showToast('error', 'Failed to load registrations');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Load initial data (respect URL param if provided)
            const params = new URLSearchParams(window.location.search);
            const preStatus = params.get('status') || '';
            if (preStatus) {
                document.getElementById('statusFilter').value = preStatus;
            }

            loadRegistrations(preStatus);

            // Filter change
            document.getElementById('statusFilter').addEventListener('change', function() {
                const val = this.value;
                // Update URL without reloading
                const url = new URL(window.location);
                if (val) url.searchParams.set('status', val);
                else url.searchParams.delete('status');
                window.history.replaceState({}, '', url);

                loadRegistrations(val);
            });
        });

        // Delegated handler for update buttons
        document.addEventListener('click', async function(e) {
            const btn = e.target.closest('.update-status');
            if (!btn) return;
            const id = btn.dataset.id;
            const select = document.querySelector(`select.status-select[data-id="${id}"]`);
            if (!select) return;
            const newStatus = select.value;
            if (!confirm(`Change status to ${toTitle(newStatus)}?`)) return;
            btn.disabled = true;
            try {
                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    body: new URLSearchParams({ action: 'update_status', id: id, status: newStatus })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message || 'Update failed');
                if (json.success) {
                    loadRegistrations(document.getElementById('statusFilter').value);
                }
            } catch (err) {
                console.error(err);
                showToast('error', 'Update failed');
            } finally {
                btn.disabled = false;
            }
        });
    </script>
</body>

</html>