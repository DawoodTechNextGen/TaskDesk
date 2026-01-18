<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Only admin access
if (
    !isset($_SESSION['user_role']) ||
    ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4)
) {

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
        /* =====================================================
   Helpers
===================================================== */
        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${
        type === 'success' ? 'bg-green-600' :
        type === 'error' ? 'bg-red-600' : 'bg-yellow-500'
    }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        /* =====================================================
           Columns
        ===================================================== */
        const visibleColumns = [
            'id',
            'name',
            'mbl_number',
            'technology',
            'internship_type',
            'experience',
            'status'
        ];

        const expandableColumns = ['email','cnic', 'city', 'country', 'created_at'];

        const headerMap = {
            id: 'ID',
            name: 'Name',
            email: 'Email',
            mbl_number: 'Contact',
            technology: 'Technology',
            internship_type: 'Internship Type',
            experience: 'Experience',
            status: 'Status',
            cnic: 'CNIC',
            city: 'City',
            country: 'Country',
            created_at: 'Created At'
        };

        /* =====================================================
           Status Normalizer
        ===================================================== */
        function normalizeStatus(val) {
            if (!val) return 'new';
            return String(val).toLowerCase();
        }

        /* =====================================================
           Expand Row
        ===================================================== */
        function formatDetails(row) {
            return `
        <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
            <div class="grid grid-cols-2 gap-4 text-sm">
                ${expandableColumns.map(k => `
                    <div>
                        <span class="font-semibold">${headerMap[k]}:</span>
                        <span>${escapeHTML(row[k] ?? '-')}</span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
        }

        /* =====================================================
           Status Dropdown
        ===================================================== */
        function createStatusDropdown(currentStatus, id) {
            return `
        <select class="status-select px-2 py-1 border rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm w-28"
                data-id="${id}"
                data-current="${normalizeStatus(currentStatus)}">
            <option value="new">New</option>
            <option value="contact">Contact</option>
            <option value="hire">Hire</option>
            <option value="rejected">Rejected</option>
        </select>
    `;
        }



        /* =====================================================
           Actions Column
        ===================================================== */
        function renderActions(row) {
            return `
        <div class="flex items-center space-x-2">
            ${createStatusDropdown(row.status, row.id)}
            <button
                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-sm update-status-btn"
                data-id="${row.id}">
                Update
            </button>
            <input type="hidden" class="current-status-value" value="${normalizeStatus(row.status)}">
        </div>
    `;
        }


        /* =====================================================
           Load Registrations
        ===================================================== */
        async function loadRegistrations(filter = '') {
            const res = await fetch(
                'controller/registrations.php?action=get_registrations' +
                (filter ? '&status=' + encodeURIComponent(filter) : '')
            );
            const json = await res.json();

            if (!json.success) {
                showToast('error', 'Load failed');
                return;
            }

            if ($.fn.dataTable.isDataTable('#registrationsTable')) {
                $('#registrationsTable').DataTable().destroy();
            }

            $('#registrationsTable thead').html(`
        <tr>
            <th></th>
            ${visibleColumns.map(c => `<th>${headerMap[c]}</th>`).join('')}
            <th>Actions</th>
        </tr>
    `);

            const table = $('#registrationsTable').DataTable({
                data: json.data,
                order: [
                    [1, 'desc']
                ],
                pageLength: 10,
                responsive: true,

                drawCallback: function() {
                    document.querySelectorAll('.status-select').forEach(select => {
                        select.value = select.dataset.current;
                    });
                },

                columns: [{
                        className: 'details-control cursor-pointer select-none text-center font-bold',
                        orderable: false,
                        defaultContent: '+'
                    },
                    ...visibleColumns.map(k => ({
                        data: k,
                        render: function(data, type) {
                            if (type !== 'display') return data;
                            if (k === 'status') {
                                const s = normalizeStatus(data);
                                const map = {
                                    new: ['NEW', 'bg-blue-600'],
                                    contact: ['CONTACT', 'bg-yellow-500'],
                                    hire: ['HIRE', 'bg-green-600'],
                                    rejected: ['REJECTED', 'bg-red-600']
                                };
                                return `<span class="px-3 py-1 rounded-full text-xs text-white ${map[s][1]}">${map[s][0]}</span>`;
                            }
                            return escapeHTML(data);
                        }
                    })),
                    {
                        data: null,
                        orderable: false,
                        render: row => renderActions(row)
                    }
                ]
            });


            $('#registrationsTable tbody').on('click', 'td.details-control', function() {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $(this).html('+');
                } else {
                    row.child(formatDetails(row.data())).show();
                    tr.addClass('shown');
                    $(this).html('−');
                }
            });
        }

        /* =====================================================
           Update Status
        ===================================================== */
        document.addEventListener('click', async e => {
            const btn = e.target.closest('.update-status-btn');
            if (!btn) return;

            const tr = btn.closest('tr');
            const select = tr.querySelector('.status-select');
            const hidden = tr.querySelector('.current-status-value');

            if (normalizeStatus(select.value) === normalizeStatus(hidden.value)) {
                showToast('info', 'Status already selected');
                return;
            }

            if (!confirm(`Change status to "${select.value}"?`)) return;

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'update_status',
                    id: btn.dataset.id,
                    status: select.value
                })
            });

            const json = await res.json();
            showToast(json.success ? 'success' : 'error', json.message);

            if (json.success) {
                loadRegistrations(document.getElementById('statusFilter').value);
            }
        });

        /* =====================================================
           Init
        ===================================================== */
        document.addEventListener('DOMContentLoaded', () => {
            const status = new URLSearchParams(window.location.search).get('status') || '';
            loadRegistrations(status);
        });
    </script>


</body>

</html>