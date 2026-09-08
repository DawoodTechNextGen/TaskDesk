<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Admin, Manager, and read-only Collaborators - same access as the rest of the
// registrations pipeline.
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 4 && $_SESSION['user_role'] != 5)) {
    header('Location: index.php');
    exit;
}

include_once './include/connection.php';
requirePageModule(MODULE_REGISTRATIONS);
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Assessment Pipeline - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<style>
    .expand-icon { width: 10px; height: 10px; display: inline-block; position: relative; cursor: pointer; transition: transform 300ms ease; }
    .expand-icon .bar { position: absolute; background-color: currentColor; border-radius: 2px; }
    .expand-icon .horizontal { width: 100%; height: 1.5px; top: 50%; left: 0; transform: translateY(-50%); }
    .expand-icon .vertical { height: 100%; width: 1.5px; left: 50%; top: 0; transform: translateX(-50%); }
    tr.shown .expand-icon { transform: rotate(45deg); }
    .details-wrapper { display: none; overflow: hidden; }
    .filter-btn.active { background-color: #4f46e5; color: #fff; }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Assessment Pipeline</h2>
                    <div class="flex items-center space-x-2">
                        <button id="refreshTableBtn" type="button" title="Refresh" class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg shadow-md transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                        <button id="bulkRejectAssessmentBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold transition-all shadow-md flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Bulk Reject (> 15 Days)</span>
                        </button>
                    </div>
                </div>

                <div class="mb-4 flex flex-wrap gap-2">
                    <button class="filter-btn active px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200" data-result="">All</button>
                    <button class="filter-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200" data-result="pending">Pending</button>
                    <button class="filter-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200" data-result="in_progress">In Progress</button>
                    <button class="filter-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200" data-result="pass">Pass</button>
                    <button class="filter-btn px-4 py-2 rounded-lg text-sm font-semibold bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200" data-result="fail">Fail</button>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="assessmentTable" class="min-w-full">
                            <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- Hire Modal -->
    <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Hire Candidate</h3>
                <button type="button" id="hireCancelBtn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="hireForm" class="space-y-6">
                <input type="hidden" id="hireId" name="id">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Candidate Name</label>
                        <input type="text" id="hireName" class="w-full px-3 py-2.5 border rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Technology</label>
                        <input type="text" id="hireTechnology" class="w-full px-3 py-2.5 border rounded-lg text-gray-800 dark:text-gray-200" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Internship Duration <span class="text-red-500">*</span></label>
                        <select id="hireDuration" name="hireDuration" class="w-full px-3 py-2.5 border rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" required>
                            <option value="">Select Duration</option>
                            <option value="4 weeks">4 weeks</option>
                            <option value="8 weeks">8 weeks</option>
                            <option value="12 weeks">12 weeks</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Supervisor</label>
                        <div class="searchable-wrapper relative w-full">
                            <select id="hireTrainer" class="searchable-select hidden" name="hireTrainer" required>
                                <option value="">Select Supervisor</option>
                                <?php
                                $userQuery = "SELECT id, name FROM users WHERE user_role = 3 ORDER BY name ASC";
                                $userResult = mysqli_query($conn, $userQuery);
                                while ($user = mysqli_fetch_assoc($userResult)) {
                                    echo "<option value=\"{$user['id']}\">{$user['name']}</option>";
                                }
                                ?>
                            </select>
                            <div class="relative">
                                <input type="text" class="searchable-input w-full px-3 py-2.5 pr-10 border rounded-lg bg-white dark:bg-gray-800 dark:text-gray-200 cursor-pointer" placeholder="Select Supervisor" autocomplete="off" required>
                            </div>
                            <ul class="searchable-dropdown hidden absolute z-50 w-full bg-white dark:bg-gray-800 border rounded-lg mt-1 max-h-60 overflow-y-auto shadow-lg"></ul>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="cancelHireBtn" class="px-4 py-2.5 border rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700">Hire Candidate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Schedule Interview Modal -->
    <div id="interviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Schedule Interview</h3>
                <button type="button" id="interviewCancelBtn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form id="interviewForm" class="space-y-4">
                <input type="hidden" id="interviewId" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Candidate Name</label>
                    <input type="text" id="interviewName" class="w-full px-3 py-2.5 border rounded-lg text-gray-800 dark:text-gray-200" readonly>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start</label>
                        <input type="datetime-local" id="interviewStart" name="interview_start" class="w-full px-3 py-2.5 border rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End</label>
                        <input type="datetime-local" id="interviewEnd" name="interview_end" class="w-full px-3 py-2.5 border rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Platform</label>
                    <select id="interviewPlatform" name="platform" class="w-full px-3 py-2.5 border rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200" required>
                        <option value="Google Meet">Google Meet</option>
                        <option value="Zoom">Zoom</option>
                        <option value="Physical">Physical</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="cancelInterviewBtn" class="px-4 py-2.5 border rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resend Assessment Modal -->
    <div id="resendModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800 dark:text-white">Resend Assessment</h3>
                <button type="button" id="resendCancelBtn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">This creates a fresh, single attempt and resets the candidate's login password. They'll be emailed/WhatsApp'd new credentials.</p>
            <form id="resendForm" class="space-y-4">
                <input type="hidden" id="resendId" name="id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assessment</label>
                    <select id="resendAssessmentId" name="assessment_id" class="w-full px-3 py-2.5 border rounded-lg bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                        <option value="">Same assessment as last time</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" id="cancelResendBtn" class="px-4 py-2.5 border rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" class="px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Resend</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
    <script>
        // Row "Actions" dropdown (Resend/Interview/Hire/Reject) - delegated since
        // DataTables re-renders rows, and only one menu open at a time.
        $(document).on('click', '.actions-toggle-btn', function(e) {
            e.stopPropagation();
            const menu = $(this).siblings('.actions-dropdown-menu');
            $('.actions-dropdown-menu').not(menu).addClass('hidden');
            menu.toggleClass('hidden');
        });
        $(document).on('click', function() {
            $('.actions-dropdown-menu').addClass('hidden');
        });

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
        function escapeHTML(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        let currentResult = '';

        $(document).ready(function() {
            $('#assessmentTable thead').html(`
                <tr>
                    <th></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Technology</th>
                    <th>Assessment</th>
                    <th>Result</th>
                    <th>Score</th>
                    <th>Sent</th>
                    <th>Actions</th>
                </tr>
            `);

            const table = $('#assessmentTable').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: 'controller/registrations.php',
                    type: 'GET',
                    data: function(d) {
                        d.action = 'assessment';
                        d.result = currentResult;
                    }
                },
                columns: [
                    {
                        class: 'details-control cursor-pointer text-center font-bold',
                        orderable: false,
                        data: null,
                        defaultContent: `<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>`
                    },
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'mbl_number' },
                    { data: 'technology' },
                    { data: 'assessment_title', defaultContent: '-' },
                    {
                        data: 'assessment_status',
                        render: function(status) {
                            const map = {
                                pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                pass: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                fail: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                            };
                            const label = { pending: 'Pending', in_progress: 'In Progress', pass: 'Pass', fail: 'Fail' }[status] || status;
                            return `<span class="px-2 py-1 rounded-full text-xs font-semibold ${map[status] || ''}">${label}</span>`;
                        }
                    },
                    {
                        data: 'percentage',
                        render: function(p) { return p !== null ? `${parseFloat(p).toFixed(0)}%` : '-'; }
                    },
                    { data: 'created_at' },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                            <div class="relative inline-block text-left actions-dropdown-wrapper">
                                <button type="button" class="actions-toggle-btn px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold inline-flex items-center gap-1">
                                    Actions
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>
                                <div class="actions-dropdown-menu hidden absolute right-0 mt-1 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-20 overflow-hidden">
                                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-amber-50 dark:hover:bg-amber-950/40 resend-btn" data-id="${row.id}" data-assessment-id="${row.assessment_id || ''}">Resend</button>
                                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 schedule-btn" data-id="${row.id}">Interview</button>
                                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-green-50 dark:hover:bg-green-950/40 hire-btn" data-id="${row.id}">Hire</button>
                                    <button class="w-full text-left px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 reject-btn" data-id="${row.id}">Reject</button>
                                </div>
                            </div>`;
                        }
                    }
                ],
                order: [[1, 'desc']],
                language: { emptyTable: 'No candidates in the assessment pipeline', zeroRecords: 'No matching candidates found' }
            });

            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                currentResult = $(this).data('result') || '';
                table.ajax.reload();
            });

            $('#assessmentTable tbody').on('click', 'td.details-control', function() {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                if (row.child.isShown()) {
                    $(row.child()).find('.details-wrapper').slideUp(300, function() { row.child.hide(); tr.removeClass('shown'); });
                } else {
                    const d = row.data();
                    row.child(`<div class="details-wrapper"><div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">
                        <div><span class="font-semibold">Email:</span> ${escapeHTML(d.email)}</div>
                        <div><span class="font-semibold">Violations:</span> ${d.violation_count ?? 0}</div>
                        <div><span class="font-semibold">Fail Reason:</span> ${escapeHTML(d.fail_reason) || '-'}</div>
                        <div><span class="font-semibold">Completed At:</span> ${escapeHTML(d.completed_at) || '-'}</div>
                    </div></div>`).show();
                    tr.addClass('shown');
                    $(row.child()).find('.details-wrapper').slideDown(300);
                }
            });

            // Hire
            $(document).on('click', '.hire-btn', function() {
                const row = table.row($(this).closest('tr')).data();
                $('#hireId').val(row.id);
                $('#hireName').val(row.name);
                $('#hireTechnology').val(row.technology);
                $('#hireModal').removeClass('hidden');
            });
            $('#hireCancelBtn, #cancelHireBtn').on('click', () => $('#hireModal').addClass('hidden'));
            $('#hireForm').on('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'update_hire_status');
                const res = await fetch('controller/registrations.php', { method: 'POST', body: formData });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) { $('#hireModal').addClass('hidden'); table.ajax.reload(); }
            });

            // Interview
            $(document).on('click', '.schedule-btn', function() {
                const row = table.row($(this).closest('tr')).data();
                $('#interviewId').val(row.id);
                $('#interviewName').val(row.name);
                $('#interviewModal').removeClass('hidden');
            });
            $('#interviewCancelBtn, #cancelInterviewBtn').on('click', () => $('#interviewModal').addClass('hidden'));
            $('#interviewForm').on('submit', async function(e) {
                e.preventDefault();
                const toMysql = (v) => v.replace('T', ' ') + ':00';
                const formData = new FormData();
                formData.append('action', 'schedule_interview');
                formData.append('id', $('#interviewId').val());
                formData.append('interview_start', toMysql($('#interviewStart').val()));
                formData.append('interview_end', toMysql($('#interviewEnd').val()));
                formData.append('platform', $('#interviewPlatform').val());
                formData.append('name', $('#interviewName').val());
                formData.append('contact', '');
                const res = await fetch('controller/registrations.php', { method: 'POST', body: formData });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) { $('#interviewModal').addClass('hidden'); table.ajax.reload(); }
            });

            // Reject
            $(document).on('click', '.reject-btn', async function() {
                if (!confirm('Reject this candidate? They will be notified by email.')) return;
                const id = $(this).data('id');
                const res = await fetch('controller/registrations.php', { method: 'POST', body: new URLSearchParams({ action: 'reject_candidate', id }) });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) table.ajax.reload();
            });

            // Refresh table without reloading the page
            $('#refreshTableBtn').on('click', function() {
                const icon = $(this).find('svg');
                icon.addClass('animate-spin');
                table.ajax.reload(null, false);
                setTimeout(() => icon.removeClass('animate-spin'), 500);
            });

            // Bulk Reject - candidates who haven't completed their assessment within 15 days
            $('#bulkRejectAssessmentBtn').on('click', async function() {
                if (!confirm("Reject all candidates who haven't completed their assessment within 15 days of it being sent? They will be notified by email.")) return;

                const btn = $(this);
                btn.prop('disabled', true).addClass('opacity-60 cursor-not-allowed');
                try {
                    const res = await fetch('controller/registrations.php', { method: 'POST', body: new URLSearchParams({ action: 'bulk_reject_pending_assessments' }) });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) table.ajax.reload();
                } catch (e) {
                    showToast('error', 'Request failed: ' + e.message);
                } finally {
                    btn.prop('disabled', false).removeClass('opacity-60 cursor-not-allowed');
                }
            });

            // Resend
            $(document).on('click', '.resend-btn', async function() {
                const id = $(this).data('id');
                const assessmentId = $(this).data('assessment-id');
                $('#resendId').val(id);

                const row = table.row($(this).closest('tr')).data();
                $('#resendAssessmentId').html('<option value="">Same assessment as last time</option>');
                try {
                    const listRes = await fetch(`controller/assessments.php?action=active_by_technology&technology_id=${row.technology_id || ''}`);
                    const listJson = await listRes.json();
                    if (listJson.success) {
                        listJson.data.forEach(a => {
                            $('#resendAssessmentId').append(`<option value="${a.id}" ${a.id == assessmentId ? 'selected' : ''}>${escapeHTML(a.title)}</option>`);
                        });
                    }
                } catch (e) { /* dropdown stays on default option */ }

                $('#resendModal').removeClass('hidden');
            });
            $('#resendCancelBtn, #cancelResendBtn').on('click', () => $('#resendModal').addClass('hidden'));
            $('#resendForm').on('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'resend_assessment');
                const res = await fetch('controller/registrations.php', { method: 'POST', body: formData });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) { $('#resendModal').addClass('hidden'); table.ajax.reload(); }
            });
        });
    </script>
</body>
</html>
