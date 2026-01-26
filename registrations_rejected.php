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

    tr.shown .expand-icon {
        transform: rotate(45deg);
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

    .details-wrapper {
        display: none;
        overflow: hidden;
    }
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

            <!-- Hire Modal -->
            <div id="hireModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md shadow-2xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 dark:text-white">Hire Candidate</h3>
                        <button type="button" id="hireCancelBtn" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
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
                            <button type="button" id="cancelHireBtn" class="px-4 py-2.5 border rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Hire Candidate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

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
        const headerMap = {
            id: 'ID',
            name: 'Name',
            email: 'Email',
            mbl_number: 'Contact',
            technology: 'Technology',
            internship_type: 'Internship Type',
            experience: 'Experience',
            cnic: 'CNIC',
            city: 'City',
            country: 'Country',
            created_at: 'Created At',
            status: 'Status'
        };

        function formatDetails(row) {
            return `<div class="details-wrapper"><div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg grid grid-cols-2 gap-4 text-sm">${expandableColumns.map(k => `<div><span class="font-semibold">${headerMap[k]}:</span> <span>${escapeHTML(row[k] ?? '-')}</span></div>`).join('')}</div></div>`;
        }

        $(document).ready(function() {
            /* Utilities */
            const LoaderManager = {
                showGlobal: function() { $('#globalLoader').removeClass('hidden'); },
                hideGlobal: function() { $('#globalLoader').addClass('hidden'); }
            };

            function showToast(type, msg) {
                const toast = document.createElement('div');
                toast.className = `px-5 py-3 rounded-lg text-white shadow-lg ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
                toast.textContent = msg;
                $('#toast-container').append(toast);
                setTimeout(() => toast.remove(), 4000);
            }

            /* Searchable Select Logic */
            function initSearchableSelect() {
                const $wrapper = $('.searchable-wrapper');
                const $originalSelect = $wrapper.find('.searchable-select');
                const $searchInput = $wrapper.find('.searchable-input');
                const $dropdown = $wrapper.find('.searchable-dropdown');

                function populateDropdown() {
                    $dropdown.empty();
                    $originalSelect.find('option').each(function() {
                        if (this.value === '') return;
                        const $li = $('<li>').addClass('px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200')
                            .on('click', () => {
                                $originalSelect.val(this.value);
                                $searchInput.val(this.textContent);
                                $dropdown.addClass('hidden');
                            });
                        $li.text(this.textContent);
                        $dropdown.append($li);
                    });
                }

                $searchInput.on('click input', function() {
                    populateDropdown();
                    const term = this.value.toLowerCase();
                    $dropdown.find('li').each(function() {
                        const $li = $(this);
                        const text = $li.text().toLowerCase();
                        $li.toggle(text.indexOf(term) > -1);
                    });
                    $dropdown.removeClass('hidden');
                });

                $(document).on('click', function(e) {
                    if (!$wrapper.is(e.target) && $wrapper.has(e.target).length === 0) {
                        $dropdown.addClass('hidden');
                    }
                });
            }

            initSearchableSelect();

            // Build table header
            $('#rejectedTable thead').html(`<tr><th></th><th>ID</th><th>Name</th><th>Contact</th><th>Internship Type</th><th>Technology</th><th>Experience</th><th>Remarks</th><th>Actions</th></tr>`);

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
                columns: [{
                        class: 'details-control cursor-pointer text-center font-bold',
                        orderable: false,
                        data: null,
                        defaultContent: '<span class="expand-icon"><span class="bar horizontal"></span><span class="bar vertical"></span></span>'
                    },
                    {
                        data: 'id'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'mbl_number'
                    },
                    {
                        data: 'internship_type_text'
                    },
                    {
                        data: 'technology'
                    },
                    {
                        data: 'experience_text'
                    },
                    {
                        data: 'remarks_text',
                        render: function(data, type, row) {
                            if (row.has_remarks) {
                                return '<span class="tooltip bg-sky-900 text-sky-100 px-2 py-1 rounded-full cursor-pointer" data-tooltip="' + escapeHTML(row.remarks_text) + '">View</span>';
                            } else {
                                return '<span class="text-muted text-gray-400 dark:text-gray-700">No remarks</span>';
                            }
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                            <div class="flex items-center space-x-1">
                                <button class="px-2 py-1 bg-green-600 hover:bg-green-700 text-white rounded text-xs hire-btn" data-id="${row.id}">Hire</button>
                                <button class="px-2 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs contact-btn" data-id="${row.id}" title="Move to Contact">Contact</button>
                            </div>`;
                        }
                    }

                ],
                order: [
                    [1, 'desc']
                ],
                language: {
                    emptyTable: 'No rejected records',
                    zeroRecords: 'No rejected records'
                }
            });

            // Row details expansion with animation
            $('#rejectedTable tbody').on('click', 'td.details-control', function() {
                var tr = $(this).closest('tr');
                var row = table.row(tr);
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

            // Hire Functionality
            function openHireModal(row) {
                $('#hireId').val(row.id);
                $('#hireName').val(row.name);
                $('#hireTechnology').val(row.technology);
                $('#hireModal').removeClass('hidden');
            }

            $(document).on('click', '.hire-btn', function() {
                const row = table.row($(this).closest('tr')).data();
                openHireModal(row);
            });

            $('#hireCancelBtn, #cancelHireBtn').on('click', () => $('#hireModal').addClass('hidden'));

            $('#hireForm').on('submit', async function(e) {
                e.preventDefault();

                if (!$('#hireTrainer').val()) {
                    showToast('error', 'Please select a supervisor');
                    return;
                }

                if (!confirm('Are you sure you want to hire this candidate?')) return;

                LoaderManager.showGlobal();
                try {
                    const formData = new FormData(this);
                    formData.append('action', 'update_hire_status');

                    const res = await fetch('controller/registrations.php', {
                        method: 'POST',
                        body: formData
                    });
                    const json = await res.json();

                    if (json.success) {
                        showToast('success', 'Candidate hired successfully');
                        $('#hireModal').addClass('hidden');
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

            // Move to Contact Action
            $(document).on('click', '.contact-btn', async function() {
                const id = $(this).data('id');
                if (!confirm('Move this candidate back to Contact?')) return;

                LoaderManager.showGlobal();
                try {
                    const formDataObj = new FormData();
                    formDataObj.append('action', 'update_registration_status');
                    formDataObj.append('id', id);
                    formDataObj.append('status', 'contact');

                    const res = await fetch('controller/registrations.php', {
                        method: 'POST',
                        body: formDataObj
                    });
                    const json = await res.json();

                    if (json.success) {
                        showToast('success', 'Candidate moved to contact');
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

        });
    </script>
</body>

</html>