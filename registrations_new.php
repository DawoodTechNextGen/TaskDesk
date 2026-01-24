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
                        <select class="status-select px-2 py-1 border rounded bg-white dark:bg-gray-700 text-xs w-24">
                            <option value="new" selected>New</option>
                            <option value="contact">Contact </option>
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
            processing: 'Processing...',
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

});

</script>
</body>
</html>
