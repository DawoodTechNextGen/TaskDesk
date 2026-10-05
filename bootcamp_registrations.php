<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Admin and Manager only, plus read-only Collaborators
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4 && $_SESSION['user_role'] != 5)) {
    header('Location: index.php');
    exit;
}

include_once './include/connection.php';
include_once './include/bootcamp_helper.php';
requirePageModule(MODULE_BOOTCAMP);

// The sidebar's Bootcamp submenu points every stage at this one page with
// ?status=, so the heading names whichever stage was opened.
$stage_labels = [
    'new'      => 'New Enrollments',
    'contact'  => 'Contacted Enrollments',
    'enrolled' => 'Enrolled',
    'completed' => 'Completed',
    'rejected' => 'Rejected Enrollments',
];
$stage = $_GET['status'] ?? '';
$page_heading = $stage_labels[$stage] ?? 'Bootcamp Enrollments';

// Bootcamp filter: ?bootcamp_id= comes from the "Enrollments" link on bootcamps.php.
$bootcampOptions = [];
try {
    $bcResult = $conn->query("SELECT id, title, status FROM " . BOOTCAMPS_TABLE . " ORDER BY created_at DESC");
    $bootcampOptions = $bcResult ? $bcResult->fetch_all(MYSQLI_ASSOC) : [];
} catch (\Throwable $e) {
    $bootcampOptions = [];
}
$selectedBootcamp = (string)($_GET['bootcamp_id'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = $page_heading . ' - TaskDesk';
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
                <div class="flex flex-wrap gap-3 justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white"><?php echo $page_heading; ?></h2>
                    <div class="flex items-center gap-3">
                        <select id="bootcamp-filter" class="px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm">
                            <option value="">All Bootcamps</option>
                            <?php foreach ($bootcampOptions as $bc): ?>
                                <option value="<?= (int)$bc['id'] ?>" <?= $selectedBootcamp === (string)$bc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($bc['title']) ?> (<?= htmlspecialchars(ucfirst($bc['status'])) ?>)</option>
                            <?php endforeach; ?>
                            <option value="none" <?= $selectedBootcamp === 'none' ? 'selected' : '' ?>>Unassigned (older sign-ups)</option>
                        </select>
                        <a href="bootcamps.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Manage Bootcamps</a>
                    </div>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white"><?php echo $page_heading; ?></h2>
                    </div>
                    <div class="table-container">
                        <!-- Table Loader -->
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading enrollments...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="bootcampTable" class="min-w-full">
                                <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
<script>
    /* =====================================================
    Loader Management
    ===================================================== */
    const LoaderManager = {
        showGlobal: function() {
            document.getElementById('globalLoader').classList.remove('hidden');
        },

        hideGlobal: function() {
            document.getElementById('globalLoader').classList.add('hidden');
        }
    };


    // Which stage this page is showing (New / Contact / Enrolled / Rejected / all),
    // decided server-side by the sidebar link's ?status= - there is no on-page
    // filter for it any more.
    const currentStage = <?php echo json_encode($stage); ?>;

    /* =====================================================
    Columns
    ===================================================== */
    // Province, CNIC and Created At are not shown as table columns any more -
    // like registrations.php, they live in the expand-row instead, opened with
    // the "+" icon.
    const visibleColumns = [
        'name',
        'bootcamp_title',
        'email',
        'mbl_number',
        'city',
        'status'
    ];

    const expandableColumns = [
        'province',
        'cnic',
        'created_at',
        'completed_at',
        'certificate_code'
    ];

    const headerMap = {
        name: 'Name',
        bootcamp_title: 'Bootcamp',
        email: 'Email',
        mbl_number: 'WhatsApp',
        province: 'Province',
        city: 'City',
        cnic: 'CNIC',
        created_at: 'Created At',
        completed_at: 'Completed On',
        certificate_code: 'Certificate ID',
        status: 'Status'
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
    Status Normalizer
    ===================================================== */
    function normalizeStatus(val) {
        if (!val) return 'new';
        return String(val).toLowerCase();
    }

    // Which dropdown option a row currently sits on. `contact` is one status in
    // the database but two options in the dropdown, so email_status decides which
    // of the two to show back (1 = email sent, 2 = email failed, 3 = WhatsApp).
    function currentDropdownValue(row) {
        const status = normalizeStatus(row.status);
        if (status !== 'contact') return status;
        return (Number(row.email_status) === 1 || Number(row.email_status) === 2)
            ? 'contact_email'
            : 'contact_whatsapp';
    }

    /* =====================================================
    Status Dropdown
    ===================================================== */
    // "Contact" is split into two options the same way registrations_new.php does
    // it - picking one both moves the row to `contact` and records how the
    // enrollee was reached (WhatsApp, or a template email sent right away).
    // Each stage's list only offers the options relevant to that stage - the
    // "All" view (no status filter) gets the full set so a row can still be
    // corrected manually from there.
    const STAGE_OPTIONS = {
        new: [
            ['new', 'New'],
            ['contact_whatsapp', 'Contact by WhatsApp'],
            ['contact_email', 'Contact by Email']
        ],
        contact: [
            ['contact', 'Contact'],
            ['enrolled', 'Enrolled'],
            ['rejected', 'Rejected']
        ],
        // Completed = finished the bootcamp; saving it emails the certificate.
        enrolled: [
            ['enrolled', 'Enrolled'],
            ['completed', 'Completed + Certificate'],
            ['rejected', 'Rejected']
        ],
        all: [
            ['new', 'New'],
            ['contact_whatsapp', 'Contact by WhatsApp'],
            ['contact_email', 'Contact by Email'],
            ['enrolled', 'Enrolled'],
            ['completed', 'Completed + Certificate'],
            ['rejected', 'Rejected']
        ]
    };

    // certificate_status: 0 = not sent, 1 = sent, 2 = failed
    function certificateBadge(row) {
        const s = Number(row.certificate_status);
        if (s === 1) return '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Certificate sent</span>';
        if (s === 2) return '<span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">Certificate failed</span>';
        return '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">Not sent</span>';
    }

    function createStatusDropdown(current, id, options) {
        const optionsHtml = options.map(([value, label]) => `<option value="${value}">${label}</option>`).join('');
        return `
<select class="status-select px-2 py-1 border rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs w-36"
        data-id="${id}"
        data-current="${current}">
    ${optionsHtml}
</select>`;
    }

    /* =====================================================
    Actions Column
    ===================================================== */
    function renderActions(row) {
        // A completed row is finished - its only action is the certificate, in
        // every view (no stage dropdown).
        if (normalizeStatus(row.status) === 'completed') {
            return `
<div class="flex items-center gap-2 whitespace-nowrap">
    ${certificateBadge(row)}
    <button class="px-2 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs resend-certificate-btn" data-id="${row.id}">
        ${Number(row.certificate_status) === 1 ? 'Resend' : 'Send'} Certificate
    </button>
    <button class="px-2 py-1 bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-100 rounded text-xs undo-completion-btn" data-id="${row.id}" data-name="${escapeHTML(row.name)}" title="Move back to Enrolled">
        Undo
    </button>
</div>`;
        }

        const options = STAGE_OPTIONS[currentStage] || STAGE_OPTIONS.all;
        // In the New/Contact/Enrolled lists every row already sits on that stage, so
        // the dropdown's current value is just the stage itself. The "All" view mixes
        // every stage, so it falls back to reading the row's own status.
        const current = ['new', 'contact', 'enrolled'].includes(currentStage) ? currentStage : currentDropdownValue(row);

        return `
<div class="flex items-center space-x-2">
    ${createStatusDropdown(current, row.id, options)}
    <button
        class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs update-status-btn"
        data-id="${row.id}">
        Update
    </button>
    <input type="hidden" class="current-status-value" value="${current}">
</div>`;
    }

    /* =====================================================
    Render Table Function - SERVER SIDE
    ===================================================== */
    let dataTable = null;

    function initDataTable() {
        if (dataTable) {
            dataTable.ajax.reload();
            return;
        }

        dataTable = $('#bootcampTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/bootcamp_registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'get_bootcamp_registrations';
                    d.status = currentStage;
                    d.bootcamp_id = document.getElementById('bootcamp-filter').value;
                }
            },
            columns: [
                {
                    class: 'details-control cursor-pointer text-center font-bold select-none',
                    orderable: false,
                    data: null,
                    defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                },
                { data: 'name' },
                {
                    data: 'bootcamp_title',
                    render: function(data) {
                        return data ? escapeHTML(data) : '<span class="text-gray-400 italic">Unassigned</span>';
                    }
                },
                { data: 'email' },
                { data: 'mbl_number' },
                { data: 'city' },
                {
                    data: 'status',
                    render: function(data, type, row) {
                        const s = normalizeStatus(data);
                        const map = {
                            new: ['NEW', 'bg-blue-600'],
                            contact: ['CONTACT', 'bg-yellow-500'],
                            enrolled: ['ENROLLED', 'bg-green-600'],
                            completed: ['COMPLETED', 'bg-purple-600'],
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
            order: [[1, 'asc']], // Order by name by default
            language: {
                processing: '<div class="loader-small"></div> Processing...',
                emptyTable: 'No data available in table',
                zeroRecords: 'No matching records found'
            },
            drawCallback: function(settings) {
                // Put each row's dropdown back on its stored status after a redraw
                document.querySelectorAll('.status-select').forEach(select => {
                    select.value = select.dataset.current;
                });
            }
        });

        // Set up row expansion
        $('#bootcampTable tbody').on('click', 'td.details-control', function() {
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
    Init
    ===================================================== */
    $(document).ready(function() {
        // Build table header first
        $('#bootcampTable thead').html(`
            <tr>
                <th></th>
                ${visibleColumns.map(c => `<th>${headerMap[c]}</th>`).join('')}
                <th>Actions</th>
            </tr>
        `);

        // Initialize DataTables - the sidebar's New/Contact/Enrolled/Rejected links
        // pick the stage, and the bootcamp dropdown narrows it to one bootcamp.
        initDataTable();

        document.getElementById('bootcamp-filter').addEventListener('change', function() {
            const url = new URL(window.location.href);
            if (this.value) {
                url.searchParams.set('bootcamp_id', this.value);
            } else {
                url.searchParams.delete('bootcamp_id');
            }
            history.replaceState(null, '', url);
            dataTable.ajax.reload();
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
            const selected = select.val();
            const previous = hidden.val();
            const id = btn.data('id');

            if (selected === previous) {
                showToast('info', 'Status already selected');
                return;
            }

            // Both contact options land on the `contact` status - what differs is
            // how the enrollee is reached.
            const finalStatus = selected.startsWith('contact_') ? 'contact' : selected;

            if (selected === 'contact_email') {
                if (!confirm('Are you sure you want to contact this enrollee by email? A predefined template email will be sent automatically.')) return;
            } else if (selected === 'completed') {
                if (!confirm('Mark this student as completed? Their bootcamp certificate will be emailed to them right away.')) return;
            } else if (!confirm('Are you sure you want to update this status?')) {
                return;
            }

            try {
                LoaderManager.showGlobal();

                const body = {
                    action: 'update_status',
                    id: id,
                    status: finalStatus
                };
                if (selected === 'contact_email') {
                    body.send_email = '1';
                } else if (selected === 'contact_whatsapp') {
                    body.contact_via = 'whatsapp';
                }

                const res = await fetch('controller/bootcamp_registrations.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(body)
                });

                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);

                if (json.success) {
                    // Reload DataTable
                    if (dataTable) {
                        dataTable.ajax.reload();
                    }
                }
            } catch (error) {
                showToast('error', 'Update failed: ' + error.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });

        $(document).on('click', '.undo-completion-btn', async function(e) {
            e.preventDefault();
            const name = $(this).data('name');
            if (!confirm(`Undo completion for ${name}?\n\nThey will move back to Enrolled, and the certificate already emailed to them will show as NOT valid on the verification page until they are marked completed again.`)) return;

            try {
                LoaderManager.showGlobal();
                const res = await fetch('controller/bootcamp_registrations.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'undo_completion', id: $(this).data('id') })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success && dataTable) dataTable.ajax.reload(null, false);
            } catch (error) {
                showToast('error', 'Undo failed: ' + error.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });

        $(document).on('click', '.resend-certificate-btn', async function(e) {
            e.preventDefault();
            if (!confirm('Email this student their bootcamp certificate?')) return;

            try {
                LoaderManager.showGlobal();
                const res = await fetch('controller/bootcamp_registrations.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'resend_certificate', id: $(this).data('id') })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (dataTable) dataTable.ajax.reload(null, false);
            } catch (error) {
                showToast('error', 'Sending failed: ' + error.message);
            } finally {
                LoaderManager.hideGlobal();
            }
        });
    });
</script>
</body>

</html>
