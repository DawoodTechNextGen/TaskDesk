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
?>

<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'New Registrations - TaskDesk';
include_once "./include/headerLinks.php";
?>


<style>
    /* ... (Same styles as registrations.php) ... */
    .expand-icon { width: 10px; height: 10px; display: inline-block; position: relative; cursor: pointer; transition: transform 300ms ease; }
    .expand-icon .bar { position: absolute; background-color: currentColor; border-radius: 2px; }
    .expand-icon .horizontal { width: 100%; height: 1.5px; top: 50%; left: 0; transform: translateY(-50%); }
    .expand-icon .vertical { height: 100%; width: 1.5px; left: 50%; top: 0; transform: translateX(-50%); }
    tr.shown .expand-icon { transform: rotate(45deg); }
    .loader-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center; align-items: center; z-index: 9999; transition: opacity 0.3s ease; }
    .loader { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .table-loader { display: none; text-align: center; padding: 20px; }
    .table-loader.active { display: block; }
    .table-container { position: relative; min-height: 200px; }
    
    .details-wrapper {
        display: none;
        overflow: hidden;
    }

    /* Force text and background visibility in Email Modal inputs/textarea */
    #emailContactModal input:not([readonly]), #emailContactModal textarea {
        color: #0F172A !important;
        background-color: #FFFFFF !important;
    }
    #emailContactModal input[readonly] {
        color: #64748B !important;
        background-color: #F1F5F9 !important;
    }
    
    /* Dark mode override */
    .dark #emailContactModal input:not([readonly]), .dark #emailContactModal textarea {
        color: #FFFFFF !important;
        background-color: #1F2937 !important;
    }
    .dark #emailContactModal input[readonly] {
        color: #94A3B8 !important;
        background-color: #374151 !important;
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
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">New Registrations</h2>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="table-container">
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading registrations...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="newRegistrationsTable" class="min-w-full">
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

    <!-- Email Contact Modal -->
    <div id="emailContactModal" class="modal hidden fixed inset-0 z-[999] bg-black/60 backdrop-blur-sm flex items-center justify-center transition-all duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden border border-gray-100 dark:border-gray-700 transform scale-95 transition-all duration-300">
            <!-- Header -->
            <div class="bg-[#1E293B] px-6 py-4 flex items-center justify-between border-b-4 border-[#06B6D4]">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-[#2563EB]/10 rounded-lg text-[#06B6D4]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white">Send Email Notification</h3>
                </div>
                <button type="button" class="close-email-modal text-gray-400 hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form id="emailContactForm" class="p-6 space-y-4">
                <input type="hidden" id="emailModalCandidateId" name="id">
                
                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Candidate Name</label>
                    <input type="text" id="emailModalCandidateName" readonly class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 outline-none cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Recipient Email</label>
                    <div class="relative flex items-center space-x-2">
                        <input type="email" id="emailModalCandidateEmail" name="email" required readonly class="flex-1 px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 cursor-not-allowed border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 outline-none transition-all">
                        <button type="button" id="editEmailBtn" title="Edit Email" class="p-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-lg transition-colors border border-gray-200 dark:border-gray-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Subject</label>
                    <input type="text" id="emailModalSubject" value="Application Update - DawoodTech NextGen" readonly class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-lg text-sm text-gray-700 dark:text-gray-200 outline-none cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Message Content</label>
                    <textarea id="emailModalMessage" required rows="6" placeholder="Type your email message here..." class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-450 dark:placeholder-gray-400 focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/25 outline-none resize-none transition-all custom-scrollbar"></textarea>
                </div>

                <!-- Action buttons -->
                <div class="flex justify-end items-center space-x-3 pt-2">
                    <button type="button" class="close-email-modal px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-[#2563EB] hover:bg-[#1D4ED8] text-white font-semibold rounded-lg text-sm shadow-md hover:shadow-lg focus:ring-4 focus:ring-[#2563EB]/30 transition-all flex items-center space-x-2">
                        <span>Send Email</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>
<script>
    /* Reused utilities */
    const LoaderManager = {
        showGlobal: function() { document.getElementById('globalLoader').classList.remove('hidden'); },
        hideGlobal: function() { document.getElementById('globalLoader').classList.add('hidden'); }
    };

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

    const expandableColumns = ['email', 'cnic', 'city', 'country', 'created_at'];
    const headerMap = { id: 'ID', name: 'Name', email: 'Email', mbl_number: 'Contact', technology: 'Technology', internship_type: 'Internship Type', experience: 'Experience', cnic: 'CNIC', city: 'City', country: 'Country', created_at: 'Created At' };

    function formatDetails(row) {
        return `<div class="details-wrapper"><div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${expandableColumns.map(k => `<div><span class="font-semibold">${headerMap[k]}:</span> <span>${escapeHTML(row[k] ?? '-')}</span></div>`).join('')}</div></div>`;
    }

$(document).ready(function() {

    $('#newRegistrationsTable thead').html(`
        <tr>
            <th></th>
            <th>ID</th>
            <th>Name</th>
            <th>Contact</th>
            <th>Technology</th>
            <th>Internship Type</th>
            <th>Experience</th>
            <th>Actions</th>
        </tr>
    `);

    const table = $('#newRegistrationsTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'controller/registrations.php',
            type: 'GET',
            data: function(d) {
                d.action = 'new'; // backend case 'new'
            }
        },
        columns: [
            {
                class: 'details-control cursor-pointer text-center font-bold',
                orderable: false,
                data: null,
                defaultContent: `
                    <span class="expand-icon">
                        <span class="bar horizontal"></span>
                        <span class="bar vertical"></span>
                    </span>`
            },
            { data: 'id' },
            { data: 'name' },
            { data: 'mbl_number' },
            { data: 'technology' },
            { data: 'internship_type_text' },
            { data: 'experience_text' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return `
                    <div class="flex items-center space-x-2">
                        <select class="status-select px-2 py-1 border rounded bg-white dark:bg-gray-700 text-xs w-36">
                            <option value="new" selected>New</option>
                            <option value="contact_whatsapp">Contact by WhatsApp</option>
                            <option value="contact_email">Contact by Email</option>
                        </select>
                        <button class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs update-btn"
                            data-id="${row.id}">
                            Update
                        </button>
                    </div>`;
                }
            }
        ],
        order: [[1, 'desc']],
        language: {
            emptyTable: 'No new registrations',
            zeroRecords: 'No new registrations found'
        }
    });

    // Expand row with animation
    $('#newRegistrationsTable tbody').on('click', 'td.details-control', function() {
        const tr = $(this).closest('tr');
        const row = table.row(tr);

        if (row.child.isShown()) {
            $(row.child()).find('.details-wrapper').slideUp(300, function() {
                row.child.hide();
                tr.removeClass('shown');
            });
        } else {
            row.child(formatDetails(row.data())).show();
            tr.addClass('shown');
            $(row.child()).find('.details-wrapper').slideDown(300);
        }
    });

    // Handle update button click
    $(document).on('click', '.update-btn', async function() {
        const id = $(this).data('id');
        const status = $(this).siblings('.status-select').val();

        if (status === 'contact_email') {
            // Open modal instead of updating directly
            const tr = $(this).closest('tr');
            const rowData = table.row(tr).data();

            $('#emailModalCandidateId').val(id);
            $('#emailModalCandidateName').val(rowData.name || '');
            
            // Set/Reset email field to readonly when opening modal
            const emailInput = $('#emailModalCandidateEmail');
            emailInput.val(rowData.email || '');
            emailInput.prop('readonly', true).attr('readonly', 'readonly');
            $('#editEmailBtn').html(`
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            `).attr('title', 'Edit Email');

            $('#emailModalMessage').val(''); // clear message

            // Open modal with smooth animation
            const modal = $('#emailContactModal');
            modal.removeClass('hidden').addClass('flex');
            setTimeout(() => {
                modal.find('.transform').removeClass('scale-95').addClass('scale-100');
            }, 50);
            return;
        }

        // Map contact_whatsapp to contact in database
        const finalStatus = (status === 'contact_whatsapp') ? 'contact' : status;

        if (!confirm('Are you sure you want to update this status?')) return;

        LoaderManager.showGlobal();
        try {
            const formData = new FormData();
            formData.append('action', 'update_registration_status');
            formData.append('id', id);
            formData.append('status', finalStatus);

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                showToast('success', json.message);
                table.ajax.reload();
            } else {
                showToast('error', json.message);
            }
        } catch (e) {
            showToast('error', e.message);
        } finally {
            LoaderManager.hideGlobal();
        }
    });

    // Handle modal form submit
    $('#emailContactForm').on('submit', async function(e) {
        e.preventDefault();
        
        const id = $('#emailModalCandidateId').val();
        const email = $('#emailModalCandidateEmail').val();
        const emailMessage = $('#emailModalMessage').val();
        
        // Hide modal
        closeEmailModal();
        
        LoaderManager.showGlobal();
        try {
            const formData = new FormData();
            formData.append('action', 'update_registration_status');
            formData.append('id', id);
            formData.append('status', 'contact'); // the status in DB is 'contact'
            formData.append('email', email);
            formData.append('send_email', '1');
            formData.append('email_message', emailMessage);

            const res = await fetch('controller/registrations.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.success) {
                showToast('success', json.message);
                table.ajax.reload();
            } else {
                showToast('error', json.message);
            }
        } catch (err) {
            showToast('error', err.message);
        } finally {
            LoaderManager.hideGlobal();
        }
    });

    // Toggle edit state for email field
    $('#editEmailBtn').on('click', function() {
        const emailInput = $('#emailModalCandidateEmail');
        const isReadOnly = emailInput.prop('readonly');
        
        if (isReadOnly) {
            // Make editable
            emailInput.prop('readonly', false).removeAttr('readonly');
            emailInput.focus();
            // Change button icon to checkmark/shield (Lock/Save icon)
            $(this).html(`
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            `).attr('title', 'Lock Email');
        } else {
            // Lock and make readonly
            emailInput.prop('readonly', true).attr('readonly', 'readonly');
            // Change button icon back to Pencil icon
            $(this).html(`
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            `).attr('title', 'Edit Email');
        }
    });

    // Close modal helper
    function closeEmailModal() {
        const modal = $('#emailContactModal');
        modal.find('.transform').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => {
            modal.addClass('hidden').removeClass('flex');
            
            // Reset email field to readonly
            const emailInput = $('#emailModalCandidateEmail');
            emailInput.prop('readonly', true).attr('readonly', 'readonly');
            $('#editEmailBtn').html(`
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            `).attr('title', 'Edit Email');
        }, 150);
    }

    // Modal close event handlers
    $(document).on('click', '.close-email-modal', function() {
        closeEmailModal();
    });

    // Close modal if clicking outside the modal content container
    $('#emailContactModal').on('click', function(e) {
        if ($(e.target).is('#emailContactModal')) {
            closeEmailModal();
        }
    });

});

</script>
</body>
</html>
