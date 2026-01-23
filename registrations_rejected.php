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
$page_title = 'Rejected Candidates - TaskDesk';
include_once "./include/headerLinks.php";
?>

<style>
    /* ... (Same styles as registrations.php) ... */
    .expand-icon { width: 10px; height: 10px; display: inline-block; position: relative; cursor: pointer; transition: transform 300ms ease; }
    .expand-icon .bar { position: absolute; background-color: currentColor; border-radius: 2px; }
    .expand-icon .horizontal { width: 100%; height: 1.5px; top: 50%; left: 0; transform: translateY(-50%); }
    .expand-icon .vertical { height: 100%; width: 1.5px; left: 50%; top: 0; transform: translateX(-50%); }
    tr.shown .expand-icon { transform: rotate(45deg); }
    .table-loader { display: none; text-align: center; padding: 20px; }
    .table-loader.active { display: block; }
    .table-container { position: relative; min-height: 200px; }
</style>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Rejected Candidates</h2>
                </div>

                <div class="bg-white mb-4 dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="table-container">
                        <div id="tableLoader" class="table-loader p-8">
                            <div class="flex justify-center items-center space-x-4">
                                <div class="loader"></div>
                                <span class="text-gray-600 dark:text-gray-300">Loading rejected...</span>
                            </div>
                        </div>

                        <!-- Table Content -->
                        <div class="overflow-x-auto p-4 custom-scrollbar">
                            <table id="rejectedTable" class="min-w-full">
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
    function escapeHTML(str) { 
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); 
    }

    const expandableColumns = ['email', 'cnic', 'city', 'country', 'created_at', 'status'];
    const headerMap = { id: 'ID', name: 'Name', email: 'Email', mbl_number: 'Contact', technology: 'Technology', internship_type: 'Internship Type', experience: 'Experience', cnic: 'CNIC', city: 'City', country: 'Country', created_at: 'Created At', status: 'Status' };

    function formatDetails(row) {
        return `<div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${expandableColumns.map(k => `<div><span class="font-semibold">${headerMap[k]}:</span> <span>${escapeHTML(row[k] ?? '-')}</span></div>`).join('')}</div>`;
    }

    $(document).ready(function() {
        // Build table header
        $('#rejectedTable thead').html(`<tr><th></th><th>ID</th><th>Name</th><th>Contact</th><th>Technology</th><th>Exp</th></tr>`);

        const table = $('#rejectedTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: 'controller/registrations.php',
                type: 'GET',
                data: function(d) {
                    d.action = 'rejected';
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
                { data: 'experience' }
            ],
            order: [[1, 'desc']],
            language: { processing: 'Processing...', emptyTable: 'No rejected records', zeroRecords: 'No rejected records' }
        });

        // Row details
        $('#rejectedTable tbody').on('click', 'td.details-control', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);
            if (row.child.isShown()) { row.child.hide(); tr.removeClass('shown'); }
            else { row.child(formatDetails(row.data())).show(); tr.addClass('shown'); }
        });
    });
</script>
</body>
</html>
