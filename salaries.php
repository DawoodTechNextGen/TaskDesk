<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header('Location: login.php');
    exit;
}
include_once './include/connection.php';

// Set selected month and year
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch salaries query
$query = "
    SELECT u.id, u.name, u.email, u.user_role, u.commission_rate,
           COALESCE(c.interns_hired, 0) as interns_hired,
           COALESCE(c.total_salary, 0) as total_salary
    FROM users u
    LEFT JOIN (
        SELECT supervisor_id, COUNT(id) as interns_hired, SUM(amount) as total_salary
        FROM commissions
        WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
        GROUP BY supervisor_id
    ) c ON u.id = c.supervisor_id
    WHERE u.user_role IN (3, 4) AND u.status = 1
    ORDER BY u.name ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $selected_month, $selected_year);
$stmt->execute();
$salaries_result = $stmt->get_result();

$total_payroll = 0;
$total_hired_interns = 0;
$salaries_data = [];

while ($row = $salaries_result->fetch_assoc()) {
    $total_payroll += (int)$row['total_salary'];
    $total_hired_interns += (int)$row['interns_hired'];
    $salaries_data[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<?php
$page_title = 'Salaries & Commissions - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                
                <!-- Title & Filter Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Salaries & Commissions</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Track and manage monthly supervisor and manager payouts.</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Month/Year Form -->
                        <form method="GET" class="flex items-center gap-3 bg-white dark:bg-gray-800 p-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <select name="month" class="px-3 py-2 bg-transparent text-gray-700 dark:text-gray-200 border-0 focus:ring-0 cursor-pointer font-medium">
                                <?php
                                for ($m = 1; $m <= 12; $m++) {
                                    $dateObj = DateTime::createFromFormat('!m', $m);
                                    $monthName = $dateObj->format('F');
                                    $selected = ($m == $selected_month) ? 'selected' : '';
                                    echo "<option value=\"$m\" $selected>$monthName</option>";
                                }
                                ?>
                            </select>
                            
                            <select name="year" class="px-3 py-2 bg-transparent text-gray-700 dark:text-gray-200 border-0 focus:ring-0 cursor-pointer font-medium">
                                <?php
                                $currentYear = (int)date('Y');
                                for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++) {
                                    $selected = ($y == $selected_year) ? 'selected' : '';
                                    echo "<option value=\"$y\" $selected>$y</option>";
                                }
                                ?>
                            </select>
                            
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold transition-all">
                                Filter
                            </button>
                        </form>
                        
                        <button id="exportCsvBtn" type="button" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold transition-all flex items-center gap-1.5 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Export CSV
                        </button>
                    </div>
                </div>

                <!-- Summary Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Total Payroll Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 relative overflow-hidden">
                        <div class="relative">
                            <p class="text-indigo-500 dark:text-indigo-200 text-sm font-medium mb-2">Total Payroll for Selected Month</p>
                            <h3 class="text-3xl font-bold mb-2 text-black dark:text-white"><?= number_format($total_payroll) ?> PKR</h3>
                            <p class="text-indigo-500 dark:text-indigo-200 text-sm">Combined payouts</p>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-indigo-500"></div>
                    </div>

                    <!-- Total Interns Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 relative overflow-hidden">
                        <div class="relative">
                            <p class="text-green-500 dark:text-green-200 text-sm font-medium mb-2">Commission-Eligible Interns Hired</p>
                            <h3 class="text-3xl font-bold mb-2 text-black dark:text-white"><?= $total_hired_interns ?></h3>
                            <p class="text-green-500 dark:text-green-200 text-sm">Learning Base Interns</p>
                        </div>
                        <div class="absolute bottom-0 left-0 w-full h-1 bg-green-500"></div>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Payout Breakdown</h2>
                    </div>
                    
                    <div class="overflow-x-auto p-4 custom-scrollbar">
                        <table id="salariesTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Rate (Per Intern)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Interns Hired</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Total Salary</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs dark:text-gray-100 text-gray-800">
                                <?php if (count($salaries_data) > 0): ?>
                                    <?php foreach ($salaries_data as $row): ?>
                                        <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($row['id']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium">
                                                <button class="view-breakdown text-indigo-600 dark:text-indigo-400 hover:underline font-semibold" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name']) ?>">
                                                    <?= htmlspecialchars($row['name']) ?>
                                                </button>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400"><?= htmlspecialchars($row['email']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $row['user_role'] == 4 ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' ?>">
                                                    <?= $row['user_role'] == 4 ? 'Manager' : 'Supervisor' ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($row['commission_rate']) ?> PKR</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center font-semibold"><?= htmlspecialchars($row['interns_hired']) ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-900 dark:text-white"><?= number_format($row['total_salary']) ?> PKR</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No active supervisors or managers found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            
            <!-- Breakdown Details Modal -->
            <div id="breakdown-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-2xl p-6 flex flex-col max-h-[85vh]">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-150 dark:border-gray-700">
                        <h3 class="text-xl font-bold text-gray-950 dark:text-gray-50">
                            Earning Breakdown - <span id="modal-supervisor-name">Supervisor</span>
                        </h3>
                        <button class="close-modal text-gray-500 hover:text-gray-700">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="overflow-y-auto custom-scrollbar flex-1 pr-1">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-150 dark:bg-gray-750 text-gray-700 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold">Intern Name</th>
                                    <th class="px-4 py-2 text-left font-semibold">Email</th>
                                    <th class="px-4 py-2 text-left font-semibold">Duration</th>
                                    <th class="px-4 py-2 text-left font-semibold">Date</th>
                                    <th class="px-4 py-2 text-right font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="breakdown-table-body" class="text-gray-800 dark:text-gray-200">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Close</button>
                    </div>
                </div>
            </div>

            <?php include_once "./include/footer.php"; ?>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        $(document).ready(function() {
            const table = $('#salariesTable').DataTable({
                ordering: true,
                pageLength: 10,
                order: [[5, 'desc']] // Order by Hired Interns count by default
            });

            // Open breakdown details modal
            $('.view-breakdown').click(async function() {
                const supervisorId = $(this).data('id');
                const supervisorName = $(this).data('name');
                const month = '<?= $selected_month ?>';
                const year = '<?= $selected_year ?>';

                $('#modal-supervisor-name').text(supervisorName);
                $('#breakdown-table-body').html('<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading breakdown details...</td></tr>');
                $('#breakdown-modal').removeClass('hidden');

                try {
                    const res = await fetch(`controller/user.php?action=get_salary_breakdown&supervisor_id=${supervisorId}&month=${month}&year=${year}`);
                    const json = await res.json();
                    
                    if (json.success) {
                        if (json.data.length === 0) {
                            $('#breakdown-table-body').html('<tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No transactions recorded for this month.</td></tr>');
                        } else {
                            let rowsHTML = '';
                            json.data.forEach(item => {
                                const amount = parseInt(item.amount);
                                const amountClass = amount < 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-green-600 dark:text-green-400 font-semibold';
                                const amountDisplay = (amount > 0 ? '+' : '') + amount.toLocaleString() + ' PKR';
                                const dateFormatted = new Date(item.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                                
                                rowsHTML += `
                                <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3 font-medium">${item.intern_name || 'N/A'}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">${item.intern_email || 'N/A'}</td>
                                    <td class="px-4 py-3">${item.internship_duration || 'Learning Base'}</td>
                                    <td class="px-4 py-3">${dateFormatted}</td>
                                    <td class="px-4 py-3 text-right ${amountClass}">${amountDisplay}</td>
                                </tr>`;
                            });
                            $('#breakdown-table-body').html(rowsHTML);
                        }
                    } else {
                        $('#breakdown-table-body').html(`<tr><td colspan="5" class="px-4 py-4 text-center text-red-500">Error: ${json.message}</td></tr>`);
                    }
                } catch (error) {
                    console.error('Failed to load breakdown:', error);
                    $('#breakdown-table-body').html('<tr><td colspan="5" class="px-4 py-4 text-center text-red-500">Network error. Please try again.</td></tr>');
                }
            });

            // Close modal event
            $('.close-modal').click(function() {
                $('#breakdown-modal').addClass('hidden');
            });

            // Export to CSV Functionality
            $('#exportCsvBtn').click(function() {
                let csv = [];
                let headers = [];
                $('#salariesTable thead th').each(function() {
                    headers.push($(this).text().trim());
                });
                csv.push(headers.join(','));

                table.rows().data().each(function(row) {
                    let cleanRow = [];
                    row.forEach((cell) => {
                        let text = cell.toString().replace(/<[^>]*>/g, '').trim();
                        text = text.replace(/"/g, '""');
                        if (text.includes(',') || text.includes('\n')) {
                            text = `"${text}"`;
                        }
                        cleanRow.push(text);
                    });
                    csv.push(cleanRow.join(','));
                });

                let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
                let encodedUri = encodeURI(csvContent);
                let link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                const selectedMonth = '<?= $selected_month ?>';
                const selectedYear = '<?= $selected_year ?>';
                link.setAttribute("download", `TaskDesk_Salaries_M${selectedMonth}_Y${selectedYear}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</body>

</html>
