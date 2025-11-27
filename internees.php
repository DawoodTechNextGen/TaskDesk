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
$page_title = 'Internees Management - TaskDesk';
include_once "./include/headerLinks.php"; ?>

<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
    <div id="toast-container" class="fixed top-18 right-4 z-[9999] space-y-4"></div>

    <div class="flex h-screen overflow-hidden">
        <?php include_once "./include/sideBar.php"; ?>
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include_once "./include/header.php"; ?>

            <main class="flex-1 overflow-y-auto px-6 pt-24 bg-gray-50 dark:bg-gray-900/50 custom-scrollbar">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Manage Internees</h2>
                    <button class="open-modal bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-medium"
                        data-modal="add-internee-modal">
                        Add Internee
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">All Internees</h2>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table id="interneesTable" class="min-w-full">
                            <thead class="bg-indigo-200 dark:bg-indigo-600">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-200 uppercase tracking-wider">Technology</th>
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

    <!-- Add & Edit Modal (indigo theme like supervisors but with indigo instead of indigo) -->
    <div id="internee-modal" class="modal hidden fixed inset-0 z-50 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-11/12 max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold" id="modal-title">Add Internee</h3>
                <button class="close-modal text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z" />
                    </svg>
                </button>
            </div>
            <form id="internee-form">
                <input type="hidden" name="id">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">
                        Password
                        <span class="text-xs text-gray-500">(Leave blank to keep current)</span>
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password-input"
                            class="w-full px-3 py-2 pr-12 border rounded-lg bg-white dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            placeholder="Enter password">

                        <button type="button" id="toggle-password"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg id="eye-open" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.2">
                                <path d="M4 12C4 12 5.6 7 12 7M12 7C18.4 7 20 12 20 12M12 7V4M18 5L16 7.5M6 5L8 7.5M15 13C15 14.6569 13.6569 16 12 16C10.3431 16 9 14.6569 9 13C9 11.3431 10.3431 10 12 10C13.6569 10 15 11.3431 15 13Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <svg id="eye-closed" class="hidden" width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4 10C4 10 5.6 15 12 15M12 15C18.4 15 20 10 20 10M12 15V18M18 17L16 14.5M6 17L8 14.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Assigned Technology</label>
                    <select name="tech_id" required class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700">
                        <option value="">Select Technology</option>
                        <?php
                        $techs = $conn->query("SELECT id, name FROM technologies ORDER BY name");
                        while ($t = $techs->fetch_assoc()) {
                            echo "<option value='{$t['id']}'>{$t['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" class="close-modal px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "./include/footerLinks.php"; ?>

    <script>
        const table = $('#interneesTable').DataTable({
            ordering: false,
            pageLength: 10,
        });

        async function loadInternees() {
            try {
                const res = await fetch('controller/user.php?action=get_internees');
                const json = await res.json();
                if (json.success) {
                    table.clear();
                    json.data.forEach(u => {
                        console.log(typeof(u.approve_status));
                        table.row.add([
                            u.id,
                            u.name,
                            u.email || '<em class="text-gray-400">No email</em>',
                            u.tech_name ? `<span class="text-indigo-600 font-medium">${u.tech_name}</span>` : '<em class="text-gray-400">Not assigned</em>',
                            `<button class="edit-internee text-blue-600 mr-3" 
                                    data-id="${u.id}" 
                                    data-name="${u.name}" 
                                    data-email="${u.email || ''}"
                                    data-tech="${u.tech_id || ''}"
                                    data-pass="${u.plain_password || ''}">
                                <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2 12C2 16.714 2 19.0711 3.46447 20.5355C4.92893 22 7.28595 22 12 22C16.714 22 19.0711 22 20.5355 20.5355C22 19.0711 22 16.714 22 12V10.5M13.5 2H12C7.28595 2 4.92893 2 3.46447 3.46447C2.49073 4.43821 2.16444 5.80655 2.0551 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M16.652 3.45506L17.3009 2.80624C18.3759 1.73125 20.1188 1.73125 21.1938 2.80624C22.2687 3.88124 22.2687 5.62415 21.1938 6.69914L20.5449 7.34795M16.652 3.45506C16.652 3.45506 16.7331 4.83379 17.9497 6.05032C19.1662 7.26685 20.5449 7.34795 20.5449 7.34795M16.652 3.45506L10.6872 9.41993C10.2832 9.82394 10.0812 10.0259 9.90743 10.2487C9.70249 10.5114 9.52679 10.7957 9.38344 11.0965C9.26191 11.3515 9.17157 11.6225 8.99089 12.1646L8.41242 13.9M20.5449 7.34795L17.5625 10.3304M14.5801 13.3128C14.1761 13.7168 13.9741 13.9188 13.7513 14.0926C13.4886 14.2975 13.2043 14.4732 12.9035 14.6166C12.6485 14.7381 12.3775 14.8284 11.8354 15.0091L10.1 15.5876M10.1 15.5876L8.97709 15.9619C8.71035 16.0508 8.41626 15.9814 8.21744 15.7826C8.01862 15.5837 7.9492 15.2897 8.03811 15.0229L8.41242 13.9M10.1 15.5876L8.41242 13.9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>
                            ${(u.completion_rate >= 75 && u.months_completed >= 3)? `<button class="approve-certificate ${(u.approve_status !== 1)?'text-amber-300':'text-green-300'}" data-id="${u.id}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle cx="12" cy="16" r="3" stroke="currentColor" stroke-width="1.5"></circle> <path d="M12 19.2599L9.73713 21.4293C9.41306 21.74 9.25102 21.8953 9.1138 21.9491C8.80111 22.0716 8.45425 21.9667 8.28977 21.7C8.21758 21.583 8.19509 21.3719 8.1501 20.9496C8.1247 20.7113 8.112 20.5921 8.07345 20.4922C7.98715 20.2687 7.80579 20.0948 7.57266 20.0121C7.46853 19.9751 7.3442 19.963 7.09553 19.9386C6.65512 19.8955 6.43491 19.8739 6.31283 19.8047C6.03463 19.647 5.92529 19.3145 6.05306 19.0147C6.10913 18.8832 6.27116 18.7278 6.59523 18.4171L8.07345 16.9999L9.1138 15.9596" stroke="currentColor" stroke-width="1.5"></path> <path d="M12 19.2599L14.2629 21.4294C14.5869 21.7401 14.749 21.8954 14.8862 21.9492C15.1989 22.0717 15.5457 21.9668 15.7102 21.7001C15.7824 21.5831 15.8049 21.372 15.8499 20.9497C15.8753 20.7113 15.888 20.5921 15.9265 20.4923C16.0129 20.2688 16.1942 20.0949 16.4273 20.0122C16.5315 19.9752 16.6558 19.9631 16.9045 19.9387C17.3449 19.8956 17.5651 19.874 17.6872 19.8048C17.9654 19.6471 18.0747 19.3146 17.9469 19.0148C17.8909 18.8832 17.7288 18.7279 17.4048 18.4172L15.9265 17L15 16.0735" stroke="currentColor" stroke-width="1.5"></path> <path d="M17.3197 17.9957C19.2921 17.9748 20.3915 17.8512 21.1213 17.1213C22 16.2426 22 14.8284 22 12V9M7 17.9983C4.82497 17.9862 3.64706 17.8897 2.87868 17.1213C2 16.2426 2 14.8284 2 12L2 8C2 5.17157 2 3.75736 2.87868 2.87868C3.75736 2 5.17157 2 8 2L16 2C18.8284 2 20.2426 2 21.1213 2.87868C21.6112 3.36857 21.828 4.02491 21.9239 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M9 6L15 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M7 9.5H9M17 9.5H12.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>`:''}
                            <button class="delete-internee text-red-600" data-id="${u.id}">
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M20.5001 6H3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M9.5 11L10 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M14.5 11L14 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> <path d="M6.5 6C6.55588 6 6.58382 6 6.60915 5.99936C7.43259 5.97849 8.15902 5.45491 8.43922 4.68032C8.44784 4.65649 8.45667 4.62999 8.47434 4.57697L8.57143 4.28571C8.65431 4.03708 8.69575 3.91276 8.75071 3.8072C8.97001 3.38607 9.37574 3.09364 9.84461 3.01877C9.96213 3 10.0932 3 10.3553 3H13.6447C13.9068 3 14.0379 3 14.1554 3.01877C14.6243 3.09364 15.03 3.38607 15.2493 3.8072C15.3043 3.91276 15.3457 4.03708 15.4286 4.28571L15.5257 4.57697C15.5433 4.62992 15.5522 4.65651 15.5608 4.68032C15.841 5.45491 16.5674 5.97849 17.3909 5.99936C17.4162 6 17.4441 6 17.5 6" stroke="currentColor" stroke-width="1.5"></path> <path d="M18.3735 15.3991C18.1965 18.054 18.108 19.3815 17.243 20.1907C16.378 21 15.0476 21 12.3868 21H11.6134C8.9526 21 7.6222 21 6.75719 20.1907C5.89218 19.3815 5.80368 18.054 5.62669 15.3991L5.16675 8.5M18.8334 8.5L18.6334 11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path> </g></svg>
                            </button>`
                        ]);
                    });
                    table.draw(false);
                }
            } catch (err) {
                console.error("Failed to load internees:", err);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Open Add Modal
            document.querySelector('.open-modal').onclick = () => {
                document.getElementById('modal-title').textContent = 'Add Internee';
                document.getElementById('internee-form').reset();
                document.querySelector('[name="id"]').value = '';
                document.querySelector('[name="password"]').required = true;
                document.querySelector('[name="email"]').required = true;
                document.getElementById('internee-modal').classList.remove('hidden');
            };

            // Edit Internee
            document.addEventListener('click', e => {
                const editBtn = e.target.closest('.edit-internee');
                if (editBtn) {
                    document.getElementById('modal-title').textContent = 'Edit Internee';
                    document.querySelector('[name="id"]').value = editBtn.dataset.id;
                    document.querySelector('[name="name"]').value = editBtn.dataset.name;
                    document.querySelector('[name="email"]').value = editBtn.dataset.email;
                    document.querySelector('[name="tech_id"]').value = editBtn.dataset.tech;
                    document.querySelector('[name="password"]').required = false;
                    document.querySelector('[name="password"]').value = editBtn.dataset.pass;
                    document.getElementById('internee-modal').classList.remove('hidden');
                }
            });

            // Submit Form
            document.getElementById('internee-form').onsubmit = async e => {
                e.preventDefault();
                const fd = new FormData(e.target);
                fd.append('role', '2'); // or whatever your backend expects for internee
                fd.append('action', fd.get('id') ? 'update' : 'create');

                const res = await fetch('controller/user.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();
                showToast(json.success ? 'success' : 'error', json.message);

                if (json.success) {
                    document.querySelector('.close-modal').click();
                    loadInternees();
                }
            };

            // Delete Internee
            document.addEventListener('click', async e => {
                const delBtn = e.target.closest('.delete-internee');
                if (delBtn && confirm('Delete this internee permanently?')) {
                    const res = await fetch('controller/user.php', {
                        method: 'POST',
                        body: new URLSearchParams({
                            action: 'delete',
                            id: delBtn.dataset.id,
                            role: 'student'
                        })
                    });
                    const json = await res.json();
                    showToast(json.success ? 'success' : 'error', json.message);
                    if (json.success) loadInternees();
                }
            });

            // Close Modal
            document.querySelectorAll('.close-modal').forEach(b => {
                b.onclick = () => b.closest('.modal').classList.add('hidden');
            });

            // Initial load
            loadInternees();
        });

        function showToast(type, msg) {
            const toast = document.createElement('div');
            toast.className = `px-5 py-3 rounded-lg text-white font-medium shadow-lg animate-slide-in ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.textContent = msg;
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        // Password visibility toggle
        document.getElementById('toggle-password')?.addEventListener('click', function() {
            const input = document.getElementById('password-input');
            const open = document.getElementById('eye-open');
            const closed = document.getElementById('eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                open.classList.add('hidden');
                closed.classList.remove('hidden');
            } else {
                input.type = 'password';
                open.classList.remove('hidden');
                closed.classList.add('hidden');
            }
        });
        document.addEventListener('click', async function(e) {
            if (e.target.closest('.text-amber-300')) {
                const button = e.target.closest('.text-amber-300');
                const internId = button.dataset.id;
                const res = await fetch('controller/certificate-approval.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'approve',
                        id: internId,
                    })
                });
                const json = await res.json();
                console.log(json);
                showToast(json.success ? 'success' : 'error', json.message);
                if (json.success) loadInternees();
            }
        });
    </script>
</body>

</html>