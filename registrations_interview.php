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
$page_title = 'Interview List - TaskDesk';
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
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
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
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Interviews</h2>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="table-container">
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading interviews...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="interviewTable" class="min-w-full">
                                <thead class="text-sm text-gray-800 dark:text-gray-50"></thead>
                                <tbody class="text-xs dark:text-gray-100 text-gray-800"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Reschedule Modal -->
            <div id="rescheduleModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Reschedule Interview</h3>
                    <form id="rescheduleForm" class="space-y-4">
                        <input type="hidden" id="reschRegId" name="registration_id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Candidate Name</label>
                            <input type="text" id="reschName" class="w-full px-3 py-2 border rounded bg-gray-100 dark:bg-gray-700 dark:text-gray-400" readonly>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Date</label>
                                <input type="date" id="reschDate" name="date" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200" required>
                             </div>
                             <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Time</label>
                                <input type="time" id="reschTime" name="time" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-gray-200" required>
                             </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-2">
                            <button type="button" id="closeReschModal" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 dark:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Reschedule</button>
                        </div>
                    </form>
                </div>
            </div>

             <!-- Hire Modal (Existing) -->
             <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">Hire Details</h3>
                    <form id="hireForm" class="space-y-4">
                        <input type="hidden" id="hireId" name="id">
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
                                </div>
                                <ul class="searchable-dropdown hidden absolute z-50 text-gray-900 w-full bg-white dark:bg-gray-700 border rounded mt-1 max-h-60 overflow-y-auto"></ul>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button type="button" id="hireCancelBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 dark:bg-gray-600">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Submit Hire</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include_once "./include/footer.php"; ?>
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

    /* Searchable Select Logic (Simplified) */
    function initSearchableSelect() {
        const $wrapper = $('.searchable-wrapper');
        const $originalSelect = $wrapper.find('.searchable-select');
        const $searchInput = $wrapper.find('.searchable-input');
        const $dropdown = $wrapper.find('.searchable-dropdown');

        function populateDropdown() {
             $dropdown.empty();
             $originalSelect.find('option').each(function() {
                 if(this.value === '') return;
                 const $li = $('<li>').addClass('px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600').text(this.textContent)
                    .on('click', () => {
                         $originalSelect.val(this.value);
                         $searchInput.val(this.textContent);
                         $dropdown.addClass('hidden');
                    });
                 $dropdown.append($li);
             });
        }
        $searchInput.on('click input', function() {
            populateDropdown();
            const term = this.value.toLowerCase();
            $dropdown.find('li').toggle(function() { return $(this).text().toLowerCase().indexOf(term) > -1; });
            $dropdown.removeClass('hidden');
        });
        $(document).on('click', function(e) { if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) $dropdown.addClass('hidden'); });
    }

    const expandableColumns = ['email', 'cnic', 'city', 'country', 'created_at'];
    const headerMap = { id: 'ID', name: 'Name', email: 'Email', mbl_number: 'Contact', technology: 'Technology', internship_type: 'Internship Type', experience: 'Experience', cnic: 'CNIC', city: 'City', country: 'Country', created_at: 'Created At' };

    function formatDetails(row) {
        return `<div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${expandableColumns.map(k => `<div><span class="font-semibold">${headerMap[k]}:</span> <span>${escapeHTML(row[k] ?? '-')}</span></div>`).join('')}</div>`;
    }

    $(document).ready(function() {
        initSearchableSelect();

        // Build table header
        $('#interviewTable thead').html(`<tr><th></th><th>ID</th><th>Name</th><th>Contact</th><th>Technology</th><th>Exp</th><th>Actions</th></tr>`);

        const table = $('#interviewTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'interview';
                }
            },
            columns: [
                {
                    class: 'details-control cursor-pointer text-center font-bold',
                    orderable: false,
                    data: null,
                    defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                },
                { data: 'id' },
                { data: 'name' },
                { data: 'mbl_number' },
                { data: 'technology' },
                { data: 'experience' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                        <div class="flex space-x-2">
                             <button class="px-2 py-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs reschedule-btn" data-id="${row.id}">Reschedule</button>
                             <button class="px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs hire-btn" data-id="${row.id}">Hire</button>
                             <button class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-xs reject-btn" data-id="${row.id}">Reject</button>
                        </div>`;
                    }
                }
            ],
            order: [[1, 'desc']],
            language: { processing: 'Processing...', emptyTable: 'No interviews scheduled', zeroRecords: 'No interviews scheduled' }
        });

        // Row details
        $('#interviewTable tbody').on('click', 'td.details-control', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            if (row.child.isShown()) { row.child.hide(); tr.removeClass('shown'); }
            else { row.child(formatDetails(row.data())).show(); tr.addClass('shown'); }
        });

        // Reschedule
        $(document).on('click', '.reschedule-btn', function() {
            const row = table.row($(this).closest('tr')).data();
            $('#reschRegId').val(row.id);
            $('#reschName').val(row.name);
            $('#rescheduleModal').removeClass('hidden');
        });
        
        $('#closeReschModal').on('click', () => $('#rescheduleModal').addClass('hidden'));

        $('#rescheduleForm').on('submit', async function(e) {
            e.preventDefault();
            LoaderManager.showGlobal();
            try {
                const formData = new FormData(this);
                formData.append('action', 'reschedule_interview');
                const res = await fetch('controller/registrations.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) {
                    showToast('success', 'Rescheduled successfully');
                    $('#rescheduleModal').addClass('hidden');
                } else {
                    showToast('error', json.message);
                }
            } catch(e) { showToast('error', e.message); }
            finally { LoaderManager.hideGlobal(); }
        });

        // Hire
        $(document).on('click', '.hire-btn', function() {
            const row = table.row($(this).closest('tr')).data();
            $('#hireId').val(row.id); // Registrations ID
            $('#hireName').val(row.name);
            $('#hireTechnology').val(row.technology);
            $('#hireEmail').val(row.email);
            $('.searchable-input').val(''); // Reset
            $('#hireModal').removeClass('hidden');
        });

        $('#hireCancelBtn').on('click', () => $('#hireModal').addClass('hidden'));

        $('#hireForm').on('submit', async function(e) {
            e.preventDefault();
            if(!$('#hireTrainer').val()) { alert('Select Supervisor'); return; }
            if(!confirm('Confirm Hire?')) return;

            LoaderManager.showGlobal();
            try {
                const formData = new FormData(this);
                formData.append('action', 'update_hire_status');
                formData.append('trainer', $('#hireTrainer').val()); // Ensure trainer is sent
                
                const res = await fetch('controller/registrations.php', { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) {
                    showToast('success', 'Hired successfully');
                    $('#hireModal').addClass('hidden');
                    table.ajax.reload();
                } else {
                    showToast('error', json.message);
                }
            } catch(e) { showToast('error', e.message); }
            finally { LoaderManager.hideGlobal(); }
        });

        // Reject
        $(document).on('click', '.reject-btn', async function() {
             if(!confirm('Reject this candidate?')) return;
             const id = $(this).data('id');
             LoaderManager.showGlobal();
             try {
                const res = await fetch('controller/registrations.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}, 
                    body: new URLSearchParams({ action: 'update_status', id: id, status: 'rejected' }) 
                });
                const json = await res.json();
                if(json.success) { showToast('success', 'Candidate rejected'); table.ajax.reload(); }
                else { showToast('error', json.message); }
             } catch(e) { showToast('error', e.message); }
             finally { LoaderManager.hideGlobal(); }
        });
    });
</script>
</body>
</html>
