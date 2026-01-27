<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<?php
$page_title = 'Completed Interns - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Completed Interns</h2>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Internship Completed</h2>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="completedTable" class="min-w-full">
                            <thead class="bg-green-200 dark:bg-green-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Technology</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Joining Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Completion Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Certificate Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs text-gray-800 dark:text-gray-100"></tbody>
                        </table>
                    </div>
                </div>
            </main>
            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <!-- View Internee Modal -->
    <div id="view-internee-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50">Internee Details</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            
            <div class="space-y-5 overflow-y-scroll max-h-96 custom-scrollbar">
                <!-- Personal Info -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">Personal Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Full Name</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-name">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Email</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-email">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Internee ID</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-id">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Status</p>
                            <span id="view-status" class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Completed</span>
                        </div>
                    </div>
                </div>

                <!-- Technology & Progress -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">Technology & Progress</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Assigned Technology</p>
                            <p class="font-medium text-indigo-600 dark:text-indigo-400" id="view-technology">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</p>
                            <div class="flex items-center space-x-2">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-indigo-600 h-2 rounded-full" id="view-progress-bar"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" id="view-completion">0%</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Attendance Rate</p>
                            <div class="flex items-center space-x-2">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-emerald-600 h-2 rounded-full" id="view-attendance-bar"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" id="view-attendance">0%</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Months Completed</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-months">0</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Certificate Status</p>
                            <span id="view-cert-status" class="inline-flex px-2 py-1 text-xs font-medium rounded-full">Not Eligible</span>
                        </div>
                    </div>
                </div>

                <!-- Supervisor Info -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">Supervisor Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Supervisor Name</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-supervisor">-</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Supervisor ID</p>
                            <p class="font-medium text-gray-900 dark:text-gray-100" id="view-supervisor-id">-</p>
                        </div>
                    </div>
                </div>

                <!-- Eligibility Criteria -->
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">Certificate Eligibility</h4>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Completion Rate ≥ 75%</span>
                            <span id="view-eligibility-rate" class="text-sm font-medium text-red-600 dark:text-red-400">✗ Not Met</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Attendance Rate ≥ 75%</span>
                            <span id="view-eligibility-attendance" class="text-sm font-medium text-red-600 dark:text-red-400">✗ Not Met</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Months Completed ≥ 3</span>
                            <span id="view-eligibility-months" class="text-sm font-medium text-red-600 dark:text-red-400">✗ Not Met</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">Overall Eligibility</span>
                            <span id="view-eligibility-overall" class="text-sm font-medium text-red-600 dark:text-red-400">Not Eligible</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const style = document.createElement('style');
        style.textContent = `
            .approve-loader {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid rgba(245, 158, 11, .3);
                border-radius: 50%;
                border-top-color: #f59e0b;
                animation: spin 1s ease-in-out infinite;
                vertical-align: middle;
            }
            @keyframes spin { to { transform: rotate(360deg); } }
            .btn-disabled { opacity: 0.7; cursor: not-allowed !important; pointer-events: none; }
        `;
        document.head.appendChild(style);

        const table = $('#completedTable').DataTable({
            ordering: false,
            pageLength: 10,
            language: {
                emptyTable: "No completed interns",
                info: "Showing _START_ to _END_ of _TOTAL_ completed interns",
                infoEmpty: "Showing 0 to 0 of 0 completed interns"
            }
        });

        async function loadCompletedInterns() {
            try {
                const res = await fetch('controller/user.php?action=get_completed_internees');
                const json = await res.json();

                if (json.success) {
                    table.clear();
                    json.data.forEach(u => {
                        const certStatus = u.approve_status == 1 
                            ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Approved</span>'
                            : '<span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Pending</span>';
                        
                        let actionsHTML = '<div class="flex items-center space-x-2">';
                        
                        // View Button
                        actionsHTML += `
                            <button class="view-internee text-green-600 hover:text-green-800 transition-colors" 
                                    data-id="${u.id}"
                                    data-name="${u.name}"
                                    data-email="${u.email || ''}"
                                    data-tech-name="${u.tech_name || ''}"
                                    data-supervisor-name="${u.supervisor_name || ''}"
                                    data-supervisor-id="${u.supervisor_id || ''}"
                                    data-completion="${u.completion_rate || 0}"
                                    data-attendance="${u.attendance_rate || 0}"
                                    data-months="${u.months_completed || 0}"
                                    data-days-left="${u.days_left || 0}"
                                    data-type="${u.internship_type || 0}"
                                    data-approved="${u.approve_status || 0}"
                                    title="View Details">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21.544 11.045C21.848 11.4713 22 11.6845 22 12C22 12.3155 21.848 12.5287 21.544 12.955C20.1779 14.8706 16.6892 19 12 19C7.31078 19 3.8221 14.8706 2.45604 12.955C2.15201 12.5287 2 12.3155 2 12C2 11.6845 2.15201 11.4713 2.45604 11.045C3.8221 9.12944 7.31078 5 12 5C16.6892 5 20.1779 9.12944 21.544 11.045Z" stroke="currentColor" stroke-width="1.5"></path>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                                </svg>
                            </button>`;

                        // Approve Certificate Button (always show if not approved)
                        if (u.approve_status != 1) {
                            actionsHTML += `
                                <button class="approve-certificate text-amber-500 hover:text-amber-600 transition-colors" 
                                        data-id="${u.id}"
                                        data-name="${u.name}"
                                        data-completion="${u.completion_rate || 0}"
                                        data-attendance="${u.attendance_rate || 0}"
                                        data-months="${u.months_completed || 0}"
                                        data-days-left="${u.days_left || 0}"
                                        data-type="${u.internship_type || 0}"
                                        title="Approve Certificate">
                                    <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="16" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                                        <path d="M12 19.2599L9.73713 21.4293C9.41306 21.74 9.25102 21.8953 9.1138 21.9491C8.80111 22.0716 8.45425 21.9667 8.28977 21.7C8.21758 21.583 8.19509 21.3719 8.1501 20.9496C8.1247 20.7113 8.112 20.5921 8.07345 20.4922C7.98715 20.2687 7.80579 20.0948 7.57266 20.0121C7.46853 19.9751 7.3442 19.963 7.09553 19.9386C6.65512 19.8955 6.43491 19.8739 6.31283 19.8047C6.03463 19.647 5.92529 19.3145 6.05306 19.0147C6.10913 18.8832 6.27116 18.7278 6.59523 18.4171L8.07345 16.9999L9.1138 15.9596" stroke="currentColor" stroke-width="1.5"></path>
                                        <path d="M12 19.2599L14.2629 21.4294C14.5869 21.7401 14.749 21.8954 14.8862 21.9492C15.1989 22.0717 15.5457 21.9668 15.7102 21.7001C15.7824 21.5831 15.8049 21.372 15.8499 20.9497C15.8753 20.7113 15.888 20.5921 15.9265 20.4923C16.0129 20.2688 16.1942 20.0949 16.4273 20.0122C16.5315 19.9752 16.6558 19.9631 16.9045 19.9387C17.3449 19.8956 17.5651 19.874 17.6872 19.8048C17.9654 19.6471 18.0747 19.3146 17.9469 19.0148C17.8909 18.8832 17.7288 18.7279 17.4048 18.4172L15.9265 17L15 16.0735" stroke="currentColor" stroke-width="1.5"></path>
                                        <path d="M17.3197 17.9957C19.2921 17.9748 20.3915 17.8512 21.1213 17.1213C22 16.2426 22 14.8284 22 12V9M7 17.9983C4.82497 17.9862 3.64706 17.8897 2.87868 17.1213C2 16.2426 2 14.8284 2 12L2 8C2 5.17157 2 3.75736 2.87868 2.87868C3.75736 2 5.17157 2 8 2L16 2C18.8284 2 20.2426 2 21.1213 2.87868C21.6112 3.36857 21.828 4.02491 21.9239 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M9 6L15 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                        <path d="M7 9.5H9M17 9.5H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                    </svg>
                                </button>`;
                        } else {
                            actionsHTML += `<span class="text-green-500" title="Approved">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12Z" stroke="currentColor" stroke-width="1.5"/>
                                    <path d="M8 12.5L10.5 15L16 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>`;
                        }

                        actionsHTML += '</div>';

                        table.row.add([
                            u.id,
                            u.name,
                            u.email || '<em class="text-gray-400">No email</em>',
                            u.tech_name ? `<span class="text-green-600 font-medium">${u.tech_name}</span>` : '<em class="text-gray-400">Not assigned</em>',
                            formatDate(u.joining_date),
                            formatDate(u.completion_date),
                            certStatus,
                            actionsHTML
                        ]);
                    });
                    table.draw(false);
                }
            } catch (err) {
                console.error("Failed to load completed interns:", err);
                showToast('error', 'Failed to load completed interns');
            }
        }

        function viewInterneeDetails(btn) {
            const d = btn.dataset;
            document.getElementById('view-id').textContent = d.id;
            document.getElementById('view-name').textContent = d.name;
            document.getElementById('view-email').textContent = d.email || 'N/A';
            document.getElementById('view-technology').textContent = d.techName || 'Not Assigned';
            document.getElementById('view-supervisor').textContent = d.supervisorName || 'Not Assigned';
            document.getElementById('view-supervisor-id').textContent = d.supervisorId || 'N/A';
            document.getElementById('view-completion').textContent = `${d.completion}%`;
            document.getElementById('view-progress-bar').style.width = `${d.completion}%`;
            document.getElementById('view-attendance').textContent = `${d.attendance}%`;
            document.getElementById('view-attendance-bar').style.width = `${d.attendance}%`;
            document.getElementById('view-months').textContent = d.months;

            const completion = parseInt(d.completion);
            const attendance = parseInt(d.attendance);
            const months = parseInt(d.months);
            const daysLeft = parseInt(d.daysLeft);
            const type = parseInt(d.type);
            const approved = parseInt(d.approved);

            const certStatus = document.getElementById('view-cert-status');
            const rateEligibility = document.getElementById('view-eligibility-rate');
            const attendanceEligibility = document.getElementById('view-eligibility-attendance');
            const monthsEligibility = document.getElementById('view-eligibility-months');
            const overallEligibility = document.getElementById('view-eligibility-overall');

            // Certificate Status
            if (approved === 1) {
                certStatus.textContent = 'Approved';
                certStatus.className = 'inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
            } else {
                certStatus.textContent = 'Pending';
                certStatus.className = 'inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            }

            // Rate Eligibility
            if (completion >= 75) {
                rateEligibility.innerHTML = `<span class="text-green-600 dark:text-green-400">✓ Met (${completion}%)</span>`;
            } else {
                rateEligibility.innerHTML = `<span class="text-red-600 dark:text-red-400">✗ ${completion}% / 75%</span>`;
            }

            // Attendance Eligibility
            if (attendance >= 75) {
                attendanceEligibility.innerHTML = `<span class="text-green-600 dark:text-green-400">✓ Met (${attendance}%)</span>`;
            } else {
                attendanceEligibility.innerHTML = `<span class="text-red-600 dark:text-red-400">✗ ${attendance}% / 75%</span>`;
            }

            // Duration Eligibility
            const targetWeeks = type == 0 ? 4 : 12;
            if (daysLeft <= 0) {
                monthsEligibility.innerHTML = `<span class="text-green-600 dark:text-green-400">✓ Met (${targetWeeks} weeks reached)</span>`;
            } else {
                const weeksLeft = Math.ceil(daysLeft / 7);
                const leftDisplay = weeksLeft > 1 ? `${weeksLeft} weeks left` : (daysLeft > 1 ? `${daysLeft} days left` : `1 day left`);
                monthsEligibility.innerHTML = `<span class="text-red-600 dark:text-red-400">✗ ${leftDisplay} (Goal: ${targetWeeks}w)</span>`;
            }

            // Overall Eligibility
            if (completion >= 75 && attendance >= 75 && daysLeft <= 0) {
                overallEligibility.textContent = 'Eligible';
                overallEligibility.className = 'text-sm font-medium text-green-600 dark:text-green-400';
            } else {
                overallEligibility.textContent = 'Not Eligible';
                overallEligibility.className = 'text-sm font-medium text-red-600 dark:text-red-400';
            }

            document.getElementById('view-internee-modal').classList.remove('hidden');
        }

        async function approveCertificate(btn) {
            const internId = btn.dataset.id;
            const internName = btn.dataset.name;
            const completion = parseInt(btn.dataset.completion);
            const attendance = parseInt(btn.dataset.attendance);
            const daysLeft = parseInt(btn.dataset.daysLeft);
            const type = parseInt(btn.dataset.type);

            const targetWeeks = type == 0 ? 4 : 12;
            let confirmMsg = `Approve certificate for ${internName}?`;
            
            if (completion < 75 || attendance < 75 || daysLeft > 0) {
                let durationMsg = daysLeft <= 0 ? `Met (${targetWeeks} weeks)` : `${Math.ceil(daysLeft/7)} weeks remaining`;
                confirmMsg = `⚠️ WARNING: ${internName} does not fully meet eligibility:
- Completion: ${completion}% (Required: 75%)
- Attendance: ${attendance}% (Required: 75%)
- Duration: ${durationMsg} (Goal: ${targetWeeks}w)

Are you sure you still want to approve their certificate?`;
            }

            if (!confirm(confirmMsg)) return;

            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span class="approve-loader"></span>';
            btn.classList.add('btn-disabled');

            try {
                const res = await fetch('controller/certificate-approval.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'approve',
                        id: internId
                    })
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) await loadCompletedInterns();
            } catch (error) {
                showToast('error', 'Network error. Please try again.');
                console.error('Approve certificate error:', error);
            } finally {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-disabled');
            }
        }

        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const d = new Date(dateStr);
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return d.toLocaleDateString('en-US', options);
        }

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white font-medium shadow-lg animate-slide-in ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadCompletedInterns();

            document.addEventListener('click', e => {
                const viewBtn = e.target.closest('.view-internee');
                if (viewBtn) viewInterneeDetails(viewBtn);

                const approveBtn = e.target.closest('.approve-certificate');
                if (approveBtn) approveCertificate(approveBtn);

                const closeBtn = e.target.closest('.close-modal');
                if (closeBtn) closeBtn.closest('.modal').classList.add('hidden');
            });
        });
    </script>
</body>

</html>
