<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Only admin access
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4)) {
    header('Location: index.php');
    exit;
}

include_once './include/connection.php';

// Initial counts will be loaded via AJAX to improve page load speed
$total_contact = '-';
$total_hire = '-';
$total_rejected = '-';
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Registrations - TaskDesk';
include_once "./include/headerLinks.php";
?>

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

    /* ROTATION STATE */
    tr.shown .expand-icon {
        transform: rotate(45deg);
    }

    /* Loader Styles */
    .loader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.3s ease;
    }

    .loader {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .table-loader {
        display: none;
        text-align: center;
        padding: 20px;
    }

    .table-loader.active {
        display: block;
    }

    .table-container {
        position: relative;
        min-height: 200px;
    }

    .skeleton-row {
        height: 40px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        margin: 5px 0;
        border-radius: 4px;
    }

    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <!-- Global Loader Overlay -->
    <div id="globalLoader" class="loader-overlay hidden">
        <div class="loader"></div>
    </div>

    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <!-- Cards Section -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                    <!-- Contact Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                        <h3 class="text-sm font-semibold text-yellow-500 dark:text-yellow-400 mb-2">Total Contact</h3>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-200" id="totalContact">
                            <?php echo $total_contact; ?>
                        </p>
                    </div>

                    <!-- Hire Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                        <h3 class="text-sm font-semibold text-green-500 dark:text-green-400 mb-2">Total Hire</h3>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-200" id="totalHire">
                            <?php echo $total_hire; ?>
                        </p>
                    </div>

                    <!-- Rejected Card -->
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md border border-gray-100 dark:border-gray-700 flex flex-col items-center justify-center">
                        <h3 class="text-sm font-semibold text-red-500 dark:text-red-400 mb-2">Total Rejected</h3>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-200" id="totalRejected">
                            <?php echo $total_rejected; ?>
                        </p>
                    </div>
                </div>

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

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">New Registrations</h2>
                    </div>
                    <div class="table-container">
                        <!-- Table Loader -->
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading registrations...</span>
                            </div>
                        </div>

                        <!-- Skeleton Loading -->
                        <div id="skeletonLoader" class="p-4 hidden">
                            <div class="skeleton-row"></div>
                            <div class="skeleton-row"></div>
                            <div class="skeleton-row"></div>
                            <div class="skeleton-row"></div>
                            <div class="skeleton-row"></div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="registrationsTable" class="min-w-full">
                                <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Hire Modal -->
            <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Hire Details</h3>
                    <form id="hireForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input type="text" id="hireName" class="w-full px-3 py-2 border rounded text-gray-600 dark:text-gray-200" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Technology</label>
                            <input type="text" id="hireTechnology" class="w-full px-3 py-2 border rounded text-gray-600 dark:text-gray-200" readonly>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" id="hireEmail" class="w-full px-3 py-2 border rounded text-gray-600 dark:text-gray-200" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
                            <div class="searchable-wrapper relative w-full">
                                <select id="hireTrainer" class="searchable-select hidden" name="hireTrainer">
                                    <option value="">Select Supervisor</option>
                                    <?php
                                    $userQuery = "SELECT id, name FROM users where user_role = 3 ORDER BY name ASC";
                                    $userResult = mysqli_query($conn, $userQuery);
                                    while ($user = mysqli_fetch_assoc($userResult)) {
                                        echo "<option value=\"{$user['id']}\">{$user['name']}</option>";
                                    }
                                    ?>
                                </select>

                                <div class="relative">
                                    <input type="text" class="searchable-input w-full px-3 py-2 pr-10 border rounded bg-white dark:bg-gray-700 dark:text-gray-200 cursor-pointer" placeholder="Select Supervisor" autocomplete="off">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-500 dark:text-gray-300">
                                        <svg class="size-2" fill="currentColor" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                                            <g id="SVGRepo_iconCarrier">
                                                <path d="M0.256 8.606c0-0.269 0.106-0.544 0.313-0.75 0.412-0.412 1.087-0.412 1.5 0l14.119 14.119 13.913-13.912c0.413-0.412 1.087-0.412 1.5 0s0.413 1.088 0 1.5l-14.663 14.669c-0.413 0.413-1.088 0.413-1.5 0l-14.869-14.869c-0.213-0.213-0.313-0.481-0.313-0.756z"></path>
                                            </g>
                                        </svg>
                                    </span>
                                </div>

                                <ul class="searchable-dropdown hidden absolute z-50 text-gray-900 w-full bg-white dark:bg-gray-700 border rounded mt-1 max-h-60 overflow-y-auto"></ul>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="hireCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Submit</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
<script>
    // Loader Management
    const LoaderManager = {
        showGlobal: function() {
            document.getElementById('globalLoader').classList.remove('hidden');
        },
        
        hideGlobal: function() {
            document.getElementById('globalLoader').classList.add('hidden');
        },
        
        showTable: function() {
            document.getElementById('tableLoader').classList.add('active');
            document.getElementById('skeletonLoader').classList.remove('hidden');
            document.querySelector('#registrationsTable').style.opacity = '0.3';
        },
        
        hideTable: function() {
            document.getElementById('tableLoader').classList.remove('active');
            document.getElementById('skeletonLoader').classList.add('hidden');
            document.querySelector('#registrationsTable').style.opacity = '1';
        }
    };

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

    const expandableColumns = [
        'email',
        'cnic',
        'city',
        'country',
        'created_at'
    ];

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
    Expand Row Template
    ===================================================== */
    function formatDetails(row) {
        return `
<div class="expand-wrapper overflow-hidden transition-all duration-300 ease-in-out opacity-0 max-h-0">
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
</div>`;
    }

    /* =====================================================
    Status Dropdown
    ===================================================== */
    function createStatusDropdown(currentStatus, id) {
        return `
<select class="status-select px-2 py-1 border rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs w-20"
        data-id="${id}"
        data-current="${normalizeStatus(currentStatus)}">
    <option value="new">New</option>
    <option value="contact">Contact</option>
    <option value="hire">Hire</option>
    <option value="rejected">Rejected</option>
</select>`;
    }

    /* =====================================================
    Actions Column
    ===================================================== */
    function renderActions(row) {
        return `
<div class="flex items-center space-x-2">
    ${createStatusDropdown(row.status, row.id)}
    <button
        class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs update-status-btn"
        data-id="${row.id}">
        Update
    </button>
    <input type="hidden" class="current-status-value" value="${normalizeStatus(row.status)}">
</div>`;
    }

    /* =====================================================
    Animate Expand / Collapse
    ===================================================== */
    function animateExpand(el) {
        el.style.maxHeight = el.scrollHeight + 'px';
        el.style.opacity = '1';
    }

    function animateCollapse(el) {
        el.style.maxHeight = '0px';
        el.style.opacity = '0';
    }

    /* =====================================================
    Load Registrations - OPTIMIZED with Caching
    ===================================================== */
    /* =====================================================
    Render Table Function - SERVER SIDE
    ===================================================== */
    let dataTable = null;

    function initDataTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        dataTable = $('#registrationsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'get_registrations';
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [
                {
                    class: 'details-control cursor-pointer text-center font-bold select-none',
                    orderable: false,
                    data: null,
                    defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                },
                { data: 'id' },
                { data: 'name' },
                { data: 'mbl_number' },
                { data: 'technology' },
                { data: 'internship_type' },
                { data: 'experience' },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        const s = normalizeStatus(data);
                        const map = {
                            new: ['NEW', 'bg-blue-600'],
                            contact: ['CONTACT', 'bg-yellow-500'],
                            hire: ['HIRE', 'bg-green-600'],
                            rejected: ['REJECTED', 'bg-red-600']
                        };
                        return `<span class="px-2 py-1 rounded-full text-xs text-white ${map[s][1]}">${map[s][0]}</span>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return renderActions(row);
                    }
                }
            ],
            order: [[1, 'desc']], // Order by ID desc by default
            language: {
                processing: '<div class="loader-small"></div> Processing...',
                emptyTable: 'No data available in table',
                zeroRecords: 'No matching records found'
            },
            createdRow: function(row, data, dataIndex) {
                 $(row).attr('data-row-index', dataIndex);
            },
            drawCallback: function(settings) {
                 // Re-initialize status dropdowns if needed
                 document.querySelectorAll('.status-select').forEach(select => {
                    select.value = select.dataset.current;
                });
            }
        });

        // Set up row expansion
        $('#registrationsTable tbody').on('click', 'td.details-control', function() {
            const tr = $(this).closest('tr');
            const row = dataTable.row(tr);
            
            if (row.child.isShown()) {
                const el = tr.next('tr').find('.expand-wrapper')[0];
                if (el) {
                    animateCollapse(el);
                    setTimeout(() => row.child.hide(), 300);
                }
                tr.removeClass('shown');
            } else {
                // Row data is already available in 'row.data()' for server-side
                const rowData = row.data();
                if (rowData) {
                    row.child(formatDetails(rowData)).show();
                    const el = tr.next('tr').find('.expand-wrapper')[0];
                    if (el) {
                        requestAnimationFrame(() => animateExpand(el));
                    }
                    tr.addClass('shown');
                }
            }
        });
    }

    /* =====================================================
    Render Table Function - UPDATED
    ===================================================== */


    /* =====================================================
    Searchable Select for Supervisor
    ===================================================== */
    /* =====================================================
    Searchable Select for Supervisor (jQuery Version)
    ===================================================== */
    function initSearchableSelect() {
        const $wrapper = $('.searchable-wrapper');
        if (!$wrapper.length) return;

        const $originalSelect = $wrapper.find('.searchable-select');
        const $searchInput = $wrapper.find('.searchable-input');
        const $dropdown = $wrapper.find('.searchable-dropdown');

        // Unbind existing events to prevent duplicates
        $searchInput.off('click.search input.search');
        $(document).off('click.searchableSelect');
        $dropdown.off('click', 'li');

        // Populate dropdown
        function populateDropdown() {
            const options = $originalSelect.find('option').toArray();
            $dropdown.empty();

            options.forEach(option => {
                const $li = $('<li>')
                    .addClass('px-3 py-2 cursor-pointer text-gray-900 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200')
                    .text(option.textContent)
                    .attr('data-value', option.value)
                    .on('click', function() {
                         $originalSelect.val(option.value);
                         $searchInput.val(option.textContent);
                         $dropdown.addClass('hidden');
                    });
                
                $dropdown.append($li);
            });
        }

        // Toggle dropdown on input click
        $searchInput.on('click.search', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            populateDropdown();
            
            if ($dropdown.hasClass('hidden')) {
                $dropdown.removeClass('hidden');
                // Filter immediately if there is text? 
                // Normally we want to show all options on open
                filterDropdown($(this).val()); 
            } else {
                $dropdown.addClass('hidden');
            }
        });

        // Filter on input
        $searchInput.on('input.search', function(e) {
            populateDropdown();
            filterDropdown($(this).val());
            $dropdown.removeClass('hidden');
        });

        function filterDropdown(searchTerm) {
            const term = searchTerm.toLowerCase();
            $dropdown.find('li').each(function() {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(term) > -1);
            });
        }

        // Close dropdown when clicking outside
        $(document).on('click.searchableSelect', function(e) {
            if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                $dropdown.addClass('hidden');
            }
        });

        // Initialize with current value
        const $selected = $originalSelect.find('option:selected');
        if ($selected.length && $selected.val()) {
            $searchInput.val($selected.text());
        }
    }

    /* =====================================================
    Update Counts Function
    ===================================================== */
    async function updateCounts(filter = '') {
        try {
            const res = await fetch(
                'controller/registrations.php?action=get_counts' +
                (filter ? '&status=' + encodeURIComponent(filter) : '') +
                '&_=' + Date.now()
            );
            const json = await res.json();

            if (json.success) {
                document.getElementById('totalContact').textContent = json.total_contact || 0;
                document.getElementById('totalHire').textContent = json.total_hire || 0;
                document.getElementById('totalRejected').textContent = json.total_rejected || 0;
            }
        } catch (error) {
            console.error('Failed to update counts:', error);
        }
    }

    /* =====================================================
    Init
    ===================================================== */
    $(document).ready(function() {
        // Build table header first
        $('#registrationsTable thead').html(`
            <tr>
                <th></th>
                ${visibleColumns.map(c => `<th>${headerMap[c]}</th>`).join('')}
                <th>Actions</th>
            </tr>
        `);

        // Load initial data
        const status = new URLSearchParams(window.location.search).get('status') || '';
        $('#statusFilter').val(status);
        
        // Initialize DataTables
        initDataTable();
        
        // Update counts
        updateCounts(status);
        
        // Initialize searchable select
        initSearchableSelect();

        // Status filter change
        $('#statusFilter').on('change', function() {
            const filterValue = this.value;
            // Reload DataTable
            if (dataTable) {
                dataTable.ajax.reload();
            }
            updateCounts(filterValue);
        });

        /* =====================================================
        Update Status Handler
        ===================================================== */
        $(document).on('click', '.update-status-btn', async function(e) {
            e.preventDefault();

            const btn = $(this);
            const tr = btn.closest('tr');
            const select = tr.find('.status-select');
            const hidden = tr.find('.current-status-value');
            const newStatus = normalizeStatus(select.val());
            const oldStatus = normalizeStatus(hidden.val());
            const id = btn.data('id');

            if (newStatus === oldStatus) {
                showToast('info', 'Status already selected');
                return;
            }

            if (newStatus === 'hire') {
                // Get row data from DataTable
                const rowData = dataTable.row(tr).data();

                if (rowData) {
                    // Prefill modal inputs
                    $('#hireName').val(rowData.name || '');
                    $('#hireTechnology').val(rowData.technology || '');
                    $('#hireEmail').val(rowData.email || '');
                    $('#hireTrainer').val('');
                    $('.searchable-input').val('');

                    // Store current row id in form dataset
                    $('#hireForm').data('id', id);

                    // Show modal
                    $('#hireModal').removeClass('hidden');
                } else {
                    showToast('error', 'Could not load registration data');
                }

            } else {
                // For other statuses, confirm and update directly
                if (!confirm(`Change status to "${select.val()}"?`)) return;

                try {
                    LoaderManager.showGlobal();
                    
                    const res = await fetch('controller/registrations.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            action: 'update_status',
                            id: id,
                            status: select.val()
                        })
                    });

                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);

                    if (json.success) {
                        // Reload DataTable
                        if (dataTable) {
                            dataTable.ajax.reload();
                        }
                        updateCounts($('#statusFilter').val());
                    }
                } catch (error) {
                    showToast('error', 'Update failed: ' + error.message);
                } finally {
                    LoaderManager.hideGlobal();
                }
            }
        });

        // Cancel modal button handler
        $('#hireCancelBtn').on('click', function() {
            $('#hireModal').addClass('hidden');
        });

        // Modal form submission handler
        $('#hireForm').on('submit', async function(e) {
            e.preventDefault();

            const id = $(this).data('id');
            const trainer = $('#hireTrainer').val();

            if (trainer === '') {
                alert('Please select a supervisor.');
                return;
            }

            if (!confirm('Submit hire details?')) return;

            try {
                LoaderManager.showGlobal();
                
                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update_hire_status',
                        id: id,
                        trainer: trainer
                    })
                });

                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);

                if (json.success) {
                    $('#hireModal').addClass('hidden');
                    // Reload DataTable
                    if (dataTable) {
                        dataTable.ajax.reload();
                    }
                    updateCounts($('#statusFilter').val());
                }
            } catch (error) {
                showToast('error', 'Submission failed: ' + error.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });
    });
</script>
</body>
</html>